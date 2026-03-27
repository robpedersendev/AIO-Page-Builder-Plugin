/** Stable data-testid hooks (plugin admin); prefer roles/labels in tests when sufficient. */
export const AIO_TEST_IDS = {
	dashboardScreen: '[data-testid="aio-dashboard-screen"]',
	buildPlansListScreen: '[data-testid="aio-build-plans-list-screen"]',
	buildPlanWorkspaceScreen: '[data-testid="aio-build-plan-workspace-screen"]',
} as const;
