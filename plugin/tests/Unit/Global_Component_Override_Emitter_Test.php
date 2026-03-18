<?php
/**
 * Unit tests for Global_Component_Override_Emitter (Prompt 250): valid emission, invalid omitted, no new selectors.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Component_Override_Registry;
use AIOPageBuilder\Domain\Styling\Global_Component_Override_Emitter;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Component_Override_Emitter.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Component_Override_Registry.php';

final class Global_Component_Override_Emitter_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_emit_without_registry_returns_empty(): void {
		$repo    = new Global_Style_Settings_Repository( null, null );
		$emitter = new Global_Component_Override_Emitter( $repo, null );
		$this->assertSame( '', $emitter->emit() );
	}

	public function test_emit_with_valid_overrides_contains_spec_derived_selector(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$comp_reg = new Component_Override_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( null, $comp_reg );
		$repo->set_global_component_overrides(
			array(
				'card' => array( '--aio-color-surface' => '#f5f5f5' ),
			)
		);
		$emitter = new Global_Component_Override_Emitter( $repo, $comp_reg );
		$css     = $emitter->emit();
		$this->assertStringContainsString( 'aio-s-', $css );
		$this->assertStringContainsString( '__card', $css );
		$this->assertStringContainsString( '--aio-color-surface', $css );
		$this->assertStringContainsString( '#f5f5f5', $css );
	}

	public function test_emit_uses_only_attribute_selector_no_arbitrary_class(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$comp_reg = new Component_Override_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( null, $comp_reg );
		$repo->set_global_component_overrides(
			array(
				'cta' => array( '--aio-color-primary' => '#0066cc' ),
			)
		);
		$emitter = new Global_Component_Override_Emitter( $repo, $comp_reg );
		$css     = $emitter->emit();
		$this->assertStringContainsString( '[class*="', $css );
		$this->assertStringNotContainsString( '</style>', $css );
		$this->assertStringNotContainsString( '<', $css );
	}

	public function test_emit_omits_invalid_component_id(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$comp_reg = new Component_Override_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( null, $comp_reg );
		$full     = $repo->get_full();
		$full[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] = array(
			'nonexistent_component' => array( '--aio-color-primary' => '#000' ),
		);
		\update_option( Global_Style_Settings_Schema::OPTION_KEY, $full );
		$emitter = new Global_Component_Override_Emitter( $repo, $comp_reg );
		$css     = $emitter->emit();
		$this->assertSame( '', $css );
	}
}
