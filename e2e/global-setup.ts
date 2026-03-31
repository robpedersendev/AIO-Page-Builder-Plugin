import { execSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { basename, join } from 'node:path';

/**
 * Directory name under wp-content/plugins/ where wp-env mounts the first plugin from .wp-env.json.
 * Override when your mapping differs (directory name only, e.g. `plugin`).
 */
function resolveWpPluginDirName( repoRoot: string ): string {
	const override = process.env.AIO_E2E_WP_PLUGIN_DIR;
	if ( override !== undefined && override !== '' ) {
		const normalized = override.replace( /\\/g, '/' ).replace( /^\/+|\/+$/g, '' );
		const parts = normalized.split( '/' );
		return parts[ parts.length - 1 ] ?? 'plugin';
	}
	const cfgPath = join( repoRoot, '.wp-env.json' );
	if ( existsSync( cfgPath ) ) {
		try {
			const raw = readFileSync( cfgPath, 'utf8' );
			const cfg = JSON.parse( raw ) as { plugins?: string[] };
			const first = cfg.plugins?.[0];
			if ( typeof first === 'string' && first.length > 0 ) {
				return basename( first.replace( /\\/g, '/' ) );
			}
		} catch {
			// * Invalid JSON or unreadable; fall through to default.
		}
	}
	return 'plugin';
}

/**
 * Resets the E2E Build Plan before Playwright when CI=1 or AIO_E2E_SEED=1 (requires wp-env).
 */
/**
 * Ensures Playwright restricted-role user exists (idempotent). Skipped when AIO_E2E_SKIP_SUBSCRIBER=1 or wp-env is unavailable.
 */
function ensureE2ESubscriberUser( repoRoot: string ): void {
	if ( process.env.AIO_E2E_SKIP_SUBSCRIBER === '1' ) {
		return;
	}
	try {
		execSync( 'npx wp-env run cli wp user get aio_e2e_subscriber --field=ID', {
			cwd: repoRoot,
			stdio: 'pipe',
			env: { ...process.env },
		} );
	} catch {
		try {
			execSync(
				'npx wp-env run cli wp user create aio_e2e_subscriber aioe2e_subscriber@example.test --role=subscriber --user_pass=password',
				{
					cwd: repoRoot,
					stdio: 'inherit',
					env: { ...process.env },
				}
			);
		} catch {
			// * wp-env not running: restricted-role tests skip after failed login probe.
			console.warn(
				'[global-setup] Could not create aio_e2e_subscriber (start wp-env for restricted-role E2E).'
			);
		}
	}
}

export default function globalSetup(): void {
	const repoRoot = process.cwd();
	ensureE2ESubscriberUser( repoRoot );
	if ( process.env.AIO_E2E_SKIP_SEED === '1' ) {
		return;
	}
	if ( process.env.CI !== 'true' && process.env.AIO_E2E_SEED !== '1' ) {
		return;
	}
	const pluginDir = resolveWpPluginDirName( repoRoot );
	const evalPath = `wp-content/plugins/${ pluginDir }/tools/e2e-seed-build-plan.php`;
	execSync( `npx wp-env run cli wp eval-file ${ evalPath }`, {
		cwd: repoRoot,
		stdio: 'inherit',
		env: { ...process.env },
	} );
}
