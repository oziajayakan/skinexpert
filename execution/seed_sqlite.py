"""
Script untuk mengonversi skinexpert_db.sql menjadi database SQLite lokal
sehingga sistem pakar dapat langsung berjalan offline tanpa MySQL server.
"""

import sqlite3
import os
import re

def seed_sqlite():
    sql_path = os.path.join(os.path.dirname(__file__), "..", "skinexpert_db.sql")
    sqlite_path = os.path.join(os.path.dirname(__file__), "..", "skinexpert.sqlite")

    if os.path.exists(sqlite_path):
        os.remove(sqlite_path)

    conn = sqlite3.connect(sqlite_path)
    cur = conn.cursor()

    with open(sql_path, "r", encoding="utf-8") as f:
        sql_content = f.read()

    # DDL SQLite yang kompatibel
    ddl_statements = [
        """
        CREATE TABLE IF NOT EXISTS gejala (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode TEXT NOT NULL UNIQUE,
            nama TEXT NOT NULL,
            deskripsi TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        """,
        """
        CREATE TABLE IF NOT EXISTS kondisi_kulit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode TEXT NOT NULL UNIQUE,
            nama TEXT NOT NULL,
            deskripsi TEXT,
            saran TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        """,
        """
        CREATE TABLE IF NOT EXISTS rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            kode TEXT NOT NULL UNIQUE,
            nama TEXT NOT NULL,
            kondisi_kulit_id INTEGER NOT NULL,
            aktif INTEGER NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (kondisi_kulit_id) REFERENCES kondisi_kulit (id) ON DELETE CASCADE
        );
        """,
        """
        CREATE TABLE IF NOT EXISTS rule_gejala (
            rule_id INTEGER NOT NULL,
            gejala_id INTEGER NOT NULL,
            PRIMARY KEY (rule_id, gejala_id),
            FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE,
            FOREIGN KEY (gejala_id) REFERENCES gejala (id) ON DELETE CASCADE
        );
        """,
        """
        CREATE TABLE IF NOT EXISTS pemeriksaan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            hasil TEXT
        );
        """,
        """
        CREATE TABLE IF NOT EXISTS pemeriksaan_gejala (
            pemeriksaan_id INTEGER NOT NULL,
            gejala_id INTEGER NOT NULL,
            PRIMARY KEY (pemeriksaan_id, gejala_id),
            FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id) ON DELETE CASCADE,
            FOREIGN KEY (gejala_id) REFERENCES gejala (id) ON DELETE CASCADE
        );
        """
    ]

    for ddl in ddl_statements:
        cur.executescript(ddl)

    # Ekstrak data INSERT dari skinexpert_db.sql
    insert_pattern = re.compile(r"INSERT INTO `?(\w+)`?\s*\(([^)]+)\)\s*VALUES\s*([^;]+);", re.DOTALL | re.IGNORECASE)
    matches = insert_pattern.findall(sql_content)

    for table, columns, values_block in matches:
        # Bersihkan values
        values_tuples = re.findall(r"\(([^)]+)\)", values_block)
        col_names = [c.strip().strip("`") for c in columns.split(",")]
        placeholders = ", ".join(["?"] * len(col_names))
        insert_sql = f"INSERT OR IGNORE INTO {table} ({', '.join(col_names)}) VALUES ({placeholders})"

        for vt in values_tuples:
            # Tokenize CSV values
            # Sederhana: gunakan regex parsing nilai SQL
            tokens = []
            # Memisahkan string literal '...' dan angka/NULL
            token_matches = re.finditer(r"'([^'\\]*(?:\\.[^'\\]*)*)'|NULL|([0-9\.\-]+)", vt)
            row_vals = []
            for tm in token_matches:
                if tm.group(1) is not None:
                    # String
                    val = tm.group(1).replace("\\'", "'").replace('\\"', '"').replace('\\n', '\n')
                    row_vals.append(val)
                elif tm.group(0) == "NULL":
                    row_vals.append(None)
                elif tm.group(2) is not None:
                    # Angka
                    num_str = tm.group(2)
                    row_vals.append(int(num_str) if num_str.isdigit() else float(num_str))

            if len(row_vals) == len(col_names):
                cur.execute(insert_sql, row_vals)

    conn.commit()

    # Hitung jumlah
    for tbl in ["gejala", "kondisi_kulit", "rules", "rule_gejala", "pemeriksaan"]:
        cur.execute(f"SELECT COUNT(*) FROM {tbl}")
        print(f"Tabel {tbl}: {cur.fetchone()[0]} baris")

    conn.close()
    print("Seeding SQLite berhasil!")

if __name__ == "__main__":
    seed_sqlite()
