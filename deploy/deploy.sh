#!/usr/bin/env bash
set -euo pipefail

cd /var/www/fintech-wallet
git fetch origin main
git reset --hard origin/main

docker compose -f compose.prod.yaml up -d --build --remove-orphans
docker image prune -f

# Só termina com sucesso se a API responder
curl -fsS --retry 10 --retry-delay 3 --retry-all-errors http://127.0.0.1:4176/api/health > /dev/null
echo "Deploy concluído"
