#!/usr/bin/env bash
set -euo pipefail

npm --prefix frontend run test
docker compose exec -T app composer test
