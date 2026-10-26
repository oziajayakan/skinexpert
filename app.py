import sqlite3, os
from flask import Flask, render_template, request, redirect, url_for, flash
from datetime import datetime

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, 'skinexpert.sqlite')

app = Flask(__name__)
app.secret_key = 'skinexpert-sistem-pakar-kulit-2026'

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA foreign_keys = ON")
    return conn

# ====================== ROUTES ======================
@app.route('/')
def index():
    return render_template('index.html', page='home')

# ---------- GEJALA ----------
@app.route('/gejala')
def gejala_list():
    conn = get_db()
    data = conn.execute("SELECT * FROM gejala ORDER BY kode").fetchall()
    conn.close()
    return render_template('index.html', page='gejala', data=data, edit=None)

@app.route('/gejala/tambah', methods=['POST'])
def gejala_tambah():
    kode = request.form['kode'].strip().upper()
    nama = request.form['nama'].strip()
    deskripsi = request.form.get('deskripsi', '').strip()
    if not kode or not nama:
        flash('Kode dan nama wajib diisi!', 'error')
        return redirect(url_for('gejala_list'))
    conn = get_db()
    try:
        conn.execute("INSERT INTO gejala(kode,nama,deskripsi) VALUES(?,?,?)", (kode, nama, deskripsi))
        conn.commit()
        flash('Gejala berhasil ditambahkan.', 'success')
    except sqlite3.IntegrityError:
        flash('Kode gejala sudah dipakai!', 'error')
    conn.close()
    return redirect(url_for('gejala_list'))

@app.route('/gejala/edit/<int:gid>', methods=['GET', 'POST'])
def gejala_edit(gid):
    conn = get_db()
    row = conn.execute("SELECT * FROM gejala WHERE id=?", (gid,)).fetchone()
    if not row:
        conn.close()
        return redirect(url_for('gejala_list'))
    if request.method == 'POST':
        try:
            conn.execute("UPDATE gejala SET kode=?,nama=?,deskripsi=? WHERE id=?",
                         (request.form['kode'].strip().upper(), request.form['nama'].strip(),
                          request.form.get('deskripsi', '').strip(), gid))
            conn.commit()
            flash('Gejala berhasil diperbarui.', 'success')
            conn.close()
            return redirect(url_for('gejala_list'))
        except sqlite3.IntegrityError:
            flash('Kode gejala sudah dipakai!', 'error')
    data = conn.execute("SELECT * FROM gejala ORDER BY kode").fetchall()
    conn.close()
    return render_template('index.html', page='gejala', data=data, edit=row)

@app.route('/gejala/hapus/<int:gid>', methods=['POST'])
def gejala_hapus(gid):
    conn = get_db()
    try:
        conn.execute("DELETE FROM rule_gejala WHERE gejala_id=?", (gid,))
        conn.execute("DELETE FROM pemeriksaan_gejala WHERE gejala_id=?", (gid,))
        conn.execute("DELETE FROM gejala WHERE id=?", (gid,))
        conn.commit()
        flash('Gejala berhasil dihapus.', 'success')
    except Exception as e:
        conn.rollback()
        flash(f'Gagal menghapus gejala: {e}', 'error')
    finally:
        conn.close()
    return redirect(url_for('gejala_list'))

# ---------- KONDISI KULIT ----------
@app.route('/kondisi')
def kondisi_list():
    conn = get_db()
    data = conn.execute("SELECT * FROM kondisi_kulit ORDER BY kode").fetchall()
    rules = conn.execute("""
        SELECT r.kondisi_kulit_id, COUNT(rg.gejala_id)
        FROM rules r
        LEFT JOIN rule_gejala rg ON rg.rule_id = r.id
        GROUP BY r.kondisi_kulit_id
    """).fetchall()
    rule_count = {r[0]: r[1] for r in rules}
    conn.close()
    return render_template('index.html', page='kondisi', data=data, edit=None, rule_count=rule_count)

@app.route('/kondisi/tambah', methods=['POST'])
def kondisi_tambah():
    conn = get_db()
    kode = request.form['kode'].strip().upper()
    nama = request.form['nama'].strip()
    deskripsi = request.form.get('deskripsi', '').strip()
    saran = request.form.get('saran', '').strip()
    try:
        conn.execute("INSERT INTO kondisi_kulit(kode,nama,deskripsi,saran) VALUES(?,?,?,?)",
                     (kode, nama, deskripsi, saran))
        conn.commit()
        flash('Kondisi kulit berhasil ditambahkan.', 'success')
    except sqlite3.IntegrityError:
        flash('Kode kondisi sudah dipakai!', 'error')
    conn.close()
    return redirect(url_for('kondisi_list'))

