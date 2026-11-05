#!/bin/bash
# 서비스 DB 일일 백업. PHP에서 프로세스 실행이 막혀 있어도 크론이 이 스크립트를 직접 실행한다.
# 한국시간 03:20. 서버 시계가 CST(UTC+8)이면 크론은 02:20.
set -eu
export TZ=Asia/Seoul

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
ENV_FILE="$ROOT/.env"
DIR="$ROOT/storage/backups/db"
mkdir -p "$DIR"
chmod 700 "$DIR"
if [ ! -f "$DIR/.htaccess" ]; then
  printf '%s\n' 'Require all denied' 'Deny from all' > "$DIR/.htaccess"
  chmod 644 "$DIR/.htaccess"
fi

exec 9>"$DIR/.lock"
if ! flock -n 9; then
  echo "SKIP already running"
  exit 0
fi

get_env() {
  key="$1"
  line="$(grep -m1 "^${key}=" "$ENV_FILE" || true)"
  val="${line#*=}"
  val="${val#"${val%%[![:space:]]*}"}"
  val="${val%"${val##*[![:space:]]}"}"
  val="${val%\"}"
  val="${val#\"}"
  val="${val%\'}"
  val="${val#\'}"
  printf '%s' "$val"
}

if [ ! -r "$ENV_FILE" ]; then
  echo "FAIL .env 파일을 읽지 못했습니다." >&2
  exit 1
fi

host="$(get_env DB_HOST)"
port="$(get_env DB_PORT)"
database="$(get_env DB_DATABASE)"
username="$(get_env DB_USERNAME)"
password="$(get_env DB_PASSWORD)"
keep="$(get_env DB_BACKUP_KEEP_DAYS)"
port="${port:-3306}"
keep="${keep:-14}"
case "$keep" in
  ''|*[!0-9]*) keep=14 ;;
esac
if [ "$keep" -lt 1 ]; then keep=14; fi
if [ "$keep" -gt 90 ]; then keep=90; fi

started="$(date '+%Y-%m-%d %H:%M:%S')"
started_ts="$(date +%s)"
engine="mysqldump"
filename=""
final=""
removed=0
err_msg=""

