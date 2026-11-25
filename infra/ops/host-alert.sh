#!/usr/bin/env bash
# Host alarm (PROJECT1_PLAN.md §9.6, S0.5): CPU load, memory and disk against thresholds; posts to ALERT_WEBHOOK_URL
# when a check fails, and once more when it recovers. Run from cron every 5 minutes. `--test` sends a test alert.
#
# Environment: ALERT_WEBHOOK_URL (required; a Slack/Teams/Google Chat style incoming webhook taking {"text": ...}),
# LOAD_PER_CPU_MAX (default 2.0), MEM_AVAILABLE_MIN_PCT (default 10), DISK_USED_MAX_PCT (default 85),
# DISK_PATHS (default "/"), STATE_DIR (default /var/lib/dentlegal-alert).
set -euo pipefail

: "${ALERT_WEBHOOK_URL:?ALERT_WEBHOOK_URL is required}"
LOAD_PER_CPU_MAX="${LOAD_PER_CPU_MAX:-2.0}"
MEM_AVAILABLE_MIN_PCT="${MEM_AVAILABLE_MIN_PCT:-10}"
DISK_USED_MAX_PCT="${DISK_USED_MAX_PCT:-85}"
DISK_PATHS="${DISK_PATHS:-/}"
STATE_DIR="${STATE_DIR:-/var/lib/dentlegal-alert}"
HOST="$(hostname)"

send() {
  local text="$1"
  curl -fsS -m 10 -H 'Content-Type: application/json' \
    -d "$(python3 -c 'import json,sys; print(json.dumps({"text": sys.argv[1]}))' "$text")" "$ALERT_WEBHOOK_URL" >/dev/null
}

if [[ "${1:-}" == "--test" ]]; then
  send "DentLegal test alert from $HOST: alerts reach this channel."
  echo "test alert sent"
  exit 0
fi

mkdir -p "$STATE_DIR"
problems=()

cpus="$(nproc)"
load="$(cut -d' ' -f1 /proc/loadavg)"
if python3 -c "import sys; sys.exit(0 if float(sys.argv[1]) / int(sys.argv[2]) > float(sys.argv[3]) else 1)" "$load" "$cpus" "$LOAD_PER_CPU_MAX"; then
  problems+=("load $load on $cpus CPUs")
fi

mem_total="$(awk '/^MemTotal:/ {print $2}' /proc/meminfo)"
mem_avail="$(awk '/^MemAvailable:/ {print $2}' /proc/meminfo)"
mem_pct=$(( mem_avail * 100 / mem_total ))
(( mem_pct >= MEM_AVAILABLE_MIN_PCT )) || problems+=("memory available ${mem_pct}%")

for p in $DISK_PATHS; do
  used="$(df -P "$p" | awk 'NR==2 {gsub("%", "", $5); print $5}')"
  (( used <= DISK_USED_MAX_PCT )) || problems+=("disk $p ${used}% used")
done

state="$STATE_DIR/active"
if (( ${#problems[@]} )); then
  summary="$(IFS='; '; echo "${problems[*]}")"
  # Alert on a new or changed problem only, so a long incident sends one message, not one every 5 minutes.
  if [[ "$(cat "$state" 2>/dev/null || true)" != "$summary" ]]; then
    send "DentLegal alert on $HOST: $summary"
    echo "$summary" > "$state"
  fi
  echo "ALERT: $summary"
elif [[ -s "$state" ]]; then
  send "DentLegal recovered on $HOST (was: $(cat "$state"))"
  rm -f "$state"
  echo "recovered"
else
  echo "ok"
fi
