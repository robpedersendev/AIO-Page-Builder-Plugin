#!/usr/bin/env bash
# * Local runner mirroring .github/workflows/ci.yml job "php" (Composer install + PHPCS + PHPStan + PHPUnit).
# * Optional Plugin Check: INCLUDE_PLUGIN_CHECK=1 STRICT_PLUGIN_CHECK=1 ./ci-local.sh
# * Prerequisites: PHP + Composer on PATH; for Plugin Check: Docker Compose v2.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN="${ROOT}/plugin"
PC="${ROOT}/tools/plugin-check"

cd "${PLUGIN}"

if [[ "${SKIP_INSTALL:-0}" != "1" ]]; then
	echo "=== composer install --prefer-dist --no-progress ==="
	composer install --prefer-dist --no-progress
fi

for step in phpcs phpstan phpunit; do
	echo "=== composer run ${step} ==="
	composer run "${step}"
done

if [[ "${INCLUDE_PLUGIN_CHECK:-0}" == "1" ]]; then
	echo "=== Plugin Check (Docker) ==="
	( cd "${PC}" && ./run-plugin-check.sh )
	php "${PC}/summarize-report.php" "${PC}/output/plugin-check-report.json"
	set +e
	php "${PC}/exit-if-errors.php" "${PC}/output/plugin-check-report.json"
	gate=$?
	set -e
	if [[ "${STRICT_PLUGIN_CHECK:-0}" == "1" ]] && [[ "${gate}" -ne 0 ]]; then
		exit "${gate}"
	fi
	if [[ "${gate}" -ne 0 ]]; then
		echo "WARN: Plugin Check gate exit ${gate}. CI uses continue-on-error. Set STRICT_PLUGIN_CHECK=1 to fail." >&2
	fi
fi

echo "Local CI (php job) completed successfully."
exit 0
