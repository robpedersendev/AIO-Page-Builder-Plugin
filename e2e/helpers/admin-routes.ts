/**
 * Seed admin.php?page= URLs for hub smoke tests and link crawling.
 * Keep aligned with `Admin_Route_Inventory::VISIBLE_HUB_PAGE_SLUGS` and `DASHBOARD_PAGE_SLUG` in PHP.
 */
export const AIO_ADMIN_SEED_PATHS: readonly string[] = [
	'/wp-admin/admin.php?page=aio-page-builder',
	'/wp-admin/admin.php?page=aio-page-builder-settings',
	'/wp-admin/admin.php?page=aio-page-builder-diagnostics',
	'/wp-admin/admin.php?page=aio-page-builder-onboarding',
	'/wp-admin/admin.php?page=aio-page-builder-ai-workspace',
	'/wp-admin/admin.php?page=aio-page-builder-crawler-sessions',
	'/wp-admin/admin.php?page=aio-page-builder-build-plans',
	'/wp-admin/admin.php?page=aio-page-builder-page-templates',
	'/wp-admin/admin.php?page=aio-page-builder-queue-logs',
	'/wp-admin/admin.php?page=aio-page-builder-industry-profile',
	'/wp-admin/admin.php?page=aio-page-builder-global-style-tokens',
] as const;

/**
 * Extra tab deep-links for link-crawler coverage (align with `Admin_Route_Inventory::HUB_PRIMARY_TABS_BY_PAGE` in PHP).
 */
export const AIO_ADMIN_DEEP_LINK_SEED_PATHS: readonly string[] = [
	'/wp-admin/admin.php?page=aio-page-builder-settings&aio_tab=privacy',
	'/wp-admin/admin.php?page=aio-page-builder-ai-workspace&aio_tab=providers',
	'/wp-admin/admin.php?page=aio-page-builder-build-plans&aio_tab=bp_analytics',
	'/wp-admin/admin.php?page=aio-page-builder-page-templates&aio_tab=compare',
	'/wp-admin/admin.php?page=aio-page-builder-industry-profile&aio_tab=reports&aio_subtab=health',
] as const;

/** Union of base hubs + deep links (wider crawler discovery without full admin tree walk). */
export const AIO_ADMIN_ALL_SEED_PATHS: readonly string[] = [
	...AIO_ADMIN_SEED_PATHS,
	...AIO_ADMIN_DEEP_LINK_SEED_PATHS,
] as const;
