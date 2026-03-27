import type { Page } from '@playwright/test';
import { WP_ADMIN_PASSWORD, WP_ADMIN_USER } from './env';

/**
 * Logs into wp-admin using core login form. No external APIs.
 * Uses Playwright `use.baseURL` for relative URLs. On failure, use trace/screenshot from config.
 */
export async function loginAsAdmin(
	page: Page,
	creds: { user?: string; password?: string } = {}
): Promise<void> {
	const user = creds.user ?? WP_ADMIN_USER;
	const password = creds.password ?? WP_ADMIN_PASSWORD;
	await page.goto( '/wp-login.php', { waitUntil: 'domcontentloaded' } );
	await page.locator( '#user_login' ).fill( user );
	await page.locator( '#user_pass' ).fill( password );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /wp-admin/, { timeout: 30_000 } );
}
