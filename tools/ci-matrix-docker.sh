#!/usr/bin/env bash
# * Same as ci-matrix-docker.ps1: run CI Composer steps in Docker for PHP 8.1–8.3.
# * Usage: ./tools/ci-matrix-docker.sh   (from repo root; chmod +x if needed)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN="${ROOT}/plugin"
VERSIONS=(8.1 8.2 8.3)

bash_cmd='set -euo pipefail; export DEBIAN_FRONTEND=noninteractive; apt-get update -qq && apt-get install -y -qq git unzip libzip-dev >/dev/null && (docker-php-ext-install zip >/dev/null 2>&1 || true) && (test -x /usr/local/bin/composer || curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer) && cd /app && composer install --prefer-dist --no-progress && composer run phpcs && composer run phpstan && composer run phpunit'

failed=()
for ver in "${VERSIONS[@]}"; do
	echo "=== Docker matrix: php:${ver}-cli ==="
	img="php:${ver}-cli"
	docker pull "${img}" >/dev/null
	if ! docker run --rm -v "${PLUGIN}:/app" -w /app "${img}" bash -lc "${bash_cmd}"; then
		failed+=("${ver}")
	fi
done

if ((${#failed[@]} > 0)); then
	echo "Matrix failed for: ${failed[*]}" >&2
	exit 1
fi
echo "Docker PHP matrix completed successfully."
