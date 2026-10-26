"""
Migrasi skinexpert.sqlite untuk menambahkan ON DELETE CASCADE pada Foreign Key
sekaligus mencadangkan database dan mempertahankan seluruh data riwayat dan aturan.
"""

import sqlite3
import os
import shutil

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH = os.path.join(BASE_DIR, 'skinexpert.sqlite')
BACKUP_PATH = os.path.join(BASE_DIR, 'skinexpert.sqlite.bak')

def migrate():
    if not os.path.exists(DB_PATH):
        print(f"File {DB_PATH} tidak ditemukan.")
        return

    # 1. Backup file database
    shutil.copyfile(DB_PATH, BACKUP_PATH)
    print(f"Cadangan database disimpan ke: {BACKUP_PATH}")

    conn = sqlite3.connect(DB_PATH)
    cur = conn.cursor()

    # Matikan foreign keys sementara saat rekonstruksi tabel
    cur.execute("PRAGMA foreign_keys = OFF;")

    cur.execute("BEGIN TRANSACTION;")

    # Tabel rules baru dengan ON DELETE CASCADE
    cur.execute("""
    CREATE TABLE IF NOT EXISTS rules_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        kode TEXT NOT NULL UNIQUE,
        nama TEXT NOT NULL,
        kondisi_kulit_id INTEGER NOT NULL,
        aktif INTEGER NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kondisi_kulit_id) REFERENCES kondisi_kulit (id) ON DELETE CASCADE
    );
    """)
    cur.execute("INSERT INTO rules_new SELECT * FROM rules;")
    cur.execute("DROP TABLE rules;")
    cur.execute("ALTER TABLE rules_new RENAME TO rules;")

    # Tabel rule_gejala baru dengan ON DELETE CASCADE
    cur.execute("""
    CREATE TABLE IF NOT EXISTS rule_gejala_new (
        rule_id INTEGER NOT NULL,
        gejala_id INTEGER NOT NULL,
        PRIMARY KEY (rule_id, gejala_id),
        FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE,
        FOREIGN KEY (gejala_id) REFERENCES gejala (id) ON DELETE CASCADE
    );
    """)
    cur.execute("INSERT INTO rule_gejala_new SELECT * FROM rule_gejala;")
    cur.execute("DROP TABLE rule_gejala;")
    cur.execute("ALTER TABLE rule_gejala_new RENAME TO rule_gejala;")

    # Tabel pemeriksaan_gejala baru dengan ON DELETE CASCADE
    cur.execute("""
    CREATE TABLE IF NOT EXISTS pemeriksaan_gejala_new (
        pemeriksaan_id INTEGER NOT NULL,
        gejala_id INTEGER NOT NULL,
        PRIMARY KEY (pemeriksaan_id, gejala_id),
        FOREIGN KEY (pemeriksaan_id) REFERENCES pemeriksaan (id) ON DELETE CASCADE,
        FOREIGN KEY (gejala_id) REFERENCES gejala (id) ON DELETE CASCADE
    );
    """)
    cur.execute("INSERT INTO pemeriksaan_gejala_new SELECT * FROM pemeriksaan_gejala;")
    cur.execute("DROP TABLE pemeriksaan_gejala;")
    cur.execute("ALTER TABLE pemeriksaan_gejala_new RENAME TO pemeriksaan_gejala;")

    conn.commit()

    # Aktifkan kembali foreign keys dan verifikasi
    cur.execute("PRAGMA foreign_keys = ON;")
    issues = cur.execute("PRAGMA foreign_key_check;").fetchall()
    if issues:
        print("Peringatan: Ditemukan masalah integritas foreign key:", issues)
    else:
        print("Integritas foreign key valid 100%.")

    for tbl in ["gejala", "kondisi_kulit", "rules", "rule_gejala", "pemeriksaan", "pemeriksaan_gejala"]:
        cnt = cur.execute(f"SELECT COUNT(*) FROM {tbl}").fetchone()[0]
        print(f"Data {tbl}: {cnt} baris")

    conn.close()
    print("Migrasi SQLite ON DELETE CASCADE selesai!")

if __name__ == "__main__":
    migrate()
