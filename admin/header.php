<?php
if (!isset($judulHalaman)) {
    $judulHalaman = "Admin SkinExpert";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulHalaman) ?> | SkinExpert</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            color: #263b35;
        }
        .navbar {
            background: #176b55;
            color: white;
            padding: 18px 6%;
        }
        .navbar h2 { margin: 0 0 12px; }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 18px;
            font-size: 14px;
        }
        .container {
            width: 92%;
            max-width: 1100px;
            margin: 28px auto;
        }
        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #0000000d;
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
        }
        .angka { font-size: 32px; font-weight: bold; color: #176b55; }
        label { display: block; margin: 12px 0 6px; }
        input[type=text], textarea, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd7d2;
            border-radius: 7px;
            font: inherit;
        }
        textarea { min-height: 90px; resize: vertical; }
        button, .btn {
            display: inline-block;
            padding: 10px 15px;
            border: 0;
            border-radius: 7px;
            background: #176b55;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-secondary { background: #64748b; }
        .btn-danger { background: #b42318; }
        .btn-warning { background: #b7791f; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e4ebe7;
            text-align: left;
            vertical-align: top;
        }
        th { background: #edf5f1; }
        .pesan {
            padding: 12px;
            border-radius: 7px;
            background: #e6f5ec;
            margin-bottom: 16px;
        }
        .error { background: #fde8e7; color: #8c1d18; }
        .footer {
            text-align: center;
            padding: 25px;
            color: #66756e;
            font-size: 13px;
        }
        @media (max-width: 600px) {
            .navbar a { display: inline-block; margin: 8px 12px 0 0; }
            .container { width: 94%; }
        }
    </style>
</head>
<body>
<header class="navbar">
    <h2>SkinExpert — Admin</h2>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="gejala.php">Data Fakta (F)</a>
        <a href="kondisi.php">Data Masalah (M)</a>
        <a href="aturan.php">Basis Aturan (R)</a>
        <a href="../latihan.php">Skenario Latihan</a>
        <a href="../index.php">Website Utama</a>
    </nav>
</header>
<main class="container">
