<?php
/**
 * Unit tests for Template_Library_Export_Validator (Prompt 185, spec §52.2, §62.11, §62.12).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Validation\Template_Library_Export_Validator;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/vendor/autoload.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Validation/Template_Library_Export_Validator.php';

final class Template_Library_Export_Validator_Test extends TestCase {

	public function test_validate_skips_when_registries_and_compositions_not_included(): void {
		$validator = new Template_Library_Export_Validator( null, null );
		$bundle    = array(
			'registries' => array(
				'sections'       => array(),
				'page_templates' => array(),
				'compositions'   => array(),
			),
		);
		$summary   = $validator->validate( $bundle, array( 'settings', 'profiles' ) );
		$this->assertTrue( $summary['valid'] );
		$this->assertSame( 0, $summary['section_count'] );
		$this->assertArrayHasKey( 'log_reference', $summary );
	}

	public function test_validate_counts_sections_and_pages_when_registries_included(): void {
		$validator    = new Template_Library_Export_Validator( null, null );
		$section_frag = array(
			Registry_Export_Fragment_Builder::KEY_OBJECT_KEY => 'st_hero',
			Registry_Export_Fragment_Builder::KEY_PAYLOAD => array( Section_Schema::FIELD_INTERNAL_KEY => 'st_hero' ),
		);
		$page_frag    = array(
			Registry_Export_Fragment_Builder::KEY_OBJECT_KEY => 'pt_landing',
			Registry_Export_Fragment_Builder::KEY_PAYLOAD => array(
				Page_Template_Schema::FIELD_INTERNAL_KEY => 'pt_landing',
				Page_Template_Schema::FIELD_ONE_PAGER    => array( 'link' => 'https://example.com' ),
			),
		);
		$bundle       = array(
			'registries' => array(
				'sections'       => array( $section_frag ),
				'page_templates' => array( $page_frag ),
				'compositions'   => array(),
			),
		);
		$summary      = $validator->validate( $bundle, array( 'registries' ) );
		$this->assertTrue( $summary['valid'] );
		$this->assertSame( 1, $summary['section_count'] );
		$this->assertSame( 1, $summary['page_template_count'] );
		$this->assertSame( 1, $summary['one_pager_included_count'] );
		$this->assertSame( array(), $summary['one_pager_missing_keys'] );
	}

	public function test_validate_detects_missing_one_pager_metadata(): void {
		$validator = new Template_Library_Export_Validator( null, null );
		$page_frag = array(
			Registry_Export_Fragment_Builder::KEY_PAYLOAD => array(
				Page_Template_Schema::FIELD_INTERNAL_KEY => 'pt_missing_op',
				Page_Template_Schema::FIELD_ONE_PAGER    => array(),
			),
		);
		$bundle    = array(
			'registries' => array(
				'sections'       => array(),
				'page_templates' => array( $page_frag ),
				'compositions'   => array(),
			),
		);
		$summary   = $validator->validate( $bundle, array( 'registries' ) );
		$this->assertTrue( $summary['valid'] );
		$this->assertContains( 'pt_missing_op', $summary['one_pager_missing_keys'] );
		$this->assertNotEmpty( $summary['warnings'] );
	}

	public function test_validate_reports_error_when_section_fragment_missing_internal_key(): void {
		$validator    = new Template_Library_Export_Validator( null, null );
		$section_frag = array(
			Registry_Export_Fragment_Builder::KEY_PAYLOAD => array( 'name' => 'No key' ),
		);
		$bundle       = array(
			'registries' => array(
				'sections'       => array( $section_frag ),
				'page_templates' => array(),
				'compositions'   => array(),
			),
		);
		$summary      = $validator->validate( $bundle, array( 'registries' ) );
		$this->assertFalse( $summary['valid'] );
		$this->assertNotEmpty( $summary['errors'] );
	}

	public function test_example_export_summary_payload_structure(): void {
		$validator = new Template_Library_Export_Validator( null, null );
		$summary   = $validator->validate(
			array(
				'registries' => array(
					'sections'       => array(),
					'page_templates' => array(),
					'compositions'   => array(),
				),
			),
			array( 'registries' )
		);
		$this->assertArrayHasKey( 'valid', $summary );
		$this->assertArrayHasKey( 'section_count', $summary );
		$this->assertArrayHasKey( 'page_template_count', $summary );
		$this->assertArrayHasKey( 'one_pager_included_count', $summary );
		$this->assertArrayHasKey( 'one_pager_missing_keys', $summary );
		$this->assertArrayHasKey( 'appendix_regenerable', $summary );
		$this->assertArrayHasKey( 'errors', $summary );
		$this->assertArrayHasKey( 'warnings', $summary );
		$this->assertArrayHasKey( 'log_reference', $summary );
	}
}
