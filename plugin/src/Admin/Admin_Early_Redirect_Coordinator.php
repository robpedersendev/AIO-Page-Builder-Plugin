<?php
/**
 * Dispatches admin.php state-changing requests before any admin HTML so wp_safe_redirect() succeeds.
 * Screens must not handle POST/GET redirects inside render() callbacks (headers already sent → blank or broken redirects).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Bundle_Import_Preview_Screen;
use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Component_Override_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Global_Style_Token_Settings_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Single admin_init priority-0 entry for early redirects.
 */
final class Admin_Early_Redirect_Coordinator {

	/**
	 * Runs registered early handlers until one redirects or none apply.
	 *
	 * @param Service_Container $container Plugin container from {@see \AIOPageBuilder\Bootstrap\Plugin::run()}.
	 * @return void
	 */
	public static function dispatch( Service_Container $container ): void {
		if ( ! \is_admin() ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}
		$page = \sanitize_key( (string) \wp_unslash( $_GET['page'] ) );
		if ( $page === '' ) {
			return;
		}

		self::maybe_onboarding_post( $container, $page );
		self::maybe_ai_workspace_post( $container, $page );
		self::maybe_prompt_experiments( $container, $page );
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			self::maybe_ai_workspace_tab_canonical_redirect( $page );
		}
		self::maybe_global_styling_post( $container, $page );
		self::maybe_crawler_post( $container, $page );
		self::maybe_template_compare_get( $container, $page );
		self::maybe_industry_bundle_preview_get( $container, $page );
		self::maybe_section_template_detail_post( $container, $page );
		self::maybe_page_template_detail_post( $container, $page );
		self::maybe_build_plan_workspace( $container, $page );
	}

	/**
	 * When `aio_tab` requests a tab the user cannot access, redirect to the resolved tab so URL matches content.
	 * Resolves invalid aio_tab to the first tab the user may open (order: providers, ai_runs, experiments).
	 *
	 * @param string $page Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_ai_workspace_tab_canonical_redirect( string $page ): void {
		if ( $page !== AI_Runs_Screen::HUB_PAGE_SLUG ) {
			return;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::ACCESS_AI_WORKSPACE ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET-only canonical tab redirect; no mutation.
		if ( ! isset( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) ) {
			return;
		}
		$requested = \sanitize_key( (string) \wp_unslash( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) );
		if ( $requested === '' ) {
			return;
		}
		$tabs = array();
		foreach ( Admin_Screen_Hub::ai_workspace_tab_caps() as $key => $cap ) {
			$tabs[ $key ] = array( 'cap' => $cap );
		}
		$default = Admin_Screen_Hub::first_accessible_tab( 'providers', $tabs );
		$tab     = Admin_Screen_Hub::current_tab( $default, array_keys( $tabs ) );
		if ( ! isset( $tabs[ $tab ] ) || ! Capabilities::current_user_can_for_route( $tabs[ $tab ]['cap'] ) ) {
			$tab = $default;
		}
		if ( $requested === $tab ) {
			return;
		}
		$extra = array();
		if ( $tab === 'ai_runs' && isset( $_GET['run_id'] ) ) {
			$rid = \sanitize_text_field( \wp_unslash( (string) $_GET['run_id'] ) );
			if ( $rid !== '' ) {
				$extra['run_id'] = $rid;
			}
		}
		if ( isset( $_GET[ Admin_Screen_Hub::QUERY_SUBTAB ] ) ) {
			$sub = \sanitize_key( (string) \wp_unslash( $_GET[ Admin_Screen_Hub::QUERY_SUBTAB ] ) );
			if ( $sub !== '' ) {
				$extra[ Admin_Screen_Hub::QUERY_SUBTAB ] = $sub;
			}
		}
		if ( isset( $_GET[ AI_Runs_Screen::QUERY_ONBOARDING_PLAN ] )
			&& \sanitize_key( (string) \wp_unslash( (string) $_GET[ AI_Runs_Screen::QUERY_ONBOARDING_PLAN ] ) ) === AI_Runs_Screen::ONBOARDING_PLAN_SUCCESS_VALUE ) {
			$extra[ AI_Runs_Screen::QUERY_ONBOARDING_PLAN ] = AI_Runs_Screen::ONBOARDING_PLAN_SUCCESS_VALUE;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		\wp_safe_redirect( Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, $tab, $extra ) );
		exit;
	}

	private static function maybe_onboarding_post( Service_Container $container, string $page ): void {
		if ( $page !== Onboarding_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in Onboarding_Screen::get_post_redirect_url().
		if ( ! isset( $_POST[ Onboarding_Screen::NONCE_ACTION ] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! Capabilities::current_user_can_for_route( Capabilities::RUN_ONBOARDING ) ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::ADMIN_EARLY_REDIRECT_DEBUG,
				'onboarding_post_skipped_missing_capability user_id=' . (string) \get_current_user_id()
			);
			return;
		}
		$screen   = new Onboarding_Screen( $container );
		$redirect = $screen->get_post_redirect_url();
		if ( $redirect !== null ) {
			\wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_ai_workspace_post( Service_Container $container, string $page ): void {
		if ( $page !== AI_Runs_Screen::HUB_PAGE_SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonces verified in screen handlers.
		$action = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		if ( $action === 'aio_pb_test_ai_provider_connection'
			|| $action === 'aio_pb_update_ai_provider_credential'
			|| $action === 'aio_pb_save_spend_cap' ) {
			$screen = new \AIOPageBuilder\Admin\Screens\AI\AI_Providers_Screen( $container );
			$url    = $screen->get_post_redirect_url();
			if ( $url !== null ) {
				\wp_safe_redirect( $url );
				exit;
			}
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonces verified in Prompt_Experiments_Screen::get_post_redirect_url().
		if ( isset( $_POST['aio_save_experiment'] ) ) {
			$exp = new \AIOPageBuilder\Admin\Screens\AI\Prompt_Experiments_Screen( $container );
			$url = $exp->get_post_redirect_url();
			if ( $url !== null ) {
				\wp_safe_redirect( $url );
				exit;
			}
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_prompt_experiments( Service_Container $container, string $page ): void {
		if ( $page !== AI_Runs_Screen::HUB_PAGE_SLUG ) {
			return;
		}
		$tab = isset( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) ) : '';
		if ( $tab !== 'experiments' ) {
			return;
		}
		$del = isset( $_GET['delete'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['delete'] ) ) : '';
		if ( $del === '' || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}
		$exp = new \AIOPageBuilder\Admin\Screens\AI\Prompt_Experiments_Screen( $container );
		$url = $exp->get_delete_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_global_styling_post( Service_Container $container, string $page ): void {
		if ( $page !== Global_Style_Token_Settings_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		$tokens = new Global_Style_Token_Settings_Screen( $container );
		$url    = $tokens->get_post_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
		$over = new Global_Component_Override_Settings_Screen( $container );
		$url  = $over->get_post_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_crawler_post( Service_Container $container, string $page ): void {
		if ( $page !== Crawler_Sessions_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		$crawler = new Crawler_Sessions_Screen( $container );
		$url     = $crawler->get_post_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_template_compare_get( Service_Container $container, string $page ): void {
		if ( $page !== Template_Library_Hub_Urls::HUB_PAGE_SLUG ) {
			return;
		}
		$tab = isset( $_GET[ Template_Library_Hub_Urls::QUERY_TAB ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Library_Hub_Urls::QUERY_TAB ] ) ) : '';
		if ( $tab !== Template_Library_Hub_Urls::TAB_COMPARE ) {
			return;
		}
		$compare = new Template_Compare_Screen( $container );
		$url     = $compare->get_compare_get_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_industry_bundle_preview_get( Service_Container $container, string $page ): void {
		if ( $page !== Industry_Profile_Settings_Screen::SLUG ) {
			return;
		}
		$tab = isset( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Admin_Screen_Hub::QUERY_TAB ] ) ) : '';
		if ( $tab !== 'import' ) {
			return;
		}
		$preview = new Industry_Bundle_Import_Preview_Screen( $container );
		$url     = $preview->get_clear_preview_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_section_template_detail_post( Service_Container $container, string $page ): void {
		if ( $page !== Section_Template_Detail_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		$detail = new Section_Template_Detail_Screen( $container );
		$url    = $detail->get_entity_style_post_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_page_template_detail_post( Service_Container $container, string $page ): void {
		if ( $page !== Page_Template_Detail_Screen::SLUG ) {
			return;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}
		$detail = new Page_Template_Detail_Screen( $container );
		$url    = $detail->get_entity_style_post_redirect_url();
		if ( $url !== null ) {
			\wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param Service_Container $container Container.
	 * @param string            $page      Sanitized `page` query value.
	 * @return void
	 */
	private static function maybe_build_plan_workspace( Service_Container $container, string $page ): void {
		if ( $page !== Build_Plans_Screen::SLUG ) {
			return;
		}
		$plan_id = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		if ( $plan_id === '' && isset( $_GET['id'] ) ) {
			$plan_id = \sanitize_text_field( \wp_unslash( (string) $_GET['id'] ) );
		}
		if ( $plan_id === '' ) {
			return;
		}
		$workspace = new Build_Plan_Workspace_Screen( $container );
		$workspace->dispatch_early_request_handlers( $plan_id );
	}
}
