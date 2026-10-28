#!/bin/sh
# Kopia wolumenów Dockera. Użycie: ./backup.sh  (domyślnie do ./backups, trzyma 14 ostatnich)
set -e
cd "$(dirname "$0")"
PROJ="$(basename "$PWD" | tr 'A-Z' 'a-z' | tr -cd 'a-z0-9_-')"
DEST="${BACKUP_DIR:-$PWD/backups}"
TS="$(date +%F_%H%M)"
mkdir -p "$DEST"
for V in eko_data eko_uploads; do
  docker run --rm -v "${PROJ}_${V}:/src:ro" -v "$DEST:/dst" alpine \
    tar czf "/dst/${V}-${TS}.tgz" -C /src .
done
ls -1t "$DEST"/eko_data-*.tgz 2>/dev/null | tail -n +15 | xargs -r rm -f
ls -1t "$DEST"/eko_uploads-*.tgz 2>/dev/null | tail -n +15 | xargs -r rm -f
echo "Backup zapisany w $DEST ($TS)"
