# * Runs WordPress Plugin Check against the repo plugin via Docker Compose (Windows).
# * Prerequisites: Docker Desktop, Docker Compose v2.
$ErrorActionPreference = "Stop"
$ScriptDir = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $ScriptDir "..\..")).Path
$PluginPath = (Resolve-Path (Join-Path $RepoRoot "plugin")).Path
$env:PLUGIN_SOURCE = $PluginPath

Set-Location $ScriptDir
Write-Host "PLUGIN_SOURCE=$PluginPath"

docker compose up -d db wordpress

Write-Host "Waiting for WordPress files and database..."
$ready = $false
for ($i = 0; $i -lt 45; $i++) {
    docker compose run --rm -T -u root wpcli wp core version --path=/var/www/html --allow-root 2>$null | Out-Null
    if ($LASTEXITCODE -eq 0) {
        $ready = $true
        break
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Write-Error "WordPress failed to become ready (wp core version)."
}

docker compose run --rm -T -u root wpcli wp core is-installed --path=/var/www/html --allow-root 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
    docker compose run --rm -T -u root wpcli wp core install `
        --url="http://localhost" `
        --title="Plugin Check Sandbox" `
        --admin_user="admin" `
        --admin_password="admin" `
        --admin_email="admin@example.test" `
        --path=/var/www/html `
        --skip-email `
        --allow-root
}

docker compose run --rm -T -u root wpcli wp plugin install plugin-check --activate --path=/var/www/html --allow-root

$OutDir = Join-Path $ScriptDir "output"
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$OutJson = Join-Path $OutDir "plugin-check-report.json"

docker compose run --rm -T -u root wpcli wp plugin check aio-page-builder `
    --path=/var/www/html `
    --require=/var/www/html/wp-content/plugins/plugin-check/cli.php `
    --format=json `
    --allow-root `
    2>$null | Set-Content -Path $OutJson -Encoding utf8

$checkExit = $LASTEXITCODE
Write-Host "Report written to $OutJson (wp exit code $checkExit)"
Write-Host "Summarize: php $ScriptDir\summarize-report.php $OutJson"
exit $checkExit
