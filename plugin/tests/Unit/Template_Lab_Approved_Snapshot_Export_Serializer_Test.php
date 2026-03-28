<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Export_Serializer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Template_Lab_Approved_Snapshot_Export_Serializer_Test extends TestCase {

	public function test_export_allowlists_metadata_and_redacts_sensitive_keys(): void {
		/** @var array<string, mixed> */
		$artifact_store = array(
			Artifact_Category_Keys::TEMPLATE_LAB_TRACE => array(
				'artifact_fingerprint' => 'fp1',
				'api_key'              => 'sk_should_not_appear',
			),
			Artifact_Category_Keys::NORMALIZED_OUTPUT  => array(
				'composition_id' => 'comp_export_1',
				'name'           => 'Safe name',
			),
			Artifact_Category_Keys::VALIDATION_REPORT  => array(
				'ok'   => true,
				'deep' => array( 'x' => 1 ),
			),
		);
		$artifacts      = new class( $artifact_store ) implements AI_Run_Artifact_Read_Port {
			/**
			 * @param array<string, mixed> $store
			 */
			public function __construct( private array $store ) {
			}

			public function get( int $run_post_id, string $category ): mixed {
				unset( $run_post_id );
				return $this->store[ $category ] ?? null;
			}
		};
		$apply          = new class() implements AI_Run_Template_Lab_Apply_State_Port {
			public function get_template_lab_canonical_apply_record( int $post_id ): ?array {
				unset( $post_id );
				return null;
			}

			public function save_template_lab_canonical_apply_record( int $post_id, array $record ): bool {
				unset( $post_id, $record );
				return true;
			}
		};
		$run_meta       = array(
			'provider_id'   => 'fake',
			'model_used'    => 'fake-model',
			'created_at'    => '2020-01-01T00:00:00Z',
			'user_prompt'   => 'SECRET_PROMPT_DO_NOT_EXPORT',
			'raw_http_body' => 'SECRET_BODY',
			'template_lab'  => array( 'target_kind' => 'composition' ),
			'authorization' => 'bearer leak',
		);
		$out            = Template_Lab_Approved_Snapshot_Export_Serializer::serialize( 1001, $artifacts, $apply, $run_meta );
		$this->assertSame( Template_Lab_Approved_Snapshot_Export_Serializer::EXPORT_VERSION, $out['export_version'] ?? null );
		$this->assertSame( 1001, $out['run_post_id'] ?? 0 );
		$this->assertArrayNotHasKey( 'user_prompt', $out['safe_run_metadata'] ?? array() );
		$this->assertArrayNotHasKey( 'raw_http_body', $out['safe_run_metadata'] ?? array() );
		$this->assertArrayNotHasKey( 'authorization', $out['safe_run_metadata'] ?? array() );
		$this->assertSame( '[redacted]', ( $out['template_lab_trace']['api_key'] ?? null ) );
		$json = \wp_json_encode( $out );
		$this->assertStringNotContainsString( 'SECRET_PROMPT', is_string( $json ) ? $json : '' );
		$this->assertStringNotContainsString( 'sk_should_not_appear', is_string( $json ) ? $json : '' );
		$this->assertSame( 'comp_export_1', ( $out['normalized_output']['composition_id'] ?? null ) );
		$this->assertTrue( (bool) ( $out['validation_summary']['present'] ?? false ) );
		$this->assertFalse( (bool) ( $out['canonical_apply']['applied'] ?? true ) );
	}

	public function test_export_marks_applied_when_apply_record_present(): void {
		$artifacts = new class() implements AI_Run_Artifact_Read_Port {
			public function get( int $run_post_id, string $category ): mixed {
				unset( $run_post_id, $category );
				return array();
			}
		};
		$apply     = new class() implements AI_Run_Template_Lab_Apply_State_Port {
			public function get_template_lab_canonical_apply_record( int $post_id ): ?array {
				unset( $post_id );
				return array(
					'canonical_internal_key' => 'comp_x',
					'canonical_post_id'      => 55,
					'target_kind'            => 'composition',
					'artifact_fingerprint'   => 'fp',
				);
			}

			public function save_template_lab_canonical_apply_record( int $post_id, array $record ): bool {
				unset( $post_id, $record );
				return true;
			}
		};
		$out       = Template_Lab_Approved_Snapshot_Export_Serializer::serialize( 2, $artifacts, $apply, array() );
		$this->assertTrue( (bool) ( $out['canonical_apply']['applied'] ?? false ) );
		$this->assertSame( 'comp_x', (string) ( $out['canonical_apply']['canonical_internal_key'] ?? '' ) );
	}
}
