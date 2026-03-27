import { expect, type Page } from '@playwright/test';

/**
 * Waits for stable wp-admin shell after navigation (avoids racing on redirects or slow menus).
 */
export async function waitForWpAdminReady( page: Page ): Promise<void> {
	await expect( page.locator( '#wpbody-content' ) ).toBeVisible( { timeout: 30_000 } );
}
