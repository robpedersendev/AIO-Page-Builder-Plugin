<?php
/**
 * Authoritative admin route inventory (menu slugs, hub tabs, named router routes).
 *
 * Sync with {@see \AIOPageBuilder\Admin\Admin_Menu_Hub_Renderer::register_submenus()},
 * {@see \AIOPageBuilder\Admin\Admin_Menu::register()}, and {@see Admin_Router::register_defaults()}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\AdminRouting;

/**
 * Machine-readable admin IA inventory for QA tooling and drift detection.
 */
final class Admin_Route_Inventory {

	public const PARENT_MENU_SLUG = 'aio-page-builder';

	/**
	 * Top-level menu item (dashboard) uses the same slug as the parent.
	 */
	public const DASHBOARD_PAGE_SLUG = 'aio-page-builder';

	/**
	 * Visible hub roots registered in {@see Admin_Menu_Hub_Renderer::register_submenus()}.
	 *
	 * @var list<string>
	 */
	public const VISIBLE_HUB_PAGE_SLUGS = array(
		'aio-page-builder-settings',
		'aio-page-builder-diagnostics',
		'aio-page-builder-onboarding',
		'aio-page-builder-ai-workspace',
		'aio-page-builder-crawler-sessions',
		'aio-page-builder-build-plans',
		'aio-page-builder-page-templates',
		'aio-page-builder-queue-logs',
		'aio-page-builder-industry-profile',
		'aio-page-builder-global-style-tokens',
	);

	/**
	 * Primary aio_tab keys per hub page slug (empty array if the screen is not tabbed via {@see \AIOPageBuilder\Admin\Admin_Screen_Hub}).
	 *
	 * @var array<string, list<string>>
	 */
	public const HUB_PRIMARY_TABS_BY_PAGE = array(
		'aio-page-builder-settings'            => array( 'general', 'privacy', 'import_export' ),
		'aio-page-builder-diagnostics'         => array( 'overview', 'acf', 'form_provider' ),
		'aio-page-builder-onboarding'          => array( 'onboarding', 'snapshots' ),
		'aio-page-builder-ai-workspace'        => array( 'providers', 'ai_runs', 'experiments' ),
		'aio-page-builder-crawler-sessions'    => array( 'sessions', 'comparison' ),
		'aio-page-builder-build-plans'         => array( 'build_plans', 'bp_analytics', 'template_analytics' ),
		'aio-page-builder-page-templates'      => array( 'section_templates', 'page_templates', 'compositions', 'compare', 'template_lab' ),
		'aio-page-builder-queue-logs'          => array( 'queue', 'triage', 'post_release' ),
		'aio-page-builder-industry-profile'    => array(
			'profile',
			'style',
			'import',
			'repair',
			'overrides',
			'author',
			'reports',
			'comparisons',
		),
		'aio-page-builder-global-style-tokens' => array( 'tokens', 'overrides' ),
	);

	/**
	 * Industry hub: aio_subtab keys when aio_tab=reports (see {@see Admin_Menu_Hub_Renderer::render_industry_hub()}).
	 *
	 * @var list<string>
	 */
	public const INDUSTRY_REPORT_SUBTABS = array(
		'health',
		'stale',
		'drift',
		'maturity',
		'future_industry',
		'future_subtype',
		'scaffold',
		'pack_family',
	);

	/**
	 * Industry hub: aio_subtab keys when aio_tab=comparisons.
	 *
	 * @var list<string>
	 */
	public const INDUSTRY_COMPARISON_SUBTABS = array(
		'subtype',
		'bundle',
		'goal',
		'style_layer',
	);

	/**
	 * Union of every `public const SLUG` / `HUB_PAGE_SLUG` under `plugin/src/` (see {@see Admin_Page_Slug_Scanner}).
	 * Update this list when adding a screen; CI fails on mismatch.
	 *
	 * @var list<string>
	 */
	public const ALL_DISCOVERED_ADMIN_PAGE_SLUGS = array(
		'aio-page-builder',
		'aio-page-builder-acf-diagnostics',
		'aio-page-builder-ai-providers',
		'aio-page-builder-ai-runs',
		'aio-page-builder-ai-workspace',
		'aio-page-builder-build-plan-analytics',
		'aio-page-builder-build-plans',
		'aio-page-builder-compositions',
		'aio-page-builder-crawler-comparison',
		'aio-page-builder-crawler-sessions',
		'aio-page-builder-diagnostics',
		'aio-page-builder-documentation-detail',
		'aio-page-builder-export-restore',
		'aio-page-builder-form-provider-health',
		'aio-page-builder-global-component-overrides',
		'aio-page-builder-global-style-tokens',
		'aio-page-builder-industry-author-dashboard',
		'aio-page-builder-industry-bundle-comparison',
		'aio-page-builder-industry-bundle-import-preview',
		'aio-page-builder-industry-conversion-goal-comparison',
		'aio-page-builder-industry-drift-report',
		'aio-page-builder-industry-future-readiness',
		'aio-page-builder-industry-future-subtype-readiness',
		'aio-page-builder-industry-guided-repair',
		'aio-page-builder-industry-health-report',
		'aio-page-builder-industry-maturity-delta-report',
		'aio-page-builder-industry-overrides',
		'aio-page-builder-industry-pack-family-comparison',
		'aio-page-builder-industry-profile',
		'aio-page-builder-industry-scaffold-promotion-readiness-report',
		'aio-page-builder-industry-stale-content-report',
		'aio-page-builder-industry-style-layer-comparison',
		'aio-page-builder-industry-style-preset',
		'aio-page-builder-industry-subtype-comparison',
		'aio-page-builder-onboarding',
		'aio-page-builder-page-template-detail',
		'aio-page-builder-page-templates',
		'aio-page-builder-post-release-health',
		'aio-page-builder-privacy-reporting',
		'aio-page-builder-profile-snapshots',
		'aio-page-builder-prompt-experiments',
		'aio-page-builder-queue-logs',
		'aio-page-builder-section-template-detail',
		'aio-page-builder-section-templates',
		'aio-page-builder-settings',
		'aio-page-builder-support-triage',
		'aio-page-builder-template-analytics',
		'aio-page-builder-template-compare',
		'aio-page-builder-template-lab',
	);

