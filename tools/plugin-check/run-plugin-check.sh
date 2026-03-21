#!/usr/bin/env bash
# * Runs WordPress Plugin Check against the repo plugin via Docker Compose.
# * Prerequisites: Docker, Docker Compose v2.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
export PLUGIN_SOURCE="${PLUGIN_SOURCE:-${REPO_ROOT}/plugin}"

cd "${SCRIPT_DIR}"

echo "PLUGIN_SOURCE=${PLUGIN_SOURCE}"

docker compose up -d db wordpress

echo "Waiting for WordPress files and database..."
for _ in $(seq 1 45); do
	if docker compose run --rm -T -u root wpcli wp core version --path=/var/www/html --allow-root >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

if ! docker compose run --rm -T -u root wpcli wp core version --path=/var/www/html --allow-root >/dev/null 2>&1; then
	echo "WordPress failed to become ready (wp core version)." >&2
	exit 1
fi

if ! docker compose run --rm -T -u root wpcli wp core is-installed --path=/var/www/html --allow-root >/dev/null 2>&1; then
	docker compose run --rm -T -u root wpcli wp core install \
		--url="http://localhost" \
		--title="Plugin Check Sandbox" \
		--admin_user="admin" \
		--admin_password="admin" \
		--admin_email="admin@example.test" \
		--path=/var/www/html \
		--skip-email \
		--allow-root
fi

docker compose run --rm -T -u root wpcli wp plugin install plugin-check --activate --path=/var/www/html --allow-root

OUT_JSON="${SCRIPT_DIR}/output/plugin-check-report.json"
mkdir -p "${SCRIPT_DIR}/output"
set +e
docker compose run --rm -T -u root wpcli wp plugin check aio-page-builder \
	--path=/var/www/html \
	--require=/var/www/html/wp-content/plugins/plugin-check/cli.php \
	--format=json \
	--allow-root \
	2>/dev/null >"${OUT_JSON}"
CHECK_EXIT=$?
set -e

echo "Report written to ${OUT_JSON} (wp exit code ${CHECK_EXIT})"
echo "Summarize: php ${SCRIPT_DIR}/summarize-report.php ${OUT_JSON}"
exit "${CHECK_EXIT}"
