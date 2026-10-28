<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/?p=account');
csrf_check();
set_time_limit(300);

$tmpDb = sys_get_temp_dir() . '/eko-' . bin2hex(random_bytes(6)) . '.sqlite';
db()->exec("VACUUM INTO '" . str_replace("'", "''", $tmpDb) . "'");
$zipPath = sys_get_temp_dir() . '/eko-backup-' . bin2hex(random_bytes(6)) . '.zip';
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile($tmpDb, 'data/eko.sqlite');
foreach (['full', 'thumb'] as $d) {
    foreach (glob(UPLOAD_DIR . "/$d/*") ?: [] as $file) {
        if (is_file($file)) $zip->addFile($file, "uploads/$d/" . basename($file));
    }
}
$zip->close();
$name = 'eko-budowa-backup-' . date('Y-m-d_H-i') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
@unlink($tmpDb);
