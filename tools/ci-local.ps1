# * Local runner mirroring .github/workflows/ci.yml job "php" (Composer install + PHPCS + PHPStan + PHPUnit).
# * Optional: Plugin Check job (Docker) — same flow as CI plugin-check step (slow).
# * Prerequisites: PHP + Composer on PATH; for -IncludePluginCheck: Docker Desktop.
# * Usage (repo root):  powershell -NoProfile -ExecutionPolicy Bypass -File tools\ci-local.ps1
# * Usage (from plugin): composer run ci   (same PHP steps; no Plugin Check)
param(
    [switch] $SkipInstall,
    [switch] $IncludePluginCheck,
    [switch] $StrictPluginCheck
)

$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$PluginDir = Join-Path $RepoRoot 'plugin'
$ToolsDir = Join-Path $RepoRoot 'tools'
$PluginCheckDir = Join-Path $ToolsDir 'plugin-check'

if (-not (Test-Path $PluginDir)) {
    Write-Error "plugin/ not found at $PluginDir"
}

Push-Location $PluginDir
try {
    if (-not $SkipInstall) {
        Write-Host '=== composer install --prefer-dist --no-progress ===' -ForegroundColor Cyan
        composer install --prefer-dist --no-progress
        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
    }

    foreach ($step in @('phpcs', 'phpstan', 'phpunit')) {
        Write-Host "=== composer run $step ===" -ForegroundColor Cyan
        composer run $step
        if ($LASTEXITCODE -ne 0) {
            exit $LASTEXITCODE
        }
    }
}
finally {
    Pop-Location
}

if ($IncludePluginCheck) {
    if (-not (Test-Path (Join-Path $PluginCheckDir 'run-plugin-check.ps1'))) {
        Write-Error "run-plugin-check.ps1 not found under $PluginCheckDir"
    }

    Write-Host '=== Plugin Check (Docker + wp plugin check) ===' -ForegroundColor Cyan
    $pcScript = Join-Path $PluginCheckDir 'run-plugin-check.ps1'
    $proc = Start-Process -FilePath 'powershell.exe' -ArgumentList @(
        '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $pcScript
    ) -Wait -PassThru -NoNewWindow
    $pcExit = $proc.ExitCode
    Write-Host "run-plugin-check.ps1 exit code: $pcExit"

    $report = Join-Path $PluginCheckDir 'output\plugin-check-report.json'
    if (-not (Test-Path $report)) {
        Write-Error "Plugin Check report missing: $report"
    }

    Write-Host '=== summarize-report.php ===' -ForegroundColor Cyan
    php (Join-Path $PluginCheckDir 'summarize-report.php') $report
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }

    Write-Host '=== exit-if-errors.php ===' -ForegroundColor Cyan
    php (Join-Path $PluginCheckDir 'exit-if-errors.php') $report
    $gate = $LASTEXITCODE

    if ($StrictPluginCheck -and $gate -ne 0) {
        exit $gate
    }
    if (-not $StrictPluginCheck -and $gate -ne 0) {
        Write-Warning "Plugin Check gate exit $gate (ERROR-level findings). CI uses continue-on-error: true. Re-run with -StrictPluginCheck to fail this script."
    }
}

Write-Host 'Local CI (php job) completed successfully.' -ForegroundColor Green
exit 0
