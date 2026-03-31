import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_ADMIN_DEEP_LINK_SEED_PATHS, AIO_ADMIN_SEED_PATHS } from '../helpers/admin-routes';
import { waitForWpAdminReady } from '../helpers/wp-admin';
import { AIO_TEST_IDS } from '../helpers/selectors';

test.describe( 'Admin hub matrix (GET smoke)', () => {
	for ( const path of AIO_ADMIN_SEED_PATHS ) {
		test( `loads ${ path }`, async ( { page } ) => {
			await loginAsAdmin( page );
			const response = await page.goto( path, { waitUntil: 'domcontentloaded' } );
			expect( response?.status(), `HTTP status for ${ path }` ).toBeLessThan( 400 );
			await waitForWpAdminReady( page );
			await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
			const bodyText = await page.locator( '#wpbody-content' ).innerText();
			expect( bodyText.toLowerCase() ).not.toContain( 'sorry, you are not allowed' );
			const isDashboard =
				path.includes( 'page=aio-page-builder' ) && ! path.includes( 'page=aio-page-builder-' );
			if ( isDashboard ) {
				await expect( page.locator( AIO_TEST_IDS.dashboardScreen ) ).toBeVisible();
			}
			if ( path.includes( 'build-plans' ) && ! path.includes( 'plan_id' ) ) {
				await expect( page.locator( AIO_TEST_IDS.buildPlansListScreen ) ).toBeVisible();
			}
		} );
	}

	for ( const path of AIO_ADMIN_DEEP_LINK_SEED_PATHS ) {
		test( `deep tab seed loads ${ path }`, async ( { page } ) => {
			await loginAsAdmin( page );
			const response = await page.goto( path, { waitUntil: 'domcontentloaded' } );
			expect( response?.status(), `HTTP status for ${ path }` ).toBeLessThan( 400 );
			await waitForWpAdminReady( page );
			await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
		} );
	}
} );
