#!/bin/bash
set -e
PORT_NB=9001
PORT_API=9002
php -S 127.0.0.1:$PORT_NB app/tools/mock_nb.php >/tmp/mock_nb.log 2>&1 &
NB_PID=$!
sleep 1
export FS_BASE="http://127.0.0.1:$PORT_NB"
export FS_USER=user
export FS_CODE=code
export MA_PROXY=
export CACHE_TTL_SECONDS=90
export APP_VERSION=test
export FRONTEND_ORIGIN=http://localhost:3000
php -S 127.0.0.1:$PORT_API -t app app/api/index.php >/tmp/api.log 2>&1 &
API_PID=$!
sleep 1
first=$(curl -w '%{time_total}' -s http://127.0.0.1:$PORT_API/api/stations -o /tmp/first.json)
second=$(curl -w '%{time_total}' -s http://127.0.0.1:$PORT_API/api/stations -o /tmp/second.json)
preflight=$(curl -s -o /dev/null -w '%{http_code}' -X OPTIONS \
  -H "Origin: ${FRONTEND_ORIGIN:-http://localhost:3000}" \
  -H "Access-Control-Request-Method: GET" \
  "http://127.0.0.1:$PORT_API/api/stations")
storage=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT_API/storage/cookies.txt)
cat /tmp/first.json
cat /tmp/second.json
kill $API_PID $NB_PID
test "$preflight" -eq 204
test "$storage" -eq 404
python3 - <<PY
import sys
f=float("$first"); s=float("$second");
print('first',f,'second',s)
assert s<=f
PY
