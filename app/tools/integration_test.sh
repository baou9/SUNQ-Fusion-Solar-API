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
export CACHE_BACKEND=file
export RATE_LIMIT_PER_MINUTE=100
php -S 127.0.0.1:$PORT_API -t app app/api/index.php >/tmp/api.log 2>&1 &
API_PID=$!
sleep 1
first=$(curl -s -D /tmp/h1 -w '%{time_total}' http://127.0.0.1:$PORT_API/api/stations -o /tmp/first.json)
reqid=$(grep -i '^X-Request-Id:' /tmp/h1 | awk '{print $2}')
test -n "$reqid"
second=$(curl -w '%{time_total}' -s http://127.0.0.1:$PORT_API/api/stations -o /tmp/second.json)
preflight=$(curl -s -o /dev/null -w '%{http_code}' -X OPTIONS \
  -H "Origin: ${FRONTEND_ORIGIN:-http://localhost:3000}" \
  -H "Access-Control-Request-Method: GET" \
  "http://127.0.0.1:$PORT_API/api/stations")
storage=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT_API/storage/cookies.txt)
cat /tmp/first.json
cat /tmp/second.json
kill $API_PID
# rate limiting check
export RATE_LIMIT_PER_MINUTE=2
php -S 127.0.0.1:$PORT_API -t app app/api/index.php >/tmp/api.log 2>&1 &
API_PID=$!
sleep 1
c1=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT_API/api/stations)
c2=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT_API/api/stations)
c3=$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:$PORT_API/api/stations)
kill $API_PID $NB_PID
test "$preflight" -eq 204
test "$storage" -eq 404
test "$c3" -eq 429
python3 - <<PY
import sys
f=float("$first"); s=float("$second");
print('first',f,'second',s)
assert s<=f
PY