@app.route('/kondisi/edit/<int:kid>', methods=['GET', 'POST'])
def kondisi_edit(kid):
    conn = get_db()
    row = conn.execute("SELECT * FROM kondisi_kulit WHERE id=?", (kid,)).fetchone()
    if not row:
        conn.close()
        return redirect(url_for('kondisi_list'))
    if request.method == 'POST':
        try:
            conn.execute("UPDATE kondisi_kulit SET kode=?,nama=?,deskripsi=?,saran=? WHERE id=?",
                         (request.form['kode'].strip().upper(), request.form['nama'].strip(),
                          request.form.get('deskripsi', '').strip(), request.form.get('saran', '').strip(), kid))
            conn.commit()
            flash('Kondisi kulit berhasil diperbarui.', 'success')
            conn.close()
            return redirect(url_for('kondisi_list'))
        except sqlite3.IntegrityError:
            flash('Kode kondisi sudah dipakai!', 'error')
    data = conn.execute("SELECT * FROM kondisi_kulit ORDER BY kode").fetchall()
    rules = conn.execute("""
        SELECT r.kondisi_kulit_id, COUNT(rg.gejala_id)
        FROM rules r
        LEFT JOIN rule_gejala rg ON rg.rule_id = r.id
        GROUP BY r.kondisi_kulit_id
    """).fetchall()
    rule_count = {r[0]: r[1] for r in rules}
    conn.close()
    return render_template('index.html', page='kondisi', data=data, edit=row, rule_count=rule_count)

@app.route('/kondisi/hapus/<int:kid>', methods=['POST'])
def kondisi_hapus(kid):
    conn = get_db()
    try:
        rule_ids = [r[0] for r in conn.execute("SELECT id FROM rules WHERE kondisi_kulit_id=?", (kid,)).fetchall()]
        for r_id in rule_ids:
            conn.execute("DELETE FROM rule_gejala WHERE rule_id=?", (r_id,))
        conn.execute("DELETE FROM rules WHERE kondisi_kulit_id=?", (kid,))
        conn.execute("DELETE FROM kondisi_kulit WHERE id=?", (kid,))
        conn.commit()
        flash('Kondisi kulit berhasil dihapus.', 'success')
    except Exception as e:
        conn.rollback()
        flash(f'Gagal menghapus kondisi: {e}', 'error')
    finally:
        conn.close()
    return redirect(url_for('kondisi_list'))

@app.route('/kondisi/rule/<int:kid>', methods=['GET', 'POST'])
def kondisi_rule(kid):
    conn = get_db()
    k = conn.execute("SELECT * FROM kondisi_kulit WHERE id=?", (kid,)).fetchone()
    if not k:
        conn.close()
        return redirect(url_for('kondisi_list'))

    rule = conn.execute("SELECT * FROM rules WHERE kondisi_kulit_id=?", (kid,)).fetchone()

    if request.method == 'POST':
        if not rule:
            cur = conn.cursor()
            cur.execute("INSERT INTO rules(kode,nama,kondisi_kulit_id,aktif) VALUES(?,?,?,1)",
                        (f"R_{k['kode']}", f"Aturan {k['nama']}", kid))
            rule_id = cur.lastrowid
        else:
            rule_id = rule['id']

        conn.execute("DELETE FROM rule_gejala WHERE rule_id=?", (rule_id,))
        for gid in request.form.getlist('gejala'):
            conn.execute("INSERT INTO rule_gejala(rule_id, gejala_id) VALUES(?,?)", (rule_id, int(gid)))
        conn.commit()
        flash('Rule berhasil diperbarui.', 'success')
        conn.close()
        return redirect(url_for('kondisi_list'))

    gejala = conn.execute("SELECT * FROM gejala ORDER BY kode").fetchall()
    selected = set()
    if rule:
        selected = {r[0] for r in conn.execute("SELECT gejala_id FROM rule_gejala WHERE rule_id=?", (rule['id'],))}
    conn.close()
    return render_template('index.html', page='rule', k=k, gejala=gejala, selected=selected)

# ---------- KONSULTASI ----------
@app.route('/konsultasi')
def konsultasi():
    conn = get_db()
    gejala = conn.execute("SELECT * FROM gejala ORDER BY kode").fetchall()
    conn.close()
    return render_template('index.html', page='konsultasi', gejala=gejala)