	/**
	 * Hidden detail shells (empty menu title) registered in {@see Admin_Menu_Hub_Renderer::register_hidden_detail_pages()}.
	 *
	 * @var list<string>
	 */
	public const HIDDEN_DETAIL_PAGE_SLUGS = array(
		'aio-page-builder-page-template-detail',
		'aio-page-builder-section-template-detail',
		'aio-page-builder-documentation-detail',
	);

	/**
	 * Legacy submenu slugs that redirect into hubs (removed from submenu but still registered for access checks).
	 * Source: {@see Admin_Menu_Hub_Renderer::register_legacy_routes()}.
	 *
	 * @var list<string>
	 */
	public const LEGACY_REDIRECT_PAGE_SLUGS = array(
		'aio-page-builder-privacy-reporting',
		'aio-page-builder-acf-diagnostics',
		'aio-page-builder-form-provider-health',
		'aio-page-builder-profile-snapshots',
		'aio-page-builder-ai-runs',
		'aio-page-builder-ai-providers',
		'aio-page-builder-prompt-experiments',
		'aio-page-builder-export-restore',
		'aio-page-builder-crawler-comparison',
		'aio-page-builder-build-plan-analytics',
		'aio-page-builder-template-analytics',
		'aio-page-builder-section-templates',
		'aio-page-builder-compositions',
		'aio-page-builder-template-compare',
		'aio-page-builder-support-triage',
		'aio-page-builder-post-release-health',
		'aio-page-builder-global-component-overrides',
		'aio-page-builder-industry-overrides',
		'aio-page-builder-industry-author-dashboard',
		'aio-page-builder-industry-health-report',
		'aio-page-builder-industry-stale-content-report',
		'aio-page-builder-industry-drift-report',
		'aio-page-builder-industry-maturity-delta-report',
		'aio-page-builder-industry-future-readiness',
		'aio-page-builder-industry-future-subtype-readiness',
		'aio-page-builder-industry-scaffold-promotion-readiness-report',
		'aio-page-builder-industry-pack-family-comparison',
		'aio-page-builder-industry-guided-repair',
		'aio-page-builder-industry-subtype-comparison',
		'aio-page-builder-industry-bundle-comparison',
		'aio-page-builder-industry-conversion-goal-comparison',
		'aio-page-builder-industry-bundle-import-preview',
		'aio-page-builder-industry-style-preset',
		'aio-page-builder-industry-style-layer-comparison',
	);

	/**
	 * Named routes in {@see Admin_Router::register_defaults()} (keys passed to {@see Admin_Router::url()}).
	 *
	 * @var list<string>
	 */
	public const ADMIN_ROUTER_ROUTE_NAMES = array(
		'dashboard',
		'section_templates_directory',
		'page_templates_directory',
		'section_template_detail',
		'page_template_detail',
		'template_compare',
		'documentation_detail',
		'build_plan_workspace',
	);

	/**
	 * Expected slugs from {@see self::ALL_DISCOVERED_ADMIN_PAGE_SLUGS} (single source for scanner parity).
	 *
	 * @return list<string>
	 */
	public static function expected_discovered_page_slugs(): array {
		return self::ALL_DISCOVERED_ADMIN_PAGE_SLUGS;
	}

	/**
	 * @return list<string>
	 */
	public static function all_registered_menu_slugs_union(): array {
		$slugs = array_merge(
			array( self::PARENT_MENU_SLUG ),
			self::VISIBLE_HUB_PAGE_SLUGS,
			self::HIDDEN_DETAIL_PAGE_SLUGS,
			self::LEGACY_REDIRECT_PAGE_SLUGS
		);
		$slugs = array_values( array_unique( $slugs ) );
		sort( $slugs, SORT_STRING );
		return $slugs;
	}
}
