#!/usr/bin/env bash
set -euo pipefail

./tools/test-frontend.sh "${APP_URL:-http://127.0.0.1:8080}"
docker compose exec -T app composer test
