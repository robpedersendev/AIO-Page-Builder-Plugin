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

	/** Prompt 332: industry_overlay option appends industry_guidance_text to system_prompt. */
	public function test_prompt_package_builder_appends_industry_overlay_guidance(): void {
		$pack = $this->planning_pack();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION => '1',
			Input_Artifact_Schema::ROOT_CREATED_AT => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ),
			Input_Artifact_Schema::ROOT_REDACTION => array( 'redaction_applied' => false ),
			Input_Artifact_Schema::ROOT_PROFILE => array(),
		);
		$options = array(
			'industry_overlay' => array(
				'schema_version'         => '1',
				'industry_guidance_text' => 'Prefer service and local page families for this vertical.',
			),
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact, $options );
		$this->assertTrue( $result->is_success() );
		$pkg = $result->get_normalized_prompt_package();
		$this->assertNotNull( $pkg );
		$this->assertStringContainsString( 'Industry guidance', $pkg['system_prompt'] );
		$this->assertStringContainsString( 'Prefer service and local page families for this vertical.', $pkg['system_prompt'] );
	}

	/** Prompt 533: goal_overlay option appends conversion_goal_guidance_text to system_prompt. */
	public function test_prompt_package_builder_appends_goal_overlay_guidance(): void {
		$pack = $this->planning_pack();
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID => 'art-1',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION => '1',
			Input_Artifact_Schema::ROOT_CREATED_AT => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ),
			Input_Artifact_Schema::ROOT_REDACTION => array( 'redaction_applied' => false ),
			Input_Artifact_Schema::ROOT_PROFILE => array(),
		);
		$options = array(
			'goal_overlay' => array(
				'schema_version' => '1',
				'primary_goal_key' => 'calls',
				'conversion_goal_guidance_text' => 'Prioritize phone-call conversions; include click-to-call.',
			),
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact, $options );
		$this->assertTrue( $result->is_success() );
		$pkg = $result->get_normalized_prompt_package();
		$this->assertNotNull( $pkg );
		$this->assertStringContainsString( 'Conversion goal guidance', $pkg['system_prompt'] );
		$this->assertStringContainsString( 'Prioritize phone-call conversions', $pkg['system_prompt'] );
	}

	public function test_prompt_package_result_to_validation_result(): void {
		$result = new Prompt_Package_Result( false, null, array( 'missing_segments' ), null );
		$arr = $result->to_validation_result();
		$this->assertFalse( $arr['success'] );
		$this->assertSame( array( 'missing_segments' ), $arr['validation_errors'] );
		$this->assertFalse( $arr['has_package'] );
	}

	/** Prompt 210: get_planning_guidance_content returns template-family, CTA-law, hierarchy guidance and schema_version. */
	public function test_registry_get_planning_guidance_content_returns_structure(): void {
		$repo = new Test_Prompt_Pack_Repo();
		$registry = new Prompt_Pack_Registry_Service( $repo );
		$guidance = $registry->get_planning_guidance_content();
		$this->assertArrayHasKey( 'template_family_guidance', $guidance );
		$this->assertArrayHasKey( 'cta_law_rules', $guidance );
		$this->assertArrayHasKey( 'hierarchy_role_guidance', $guidance );
		$this->assertArrayHasKey( 'schema_version', $guidance );
		$this->assertSame( Prompt_Pack_Registry_Service::PLANNING_GUIDANCE_SCHEMA_VERSION, $guidance['schema_version'] );
		$this->assertStringContainsString( 'top_level', $guidance['template_family_guidance'] );
		$this->assertStringContainsString( 'hub', $guidance['template_family_guidance'] );
		$this->assertStringContainsString( 'child_detail', $guidance['template_family_guidance'] );
		$this->assertStringContainsString( 'CTA', $guidance['cta_law_rules'] );
		$this->assertStringContainsString( 'bottom', $guidance['cta_law_rules'] );
		$this->assertStringContainsString( 'adjacent', $guidance['cta_law_rules'] );
		$this->assertStringContainsString( 'top_level', $guidance['hierarchy_role_guidance'] );
	}

	/** Prompt 210: planning_guidance placeholders are substituted when artifact contains planning_guidance. */
	public function test_normalized_prompt_package_injects_planning_guidance_placeholders(): void {
		$pack = array(
			Prompt_Pack_Schema::ROOT_INTERNAL_KEY      => 'aio/build-plan-draft',
			Prompt_Pack_Schema::ROOT_NAME             => 'Build Plan Draft',
			Prompt_Pack_Schema::ROOT_VERSION          => '1.0.0',
			Prompt_Pack_Schema::ROOT_PACK_TYPE        => Prompt_Pack_Schema::PACK_TYPE_PLANNING,
			Prompt_Pack_Schema::ROOT_STATUS           => Prompt_Pack_Schema::STATUS_ACTIVE,
			Prompt_Pack_Schema::ROOT_SCHEMA_TARGET_REF => 'aio/build-plan-draft-v1',
			Prompt_Pack_Schema::ROOT_SEGMENTS         => array(
				Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE => 'You are a planning assistant.',
				Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE => 'Taxonomy: {{template_family_guidance}}',
				Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE => 'Rules: {{cta_law_rules}}',
			),
		);
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID     => 'art-1',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION  => '1',
			Input_Artifact_Schema::ROOT_CREATED_AT      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ),
			Input_Artifact_Schema::ROOT_REDACTION       => array( 'redaction_applied' => false ),
			Input_Artifact_Schema::ROOT_PLANNING_GUIDANCE => array(
				'template_family_guidance' => 'Page classes: top_level, hub, nested_hub, child_detail.',
				'cta_law_rules'           => 'Min 3 CTAs for top_level; last section must be CTA.',
				'hierarchy_role_guidance'  => 'Match hierarchy to URL depth.',
			),
		);
		$builder = new Normalized_Prompt_Package_Builder();
		$result = $builder->build( $pack, $artifact );
		$this->assertTrue( $result->is_success() );
		$pkg = $result->get_normalized_prompt_package();
		$this->assertNotNull( $pkg );
		$this->assertStringContainsString( 'Page classes: top_level, hub, nested_hub, child_detail.', $pkg['system_prompt'] );
		$this->assertStringContainsString( 'Min 3 CTAs for top_level; last section must be CTA.', $pkg['system_prompt'] );
	}

	/** Prompt 210: artifact builder accepts planning_guidance in options. */
	public function test_input_artifact_builder_accepts_planning_guidance_option(): void {
		$guidance = array(
			'template_family_guidance' => 'Taxonomy summary.',
			'cta_law_rules'            => 'CTA rules.',
			'hierarchy_role_guidance'  => 'Hierarchy roles.',
			'schema_version'           => '1',
		);
		$builder = new Input_Artifact_Builder();
		$artifact = $builder->build( 'art-1', array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ), array(
			'redaction'        => array( 'redaction_applied' => false ),
			'planning_guidance' => $guidance,
		) );
		$this->assertNotNull( $artifact );
		$this->assertArrayHasKey( Input_Artifact_Schema::ROOT_PLANNING_GUIDANCE, $artifact );
		$this->assertSame( $guidance, $artifact[ Input_Artifact_Schema::ROOT_PLANNING_GUIDANCE ] );
	}

	/** Prompt 331: input artifact accepts optional industry_context; remains valid and exportable. */
	public function test_input_artifact_builder_accepts_industry_context_option(): void {
		$industry_context = array(
			'schema_version'   => '1',
			'readiness'        => array( 'state' => 'ready', 'score' => 100, 'validation_passed' => true ),
			'industry_profile' => array( 'primary_industry_key' => 'realtor' ),
		);
		$builder = new Input_Artifact_Builder();
		$artifact = $builder->build( 'art-1', array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ), array(
			'redaction'        => array( 'redaction_applied' => false ),
			'industry_context' => $industry_context,
		) );
		$this->assertNotNull( $artifact );
		$this->assertArrayHasKey( Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT, $artifact );
		$this->assertSame( $industry_context, $artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] );
		foreach ( Input_Artifact_Schema::required_root_keys() as $key ) {
			$this->assertArrayHasKey( $key, $artifact );
		}
	}

	/** Prompt 331: industry_context is optional; artifact valid without it. */
	public function test_input_artifact_valid_without_industry_context(): void {
		$builder = new Input_Artifact_Builder();
		$artifact = $builder->build( 'art-1', array( 'internal_key' => 'aio/build-plan-draft', 'version' => '1.0.0' ), array(
			'redaction' => array( 'redaction_applied' => false ),
		) );
		$this->assertNotNull( $artifact );
		$this->assertArrayNotHasKey( Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT, $artifact );
	}

	/** Prompt 210: fixture prompt-pack has template-family and CTA-law segments and schema version traceability. */
	public function test_template_family_cta_fixture_has_expected_structure(): void {
		$fixture_path = dirname( __DIR__ ) . '/fixtures/prompt-packs/prompt-pack-template-family-cta-example.json';
		$this->assertFileExists( $fixture_path );
		$json = file_get_contents( $fixture_path );
		$this->assertNotFalse( $json );
		$pack = json_decode( $json, true );
		$this->assertIsArray( $pack );
		$this->assertArrayHasKey( Prompt_Pack_Schema::ROOT_SEGMENTS, $pack );
		$segments = $pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ];
		$this->assertArrayHasKey( Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE, $segments );
		$this->assertArrayHasKey( Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE, $segments );
		$this->assertArrayHasKey( Prompt_Pack_Schema::SEGMENT_HIERARCHY_ROLE_GUIDANCE, $segments );
		$this->assertStringContainsString( '{{template_family_guidance}}', $segments[ Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE ] );
		$this->assertStringContainsString( '{{cta_law_rules}}', $segments[ Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE ] );
		$this->assertArrayHasKey( 'placeholder_rules', $pack );
		$this->assertSame( 'planning_guidance', $pack['placeholder_rules']['template_family_guidance']['source'] ?? '' );
		$this->assertArrayHasKey( 'changelog', $pack );
		$this->assertSame( '1.1.0', $pack[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
	}
}
