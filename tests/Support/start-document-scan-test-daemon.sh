#!/usr/bin/env bash
set -euo pipefail
scan_test_root="${RUNNER_TEMP:-/tmp}/tfsf-document-scanner-ci"
mkdir -p "$scan_test_root/db"
chmod 700 "$scan_test_root"
# Harmless integration signature only; this is not a production antivirus database.
printf '%s\n' 'TFSF.Test.Document:0:*:544653462d5343414e4e45522d544553542d444554454354494f4e' > "$scan_test_root/db/test.ndb"
cat > "$scan_test_root/clamd.conf" <<EOF
Foreground no
LocalSocket $scan_test_root/clamd.sock
LocalSocketMode 600
DatabaseDirectory $scan_test_root/db
LogFile $scan_test_root/clamd.log
StreamMaxLength 12M
MaxFileSize 12M
MaxScanSize 24M
AlertExceedsMax yes
AlertEncrypted yes
User $(id -un)
EOF
clamd --config-file="$scan_test_root/clamd.conf"
printf '%s\n' 'Harmless scanner readiness check.' > "$scan_test_root/ready.txt"
for attempt in $(seq 1 20); do
    if clamdscan --config-file="$scan_test_root/clamd.conf" --stream --no-summary "$scan_test_root/ready.txt" >/dev/null 2>&1; then
        if [[ -n "${GITHUB_ENV:-}" ]]; then
            printf 'DOCUMENT_SCAN_INTEGRATION=1\nDOCUMENT_CLAMD_CONFIG=%s/clamd.conf\n' "$scan_test_root" >> "$GITHUB_ENV"
        fi
        printf 'Test scanner ready: %s/clamd.conf\n' "$scan_test_root"
        exit 0
    fi
    sleep 1
done
exit 1
