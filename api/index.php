<?php
// Entry point router untuk Vercel Serverless Function (vercel-php)

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH);
$rootDir = dirname(__DIR__);

// 1. Tangani file static jika request masuk ke handler ini
if (str_starts_with($path, '/static/')) {
    $staticFile = $rootDir . $path;
    if (is_file($staticFile)) {
        $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'  => 'text/css; charset=UTF-8',
            'js'   => 'application/javascript; charset=UTF-8',
            'json' => 'application/json',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];
        if (isset($mimeTypes[$ext])) {
            header("Content-Type: " . $mimeTypes[$ext]);
        }
        header("Cache-Control: public, max-age=86400");
        readfile($staticFile);
        exit;
    }
}

// 2. Akses ke root "/" atau "/index.php"
if ($path === '/' || $path === '' || $path === '/index.php') {
    chdir($rootDir);
    require $rootDir . '/index.php';
    exit;
}

// 3. Normalisasi path file target
$targetFile = $rootDir . $path;

// 3a. Jika direct target berupa file PHP yang ada
if (is_file($targetFile) && str_ends_with($targetFile, '.php')) {
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// 3b. Jika target direktori (misal /admin atau /admin/) yang punya index.php
if (is_dir($targetFile)) {
    $dirIndex = rtrim($targetFile, '/\\') . '/index.php';
    if (is_file($dirIndex)) {
        chdir(dirname($dirIndex));
        require $dirIndex;
        exit;
    }
}

// 3c. Jika target URL tanpa ekstensi .php (misal /konsultasi atau /riwayat)
if (is_file($targetFile . '.php')) {
    chdir(dirname($targetFile));
    require $targetFile . '.php';
    exit;
}

// 4. Jika file static lain di root (misal favicon.ico)
if (is_file($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if ($ext === 'ico') {
        header("Content-Type: image/x-icon");
        readfile($targetFile);
        exit;
    }
}

// 5. 404 Not Found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>404 Halaman Tidak Ditemukan - SkinExpert</title>
  <link rel="stylesheet" href="/static/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;">
  <div class="card" style="max-width:500px;padding:30px;">
    <h1 style="font-size:3rem;margin-bottom:10px;">404</h1>
    <h2>Halaman Tidak Ditemukan</h2>
    <p class="muted" style="margin:15px 0 25px;">Halaman yang Anda tuju tidak tersedia di sistem SkinExpert.</p>
    <a href="/" class="btn btn-primary">Kembali ke Beranda</a>
  </div>
</body>
</html>
<?php
exit;
