<?php
/**
 * Unit tests for Industry_Style_Preset_Application_Service (industry-style-preset-application-contract, Prompt 348).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Application_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Application_Service.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';

final class Industry_Style_Preset_Application_Service_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		\delete_option( Option_Names::APPLIED_INDUSTRY_PRESET );
		$key = Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::APPLIED_INDUSTRY_PRESET );
		parent::tearDown();
	}

	private function valid_preset( string $key = 'legal_serious', array $token_values = array() ): array {
		$p = array(
			Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => $key,
			Industry_Style_Preset_Registry::FIELD_LABEL            => 'Legal Serious',
			Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => Industry_Style_Preset_Registry::SUPPORTED_SCHEMA_VERSION,
			Industry_Style_Preset_Registry::FIELD_STATUS           => Industry_Style_Preset_Registry::STATUS_ACTIVE,
		);
		if ( $token_values !== array() ) {
			$p[ Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES ] = $token_values;
		}
		return $p;
	}

	public function test_apply_preset_returns_false_for_empty_key(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'legal_serious' ) ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertFalse( $service->apply_preset( '' ) );
		$this->assertFalse( $service->apply_preset( '   ' ) );
	}

	public function test_apply_preset_returns_false_for_unknown_preset_key(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'legal_serious' ) ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertFalse( $service->apply_preset( 'unknown_preset' ) );
		$this->assertNull( $service->get_applied_preset() );
	}

	public function test_apply_preset_returns_false_for_inactive_preset(): void {
		$preset = $this->valid_preset( 'draft_preset' );
		$preset[ Industry_Style_Preset_Registry::FIELD_STATUS ] = Industry_Style_Preset_Registry::STATUS_DRAFT;
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $preset ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertFalse( $service->apply_preset( 'draft_preset' ) );
	}

	public function test_apply_preset_with_no_token_values_records_and_returns_true(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'legal_serious' ) ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertTrue( $service->apply_preset( 'legal_serious' ) );
		$applied = $service->get_applied_preset();
		$this->assertNotNull( $applied );
		$this->assertSame( 'legal_serious', $applied['preset_key'] );
		$this->assertSame( 'Legal Serious', $applied['label'] );
		$this->assertNotSame( '', $applied['applied_at'] );
	}

	public function test_apply_preset_with_token_values_but_no_token_registry_returns_false(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'with_tokens', array( '--aio-color-primary' => '#1a365d' ) ) ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertFalse( $service->apply_preset( 'with_tokens' ) );
	}

	public function test_apply_preset_with_token_values_merges_into_global_tokens(): void {
		$loader     = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$token_reg  = new Style_Token_Registry( $loader );
		$style_repo = new Global_Style_Settings_Repository( $token_reg, null );
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'realtor_warm', array(
			'--aio-color-primary' => '#1a365d',
			'--aio-space-md'      => '1rem',
		) ) ) );
		$service = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, $token_reg );
		$this->assertTrue( $service->apply_preset( 'realtor_warm' ) );
		$tokens = $style_repo->get_global_tokens();
		$this->assertArrayHasKey( 'color', $tokens );
		$this->assertArrayHasKey( 'primary', $tokens['color'] );
		$this->assertSame( '#1a365d', $tokens['color']['primary'] );
		$applied = $service->get_applied_preset();
		$this->assertNotNull( $applied );
		$this->assertSame( 'realtor_warm', $applied['preset_key'] );
	}

	public function test_clear_applied_preset_removes_recorded_preset(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$preset_reg->load( array( $this->valid_preset( 'legal_serious' ) ) );
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$service->apply_preset( 'legal_serious' );
		$this->assertNotNull( $service->get_applied_preset() );
		$service->clear_applied_preset();
		$this->assertNull( $service->get_applied_preset() );
	}

	public function test_get_applied_preset_returns_null_when_none_applied(): void {
		$preset_reg = new Industry_Style_Preset_Registry();
		$style_repo = new Global_Style_Settings_Repository( null, null );
		$service    = new Industry_Style_Preset_Application_Service( $preset_reg, $style_repo, null );
		$this->assertNull( $service->get_applied_preset() );
	}
}
