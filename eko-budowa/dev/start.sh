#!/bin/sh
# Uruchomienie lokalne bez Dockera (Linux/macOS/Git Bash). Wymaga PHP 8.3+ z: gd, pdo_sqlite, zip, exif, mbstring.
cd "$(dirname "$0")/.."
[ -f .env ] || cp .env.example .env
mkdir -p data uploads/thumb uploads/full
php src/migrate.php
echo ">>> Strona: http://localhost:8080   Panel: http://localhost:8080/admin   (Ctrl+C = stop)"
exec php -S localhost:8080 -t public \
  -d upload_max_filesize=20M -d post_max_size=64M -d max_file_uploads=40 -d memory_limit=256M \
  -d display_errors=1 dev/router.php
