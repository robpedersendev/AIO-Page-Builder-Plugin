import { test, expect } from '@playwright/test';
import { expectNoAxeViolations } from '../helpers/a11y';
import { loginAsAdmin } from '../helpers/auth';
import { AIO_TEST_IDS } from '../helpers/selectors';
import { waitForWpAdminReady } from '../helpers/wp-admin';

/**
 * When AIO_E2E_PLAN_ID is set (e.g. locally after creating a plan), validates Step 2 shell + a11y.
 * Full deny-button flow requires a plan with Step 2 pending rows; keep opt-in to avoid flaky CI.
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
