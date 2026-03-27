import { defineConfig } from '@playwright/test';

const baseURL = process.env.WP_BASE_URL ?? 'http://localhost:8888';
const isCi = Boolean( process.env.CI );

export default defineConfig({
	testDir: './tests',
	fullyParallel: false,
	retries: isCi ? 1 : 0,
	use: {
		baseURL,
		trace: isCi ? 'retain-on-failure' : 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	expect: {
		timeout: 20_000,
	},
	timeout: 60_000,
	reporter: [
		[ 'list' ],
		[ 'html', { open: 'never', outputFolder: 'playwright-report' } ],
	],
	outputDir: 'test-results',
});
