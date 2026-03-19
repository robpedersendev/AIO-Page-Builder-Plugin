<?php
/**
 * Unit tests for Global_Style_Settings_Repository (Prompt 246): defaults, versioned read/write, invalid values fail safely.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use AIOPageBuilder\Domain\Styling\Style_Validation_Result;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Validation_Result.php';

final class Global_Style_Settings_Repository_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
		$applied_key = Option_Names::APPLIED_DESIGN_TOKENS;
		if ( isset( $GLOBALS['_aio_test_options'][ $applied_key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $applied_key ] );
		}
	}

	public function test_defaults_have_required_keys(): void {
		$defaults = Global_Style_Settings_Schema::get_defaults();
		$this->assertArrayHasKey( Global_Style_Settings_Schema::KEY_VERSION, $defaults );
		$this->assertArrayHasKey( Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS, $defaults );
		$this->assertArrayHasKey( Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES, $defaults );
		$this->assertSame( Global_Style_Settings_Schema::SCHEMA_VERSION, $defaults[ Global_Style_Settings_Schema::KEY_VERSION ] );
		$this->assertIsArray( $defaults[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] );
		$this->assertIsArray( $defaults[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] );
	}

	public function test_repository_without_registry_returns_empty_tokens_when_none_stored(): void {
		$repo = new Global_Style_Settings_Repository( null, null );
		$full = $repo->get_full();
		$this->assertSame( Global_Style_Settings_Schema::SCHEMA_VERSION, $repo->get_version() );
		$this->assertIsArray( $repo->get_global_tokens() );
		$this->assertIsArray( $repo->get_global_component_overrides() );
	}

	public function test_set_global_tokens_with_registry_filters_invalid_keys(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$invalid  = array(
			'color'         => array(
				'primary'      => '#333',
				'invalid_name' => '#fff',
			),
			'unknown_group' => array( 'x' => 'y' ),
		);
		$repo->set_global_tokens( $invalid );
		$read = $repo->get_global_tokens();
		$this->assertArrayHasKey( 'color', $read );
		$this->assertArrayHasKey( 'primary', $read['color'] );
		$this->assertSame( '#333', $read['color']['primary'] );
		$this->assertArrayNotHasKey( 'invalid_name', $read['color'] );
		$this->assertArrayNotHasKey( 'unknown_group', $read );
	}

	public function test_reset_to_defaults(): void {
		$repo = new Global_Style_Settings_Repository( null, null );
		$repo->set_global_tokens( array( 'color' => array( 'primary' => '#000' ) ) );
		$repo->reset_to_defaults();
		$this->assertSame( array(), $repo->get_global_tokens() );
		$this->assertSame( array(), $repo->get_global_component_overrides() );
	}

	/**
	 * Invalid value types (non-string) are not persisted; only allowed group/name with string value are stored.
	 */
	public function test_invalid_value_types_not_persisted_with_registry(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$input    = array(
			'color' => array(
				'primary' => '#333',
				'text'    => 12345,
				'surface' => array( 'bad' => 'value' ),
			),
		);
		$repo->set_global_tokens( $input );
		$read = $repo->get_global_tokens();
		$this->assertArrayHasKey( 'color', $read );
		$this->assertSame( '#333', $read['color']['primary'] );
		$this->assertArrayNotHasKey( 'text', $read['color'] );
		$this->assertArrayNotHasKey( 'surface', $read['color'] );
	}

	/**
	 * When registry is null, set_global_tokens persists nothing (fail closed).
	 */
	public function test_without_registry_set_global_tokens_persists_nothing(): void {
		$repo = new Global_Style_Settings_Repository( null, null );
		$repo->set_global_tokens( array( 'color' => array( 'primary' => '#000' ) ) );
		$this->assertSame( array(), $repo->get_global_tokens() );
	}

	/**
	 * persist_global_tokens_result persists only when result is valid (Prompt 252).
	 */
	public function test_persist_global_tokens_result_valid_persists(): void {
		$loader    = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry  = new Style_Token_Registry( $loader );
		$repo      = new Global_Style_Settings_Repository( $registry, null );
		$sanitized = array( 'color' => array( 'primary' => '#111' ) );
		$result    = new Style_Validation_Result( true, array(), $sanitized );
		$this->assertTrue( $repo->persist_global_tokens_result( $result ) );
		$this->assertSame( $sanitized, $repo->get_global_tokens() );
	}

	/**
	 * persist_global_tokens_result does not persist when result is invalid.
	 */
	public function test_persist_global_tokens_result_invalid_does_not_persist(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$result   = new Style_Validation_Result( false, array( 'Invalid token group' ), array() );
		$this->assertFalse( $repo->persist_global_tokens_result( $result ) );
		$this->assertSame( array(), $repo->get_global_tokens() );
	}

	/**
	 * get_global_tokens merges applied design tokens from execution (Prompt 640); applied overrides repo.
	 */
	public function test_get_global_tokens_merges_applied_design_tokens(): void {
		\update_option( Option_Names::APPLIED_DESIGN_TOKENS, array( 'color' => array( 'primary' => '#applied' ) ) );
		try {
			$repo = new Global_Style_Settings_Repository( null, null );
			$out  = $repo->get_global_tokens();
			$this->assertArrayHasKey( 'color', $out );
			$this->assertArrayHasKey( 'primary', $out['color'] );
			$this->assertSame( '#applied', $out['color']['primary'] );
		} finally {
			\delete_option( Option_Names::APPLIED_DESIGN_TOKENS );
		}
	}
}
