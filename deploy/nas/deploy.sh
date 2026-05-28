#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/volume2/docker/hireme/app}"
COMPOSE_FILE="$APP_DIR/deploy/nas/docker-compose.yml"

cd "$APP_DIR"

if [ ! -f .env ]; then
  echo "Missing $APP_DIR/.env. The GitHub workflow should create it from repository secrets." >&2
  exit 1
fi

docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" up -d --build --remove-orphans
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" exec -T app php artisan migrate --force
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" exec -T app php artisan db:seed --force
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" exec -T app php artisan view:cache
docker compose -f "$COMPOSE_FILE" --env-file "$APP_DIR/.env" ps
