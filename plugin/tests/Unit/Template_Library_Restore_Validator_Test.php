<?php
/**
 * Unit tests for Template_Library_Restore_Validator (Prompt 185, spec §52.8, §62.11, §62.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Validation\Template_Library_Restore_Validator;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/vendor/autoload.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Validation/Template_Library_Restore_Validator.php';

final class Template_Library_Restore_Validator_Test extends TestCase {

	private function make_validator(): Template_Library_Restore_Validator {
		$section_repo = new Section_Template_Repository();
		$page_repo    = new Page_Template_Repository();
		$comp_repo    = new Composition_Repository();
		return new Template_Library_Restore_Validator( $section_repo, $page_repo, $comp_repo, null, null );
	}

	public function test_validate_empty_restored_returns_valid_summary(): void {
		$validator = $this->make_validator();
		$summary = $validator->validate( array(), array() );
		$this->assertTrue( $summary['valid'] );
		$this->assertTrue( $summary['restore_order_ok'] );
		$this->assertSame( 0, $summary['section_count'] );
		$this->assertSame( 0, $summary['page_template_count'] );
		$this->assertSame( 0, $summary['composition_count'] );
		$this->assertArrayHasKey( 'log_reference', $summary );
	}

	public function test_validate_restore_order_ok_when_categories_in_order(): void {
		$validator = $this->make_validator();
		$manifest = array( 'included_categories' => array( 'settings', 'profiles', 'registries', 'compositions' ) );
		$summary = $validator->validate( array( 'settings', 'profiles', 'registries', 'compositions' ), $manifest );
		$this->assertTrue( $summary['restore_order_ok'] );
	}

	public function test_validate_includes_warning_when_registries_in_manifest_but_not_restored(): void {
		$validator = $this->make_validator();
		$manifest = array( 'included_categories' => array( 'registries' ) );
		$summary = $validator->validate( array( 'settings' ), $manifest );
		$this->assertNotEmpty( $summary['warnings'] );
		$joined = implode( ' ', $summary['warnings'] );
		$this->assertStringContainsString( 'Registries', $joined );
	}

	public function test_example_restore_summary_payload_structure(): void {
		$validator = $this->make_validator();
		$summary = $validator->validate( array(), array( 'included_categories' => array() ) );
		$this->assertArrayHasKey( 'valid', $summary );
		$this->assertArrayHasKey( 'restore_order_ok', $summary );
		$this->assertArrayHasKey( 'section_count', $summary );
		$this->assertArrayHasKey( 'page_template_count', $summary );
		$this->assertArrayHasKey( 'composition_count', $summary );
		$this->assertArrayHasKey( 'appendix_regenerable', $summary );
		$this->assertArrayHasKey( 'errors', $summary );
		$this->assertArrayHasKey( 'warnings', $summary );
		$this->assertArrayHasKey( 'log_reference', $summary );
	}
}
