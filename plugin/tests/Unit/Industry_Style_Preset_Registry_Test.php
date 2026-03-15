<?php
/**
 * Unit tests for Industry_Style_Preset_Registry: valid presets, invalid token/value payloads,
 * bad refs, compatibility with styling constraints (Prompt 335).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Registry.php';

final class Industry_Style_Preset_Registry_Test extends TestCase {

	private function valid_preset( string $key = 'legal_serious' ): array {
		return array(
			Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => $key,
			Industry_Style_Preset_Registry::FIELD_LABEL            => 'Legal Serious',
			Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => Industry_Style_Preset_Registry::SUPPORTED_SCHEMA_VERSION,
			Industry_Style_Preset_Registry::FIELD_STATUS          => Industry_Style_Preset_Registry::STATUS_ACTIVE,
		);
	}

	public function test_registry_loads_valid_preset_and_get_returns_it(): void {
		$registry = new Industry_Style_Preset_Registry();
		$registry->load( array( $this->valid_preset( 'legal_serious' ) ) );
		$preset = $registry->get( 'legal_serious' );
		$this->assertNotNull( $preset );
		$this->assertSame( 'legal_serious', $preset[ Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY ] );
		$this->assertSame( Industry_Style_Preset_Registry::STATUS_ACTIVE, $preset[ Industry_Style_Preset_Registry::FIELD_STATUS ] );
	}

	public function test_registry_accepts_valid_token_values(): void {
		$preset = $this->valid_preset( 'realtor_warm' );
		$preset[ Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY ] = 'realtor';
		$preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] = array(
			'--aio-color-primary' => '#1a365d',
			'--aio-space-md'      => '1rem',
		);
		$registry = new Industry_Style_Preset_Registry();
		$registry->load( array( $preset ) );
		$loaded = $registry->get( 'realtor_warm' );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'realtor', $loaded[ Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( array( '--aio-color-primary' => '#1a365d', '--aio-space-md' => '1rem' ), $loaded[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] );
	}

	public function test_invalid_token_name_rejected(): void {
		$preset = $this->valid_preset( 'bad_tokens' );
		$preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] = array(
			'--custom-token' => 'value',
		);
		$registry = new Industry_Style_Preset_Registry();
		$registry->load( array( $preset ) );
		$this->assertNull( $registry->get( 'bad_tokens' ) );
		$this->assertNotEmpty( $registry->validate_preset( $preset ) );
		$this->assertContains( 'invalid_token_name', $registry->validate_preset( $preset ) );
	}

	public function test_prohibited_token_value_rejected(): void {
		$preset = $this->valid_preset( 'unsafe' );
		$preset[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] = array(
			'--aio-color-primary' => 'url(javascript:alert(1))',
		);
		$registry = new Industry_Style_Preset_Registry();
		$registry->load( array( $preset ) );
		$this->assertNull( $registry->get( 'unsafe' ) );
		$this->assertContains( 'prohibited_token_value', $registry->validate_preset( $preset ) );
	}

	public function test_unsupported_version_rejected(): void {
		$preset = $this->valid_preset( 'v2_preset' );
		$preset[ Industry_Style_Preset_Registry::FIELD_VERSION_MARKER ] = '2';
		$registry = new Industry_Style_Preset_Registry();
		$registry->load( array( $preset ) );
		$this->assertNull( $registry->get( 'v2_preset' ) );
		$this->assertContains( 'unsupported_version', $registry->validate_preset( $preset ) );
	}

	public function test_list_by_industry_returns_matching_presets(): void {
		$registry = new Industry_Style_Preset_Registry();
		$presets = array(
			array_merge( $this->valid_preset( 'legal_a' ), array( Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY => 'legal' ) ),
			array_merge( $this->valid_preset( 'legal_b' ), array( Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY => 'legal' ) ),
			array_merge( $this->valid_preset( 'healthcare_a' ), array( Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY => 'healthcare' ) ),
		);
		$registry->load( $presets );
		$legal = $registry->list_by_industry( 'legal' );
		$this->assertCount( 2, $legal );
		$this->assertCount( 1, $registry->list_by_industry( 'healthcare' ) );
		$this->assertCount( 0, $registry->list_by_industry( 'unknown' ) );
	}

	public function test_list_by_status_returns_matching_presets(): void {
		$registry = new Industry_Style_Preset_Registry();
		$presets = array(
			$this->valid_preset( 'active_1' ),
			array_merge( $this->valid_preset( 'draft_1' ), array( Industry_Style_Preset_Registry::FIELD_STATUS => Industry_Style_Preset_Registry::STATUS_DRAFT ) ),
		);
		$registry->load( $presets );
		$this->assertCount( 1, $registry->list_by_status( Industry_Style_Preset_Registry::STATUS_ACTIVE ) );
		$this->assertCount( 1, $registry->list_by_status( Industry_Style_Preset_Registry::STATUS_DRAFT ) );
	}

	public function test_duplicate_key_first_wins(): void {
		$registry = new Industry_Style_Preset_Registry();
		$first  = $this->valid_preset( 'dup' );
		$first[ Industry_Style_Preset_Registry::FIELD_LABEL ] = 'First';
		$second = $this->valid_preset( 'dup' );
		$second[ Industry_Style_Preset_Registry::FIELD_LABEL ] = 'Second';
		$registry->load( array( $first, $second ) );
		$preset = $registry->get( 'dup' );
		$this->assertNotNull( $preset );
		$this->assertSame( 'First', $preset[ Industry_Style_Preset_Registry::FIELD_LABEL ] );
		$this->assertCount( 1, $registry->get_all() );
	}
}
