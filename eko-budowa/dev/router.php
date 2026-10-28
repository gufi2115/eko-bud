<?php
// Router dla wbudowanego serwera PHP (tylko do testów lokalnych).
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
          'png' => 'image/png', 'webp' => 'image/webp'];

function serve(string $file, array $mimes): bool
{
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!is_file($file) || !isset($mimes[$ext])) { http_response_code(404); echo 'Not found'; return true; }
    header('Content-Type: ' . $mimes[$ext]);
    readfile($file);
    return true;
}

if (str_contains($path, '..')) { http_response_code(400); exit; }

if ($path === '/sitemap.xml') { require $root . '/public/sitemap.php'; return true; }
if ($path === '/robots.txt')  { require $root . '/public/robots.php'; return true; }

if (str_starts_with($path, '/uploads/')) {
    return serve($root . $path, $mimes);
}

if ($path === '/admin' || str_starts_with($path, '/admin/')) {
    $rel = trim(substr($path, 6), '/');
    $file = $root . '/admin/' . ($rel === '' ? 'index.php' : $rel);
    if (str_starts_with($rel, 'pages')) { http_response_code(403); exit; }
    if (str_ends_with($file, '.php') && is_file($file)) { chdir(dirname($file)); require $file; return true; }
    return serve($file, $mimes);
}

return false; // reszta z katalogu public/
