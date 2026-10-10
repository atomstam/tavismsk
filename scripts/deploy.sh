#!/bin/sh
# Run this on the production server after a git push.
# It updates code only. It does not replace .env or the database.
set -e
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "Missing .env — create it on the server from .env.example before deploying."
  echo "Set APP_ENV=production APP_DEBUG=false APP_URL=https://tavis.thaiedu.in.th and the server DB_* values."
  exit 1
fi

git pull --ff-only
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --no-interaction --prefer-dist
fi

mkdir -p storage/logs storage/cache storage/sessions storage/backups storage/tmp public/uploads
chmod -R u+rwX storage public/uploads 2>/dev/null || true

php -r '
require "config/bootstrap.php";
$row = db()->fetch("SELECT DATABASE() AS db, COUNT(*) AS users FROM users");
echo "database=" . ($row["db"] ?? "") . " users=" . ($row["users"] ?? "0") . PHP_EOL;
'

echo "Deployed. Site: https://tavis.thaiedu.in.th"
