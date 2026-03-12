<?php
/**
 * Registers bootstrap-level service providers in a stable order.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Blueprints_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Assignment_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Crawler_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Compatibility_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Diagnostics_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ACF_Registration_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Rendering_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Admin_Router_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Capability_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Config_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Execution_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\ExportRestore_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Diagnostics_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Object_Registration_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Provider_Base_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Provider_Drivers_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Prompt_Pack_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Runs_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\AI_Validation_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Onboarding_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Registries_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Rollback_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Reporting_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Build_Plan_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Repositories_Provider;
use AIOPageBuilder\Infrastructure\Container\Providers\Storage_Services_Provider;

/**
 * Loads and runs only bootstrap-level providers. Domain providers are registered in later prompts.
 * Registration order is explicit and stable.
 */
final class Module_Registrar {

	/** @var Service_Container */
	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Registers all bootstrap providers in order. Call once from Plugin::run().
	 * Config_Provider registers config and settings (see global-options-schema.md).
	 * Diagnostics_Provider registers logger and diagnostics helper (see diagnostics-contract.md).
	 * Crawler_Provider registers snapshot, discovery, fetch, classification, extraction, and recrawl comparison (spec §24.12–24.17). Crawler admin screens (Sessions, Comparison) are registered in Admin_Menu and documented in crawler-admin-screen-contract.md.
	 * Queue & Logs screen (Queue_Logs_Screen) and reporting monitoring are registered in Admin_Menu; state from Logs_Monitoring_State_Builder and Reporting_Health_Summary_Builder (spec §49.11).
	 * Admin menu and screen routing are registered separately in Plugin::register_admin_menu().
	 *
	 * @return void
	 */
	public function register_bootstrap(): void {
		$providers = array(
			new Config_Provider(),
			new Diagnostics_Provider(),
			new Crawler_Provider(),
			new Admin_Router_Provider(),
			new Capability_Provider(),
			new Object_Registration_Provider(),
			new Repositories_Provider(),
			new Build_Plan_Provider(),
			new Execution_Provider(),
			new Rollback_Provider(),
			new Reporting_Provider(),
			new ACF_Blueprints_Provider(),
			new ACF_Registration_Provider(),
			new ACF_Assignment_Provider(),
			new ACF_Compatibility_Provider(),
			new ACF_Diagnostics_Provider(),
			new Rendering_Provider(),
			new Registries_Provider(),
			new AI_Validation_Provider(),
			new AI_Provider_Base_Provider(),
			new AI_Provider_Drivers_Provider(),
			new AI_Prompt_Pack_Provider(),
			new AI_Runs_Provider(),
			new Storage_Services_Provider(),
			new ExportRestore_Provider(),
			new Onboarding_Provider(),
		);
		foreach ( $providers as $provider ) {
			$provider->register( $this->container );
		}
	}

	/** @return Service_Container */
	public function container(): Service_Container {
		return $this->container;
	}
}
