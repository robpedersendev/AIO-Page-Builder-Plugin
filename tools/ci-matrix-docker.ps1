# * Runs Composer CI steps from .github/workflows/ci.yml inside Docker for PHP 8.1, 8.2, 8.3 (matrix parity).
# * First run per image: installs git, unzip, libzip, Composer; then composer install + phpcs + phpstan + phpunit.
# * Usage (repo root):  powershell -NoProfile -ExecutionPolicy Bypass -File tools\ci-matrix-docker.ps1
# * Requires: Docker Desktop.
param(
    [string[]] $PhpVersions = @('8.1', '8.2', '8.3')
)

$ErrorActionPreference = 'Stop'
$PluginDir = (Resolve-Path (Join-Path (Join-Path $PSScriptRoot '..') 'plugin')).Path

# * Single-line for docker + PowerShell argument passing (multiline -lc is fragile on Windows).
$bashCmd = 'set -euo pipefail; export DEBIAN_FRONTEND=noninteractive; apt-get update -qq && apt-get install -y -qq git unzip libzip-dev >/dev/null && (docker-php-ext-install zip >/dev/null 2>&1 || true) && (test -x /usr/local/bin/composer || curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer) && cd /app && composer install --prefer-dist --no-progress && composer run phpcs && composer run phpstan && composer run phpunit'

$failed = [System.Collections.Generic.List[string]]::new()
foreach ($ver in $PhpVersions) {
    $image = "php:${ver}-cli"
    Write-Host "=== Docker matrix: $image ===" -ForegroundColor Cyan
    docker pull $image 2>$null | Out-Null
    docker run --rm -v "${PluginDir}:/app" -w /app $image bash -lc $bashCmd
    if ($LASTEXITCODE -ne 0) {
        [void] $failed.Add($ver)
    }
}

if ($failed.Count -gt 0) {
    Write-Error ("Matrix failed for PHP version(s): " + ($failed -join ', '))
}
Write-Host 'Docker PHP matrix completed successfully.' -ForegroundColor Green
