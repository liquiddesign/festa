#!/bin/bash
set -e

cd /var/www/html

# config.local.neon je v .gitignore (lokálně ho zakládá `composer installer`).
# Na instanci ho skládáme z env compose; přístupy jdou TCP na službu festa-db.
cat > app/config/config.local.neon <<NEON
storm:
	default:
		host: ${DB_HOST:-festa-db}
		dbname: ${DB_NAME:-festa}
		user: ${DB_USER:-root}
		password: "${DB_PASSWORD:-}"
NEON

# Zapisovatelné adresáře pro Nette (DI kontejner, cache, sessions, Tracy logy).
mkdir -p temp/cache temp/sessions temp/log userfiles
chown -R www-data:www-data temp userfiles 2>/dev/null || true
chmod -R 0777 temp userfiles 2>/dev/null || true

exec apache2-foreground
