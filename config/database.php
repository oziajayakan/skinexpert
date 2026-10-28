<?php

// Definisi fallback jika ekstensi mysqli tidak aktif di serverless/lingkungan tertentu
if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}
if (!defined('MYSQLI_NUM')) {
    define('MYSQLI_NUM', 2);
}
if (!defined('MYSQLI_BOTH')) {
    define('MYSQLI_BOTH', 3);
}
if (!class_exists('mysqli_sql_exception')) {
    class mysqli_sql_exception extends Exception {}
}

$host = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$database = getenv('DB_NAME') ?: "skinexpert_db";
$port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

$conn = null;

// Hanya coba MySQL jika DB_HOST disetel di environment variables atau jika port 3306 lokal aktif (timeout 0.1s)
$tryMysql = !empty(getenv('DB_HOST'));
if (!$tryMysql && class_exists('mysqli')) {
    $fp = @fsockopen("127.0.0.1", $port, $errno, $errstr, 0.1);
    if ($fp) {
        fclose($fp);
        $tryMysql = true;
    }
}

if ($tryMysql && class_exists('mysqli')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $mysqli = @new mysqli($host, $username, $password, $database, $port);
        if (!$mysqli->connect_error) {
            $mysqli->set_charset("utf8mb4");
            $conn = $mysqli;
            if (!defined('DB_DRIVER')) {
                define('DB_DRIVER', 'MYSQL');
            }
        }
    } catch (Throwable $e) {
        $conn = null;
    }
}

// 2. Jika MySQL tidak tersedia, gunakan SQLite mandiri sebagai fallback lokal / Vercel
if ($conn === null) {
    if (!defined('DB_DRIVER')) {
        define('DB_DRIVER', 'SQLITE');
    }

    $isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']) || !empty(getenv('NOW_REGION'));
    $baseSqlite = __DIR__ . '/../skinexpert.sqlite';

    if ($isVercel) {
        // Lingkungan Vercel Serverless bersifat Read-Only, salin DB ke /tmp agar operasi tulis berjalan lancar
        $sqlitePath = '/tmp/skinexpert.sqlite';
        $needsCopy = !file_exists($sqlitePath) || (filesize($sqlitePath) === 0) || (file_exists($baseSqlite) && filemtime($baseSqlite) > filemtime($sqlitePath));
        if ($needsCopy && file_exists($baseSqlite)) {
            @copy($baseSqlite, $sqlitePath);
        } elseif (!file_exists($sqlitePath)) {
            @touch($sqlitePath);
        }
    } else {
        $sqlitePath = $baseSqlite;
        if (!file_exists($sqlitePath)) {
            @touch($sqlitePath);
        }
    }

    try {
        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");
    } catch (PDOException $e) {
        die("Koneksi database gagal (MySQL & SQLite tidak aktif): " . $e->getMessage());
    }

    class SQLiteResultWrapper {
        private array $rows;
        private int $pointer = 0;
        public int $num_rows = 0;

        public function __construct(array $rows) {
            $this->rows = $rows;
            $this->num_rows = count($rows);
        }

        public function fetch_assoc(): ?array {
            if ($this->pointer < $this->num_rows) {
                return $this->rows[$this->pointer++];
            }
            return null;
        }

        public function fetch_all(int $mode = MYSQLI_ASSOC): array {
            return $this->rows;
        }
    }

    class SQLiteStatementWrapper {
        private PDO $pdo;
        private PDOStatement $stmt;
        private array $boundParams = [];
        private ?SQLiteResultWrapper $lastResult = null;
        public int $affected_rows = 0;

        public function __construct(PDO $pdo, string $sql) {
            $this->pdo = $pdo;
            // Normalisasi sintaks MySQL ringan jika ada
            $sql = str_replace('`', '', $sql);
            $sql = preg_replace('/\bFOR UPDATE\b/i', '', $sql);
            $this->stmt = $pdo->prepare($sql);
        }

        public function bind_param(string $types, &...$params): bool {
            $this->boundParams = [];
            foreach ($params as $idx => &$val) {
                $this->boundParams[$idx + 1] = &$val;
            }
            return true;
        }

        public function execute(): bool {
            foreach ($this->boundParams as $pos => $val) {
                $type = PDO::PARAM_STR;
                if (is_int($val)) {
                    $type = PDO::PARAM_INT;
                } elseif (is_bool($val)) {
                    $type = PDO::PARAM_BOOL;
                } elseif (is_null($val)) {
                    $type = PDO::PARAM_NULL;
                }
                $this->stmt->bindValue($pos, $val, $type);
            }
            try {
                $success = $this->stmt->execute();
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                $code = 0;
                if (stripos($msg, 'UNIQUE constraint failed') !== false) {
                    $code = 1062;
                } elseif (stripos($msg, 'FOREIGN KEY constraint failed') !== false) {
                    $code = 1451;
                }
                throw new mysqli_sql_exception($msg, $code, $e);
            }
            $this->affected_rows = $this->stmt->rowCount();
            if ($success && $this->stmt->columnCount() > 0) {
                $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->lastResult = new SQLiteResultWrapper($rows);
            } else {
                $this->lastResult = new SQLiteResultWrapper([]);
            }
            return $success;
        }

        public function get_result(): SQLiteResultWrapper {
            return $this->lastResult ?? new SQLiteResultWrapper([]);
        }

        public function close(): bool {
            $this->stmt->closeCursor();
            return true;
        }
    }

    class SQLiteConnectionWrapper {
        private PDO $pdo;
        public ?string $connect_error = null;
        public string $error = "";
        public int $affected_rows = 0;

        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
        }

        public function query(string $sql) {
            try {
                $cleanSql = str_replace('`', '', $sql);
                $cleanSql = preg_replace('/\bFOR UPDATE\b/i', '', $cleanSql);
                $stmt = $this->pdo->query($cleanSql);
                if ($stmt && $stmt->columnCount() > 0) {
                    $this->affected_rows = $stmt->rowCount();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    return new SQLiteResultWrapper($rows);
                }
                $this->affected_rows = $stmt ? $stmt->rowCount() : 0;
                return true;
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                return false;
            }
        }

        public function prepare(string $sql): SQLiteStatementWrapper {
            return new SQLiteStatementWrapper($this->pdo, $sql);
        }

        public function begin_transaction(): bool {
            if (!$this->pdo->inTransaction()) {
                return $this->pdo->beginTransaction();
            }
            return true;
        }

        public function commit(): bool {
            if ($this->pdo->inTransaction()) {
                return $this->pdo->commit();
            }
            return true;
        }

        public function rollback(): bool {
            if ($this->pdo->inTransaction()) {
                return $this->pdo->rollBack();
            }
            return true;
        }

        public function set_charset(string $charset): bool {
            return true;
        }

        public function real_escape_string(string $str): string {
            $quoted = $this->pdo->quote($str);
            return substr($quoted, 1, -1);
        }

        public function escape_string(string $str): string {
            return $this->real_escape_string($str);
        }

        public function close(): bool {
            return true;
        }

        public function __get(string $name) {
            if ($name === 'insert_id') {
                return (int) $this->pdo->lastInsertId();
            }
            if ($name === 'affected_rows') {
                return $this->affected_rows;
            }
            return null;
        }
    }

    $conn = new SQLiteConnectionWrapper($pdo);
}
