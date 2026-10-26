<?php

$host = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$database = getenv('DB_NAME') ?: "skinexpert_db";
$port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

$conn = null;

// 1. Coba koneksi MySQL / MariaDB (lokal atau cloud via Environment Variables)
if (class_exists('mysqli')) {
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
        // Lingkungan Vercel Serverless bersifat Read-Only, salin DB ke /tmp agar operasi tulis (INSERT riwayat, CRUD) berjalan lancar
        $sqlitePath = '/tmp/skinexpert.sqlite';
        if (!file_exists($sqlitePath) && file_exists($baseSqlite)) {
            copy($baseSqlite, $sqlitePath);
        } elseif (!file_exists($sqlitePath)) {
            touch($sqlitePath);
        }
    } else {
        $sqlitePath = $baseSqlite;
        if (!file_exists($sqlitePath)) {
            touch($sqlitePath);
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
            return $this->pdo->beginTransaction();
        }

        public function commit(): bool {
            return $this->pdo->commit();
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

        public function __get(string $name) {
            if ($name === 'insert_id') {
                return (int) $this->pdo->lastInsertId();
            }
            return null;
        }
    }

    $conn = new SQLiteConnectionWrapper($pdo);
}
