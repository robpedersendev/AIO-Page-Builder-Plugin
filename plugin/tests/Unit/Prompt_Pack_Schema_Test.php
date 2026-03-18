<?php
/**
 * Unit tests for Prompt_Pack_Schema and Prompt_Pack_Versioning (prompt-pack-schema.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Versioning;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Prompt_Pack_Versioning.php';

final class Prompt_Pack_Schema_Test extends TestCase {

	public function test_required_root_keys_include_internal_key_version_segments(): void {
		$keys = Prompt_Pack_Schema::required_root_keys();
		$this->assertContains( Prompt_Pack_Schema::ROOT_INTERNAL_KEY, $keys );
		$this->assertContains( Prompt_Pack_Schema::ROOT_VERSION, $keys );
		$this->assertContains( Prompt_Pack_Schema::ROOT_SEGMENTS, $keys );
		$this->assertContains( Prompt_Pack_Schema::ROOT_STATUS, $keys );
		$this->assertCount( 6, $keys );
	}

	public function test_required_segment_keys_include_system_base(): void {
		$keys = Prompt_Pack_Schema::required_segment_keys();
		$this->assertSame( array( Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ), $keys );
	}

	public function test_valid_pack_has_required_root_and_system_base(): void {
		$pack = array(
			Prompt_Pack_Schema::ROOT_INTERNAL_KEY => 'aio/test',
			Prompt_Pack_Schema::ROOT_NAME         => 'Test Pack',
			Prompt_Pack_Schema::ROOT_VERSION      => '1.0.0',
			Prompt_Pack_Schema::ROOT_PACK_TYPE    => Prompt_Pack_Schema::PACK_TYPE_PLANNING,
			Prompt_Pack_Schema::ROOT_STATUS       => Prompt_Pack_Schema::STATUS_ACTIVE,
			Prompt_Pack_Schema::ROOT_SEGMENTS     => array(
				Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE => 'You are a planning assistant.',
			),
		);
		foreach ( Prompt_Pack_Schema::required_root_keys() as $key ) {
			$this->assertArrayHasKey( $key, $pack );
		}
		$this->assertArrayHasKey( Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE, $pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ] );
		$this->assertIsString( $pack[ Prompt_Pack_Schema::ROOT_SEGMENTS ][ Prompt_Pack_Schema::SEGMENT_SYSTEM_BASE ] );
	}

	public function test_invalid_pack_missing_segments_rejected(): void {
		$pack     = array(
			Prompt_Pack_Schema::ROOT_INTERNAL_KEY => 'aio/bad',
			Prompt_Pack_Schema::ROOT_NAME         => 'Bad',
			Prompt_Pack_Schema::ROOT_VERSION      => '1.0.0',
			Prompt_Pack_Schema::ROOT_PACK_TYPE    => Prompt_Pack_Schema::PACK_TYPE_PLANNING,
			Prompt_Pack_Schema::ROOT_STATUS       => Prompt_Pack_Schema::STATUS_ACTIVE,
		);
		$required = Prompt_Pack_Schema::required_root_keys();
		$this->assertContains( Prompt_Pack_Schema::ROOT_SEGMENTS, $required );
		$this->assertArrayNotHasKey( Prompt_Pack_Schema::ROOT_SEGMENTS, $pack );
	}

	public function test_valid_statuses_and_pack_types(): void {
		$this->assertContains( Prompt_Pack_Schema::STATUS_ACTIVE, Prompt_Pack_Schema::valid_statuses() );
		$this->assertContains( Prompt_Pack_Schema::STATUS_DEPRECATED, Prompt_Pack_Schema::valid_statuses() );
		$this->assertContains( Prompt_Pack_Schema::PACK_TYPE_REPAIR, Prompt_Pack_Schema::valid_pack_types() );
		$this->assertContains( Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PROFILE, Prompt_Pack_Schema::valid_placeholder_sources() );
		$this->assertContains( Prompt_Pack_Schema::PLACEHOLDER_SOURCE_PLANNING_GUIDANCE, Prompt_Pack_Schema::valid_placeholder_sources() );
	}

	/** Prompt 210: optional segment keys for template-family and CTA-law guidance. */
	public function test_template_family_and_cta_law_segment_keys_defined(): void {
		$this->assertSame( 'template_family_guidance', Prompt_Pack_Schema::SEGMENT_TEMPLATE_FAMILY_GUIDANCE );
		$this->assertSame( 'cta_law_guidance', Prompt_Pack_Schema::SEGMENT_CTA_LAW_GUIDANCE );
		$this->assertSame( 'hierarchy_role_guidance', Prompt_Pack_Schema::SEGMENT_HIERARCHY_ROLE_GUIDANCE );
	}

	public function test_version_parse(): void {
		$this->assertSame( array( 1, 2, 3 ), Prompt_Pack_Versioning::parse( '1.2.3' ) );
		$this->assertSame( array( 2, 0, 0 ), Prompt_Pack_Versioning::parse( '2.0.0' ) );
		$this->assertSame( array( 1, 0, 0 ), Prompt_Pack_Versioning::parse( '1.0' ) );
		$this->assertSame( array( 0, 0, 0 ), Prompt_Pack_Versioning::parse( 'invalid' ) );
	}

	public function test_version_compare(): void {
		$this->assertSame( -1, Prompt_Pack_Versioning::compare( '1.0.0', '2.0.0' ) );
		$this->assertSame( 1, Prompt_Pack_Versioning::compare( '2.0.0', '1.0.0' ) );
		$this->assertSame( 0, Prompt_Pack_Versioning::compare( '1.2.3', '1.2.3' ) );
		$this->assertSame( -1, Prompt_Pack_Versioning::compare( '1.2.0', '1.2.1' ) );
		$this->assertSame( 1, Prompt_Pack_Versioning::compare( '1.3.0', '1.2.9' ) );
	}

	public function test_is_valid_semver(): void {
		$this->assertTrue( Prompt_Pack_Versioning::is_valid_semver( '1.0.0' ) );
		$this->assertTrue( Prompt_Pack_Versioning::is_valid_semver( '2.1.3' ) );
		$this->assertFalse( Prompt_Pack_Versioning::is_valid_semver( '1.0' ) );
		$this->assertFalse( Prompt_Pack_Versioning::is_valid_semver( 'v1.0.0' ) );
		$this->assertFalse( Prompt_Pack_Versioning::is_valid_semver( '' ) );
	}

	public function test_placeholder_rule_source_must_be_valid(): void {
		$sources = Prompt_Pack_Schema::valid_placeholder_sources();
		$this->assertContains( 'profile', $sources );
		$this->assertContains( 'crawl', $sources );
		$this->assertContains( 'goal', $sources );
	}
}
