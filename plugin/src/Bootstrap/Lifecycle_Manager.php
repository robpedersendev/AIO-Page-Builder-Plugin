<?php
/**
 * Lifecycle orchestration: activation, deactivation, and uninstall phases.
 * Defines hook order, blocking-failure behavior, and extension points. No destructive uninstall.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/../Infrastructure/Config/Dependency_Requirements.php';
require_once __DIR__ . '/../Infrastructure/Config/Capabilities.php';
require_once __DIR__ . '/../Infrastructure/Config/Option_Names.php';
require_once __DIR__ . '/../Infrastructure/Config/Versions.php';
require_once __DIR__ . '/../Infrastructure/Settings/Settings_Service.php';
require_once __DIR__ . '/../Domain/Storage/Migrations/Migration_Contract.php';
require_once __DIR__ . '/../Domain/Storage/Migrations/Schema_Version_Tracker.php';
require_once __DIR__ . '/../Domain/Storage/Tables/Table_Names.php';
require_once __DIR__ . '/../Domain/Storage/Tables/Table_Schema_Definitions.php';
require_once __DIR__ . '/../Domain/Storage/Tables/DbDelta_Runner.php';
require_once __DIR__ . '/../Domain/Storage/Tables/Table_Installer.php';
require_once __DIR__ . '/Environment_Validator.php';
require_once __DIR__ . '/Capability_Registrar.php';
require_once __DIR__ . '/../Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once __DIR__ . '/../Domain/Reporting/Contracts/Reporting_Payload_Schema.php';
require_once __DIR__ . '/../Domain/Reporting/Install/Install_Notification_Result.php';
require_once __DIR__ . '/../Domain/Reporting/Install/Install_Notification_Transport_Interface.php';
require_once __DIR__ . '/../Domain/Reporting/Install/Wp_Mail_Install_Transport.php';
require_once __DIR__ . '/../Domain/Reporting/Install/Install_Notification_Service.php';
require_once __DIR__ . '/../Domain/Reporting/Heartbeat/Heartbeat_Scheduler.php';
require_once __DIR__ . '/../Domain/Storage/Objects/Object_Type_Keys.php';
require_once __DIR__ . '/../Domain/ExportRestore/Uninstall/Uninstall_Cleanup_Service.php';

/**
 * Result status for a lifecycle phase or overall run.
 */
final class Lifecycle_Result {

	public const STATUS_SUCCESS         = 'success';
	public const STATUS_WARNING         = 'warning';
	public const STATUS_BLOCKING_FAILURE = 'blocking_failure';

	/** @var string One of STATUS_* */
	public string $status;

	/** @var string Human-readable message */
	public string $message;

	/** @var string|null Phase key that produced this result */
	public ?string $phase;

	/** @var array<string, mixed> Additional details */
	public array $details;

	public function __construct( string $status, string $message = '', ?string $phase = null, array $details = array() ) {
		$this->status  = $status;
		$this->message = $message;
		$this->phase   = $phase;
		$this->details = $details;
	}

	public function is_blocking(): bool {
		return $this->status === self::STATUS_BLOCKING_FAILURE;
	}

	/** @return array{status: string, message: string, phase: string|null, details: array} */
	public function to_array(): array {
		return array(
			'status'  => $this->status,
			'message' => $this->message,
			'phase'   => $this->phase,
			'details' => $this->details,
		);
	}
}

/**
 * Orchestrates activation, deactivation, and uninstall in named phases.
 * Placeholder implementations only; later prompts own real logic.
 */
final class Lifecycle_Manager {

	/**
	 * Runs activation phases in order. Stops on first blocking failure and returns that result.
	 * Extension point: add phases to the run order or delegate to domain services.
	 *
	 * @return Lifecycle_Result
	 */
	public function activate(): Lifecycle_Result {
		$phases = array(
			'validate_environment',
			'check_dependencies',
			'init_options',
			'check_tables_schema',
			'register_capabilities',
			'register_schedules',
			'seed_form_templates',
			'seed_section_expansion_pack',
			'install_notification_eligibility',
			'first_run_redirect_readiness',
		);
		foreach ( $phases as $phase ) {
			$result = $this->run_activation_phase( $phase );
			if ( $result->is_blocking() ) {
				return $result;
			}
		}
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', null, array( 'phases_run' => $phases ) );
	}

	/**
	 * Runs deactivation phases in order. Non-destructive; no content or option deletion.
	 *
	 * @return Lifecycle_Result
	 */
	public function deactivate(): Lifecycle_Result {
		$phases = array(
			'unschedule',
			'teardown_runtime',
		);
		foreach ( $phases as $phase ) {
			$this->run_deactivation_phase( $phase );
		}
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', null, array( 'phases_run' => $phases ) );
	}

