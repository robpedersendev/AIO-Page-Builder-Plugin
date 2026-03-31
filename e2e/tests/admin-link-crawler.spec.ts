import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_ADMIN_ALL_SEED_PATHS } from '../helpers/admin-routes';
import { waitForWpAdminReady } from '../helpers/wp-admin';

/**
 * Collects same-origin admin.php links from #wpbody-content and GETs each once (deduped).
 * Skips javascript:, mailto:, #fragments-only, and admin-post.php (POST endpoints).
 */
test( 'admin link crawler from seed hubs', async ( { page, baseURL } ) => {
	test.setTimeout( 120_000 );
	await loginAsAdmin( page );
	const origin = new URL( baseURL ?? 'http://localhost:8888' ).origin;
	const toVisit = new Set<string>();

	for ( const path of AIO_ADMIN_ALL_SEED_PATHS ) {
		await page.goto( path, { waitUntil: 'domcontentloaded' } );
		await waitForWpAdminReady( page );
		const hrefs = await page.locator( '#wpbody-content a[href]' ).evaluateAll( ( els ) =>
			els.map( ( a ) => ( a as HTMLAnchorElement ).getAttribute( 'href' ) ?? '' )
		);
		for ( const href of hrefs ) {
			if ( ! href || href.startsWith( '#' ) || href.toLowerCase().startsWith( 'javascript:' ) ) {
				continue;
			}
			if ( href.includes( 'admin-post.php' ) ) {
				continue;
			}
			let absolute: string;
			try {
				absolute = new URL( href, baseURL ?? 'http://localhost:8888' ).href;
			} catch {
				continue;
			}
			if ( ! absolute.startsWith( origin ) ) {
				continue;
			}
			if ( ! absolute.includes( 'admin.php' ) ) {
				continue;
			}
			toVisit.add( absolute );
		}
	}

	expect( toVisit.size, 'expected at least one in-body admin link from seeds' ).toBeGreaterThan( 0 );

	for ( const url of toVisit ) {
		const response = await page.goto( url, { waitUntil: 'domcontentloaded' } );
		const status = response?.status() ?? 0;
		expect(
			status === 200 || status === 302 || status === 301,
			`Unexpected status ${ String( status ) } for ${ url }`
		).toBe( true );
	}
} );
