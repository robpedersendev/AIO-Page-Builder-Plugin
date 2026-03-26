<?php
/**
 * Example persisted AI run metadata and artifact summary payloads (spec §29). Contract shape for storage and review.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';

/**
 * Example payloads for acceptance gate and documentation. No pseudocode; real shapes.
 */
final class AI_Run_Example_Payloads_Test extends TestCase {

	/** Example persisted AI run metadata payload (stored in _aio_run_metadata). */
	public const EXAMPLE_RUN_METADATA = array(
		'run_id'          => 'aio-run-550e8400-e29b-41d4-a716-446655440000',
		'actor'           => '1',
		'created_at'      => '2025-03-11T10:00:00Z',
		'completed_at'    => '2025-03-11T10:00:15Z',
		'provider_id'     => 'openai',
		'model_used'      => 'gpt-4o',
		'prompt_pack_ref' => 'aio-pp-site-audit-v1',
		'retry_count'     => 0,
		'build_plan_ref'  => '',
	);

	/** Example artifact summary payload for admin review (redacted where applicable). */
	public const EXAMPLE_ARTIFACT_SUMMARY = array(
		Artifact_Category_Keys::RAW_PROMPT                => array(
			'present'  => true,
			'summary'  => '[redacted]',
			'redacted' => true,
		),
		Artifact_Category_Keys::NORMALIZED_PROMPT_PACKAGE => array(
			'present'  => true,
			'summary'  => '[redacted]',
			'redacted' => true,
		),
		Artifact_Category_Keys::INPUT_SNAPSHOT            => array(
			'present'  => true,
			'summary'  => '[redacted]',
			'redacted' => true,
		),
		Artifact_Category_Keys::FILE_MANIFEST             => array(
			'present'  => false,
			'summary'  => '',
			'redacted' => false,
		),
		Artifact_Category_Keys::RAW_PROVIDER_RESPONSE     => array(
			'present'  => true,
			'summary'  => '[redacted]',
			'redacted' => true,
		),
		Artifact_Category_Keys::NORMALIZED_OUTPUT         => array(
			'present'  => true,
			'summary'  => array(
				'keys'  => array( 'pages' ),
				'count' => 1,
			),
			'redacted' => false,
		),
		Artifact_Category_Keys::VALIDATION_REPORT         => array(
			'present'  => true,
			'summary'  => array(
				'keys'  => array( 'valid', 'errors' ),
				'count' => 2,
			),
			'redacted' => false,
		),
		Artifact_Category_Keys::DROPPED_RECORD_REPORT     => array(
			'present'  => false,
			'summary'  => '',
			'redacted' => false,
		),
		Artifact_Category_Keys::RETRY_METADATA            => array(
			'present'  => false,
			'summary'  => '',
			'redacted' => false,
		),
		Artifact_Category_Keys::USAGE_METADATA            => array(
			'present'  => true,
			'summary'  => array(
				'keys'  => array( 'input_tokens', 'output_tokens' ),
				'count' => 2,
			),
			'redacted' => false,
		),
		Artifact_Category_Keys::EXPAND_PASS_USAGE         => array(
			'present'  => false,
			'summary'  => '',
			'redacted' => false,
		),
		Artifact_Category_Keys::BUILD_PLAN_REF            => array(
			'present'  => false,
			'summary'  => '',
			'redacted' => false,
		),
	);

	public function test_example_run_metadata_has_required_keys(): void {
		$m = self::EXAMPLE_RUN_METADATA;
		$this->assertArrayHasKey( 'run_id', $m );
		$this->assertArrayHasKey( 'actor', $m );
		$this->assertArrayHasKey( 'created_at', $m );
		$this->assertArrayHasKey( 'provider_id', $m );
		$this->assertArrayHasKey( 'model_used', $m );
		$this->assertArrayHasKey( 'prompt_pack_ref', $m );
		$this->assertArrayHasKey( 'retry_count', $m );
		$this->assertArrayHasKey( 'build_plan_ref', $m );
	}

	public function test_example_artifact_summary_has_all_categories(): void {
		foreach ( Artifact_Category_Keys::all() as $cat ) {
			$this->assertArrayHasKey( $cat, self::EXAMPLE_ARTIFACT_SUMMARY );
			$this->assertArrayHasKey( 'present', self::EXAMPLE_ARTIFACT_SUMMARY[ $cat ] );
			$this->assertArrayHasKey( 'summary', self::EXAMPLE_ARTIFACT_SUMMARY[ $cat ] );
			$this->assertArrayHasKey( 'redacted', self::EXAMPLE_ARTIFACT_SUMMARY[ $cat ] );
		}
	}
}
