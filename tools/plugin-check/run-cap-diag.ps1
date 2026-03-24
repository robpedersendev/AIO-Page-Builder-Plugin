#Requires -Version 5.1
<#
.SYNOPSIS
  Runs docker-wp-eval-caps.php against the plugin-check WordPress stack (docker compose).

.EXAMPLE
  .\run-cap-diag.ps1
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$evalFile = Join-Path $PSScriptRoot 'docker-wp-eval-caps.php'
if ( -not ( Test-Path -LiteralPath $evalFile ) ) {
	Write-Error "Missing docker-wp-eval-caps.php"
	exit 1
}

Write-Host "=== plugin-check Docker: capability snapshot ===" -ForegroundColor Cyan
docker compose run --rm `
	-v "${evalFile}:/tmp/docker-wp-eval-caps.php" `
	wpcli wp eval-file /tmp/docker-wp-eval-caps.php --path=/var/www/html

if ( $LASTEXITCODE -ne 0 ) {
	exit $LASTEXITCODE
}
Write-Host ""
Write-Host "Note: aio_cap_count is 0 until aio-page-builder activates (requires ACF Pro) and Capability_Registrar runs." -ForegroundColor DarkYellow
