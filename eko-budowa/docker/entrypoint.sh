#!/bin/sh
set -e
APP=/var/www/html

# 1. Brakujące katalogi (świeże, puste wolumeny)
mkdir -p "$APP/data" "$APP/uploads/thumb" "$APP/uploads/full"

# 2. Uprawnienia: tylko katalogi z danymi
chown -R www-data:www-data "$APP/data" "$APP/uploads"
chmod 750 "$APP/data"
chmod 755 "$APP/uploads" "$APP/uploads/thumb" "$APP/uploads/full"

# 3. Migracje bazy i opcjonalne konto admina z .env
if [ -f "$APP/src/migrate.php" ]; then
  echo "[entrypoint] Migracje bazy..."
  su -s /bin/sh www-data -c "php $APP/src/migrate.php"
fi

# 4. Start Apache
exec apache2-foreground
