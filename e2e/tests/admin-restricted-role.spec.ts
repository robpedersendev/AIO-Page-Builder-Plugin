import { test, expect } from '@playwright/test';
import { tryLoginAsE2ESubscriber } from '../helpers/auth';
import { AIO_ADMIN_ALL_SEED_PATHS } from '../helpers/admin-routes';

/**
 * Subscriber must not load AIO plugin hubs (capability / menu registration).
 * Requires wp-env + user aio_e2e_subscriber (see global-setup.ts). Set AIO_E2E_SKIP_SUBSCRIBER=1 to skip creation only; tests still need the user to exist.
 */
test.describe( 'Subscriber denied AIO admin routes', () => {
	test.beforeEach( async ( { page } ) => {
		const ok = await tryLoginAsE2ESubscriber( page );
		test.skip( ! ok, 'aio_e2e_subscriber login failed — start wp-env so global-setup can create the user' );
	} );

	for ( const path of AIO_ADMIN_ALL_SEED_PATHS ) {
		test( `subscriber cannot use ${ path }`, async ( { page } ) => {
			const response = await page.goto( path, { waitUntil: 'domcontentloaded' } );
			const status = response?.status() ?? 0;
			await page.locator( 'body' ).waitFor( { state: 'visible', timeout: 20_000 } );
			const text = await page.locator( 'body' ).innerText();
			const denied =
				status === 403 ||
				/you are not allowed|sorry, you are not allowed|do not have permission|cannot access this page|permission to access|cheatin|has been disabled/i.test(
					text
				);
			expect( denied, `expected access denial for subscriber at ${ path } (status ${ String( status ) })` ).toBe( true );
		} );
	}
} );
