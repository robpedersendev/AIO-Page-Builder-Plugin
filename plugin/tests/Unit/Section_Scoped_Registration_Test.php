<?php
/**
 * Unit tests for section-scoped ACF registration and Section_Scoped_Group_Registration_Result (Prompt 284).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
use AIOPageBuilder\Domain\ACF\Registration\Section_Scoped_Group_Registration_Result;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Section_Scoped_Group_Registration_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Section_Scoped_Registration_Test extends TestCase {

	private function minimal_blueprint( string $section_key ): array {
		return array(
			'section_key' => $section_key,
			'label'       => 'Test',
			'fields'      => array(
				array(
					'key'   => 'field_' . $section_key . '_headline',
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
			),
		);
	}

	public function test_result_holds_registered_count_and_skipped_keys(): void {
		$result = new Section_Scoped_Group_Registration_Result( 2, array( 'missing_key' ) );
		$this->assertSame( 2, $result->get_registered_count() );
		$this->assertSame( array( 'missing_key' ), $result->get_skipped_keys() );
		$this->assertTrue( $result->has_skipped() );
	}

	public function test_result_empty_skipped(): void {
		$result = new Section_Scoped_Group_Registration_Result( 1, array() );
		$this->assertFalse( $result->has_skipped() );
	}

	public function test_register_sections_with_result_deduplicates_keys(): void {
		$blueprint = $this->minimal_blueprint( 'st01_hero' );
		$mock      = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_blueprint_for_section' )->with( 'st01_hero' )->willReturn( $blueprint );
		$mock->expects( $this->never() )->method( 'get_all_blueprints' );
		$registrar = new ACF_Group_Registrar( $mock, new ACF_Group_Builder( new ACF_Field_Builder() ), null );
		$result    = $registrar->register_sections_with_result( array( 'st01_hero', 'st01_hero' ) );
		$this->assertSame( 1, $result->get_registered_count() );
		$this->assertEmpty( $result->get_skipped_keys() );
	}

	public function test_register_sections_with_result_skips_invalid_keys(): void {
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_blueprint_for_section' )->willReturnCallback(
			function ( string $key ) {
				return $key === 'st01_hero' ? $this->minimal_blueprint( $key ) : null;
			}
		);
		$mock->expects( $this->never() )->method( 'get_all_blueprints' );
		$registrar = new ACF_Group_Registrar( $mock, new ACF_Group_Builder( new ACF_Field_Builder() ), null );
		$result    = $registrar->register_sections_with_result( array( 'st01_hero', 'invalid_key', 'missing_section' ) );
		$this->assertSame( 1, $result->get_registered_count() );
		$this->assertSame( array( 'invalid_key', 'missing_section' ), $result->get_skipped_keys() );
	}

	public function test_register_sections_returns_same_count_as_result(): void {
		$blueprint = $this->minimal_blueprint( 'st01_hero' );
		$mock      = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_blueprint_for_section' )->willReturn( $blueprint );
		$mock->expects( $this->never() )->method( 'get_all_blueprints' );
		$registrar = new ACF_Group_Registrar( $mock, new ACF_Group_Builder( new ACF_Field_Builder() ), null );
		$count     = $registrar->register_sections( array( 'st01_hero' ) );
		$result    = $registrar->register_sections_with_result( array( 'st01_hero' ) );
		$this->assertSame( $result->get_registered_count(), $count );
	}
}
