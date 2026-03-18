<?php
/**
 * Unit tests for Global_Style_Token_Form_Builder (Prompt 247): field definitions from registry and repository.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Global_Style_Token_Form_Builder;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once $plugin_root . '/src/Domain/Styling/Style_Token_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Global_Style_Token_Form_Builder.php';

final class Global_Style_Token_Form_Builder_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
		$key               = \AIOPageBuilder\Domain\Styling\Global_Style_Settings_Schema::OPTION_KEY;
		if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
			unset( $GLOBALS['_aio_test_options'][ $key ] );
		}
	}

	public function test_form_tokens_key_constant(): void {
		$this->assertSame( 'aio_global_tokens', Global_Style_Token_Form_Builder::FORM_TOKENS_KEY );
	}

	public function test_field_definitions_exclude_component_group(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$builder  = new Global_Style_Token_Form_Builder( $registry, $repo );
		$defs     = $builder->get_field_definitions();
		foreach ( $defs as $def ) {
			$this->assertNotSame( 'component', $def['group'] );
		}
	}

	public function test_field_definitions_include_required_keys(): void {
		$loader   = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry = new Style_Token_Registry( $loader );
		$repo     = new Global_Style_Settings_Repository( $registry, null );
		$builder  = new Global_Style_Token_Form_Builder( $registry, $repo );
		$defs     = $builder->get_field_definitions();
		$required = array( 'group', 'name', 'name_attr', 'label', 'value', 'value_type', 'max_length' );
		foreach ( $defs as $def ) {
			foreach ( $required as $key ) {
				$this->assertArrayHasKey( $key, $def );
			}
			$this->assertStringContainsString( 'aio_global_tokens', $def['name_attr'] );
		}
	}

	public function test_fields_by_group_matches_definitions(): void {
		$loader         = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$registry       = new Style_Token_Registry( $loader );
		$repo           = new Global_Style_Settings_Repository( $registry, null );
		$builder        = new Global_Style_Token_Form_Builder( $registry, $repo );
		$defs           = $builder->get_field_definitions();
		$by_group       = $builder->get_fields_by_group();
		$count_defs     = count( $defs );
		$count_by_group = 0;
		foreach ( $by_group as $fields ) {
			$count_by_group += count( $fields );
		}
		$this->assertSame( $count_defs, $count_by_group );
	}
}