	/**
	 * Uninstall orchestration. Currently no-op; preserves export-before-cleanup pathway.
	 * Called from uninstall.php only when WP_UNINSTALL_PLUGIN is defined. No deletion here.
	 *
	 * @return Lifecycle_Result
	 */
	public function uninstall(): Lifecycle_Result {
		$phases = array(
			'export_reminder_integration',
			'cleanup_plugin_data',
		);
		foreach ( $phases as $phase ) {
			$this->run_uninstall_phase( $phase );
		}
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', null, array( 'phases_run' => $phases ) );
	}

	/**
	 * Dispatches a single activation phase. Extension point: override or filter phase behavior.
	 *
	 * @param string $phase Phase key.
	 * @return Lifecycle_Result
	 */
	private function run_activation_phase( string $phase ): Lifecycle_Result {
		switch ( $phase ) {
			case 'validate_environment':
				return $this->validate_environment();
			case 'check_dependencies':
				return $this->check_dependencies();
			case 'init_options':
				return $this->init_options();
			case 'check_tables_schema':
				return $this->check_tables_schema();
			case 'register_capabilities':
				return $this->register_capabilities();
			case 'register_schedules':
				return $this->register_schedules();
			case 'install_notification_eligibility':
				return $this->install_notification_eligibility();
			case 'first_run_redirect_readiness':
				return $this->first_run_redirect_readiness();
			case 'seed_form_templates':
				return $this->seed_form_templates();
			case 'seed_section_expansion_pack':
				return $this->seed_section_expansion_pack();
			default:
				return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', $phase );
		}
	}

	private function run_deactivation_phase( string $phase ): void {
		switch ( $phase ) {
			case 'unschedule':
				$this->unschedule();
				break;
			case 'teardown_runtime':
				$this->teardown_runtime();
				break;
			default:
				break;
		}
	}

	private function run_uninstall_phase( string $phase ): void {
		switch ( $phase ) {
			case 'export_reminder_integration':
				$this->export_reminder_integration();
				break;
			case 'cleanup_plugin_data':
				$this->cleanup_plugin_data();
				break;
			default:
				break;
		}
	}

	// ----- Activation phase placeholders (spec §53.1, §53.2) -----

	private function validate_environment(): Lifecycle_Result {
		$validator = new Environment_Validator();
		$validator->validate();
		return $validator->to_lifecycle_result( 'validate_environment' );
	}

