# * Runs WordPress Plugin Check against the repo plugin via Docker Compose (Windows).
# * Prerequisites: Docker Desktop, Docker Compose v2.
$ErrorActionPreference = "Stop"

# * Docker Compose writes routine status to stderr; PowerShell 7+ with Stop treats that as fatal.
if ($PSVersionTable.PSVersion.Major -ge 7) {
    $PSNativeCommandUseErrorActionPreference = $false
}

$ScriptDir = $PSScriptRoot
$RepoRoot = (Resolve-Path (Join-Path $ScriptDir "..\..")).Path
$PluginPath = (Resolve-Path (Join-Path $RepoRoot "plugin")).Path
$env:PLUGIN_SOURCE = $PluginPath

function Invoke-DockerComposeQuiet {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$ComposeArgs
    )
    $prior = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    try {
        & docker compose @ComposeArgs 2>&1 | Out-Null
        return [int]$LASTEXITCODE
    }
    finally {
        $ErrorActionPreference = $prior
    }
}

Set-Location $ScriptDir
Write-Host "PLUGIN_SOURCE=$PluginPath"

$upExit = Invoke-DockerComposeQuiet -ComposeArgs @("up", "-d", "db", "wordpress")
if ($upExit -ne 0) {
    Write-Error "docker compose up failed with exit code $upExit"
}

Write-Host "Waiting for WordPress files and database..."
$ready = $false
for ($i = 0; $i -lt 90; $i++) {
    $v = Invoke-DockerComposeQuiet -ComposeArgs @("run", "--rm", "-T", "-u", "root", "wpcli", "wp", "core", "version", "--path=/var/www/html", "--allow-root")
    if ($v -eq 0) {
        $ready = $true
        break
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Write-Error "WordPress failed to become ready (wp core version)."
}

$in = Invoke-DockerComposeQuiet -ComposeArgs @("run", "--rm", "-T", "-u", "root", "wpcli", "wp", "core", "is-installed", "--path=/var/www/html", "--allow-root")
if ($in -ne 0) {
    $null = Invoke-DockerComposeQuiet -ComposeArgs @(
        "run", "--rm", "-T", "-u", "root", "wpcli", "wp", "core", "install",
        "--url=http://localhost",
        "--title=Plugin Check Sandbox",
        "--admin_user=admin",
        "--admin_password=admin",
        "--admin_email=admin@example.test",
        "--path=/var/www/html",
        "--skip-email",
        "--allow-root"
    )
}

$null = Invoke-DockerComposeQuiet -ComposeArgs @("run", "--rm", "-T", "-u", "root", "wpcli", "wp", "plugin", "install", "plugin-check", "--activate", "--path=/var/www/html", "--allow-root")

$OutDir = Join-Path $ScriptDir "output"
New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$OutJson = Join-Path $OutDir "plugin-check-report.json"

$ErrorActionPreference = "Continue"
docker compose run --rm -T -u root wpcli wp plugin check aio-page-builder `
    --path=/var/www/html `
    --require=/var/www/html/wp-content/plugins/plugin-check/cli.php `
    --format=json `
    --exclude-directories=tests,legacy,phpstan-fixtures,docs `
    --exclude-files=phpstan-bootstrap.php,phpstan-wordpress-overrides.stub.php,phpcs.xml.dist,phpstan.neon.dist,phpunit.xml.dist,.wp-env.json,.phpunit.result.cache `
    --allow-root `
    2>$null | Set-Content -Path $OutJson -Encoding utf8
$checkExit = $LASTEXITCODE
$ErrorActionPreference = "Stop"

Write-Host "Report written to $OutJson (wp exit code $checkExit)"
Write-Host "Summarize: php $ScriptDir\summarize-report.php $OutJson"
exit $checkExit
