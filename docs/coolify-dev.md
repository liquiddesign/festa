# Dev instance v Coolify

Stack: `docker-compose.dev.yml` (služby `web` + `festa-db`) a `Dockerfile.dev`
(PHP 7.1 + Apache, jako v `.ddev/config.yaml`). Konfigurace kontejneru je v `_docker/`.

## Co instance potřebuje

- **`COMPOSER_AUTH`** (build-time proměnná v Coolify) — balíčky `lqdlib/*` jsou privátní
  repa na gitlab.com. Hodnota: `{"gitlab-token":{"gitlab.com":"<token s read_api>"}}`.
  Bez ní build spadne na `composer install`. Token zůstává jen v build stage `vendor`.
- **Data** — databáze `festa` se rodí prázdná; dump se nalévá přes `instance_db_import`.
  Strukturu podle entit lze srovnat `composer sync-database` uvnitř kontejneru `web`.

## Proměnné

| proměnná | výchozí | k čemu |
|---|---|---|
| `COMPOSER_AUTH` | – | token do GitLabu pro build |
| `APP_DEBUG` | `1` | `0` = produkční chování Nette (bez Tracy) |

## Rozdíly proti produkci / DDEV

- Kořenový `.htaccess` se nepoužívá (`AllowOverride None`), pravidla jsou v
  `_docker/vhost.conf` — bez přesměrování na https a www, TLS končí na Traefiku.
- `app/config/config.local.neon` zakládá při startu `_docker/entrypoint.sh` z env
  (`DB_HOST=festa-db`, root bez hesla).
- `_docker/prepend.php` nastaví `HTTPS` podle `X-Forwarded-Proto` a zapne debug mód
  (bootstrap ho jinak pouští jen pro `lqd_ip`).
- `temp/` nemá volume (čistá cache po každém deployi), `userfiles/` ano.
