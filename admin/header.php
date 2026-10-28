<?php
require_once __DIR__ . "/../includes/icons.php";

if (!isset($judulHalaman)) {
    $judulHalaman = "Admin SkinExpert";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulHalaman) ?> | SkinExpert Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #ecfdf5;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 14px rgba(15,23,42,0.07);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            line-height: 1.5;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        .navbar {
            background: #0f172a;
            color: white;
            padding: 16px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .navbar h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #34d399;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .navbar a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .navbar a:hover {
            color: white;
            background: rgba(255,255,255,0.08);
        }
        .navbar a.active {
            background: rgba(16,185,129,0.15);
            color: #34d399;
        }
        .container {
            width: 92%;
            max-width: 1120px;
            margin: 32px auto;
        }
        .card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .angka {
            font-family: 'Outfit', sans-serif;
            font-size: 38px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 8px 0 16px;
        }
        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: 600;
            font-size: 14px;
            color: #334155;
        }
        input[type=text], textarea, select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font: inherit;
            background: #ffffff;
            color: #1e293b;
            transition: border-color 0.2s;
        }
        input[type=text]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
        }
        textarea { min-height: 90px; resize: vertical; }
        button, .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: 0;
            border-radius: 9px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        button:hover, .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .btn-secondary { background: #64748b; }
        .btn-secondary:hover { background: #475569; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; }
        .btn-warning:hover { background: #d97706; }
        .table-wrap {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }
        th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
        }
        tr:last-child td { border-bottom: none; }
        .pesan {
            padding: 14px 18px;
            border-radius: 10px;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .svg-icon {
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
        }
        .footer {
            text-align: center;
            padding: 30px;
            color: var(--text-muted);
            font-size: 13px;
        }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; align-items: flex-start; }
            .container { width: 95%; margin: 20px auto; }
        }
    </style>
</head>
<body>
<header class="navbar">
    <h2>
        <?= icon('leaf', '', 22) ?>
        <span>SkinExpert</span>
        <span style="font-size:0.8rem;font-weight:500;color:#94a3b8;padding-left:4px;">Knowledge Base Admin</span>
    </h2>
    <nav>
        <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('home', '', 15) ?> Dashboard</a>
        <a href="gejala.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('search', '', 15) ?> Fakta Gejala (G)</a>
        <a href="kondisi.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('target', '', 15) ?> Kondisi Kulit (H)</a>
        <a href="aturan.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('rule', '', 15) ?> Basis Aturan (R)</a>
        <a href="../riwayat.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('clipboard', '', 15) ?> Riwayat</a>
        <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;"><?= icon('leaf', '', 15) ?> Web Utama</a>
    </nav>
</header>
<main class="container">
