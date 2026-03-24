#Requires -Version 5.1
<#
.SYNOPSIS
  Prints WordPress + AIO Page Builder capability state via WP-CLI (local install).

.PARAMETER WordPressRoot
  Absolute path to the directory that contains wp-load.php (not the plugin folder).

.EXAMPLE
  .\tools\diag-aio-caps.ps1 -WordPressRoot "C:\Users\Home\Local Sites\mysite\app\public"
#>
param(
	[Parameter( Mandatory = $true )]
	[string] $WordPressRoot
)

$WordPressRoot = $WordPressRoot.TrimEnd( '\', '/' )
$wpLoad        = Join-Path $WordPressRoot 'wp-load.php'
if ( -not ( Test-Path -LiteralPath $wpLoad ) ) {
	Write-Error ( "Not a WordPress root (missing wp-load.php): {0}" -f $WordPressRoot )
	exit 1
}

$wpBat = 'C:\wp-cli\wp.bat'
if ( -not ( Test-Path -LiteralPath $wpBat ) ) {
	$wpBat = 'wp'
}

function Invoke-Wp {
	param( [string[]] $Args )
	& $wpBat @Args
	if ( $LASTEXITCODE -ne 0 ) {
		exit $LASTEXITCODE
	}
}

Write-Host "=== WordPress ===" -ForegroundColor Cyan
Invoke-Wp @('--path', $WordPressRoot, 'core', 'version')
Write-Host ""
Write-Host "=== Users (id, login, roles) ===" -ForegroundColor Cyan
Invoke-Wp @('--path', $WordPressRoot, 'user', 'list', '--fields=ID,user_login,roles')
Write-Host ""
Write-Host "=== Administrator role: aio_* caps (sample) ===" -ForegroundColor Cyan
$aioCheck = @'
$r = get_role( "administrator" );
if ( ! $r ) { echo "ERROR: administrator role missing"; return; }
$need = array(
	"aio_view_logs",
	"aio_access_template_library",
	"aio_manage_section_templates",
	"aio_manage_settings",
);
foreach ( $need as $c ) {
	echo $c . ": " . ( $r->has_cap( $c ) ? "yes" : "no" ) . "\n";
}
echo "manage_options on role: " . ( $r->has_cap( "manage_options" ) ? "yes" : "no" ) . "\n";
'@
Invoke-Wp @('--path', $WordPressRoot, 'eval', $aioCheck.Trim())
Write-Host ""
Write-Host "=== Current user ID 1: has manage_options + aio_view_logs (if user 1 exists) ===" -ForegroundColor Cyan
$userCheck = @'
$u = get_userdata( 1 );
if ( ! $u ) { echo "No user ID 1"; return; }
echo "user_login: " . $u->user_login . "\n";
echo "manage_options: " . ( user_can( $u, "manage_options" ) ? "yes" : "no" ) . "\n";
echo "aio_view_logs: " . ( user_can( $u, "aio_view_logs" ) ? "yes" : "no" ) . "\n";
'@
Invoke-Wp @('--path', $WordPressRoot, 'eval', $userCheck.Trim())
Write-Host ""
Write-Host "Done." -ForegroundColor Green