write_status() {
  export LU_DIR="$DIR"
  export LU_OK="$1"
  export LU_ENGINE="${2:-}"
  export LU_FILE="${3:-}"
  export LU_BYTES="${4:-0}"
  export LU_STARTED="$started"
  export LU_FINISHED="$(date '+%Y-%m-%d %H:%M:%S')"
  export LU_ELAPSED="$(( $(date +%s) - started_ts ))"
  export LU_KEEP="$keep"
  export LU_REMOVED="${5:-0}"
  export LU_ERROR="${6:-}"
  /usr/bin/php <<'PHP'
<?php
$dir = (string) getenv('LU_DIR');
$status = [
    'ok' => getenv('LU_OK') === '1',
    'engine' => (string) getenv('LU_ENGINE'),
    'file' => (string) getenv('LU_FILE'),
    'bytes' => (int) getenv('LU_BYTES'),
    'started_at' => (string) getenv('LU_STARTED'),
    'finished_at' => (string) getenv('LU_FINISHED'),
    'elapsed_sec' => (int) getenv('LU_ELAPSED'),
    'keep_days' => (int) getenv('LU_KEEP'),
    'removed' => (int) getenv('LU_REMOVED'),
    'error' => (string) getenv('LU_ERROR'),
];
$path = $dir . '/latest.json';
file_put_contents($path, json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
chmod($path, 0600);
$log = $dir . '/backup.log';
$line = $status['finished_at'] . ' ' . ($status['ok'] ? 'OK' : 'FAIL') . ' '
    . $status['file'] . ' ' . $status['bytes'] . ' ' . $status['engine']
    . ($status['error'] !== '' ? ' ' . $status['error'] : '') . "\n";
file_put_contents($log, $line, FILE_APPEND);
chmod($log, 0600);
PHP
}

fail() {
  err_msg="$1"
  err_msg="$(printf '%s' "$err_msg" | tr '\n' ' ' | sed -E 's/[Pp]assword[=:][^ ]*/password=***/g')"
  if [ "${#err_msg}" -gt 400 ]; then
    err_msg="${err_msg:0:400}"
  fi
  write_status 0 "" "" 0 0 "$err_msg"
  echo "FAIL $err_msg" >&2
  exit 1
}

if [ -z "$host" ] || [ -z "$database" ] || [ -z "$username" ] || [ -z "$password" ]; then
  fail ".env 의 DB 접속 정보가 비어 있습니다."
fi
if ! printf '%s' "$database" | grep -Eq '^[A-Za-z0-9_]+$'; then
  fail "DB 이름이 백업에 적합하지 않습니다."
fi

dump_bin="$(command -v mysqldump || command -v mariadb-dump || true)"
if [ -z "$dump_bin" ] && [ -x /usr/bin/mysqldump ]; then
  dump_bin=/usr/bin/mysqldump
fi
if [ -z "$dump_bin" ]; then
  fail "mysqldump 를 찾지 못했습니다."
fi

umask 077
cnf="$(mktemp "$DIR/.client.XXXXXX")"
sql="$(mktemp "$DIR/.dump.XXXXXX")"
errfile="$(mktemp "$DIR/.err.XXXXXX")"
partial=""
SUCCESS=0
cleanup() {
  rm -f "$cnf" "$sql" "$errfile"
  if [ "$SUCCESS" != 1 ] && [ -n "${partial}" ]; then
    rm -f "$partial"
  fi
}
trap cleanup EXIT

{
  printf '[client]\n'
  printf 'host=%s\n' "$host"
  printf 'port=%s\n' "$port"
  printf 'user=%s\n' "$username"
  printf 'password="%s"\n' "$(printf '%s' "$password" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"
} > "$cnf"
chmod 600 "$cnf"

dumped=0
option_sets=(
  "--single-transaction --quick --default-character-set=utf8mb4 --hex-blob --no-tablespaces --skip-lock-tables --column-statistics=0"
  "--single-transaction --quick --default-character-set=utf8mb4 --hex-blob --no-tablespaces --skip-lock-tables"
  "--single-transaction --quick --default-character-set=utf8mb4 --skip-lock-tables"
)
for opts in "${option_sets[@]}"; do
  : > "$sql"
  : > "$errfile"
  # shellcheck disable=SC2086
  if "$dump_bin" --defaults-extra-file="$cnf" $opts "$database" > "$sql" 2>"$errfile"; then
    dumped=1
    break
  fi
  if ! grep -Eiq 'unknown option|unknown variable|ambiguous option' "$errfile"; then
    break
  fi
done

if [ "$dumped" != 1 ]; then
  fail "$(tr '\n' ' ' < "$errfile")"
fi
if [ ! -s "$sql" ]; then
  fail "mysqldump 결과가 비어 있습니다."
fi

stamp="$(date '+%Y%m%d_%H%M%S')"
filename="${database}_${stamp}.sql.gz"
final="$DIR/$filename"
partial="$DIR/.${filename}.partial"
{
  printf '%s\n' "-- LabelUp DB backup" "-- created_at: $(date '+%Y-%m-%d %H:%M:%S') Asia/Seoul" "-- database: $database" "SET NAMES utf8mb4;" "SET FOREIGN_KEY_CHECKS=0;" "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';" "USE \`$database\`;"
  cat "$sql"
  printf '\n%s\n' "-- Backup completed"
} | gzip -c > "$partial"
chmod 600 "$partial"

gzip -t "$partial"
if ! gzip -dc "$partial" | grep -m1 -q 'CREATE TABLE'; then
  fail "백업 파일에 테이블이 없습니다."
fi
if ! gzip -dc "$partial" | tail -n 5 | grep -q 'Backup completed'; then
  fail "백업 파일이 끝까지 쓰이지 않았습니다."
fi

mv "$partial" "$final"
partial=""
chmod 600 "$final"
SUCCESS=1

removed=0
while IFS= read -r old; do
  [ -n "$old" ] || continue
  rm -f "$old"
  removed=$((removed + 1))
done < <(find "$DIR" -maxdepth 1 -type f -name "${database}_*.sql.gz" -mtime +"$keep" ! -path "$final")

bytes="$(wc -c < "$final" | tr -d ' ')"
write_status 1 "$engine" "$filename" "$bytes" "$removed" ""
echo "OK $filename $bytes $engine"
