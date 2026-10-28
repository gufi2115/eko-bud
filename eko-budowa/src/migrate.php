<?php
declare(strict_types=1);
// Uruchamiane z entrypoint.sh (CLI) oraz przez kreator instalacji.
require_once __DIR__ . '/bootstrap.php';
run_migrations();
if (PHP_SAPI === 'cli') {
    echo "[migrate] Baza gotowa.\n";
}
