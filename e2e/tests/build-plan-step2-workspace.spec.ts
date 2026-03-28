import { test, expect } from '@playwright/test';
import { expectNoAxeViolations } from '../helpers/a11y';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_TEST_IDS } from '../helpers/selectors';
import { waitForWpAdminReady } from '../helpers/wp-admin';

/**
 * When AIO_E2E_PLAN_ID is set, validates Step 2 shell + a11y. CI sets the env and globalSetup seeds e2e-step2-deny.
 */
test.describe( 'Build Plan Step 2 workspace (opt-in)', () => {
	test( 'workspace screen loads for plan id from env', async ( { page } ) => {
		const planId = process.env.AIO_E2E_PLAN_ID;
		test.skip( ! planId || planId === '', 'Set AIO_E2E_PLAN_ID to an existing plan internal_key for this test.' );

		await loginAsAdmin( page );
		const url = `/wp-admin/admin.php?page=aio-page-builder-build-plans&plan_id=${ encodeURIComponent( planId! ) }&step=2`;
		await page.goto( url, { waitUntil: 'domcontentloaded' } );
		await waitForWpAdminReady( page );
		await expect( page.locator( AIO_TEST_IDS.buildPlanWorkspaceScreen ) ).toBeVisible( { timeout: 30_000 } );
		await expectNoAxeViolations( page, 'Build Plan Step 2 workspace', {
			exclude: [ '#wpadminbar', '#adminmenumain', '#screen-meta', '#screen-meta-links' ],
		} );
	} );
} );

test.describe( 'Build Plan Step 2 row deny', () => {
	test.describe.configure( { retries: 0 } );

	test( 'GET deny link redirects with step2_row_deny_done', async ( { page } ) => {
		const planId = process.env.AIO_E2E_PLAN_ID;
		test.skip( ! planId || planId === '', 'Set AIO_E2E_PLAN_ID (CI: e2e-step2-deny after seed).' );

		await loginAsAdmin( page );
		const url = `/wp-admin/admin.php?page=aio-page-builder-build-plans&plan_id=${ encodeURIComponent( planId! ) }&step=2`;
		await page.goto( url, { waitUntil: 'domcontentloaded' } );
		await waitForWpAdminReady( page );
		await expect( page.locator( AIO_TEST_IDS.buildPlanWorkspaceScreen ) ).toBeVisible( { timeout: 30_000 } );

		const deny = page.locator( 'a.aio-row-action-deny' ).first();
		await expect( deny ).toBeVisible( { timeout: 15_000 } );
		await Promise.all( [
			page.waitForURL( /step2_row_deny_done=1/ ),
			deny.click(),
		] );
	} );
} );