@app.route('/konsultasi/proses', methods=['POST'])
def konsultasi_proses():
    selected_ids = [int(x) for x in request.form.getlist('gejala')]
    if not selected_ids:
        flash('Pilih minimal satu gejala terlebih dahulu.', 'error')
        return redirect(url_for('konsultasi'))

    conn = get_db()
    gejala_map = {r['id']: dict(r) for r in conn.execute("SELECT * FROM gejala")}
    kondisi_all = [dict(r) for r in conn.execute("SELECT * FROM kondisi_kulit ORDER BY kode")]
    rules = conn.execute("""
        SELECT r.kondisi_kulit_id, rg.gejala_id
        FROM rules r
        INNER JOIN rule_gejala rg ON rg.rule_id = r.id
        WHERE r.aktif = 1
    """).fetchall()

    rule_map = {}
    for r in rules:
        rule_map.setdefault(r['kondisi_kulit_id'], set()).add(r['gejala_id'])

    selected_set = set(selected_ids)

    # ====== BACKWARD CHAINING ======
    trace = []
    for k in kondisi_all:
        subgoals = rule_map.get(k['id'], set())
        if not subgoals:
            continue
        matched = subgoals & selected_set
        missing = subgoals - selected_set
        score = round(len(matched) / len(subgoals) * 100, 1)
        trace.append({
            'kondisi': k,
            'matched': matched,
            'missing': missing,
            'score': score,
            'full': not missing
        })

    trace.sort(key=lambda x: (-x['score'], x['kondisi']['kode']))
    trace = [t for t in trace if t['score'] > 0]
    top = trace[0] if trace else None

    def enrich(ids):
        return [gejala_map[i] for i in sorted(ids) if i in gejala_map]

    for t in trace:
        t['matched_gejala'] = enrich(t['matched'])
        t['missing_gejala'] = enrich(t['missing'])

    gejala_kode = ", ".join(sorted(gejala_map[i]['kode'] for i in selected_set if i in gejala_map))
    hasil_str = (f"{top['kondisi']['nama']} ({top['score']}%)" if top else "Tidak teridentifikasi")

    cur = conn.cursor()
    cur.execute("INSERT INTO pemeriksaan(tanggal, hasil) VALUES(?,?)",
                (datetime.now().strftime('%Y-%m-%d %H:%M:%S'), hasil_str))
    pemeriksaan_id = cur.lastrowid
    for gid in selected_set:
        cur.execute("INSERT INTO pemeriksaan_gejala(pemeriksaan_id, gejala_id) VALUES(?,?)",
                    (pemeriksaan_id, gid))

    conn.commit()
    conn.close()

    return render_template('index.html', page='hasil', trace=trace, top=top,
                           n_selected=len(selected_set), n_gejala=len(gejala_map))

# ---------- RIWAYAT ----------
@app.route('/riwayat')
def riwayat():
    conn = get_db()
    rows = conn.execute("SELECT id, tanggal, hasil FROM pemeriksaan ORDER BY id DESC").fetchall()
    data = []
    for r in rows:
        gejala_rows = conn.execute("""
            SELECT g.kode
            FROM pemeriksaan_gejala pg
            JOIN gejala g ON g.id = pg.gejala_id
            WHERE pg.pemeriksaan_id = ?
            ORDER BY g.kode
        """, (r['id'],)).fetchall()
        g_str = ", ".join(g[0] for g in gejala_rows) if gejala_rows else "-"
        data.append({
            'id': r['id'],
            'tanggal': r['tanggal'],
            'hasil': r['hasil'],
            'gejala_terpilih': g_str
        })
    conn.close()
    return render_template('index.html', page='riwayat', data=data)

@app.route('/riwayat/hapus/<int:rid>', methods=['POST'])
def riwayat_hapus(rid):
    conn = get_db()
    try:
        conn.execute("DELETE FROM pemeriksaan_gejala WHERE pemeriksaan_id=?", (rid,))
        conn.execute("DELETE FROM pemeriksaan WHERE id=?", (rid,))
        conn.commit()
        flash('Riwayat berhasil dihapus.', 'success')
    except Exception as e:
        conn.rollback()
        flash(f'Gagal menghapus riwayat: {e}', 'error')
    finally:
        conn.close()
    return redirect(url_for('riwayat'))

if __name__ == '__main__':
    print("SkinExpert Flask Server berjalan di http://127.0.0.1:5000")
    app.run(debug=True, port=5000)
