import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_TEST_IDS } from '../helpers/selectors';

test.describe( 'AIO admin smoke', () => {
	test( 'dashboard screen exposes stable hook after login', async ( { page } ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/admin.php?page=aio-page-builder' );
		await expect( page.locator( AIO_TEST_IDS.dashboardScreen ) ).toBeVisible();
	} );

	test( 'build plans list exposes stable hook', async ( { page } ) => {
		await loginAsAdmin( page );
		await page.goto( '/wp-admin/admin.php?page=aio-page-builder-build-plans' );
		await expect( page.locator( AIO_TEST_IDS.buildPlansListScreen ) ).toBeVisible();
	} );
} );