	private function check_dependencies(): Lifecycle_Result {
		// Required/optional dependency checks are run in validate_environment via Environment_Validator.
		// This phase remains for future dependency logic that runs after environment (e.g. version handshakes).
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'check_dependencies' );
	}

	private function init_options(): Lifecycle_Result {
		// Placeholder: no option writes in this prompt. Later prompt owns option initialization.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'init_options' );
	}

	private function check_tables_schema(): Lifecycle_Result {
		$wpdb = isset( $GLOBALS['wpdb'] ) && $GLOBALS['wpdb'] instanceof \wpdb ? $GLOBALS['wpdb'] : null;
		if ( ! $wpdb ) {
			return new Lifecycle_Result(
				Lifecycle_Result::STATUS_BLOCKING_FAILURE,
				__( 'Database unavailable. Table verification skipped.', 'aio-page-builder' ),
				'check_tables_schema',
				array()
			);
		}
		$settings  = new \AIOPageBuilder\Infrastructure\Settings\Settings_Service();
		$tracker   = new \AIOPageBuilder\Domain\Storage\Migrations\Schema_Version_Tracker( $settings );
		$db_delta  = new \AIOPageBuilder\Domain\Storage\Tables\DbDelta_Runner();
		$installer = new \AIOPageBuilder\Domain\Storage\Tables\Table_Installer( $wpdb, $db_delta, $tracker );
		$result    = $installer->install_or_upgrade();
		if ( ! $result['success'] ) {
			return new Lifecycle_Result(
				Lifecycle_Result::STATUS_BLOCKING_FAILURE,
				\esc_html( $result['message'] ?: __( 'Custom table installation or upgrade failed.', 'aio-page-builder' ) ),
				'check_tables_schema',
				array( 'failed_table' => $result['failed_table'] )
			);
		}
		foreach ( \AIOPageBuilder\Infrastructure\Config\Versions::version_keys() as $key ) {
			if ( $key === 'plugin' ) {
				continue;
			}
			if ( $tracker->is_installed_version_future( $key ) ) {
				return new Lifecycle_Result(
					Lifecycle_Result::STATUS_BLOCKING_FAILURE,
					__( 'Unsupported schema: installed version is newer than this plugin supports. Upgrade the plugin or contact support.', 'aio-page-builder' ),
					'check_tables_schema',
					array( 'version_key' => $key )
				);
			}
		}
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'check_tables_schema' );
	}

	private function register_capabilities(): Lifecycle_Result {
		Capability_Registrar::register();
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'register_capabilities' );
	}

	private function register_schedules(): Lifecycle_Result {
		\AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler::schedule();
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'register_schedules' );
	}

	private function install_notification_eligibility(): Lifecycle_Result {
		$service = new \AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Service();
		$result  = $service->maybe_send( __( 'all ready', 'aio-page-builder' ) );
		// * Never block activation on delivery failure (spec §46.10).
		return new Lifecycle_Result(
			Lifecycle_Result::STATUS_SUCCESS,
			'',
			'install_notification_eligibility',
			array( 'install_notification_result' => $result->to_array() )
		);
	}

	private function first_run_redirect_readiness(): Lifecycle_Result {
		// Placeholder: no redirect. Later prompt owns first-time setup / redirect to Dashboard or Onboarding.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'first_run_redirect_readiness' );
	}

	/**
	 * Seeds form section and request page template (form-provider-integration-contract).
	 * Registers CPTs if needed, then saves definitions. Non-blocking on failure.
	 *
	 * @return Lifecycle_Result
	 */
	private function seed_form_templates(): Lifecycle_Result {
		$base = __DIR__ . '/../Domain';
		$infra = __DIR__ . '/../Infrastructure';
		require_once $base . '/Storage/Repositories/Repository_Interface.php';
		require_once $base . '/Storage/Objects/Object_Status_Families.php';
		require_once $base . '/Storage/Repositories/Abstract_CPT_Repository.php';
		require_once $base . '/Storage/Objects/Object_Type_Keys.php';
		require_once $base . '/Registries/Section/Section_Schema.php';
		require_once $base . '/Registries/PageTemplate/Page_Template_Schema.php';
		require_once $base . '/Storage/Repositories/Section_Template_Repository.php';
		require_once $base . '/Storage/Repositories/Page_Template_Repository.php';
		require_once $infra . '/Config/Capabilities.php';
		require_once $base . '/Storage/Objects/Post_Type_Registrar.php';
		require_once $base . '/FormProvider/Form_Integration_Definitions.php';
		require_once $base . '/FormProvider/Form_Template_Seeder.php';

		$registrar = new \AIOPageBuilder\Domain\Storage\Objects\Post_Type_Registrar();
		$registrar->register();

		$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$page_repo    = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		$result       = \AIOPageBuilder\Domain\FormProvider\Form_Template_Seeder::run( $section_repo, $page_repo );

		return new Lifecycle_Result(
			Lifecycle_Result::STATUS_SUCCESS,
			'',
			'seed_form_templates',
			array(
				'form_templates_seeded' => $result['success'],
				'section_id'            => $result['section_id'],
				'page_id'               => $result['page_id'],
				'errors'                => $result['errors'],
			)
		);
	}

	/**
	 * Seeds the curated section expansion pack (Prompt 122). Runs after form templates; CPTs already registered.
	 *
	 * @return Lifecycle_Result
	 */
	private function seed_section_expansion_pack(): Lifecycle_Result {
		$base  = __DIR__ . '/../Domain';
		require_once $base . '/Storage/Repositories/Repository_Interface.php';
		require_once $base . '/Storage/Objects/Object_Status_Families.php';
		require_once $base . '/Storage/Repositories/Abstract_CPT_Repository.php';
		require_once $base . '/Storage/Objects/Object_Type_Keys.php';
		require_once $base . '/Registries/Section/Section_Schema.php';
		require_once $base . '/Storage/Repositories/Section_Template_Repository.php';
		require_once $base . '/Registries/Section/ExpansionPack/Section_Expansion_Pack_Definitions.php';
		require_once $base . '/Registries/Section/ExpansionPack/Section_Expansion_Pack_Seeder.php';

		$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$result      = \AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder::run( $section_repo );

		return new Lifecycle_Result(
			Lifecycle_Result::STATUS_SUCCESS,
			'',
			'seed_section_expansion_pack',
			array(
				'expansion_pack_seeded' => $result['success'],
				'section_ids'          => $result['section_ids'],
				'errors'               => $result['errors'],
			)
		);
	}

	// ----- Deactivation phase placeholders -----

	private function unschedule(): void {
		\AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Scheduler::unschedule();
	}

	private function teardown_runtime(): void {
		// Placeholder: flush caches, stop workers. No deletion of options or content.
	}

	// ----- Uninstall phase placeholders (spec §52.11, §9.12) -----

	private function export_reminder_integration(): void {
		// * Export choices are presented on the admin Uninstall screen (Uninstall_Export_Prompt_Service).
		// This phase runs only from uninstall.php (no UI); no reminder here. Built pages remain.
	}

	private function cleanup_plugin_data(): void {
		$cleanup = new \AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service();
		$cleanup->cleanup_plugin_owned_data( \AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service::SCOPE_FULL );
	}
}
