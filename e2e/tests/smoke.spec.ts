import { test, expect } from '@playwright/test';
import { expectNoAxeViolations } from '../helpers/a11y';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_TEST_IDS } from '../helpers/selectors';
import { waitForWpAdminReady } from '../helpers/wp-admin';

test.describe( 'AIO admin smoke', () => {
	test( 'dashboard screen exposes stable hook after login', async ( { page } ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/admin.php?page=aio-page-builder', { waitUntil: 'domcontentloaded' } );
		await waitForWpAdminReady( page );
		await expect( page.locator( AIO_TEST_IDS.dashboardScreen ) ).toBeVisible();
		await expectNoAxeViolations(
			page,
			'Dashboard (aio-page-builder)',
			{
				// * Core WP admin chrome (menu, notices) is outside plugin control; scan the main work area.
				exclude: [ '#wpadminbar', '#adminmenumain', '#screen-meta', '#screen-meta-links' ],
			}
		);
	} );

	test( 'build plans list exposes stable hook', async ( { page } ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/admin.php?page=aio-page-builder-build-plans', { waitUntil: 'domcontentloaded' } );
		await waitForWpAdminReady( page );
		await expect( page.locator( AIO_TEST_IDS.buildPlansListScreen ) ).toBeVisible();
		await expectNoAxeViolations(
			page,
			'Build plans list',
			{
				exclude: [ '#wpadminbar', '#adminmenumain', '#screen-meta', '#screen-meta-links' ],
			}
		);
	} );
} );
