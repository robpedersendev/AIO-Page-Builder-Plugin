/**
 * Local wp-env E2E: enables plan seed + stable plan id (same as CI). Requires `npx wp-env start` first.
 */
import { spawnSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const repoRoot = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const env = {
	...process.env,
	AIO_E2E_SEED: '1',
	AIO_E2E_PLAN_ID: 'e2e-step2-deny',
};

const result = spawnSync(
	'npx',
	[ 'playwright', 'test', '--config=e2e/playwright.config.ts', ...process.argv.slice( 2 ) ],
	{
		cwd: repoRoot,
		stdio: 'inherit',
		env,
		shell: process.platform === 'win32',
	}
);

process.exit( result.status === null ? 1 : result.status );
