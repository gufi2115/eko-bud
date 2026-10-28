@echo off
cd /d "%~dp0.."
if not exist .env copy .env.example .env
if not exist data mkdir data
if not exist uploads\thumb mkdir uploads\thumb
if not exist uploads\full mkdir uploads\full
php src\migrate.php
echo Strona: http://localhost:8080   Panel: http://localhost:8080/admin   (Ctrl+C = stop)
php -S localhost:8080 -t public -d upload_max_filesize=20M -d post_max_size=64M -d max_file_uploads=40 -d memory_limit=256M -d display_errors=1 dev\router.php
