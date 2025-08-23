#!/usr/bin/env bash
# Simple smoke test to verify endpoints when the server is running.
set -e
BASE_URL=${BASE_URL:-http://localhost:8096}

echo "GET /api/healthz"
curl -s "$BASE_URL/api/healthz" || exit 1

echo -e "\nGET /api/stations?page=1"
curl -s "$BASE_URL/api/stations?page=1" || exit 1

echo -e "\nDone"
