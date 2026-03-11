<?php
/**
 * Unit tests for prompt-pack registry, input artifact builder, normalized prompt package builder (spec §26, §27, §29).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Builder;
use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use AIOPageBuilder\Domain\AI\PromptPacks\Normalized_Prompt_Package_Builder;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Repository_Interface;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Package_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Package_Result.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Registry_Service.php';
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Builder.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Normalized_Prompt_Package_Builder.php';

/** In-memory prompt pack source for tests. */
final class Test_Prompt_Pack_Repo implements Prompt_Pack_Registry_Repository_Interface {
	private array $packs = array();

	public function add_pack( array $definition ): void {
		$key = (string) ( $definition[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
		$ver = (string) ( $definition[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
		$this->packs[ $key . '|' . $ver ] = $definition;
	}

	public function get_definition_by_key_and_version( string $internal_key, string $version ): ?array {
		return $this->packs[ $internal_key . '|' . $version ] ?? null;
	}

	public function get_definition_by_key( string $internal_key ): ?array {
		foreach ( $this->packs as $def ) {
			if ( ( $def[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' ) === $internal_key ) {
				if ( ( $def[ Prompt_Pack_Schema::ROOT_STATUS ] ?? '' ) === Prompt_Pack_Schema::STATUS_ACTIVE ) {
					return $def;
				}
			}
		}
		return isset( $this->packs[ $internal_key . '|1.0.0' ] ) ? $this->packs[ $internal_key . '|1.0.0' ] : null;
	}

	public function list_definitions_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		$out = array();
		foreach ( $this->packs as $def ) {
			if ( ( $def[ Prompt_Pack_Schema::ROOT_STATUS ] ?? '' ) === $status ) {
				$out[] = $def;
			}
		}
		return array_slice( $out, $offset, $limit ?: 50 );
	}
}

final class Prompt_Pack_Registry_And_Input_Artifact_Test extends TestCase {

	private function planning_pack(): array {
		return array(
			Prompt_Pack_Schema::ROOT_INTERNAL_KEY      => 'aio/build-plan-draft',
			Prompt_Pack_Schema::ROOT_NAME             => 'Build Plan Draft',
			Prompt_Pack_Schema::ROOT_VERSION          => '1.0.0',
			Prompt_Pack_Schema::ROOT_PACK_TYPE        => Prompt_Pack_Schema::PACK_TYPE_PLANNING,
			Prompt_Pack_Schema::ROOT_STATUS           => Prompt_Pack_Schema::STATUS_ACTIVE,
			Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF => 'aio/build-plan-draft-v1',
			Prompt_Pack_Schema::ROOT_SEGMENTS         => array(
				Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE => 'You are a site planning assistant. Output valid JSON.',
				Prompt_Pack_Schema::SEGMENT_PLANNING_INSTRUCTIONS => 'Use the context: {{profile_summary}}.',
			),
		);
	}

	public function test_registry_lookup_by_key(): void {
		$repo = new Test_Prompt_Pack_Repo();
		$repo->add_pack( $this->planning_pack() );
		$registry = new Prompt_Pack_Registry_Service( $repo );
		$pack = $registry->get_pack( 'aio/build-plan-draft' );
		$this->assertNotNull( $pack );
		$this->assertSame( 'aio/build-plan-draft', $pack[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] );
		$this->assertSame( '1.0.0', $pack[ Prompt_Pack_Schema::ROOT_VERSION ] );
	}

	public function test_registry_select_for_planning(): void {
		$repo = new Test_Prompt_Pack_Repo();
		$repo->add_pack( $this->planning_pack() );
		$registry = new Prompt_Pack_Registry_Service( $repo );
		$pack = $registry->select_for_planning( 'aio/build-plan-draft-v1', null );
		$this->assertNotNull( $pack );
		$this->assertSame( 'aio/build-plan-draft-v1', $pack[ Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF ] );
	}

	public function test_registry_provider_filtering(): void {
		$pack = $this->planning_pack();
		$pack[ Prompt_Pack_Schema::ROOT_PROVIDER_COMPATIBILITY ] = array( 'supported_providers' => array( 'openai' ) );
		$repo = new Test_Prompt_Pack_Repo();
		$repo->add_pack( $pack );
		$registry = new Prompt_Pack_Registry_Service( $repo );
		$this->assertTrue( $registry->pack_supports_provider( $pack, 'openai' ) );
		$this->assertFalse( $registry->pack_supports_provider( $pack, 'anthropic' ) );
	}

	public function test_input_artifact_builder_success(): void {
		$builder = new Input_Artifact_Builder();
		$artifact = $builder->build( 'art-1', array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ), array( 'redaction' => array( 'redaction_applied' => false ) ) );
		$this->assertNotNull( $artifact );
		$this->assertSame( 'art-1', $artifact[ Input_Artifact_Schema::ROOT_ARTIFACT_ID ] );
		$this->assertSame( '1', $artifact[ Input_Artifact_Schema::ROOT_SCHEMA_VERSION ] );
		$this->assertArrayHasKey( Input_Artifact_Schema::ROOT_PROMPT_PACK_REF, $artifact );
		$this->assertSame( 'aio/build-plan-draft', $artifact[ Input_Artifact_Schema::ROOT_PROMPT_PACK_REF ]['internal_key'] );
	}

	public function test_input_artifact_builder_failure_missing_pack_ref(): void {
		$builder = new Input_Artifact_Builder();
		$artifact = $builder->build( 'art-1', array(), array() );
		$this->assertNull( $artifact );
		$this->assertNotEmpty( $builder->get_last_validation_errors() );
	}

	public function test_normalized_prompt_package_builder_success(): void {
		$pack = $this->planning_pack();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION => '1',
			Input_Artifact_Schema::ROOT_CREATED_AT => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ),
			Input_Artifact_Schema::ROOT_REDACTION => array( 'redaction_applied' => false ),
			Input_Artifact_Schema::ROOT_PROFILE => array( 'summary' => 'Test profile' ),
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact );
		$this->assertTrue( $result->is_success() );
		$pkg = $result->get_normalized_prompt_package();
		$this->assertNotNull( $pkg );
		$this->assertArrayHasKey( 'system_prompt', $pkg );
		$this->assertArrayHasKey( 'user_message', $pkg );
		$this->assertArrayHasKey( 'schema_target_ref', $pkg );
		$this->assertArrayHasKey( 'raw_prompt_capture_ready', $pkg );
		$this->assertStringContainsString( 'Test profile', $pkg['user_message'] );
	}

	public function test_normalized_prompt_package_failure_missing_segments(): void {
		$pack = array( Prompt_Pack_Schema::ROOT_INTERNAL_KEY => 'x', Prompt_Pack_Schema::ROOT_VERSION => '1', Prompt_Pack_Schema::ROOT_SEGMENTS => array() );
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'a',
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'x', 'version' => '1' ),
			Input_Artifact_Schema::ROOT_REDACTION => array( 'redaction_applied' => false ),
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact );
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_validation_errors() );
	}

	/** Example normalized prompt package payload (spec §27, §29.2). */
	public function test_example_normalized_prompt_package_payload(): void {
		$pack = $this->planning_pack();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'artifact-abc-123',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION => '1',
			Input_Artifact_Schema::ROOT_CREATED_AT => '2025-07-01T12:00:00Z',
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ),
			Input_Artifact_Schema::ROOT_REDACTION => array( 'redaction_applied' => true ),
			Input_Artifact_Schema::ROOT_PROFILE => array( 'brand' => 'Acme' ),
			Input_Artifact_Schema::ROOT_GOAL => 'Create a small business site.',
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact );
		$this->assertTrue( $result->is_success() );
		$pkg = $result->get_normalized_prompt_package();
		$this->assertSame( 'aio/build-plan-draft-v1', $pkg['schema_target_ref'] );
		$this->assertArrayHasKey( 'system_prompt', $pkg['raw_prompt_capture_ready'] );
		$this->assertArrayHasKey( 'user_message', $pkg['raw_prompt_capture_ready'] );
		$this->assertArrayHasKey( 'prompt_pack_ref', $pkg['raw_prompt_capture_ready'] );
	}

	public function test_prompt_package_result_to_validation_result(): void {
		$result = new Prompt_Package_Result( false, null, array( 'missing_segments' ), null );
		$arr = $result->to_validation_result();
		$this->assertFalse( $arr['success'] );
		$this->assertSame( array( 'missing_segments' ), $arr['validation_errors'] );
		$this->assertFalse( $arr['has_package'] );
	}
}
