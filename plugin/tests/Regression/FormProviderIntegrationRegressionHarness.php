<?php
/**
 * Regression harness for provider-backed form integration (Prompt 238, spec §56.3, §56.8, §59.14).
 * Runs scenarios: shortcode build, registry validation, missing-provider, invalid-form-id, safe fallback.
 * Uses real Form_Provider_Registry; rendering/submission/migration/permission-denied documented for E2E.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Regression;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;

/**
 * Runs form-provider regression scenarios from fixtures; produces machine-readable and human-readable results.
 */
final class FormProviderIntegrationRegressionHarness {

	public const SCENARIO_RENDERING         = 'rendering';
	public const SCENARIO_SAVE_LOAD         = 'save_load';
	public const SCENARIO_MISSING_PROVIDER  = 'missing_provider';
	public const SCENARIO_STALE_FORM        = 'stale_form';
	public const SCENARIO_INVALID_FORM      = 'invalid_form';
	public const SCENARIO_MIGRATION_RESTORE = 'migration_restore';
	public const SCENARIO_PERMISSION_DENIED = 'permission_denied';

	/** @var string */
	private string $fixtures_base;

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $registry;

	public function __construct( string $fixtures_base = '', ?Form_Provider_Registry $registry = null ) {
		$this->fixtures_base = $fixtures_base !== '' ? rtrim( str_replace( '\\', '/', $fixtures_base ), '/' ) : dirname( __DIR__ ) . '/fixtures/form-provider-integration';
		$this->registry      = $registry ?? new Form_Provider_Registry();
	}

	/**
	 * Runs all fixture-based scenarios and returns result list.
	 *
	 * @return array<int, array{scenario_id: string, pass: bool, message: string, details: array}>
	 */
	public function run_all(): array {
		$results = array();
		$files   = glob( $this->fixtures_base . '/*.json' );
		if ( ! is_array( $files ) ) {
			return array(
				array(
					'scenario_id' => 'harness',
					'pass'        => false,
					'message'     => 'No fixtures path',
					'details'     => array(),
				),
			);
		}
		foreach ( $files as $path ) {
			$data = $this->load_json( $path );
			if ( ! is_array( $data ) ) {
				$results[] = array(
					'scenario_id' => basename( $path, '.json' ),
					'pass'        => false,
					'message'     => 'Invalid fixture',
					'details'     => array(),
				);
				continue;
			}
			$results[] = $this->run_one( $data );
		}
		return $results;
	}

	/**
	 * Runs a single scenario from fixture array.
	 *
	 * @param array<string, mixed> $fixture Scenario fixture (scenario_id, expected, section/page_template).
	 * @return array{scenario_id: string, pass: bool, message: string, details: array}
	 */
	public function run_one( array $fixture ): array {
		$scenario_id = (string) ( $fixture['scenario_id'] ?? 'unknown' );
		$expected    = $fixture['expected'] ?? array();
		$section     = $fixture['section'] ?? $fixture['page_template'] ?? array();
		$provider    = (string) ( $section['form_provider'] ?? '' );
		$form_id     = (string) ( $section['form_id'] ?? '' );

		$details                        = array();
		$provider_registered            = $this->registry->has_provider( $provider );
		$details['provider_registered'] = $provider_registered;

		$expect_registered = (bool) ( $expected['provider_registered'] ?? true );
		if ( $expect_registered !== $provider_registered ) {
			return array(
				'scenario_id' => $scenario_id,
				'pass'        => false,
				'message'     => 'Provider registration mismatch',
				'details'     => $details,
			);
		}

		$shortcode                   = $this->registry->build_shortcode( $provider, $form_id );
		$shortcode_builds            = $shortcode !== null;
		$details['shortcode_builds'] = $shortcode_builds;
		if ( $shortcode !== null ) {
			$details['shortcode'] = $shortcode;
		}

		$expect_shortcode = (bool) ( $expected['shortcode_builds'] ?? true );
		if ( $expect_shortcode !== $shortcode_builds ) {
			return array(
				'scenario_id' => $scenario_id,
				'pass'        => false,
				'message'     => 'Shortcode build expectation mismatch',
				'details'     => $details,
			);
		}

		if ( isset( $expected['shortcode_contains'] ) && $shortcode !== null ) {
			if ( strpos( $shortcode, (string) $expected['shortcode_contains'] ) === false ) {
				return array(
					'scenario_id' => $scenario_id,
					'pass'        => false,
					'message'     => 'Shortcode does not contain expected substring',
					'details'     => $details,
				);
			}
		}

		$form_id_valid            = $form_id !== '' && preg_match( '/^[a-zA-Z0-9_\-]+$/', $form_id );
		$details['form_id_valid'] = $form_id_valid;
		if ( isset( $expected['form_id_valid'] ) && (bool) $expected['form_id_valid'] !== $form_id_valid ) {
			return array(
				'scenario_id' => $scenario_id,
				'pass'        => false,
				'message'     => 'Form ID validity mismatch',
				'details'     => $details,
			);
		}

		return array(
			'scenario_id' => $scenario_id,
			'pass'        => true,
			'message'     => 'OK',
			'details'     => $details,
		);
	}

	/**
	 * Returns a summary suitable for report artifact (machine-readable).
	 *
	 * @param array<int, array{scenario_id: string, pass: bool, message: string, details: array}> $results Per-scenario run results.
	 * @return array{ran_at: string, total: int, passed: int, failed: int, results: array}
	 */
	public static function summary( array $results ): array {
		$passed = count(
			array_filter(
				$results,
				static function ( $r ) {
					return $r['pass'];
				}
			)
		);
		return array(
			'ran_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'total'   => count( $results ),
			'passed'  => $passed,
			'failed'  => count( $results ) - $passed,
			'results' => $results,
		);
	}

	/**
	 * @param string $path Path to JSON fixture file.
	 * @return array<string, mixed>|null
	 */
	private function load_json( string $path ): ?array {
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return null;
		}
		$raw = file_get_contents( $path );
		if ( $raw === false ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
