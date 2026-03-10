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
require_once __DIR__ . '/Environment_Validator.php';
require_once __DIR__ . '/Capability_Registrar.php';

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
		// Placeholder: no table creation. Later prompt owns schema/table checks.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'check_tables_schema' );
	}

	private function register_capabilities(): Lifecycle_Result {
		Capability_Registrar::register();
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'register_capabilities' );
	}

	private function register_schedules(): Lifecycle_Result {
		// Placeholder: no WP-Cron registration yet. Later prompt owns recurring schedule registration.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'register_schedules' );
	}

	private function install_notification_eligibility(): Lifecycle_Result {
		// Placeholder: no reporting send. Later prompt owns install notification eligibility.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'install_notification_eligibility' );
	}

	private function first_run_redirect_readiness(): Lifecycle_Result {
		// Placeholder: no redirect. Later prompt owns first-time setup / redirect to Dashboard or Onboarding.
		return new Lifecycle_Result( Lifecycle_Result::STATUS_SUCCESS, '', 'first_run_redirect_readiness' );
	}

	// ----- Deactivation phase placeholders -----

	private function unschedule(): void {
		// Placeholder: later prompt unschedules cron jobs and queue workers.
	}

	private function teardown_runtime(): void {
		// Placeholder: flush caches, stop workers. No deletion of options or content.
	}

	// ----- Uninstall phase placeholders (spec §52.11, §9.12) -----

	private function export_reminder_integration(): void {
		// Placeholder: export-before-uninstall prompt and choices (full backup, settings only, skip, cancel).
		// Uninstall screen must state that built pages will remain. Non-destructive.
	}

	private function cleanup_plugin_data(): void {
		// Placeholder: no deletion in this prompt. Later prompt removes only plugin-owned operational data
		// after export pathway exists; must not delete built pages or user content.
	}
}
