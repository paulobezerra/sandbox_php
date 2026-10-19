#!/usr/bin/env bash
set -euo pipefail

if [[ $# -gt 0 ]]; then
    export APP_URL="$1"
fi
npm --prefix frontend run test
