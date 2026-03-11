<?php
/**
 * Unit tests for Registry Export: deterministic serialization, prohibited fields, fixture validity, cross-reference preservation (Prompt 032).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Serializer;
use AIOPageBuilder\Domain\Registries\Fixtures\Registry_Fixture_Builder;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Snapshots/Version_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Serializer.php';
require_once $plugin_root . '/src/Domain/Registries/Fixtures/Registry_Fixture_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Documentation_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Version_Snapshot_Repository.php';

final class Registry_Export_Test extends TestCase {

	public function test_deterministic_serialization_section(): void {
		$section = Registry_Fixture_Builder::section();
		$f1 = Registry_Export_Fragment_Builder::for_section( $section );
		$f2 = Registry_Export_Fragment_Builder::for_section( $section );
		$this->assertSame( json_encode( $f1 ), json_encode( $f2 ) );
		$this->assertSame( Registry_Fixture_Builder::FIXTURE_SECTION_KEY, $f1[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
		$this->assertSame( 'section_template', $f1[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
	}

	public function test_deterministic_serialization_composition(): void {
		$comp = Registry_Fixture_Builder::composition();
		$f1 = Registry_Export_Fragment_Builder::for_composition( $comp );
		$f2 = Registry_Export_Fragment_Builder::for_composition( $comp );
		$this->assertSame( json_encode( $f1 ), json_encode( $f2 ) );
	}

	public function test_exclusion_of_prohibited_fields(): void {
		$payload = array( 'name' => 'Test', 'api_key' => 'secret123', 'internal_key' => 'st_test' );
		$sanitized = Registry_Export_Fragment_Builder::sanitize_payload( $payload );
		$this->assertArrayNotHasKey( 'api_key', $sanitized );
		$this->assertArrayHasKey( 'name', $sanitized );
		$this->assertArrayHasKey( 'internal_key', $sanitized );
	}

	public function test_prohibited_password_stripped(): void {
		$payload = array( 'password' => 'x', 'purpose_summary' => 'OK' );
		$sanitized = Registry_Export_Fragment_Builder::sanitize_payload( $payload );
		$this->assertArrayNotHasKey( 'password', $sanitized );
		$this->assertArrayHasKey( 'purpose_summary', $sanitized );
	}

	public function test_fixture_section_has_required_keys(): void {
		$section = Registry_Fixture_Builder::section();
		$required = Section_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $section, "Fixture section missing required: {$field}" );
		}
	}

	public function test_fixture_composition_has_required_keys(): void {
		$comp = Registry_Fixture_Builder::composition();
		$required = Composition_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $comp, "Fixture composition missing required: {$field}" );
		}
	}

	public function test_cross_reference_preservation_composition(): void {
		$comp = Registry_Fixture_Builder::composition();
		$frag = Registry_Export_Fragment_Builder::for_composition( $comp );
		$rels = $frag[ Registry_Export_Fragment_Builder::KEY_RELATIONSHIPS ];
		$this->assertArrayHasKey( 'section_keys', $rels );
		$this->assertContains( Registry_Fixture_Builder::FIXTURE_SECTION_KEY, $rels['section_keys'] );
		$this->assertSame( Registry_Fixture_Builder::FIXTURE_PAGE_TEMPLATE_KEY, $rels['source_template_ref'] );
	}

	public function test_cross_reference_preservation_page_template(): void {
		$pt = Registry_Fixture_Builder::page_template();
		$frag = Registry_Export_Fragment_Builder::for_page_template( $pt );
		$rels = $frag[ Registry_Export_Fragment_Builder::KEY_RELATIONSHIPS ];
		$this->assertArrayHasKey( 'section_keys', $rels );
		$this->assertContains( Registry_Fixture_Builder::FIXTURE_SECTION_KEY, $rels['section_keys'] );
	}

	public function test_fragment_has_stable_shape(): void {
		$section = Registry_Fixture_Builder::section();
		$frag = Registry_Export_Fragment_Builder::for_section( $section );
		$keys = array(
			Registry_Export_Fragment_Builder::KEY_EXPORT_SCHEMA_VERSION,
			Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE,
			Registry_Export_Fragment_Builder::KEY_OBJECT_KEY,
			Registry_Export_Fragment_Builder::KEY_OBJECT_STATUS,
			Registry_Export_Fragment_Builder::KEY_OBJECT_VERSION,
			Registry_Export_Fragment_Builder::KEY_PAYLOAD,
			Registry_Export_Fragment_Builder::KEY_RELATIONSHIPS,
			Registry_Export_Fragment_Builder::KEY_DEPRECATION,
			Registry_Export_Fragment_Builder::KEY_SOURCE_METADATA,
		);
		foreach ( $keys as $k ) {
			$this->assertArrayHasKey( $k, $frag, "Fragment missing stable key: {$k}" );
		}
	}

	public function test_serializer_build_registry_bundle_structure(): void {
		$GLOBALS['_aio_post_meta'] = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$serializer = new Registry_Export_Serializer(
			new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository()
		);
		$bundle = $serializer->build_registry_bundle( 10 );
		$this->assertArrayHasKey( 'registries', $bundle );
		$this->assertArrayHasKey( 'sections', $bundle['registries'] );
		$this->assertArrayHasKey( 'page_templates', $bundle['registries'] );
		$this->assertArrayHasKey( 'compositions', $bundle['registries'] );
		$this->assertIsArray( $bundle['registries']['sections'] );
		$this->assertIsArray( $bundle['registries']['page_templates'] );
		$this->assertIsArray( $bundle['registries']['compositions'] );
	}

	public function test_serializer_manifest_fragment(): void {
		$serializer = new Registry_Export_Serializer(
			new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Documentation_Repository(),
			new \AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository()
		);
		$manifest = $serializer->build_manifest_fragment();
		$this->assertArrayHasKey( 'export_schema_version', $manifest );
		$this->assertArrayHasKey( 'object_types', $manifest );
		$this->assertContains( 'section_template', $manifest['object_types'] );
		$this->assertContains( 'composition', $manifest['object_types'] );
	}

	public function test_fixture_full_bundle_keys(): void {
		$bundle = Registry_Fixture_Builder::full_bundle();
		$this->assertArrayHasKey( 'sections', $bundle );
		$this->assertArrayHasKey( 'page_templates', $bundle );
		$this->assertArrayHasKey( 'compositions', $bundle );
		$this->assertArrayHasKey( 'documentation', $bundle );
		$this->assertArrayHasKey( 'snapshots', $bundle );
		$this->assertCount( 1, $bundle['sections'] );
		$this->assertSame( Registry_Fixture_Builder::FIXTURE_SECTION_KEY, $bundle['sections'][0][ Section_Schema::FIELD_INTERNAL_KEY ] );
	}

	public function test_deprecation_extracted_when_present(): void {
		$section = Registry_Fixture_Builder::section();
		$section['deprecation'] = array( 'deprecated' => true, 'reason' => 'Superseded', 'replacement_section_key' => 'st02_new' );
		$frag = Registry_Export_Fragment_Builder::for_section( $section );
		$dep = $frag[ Registry_Export_Fragment_Builder::KEY_DEPRECATION ];
		$this->assertTrue( $dep['deprecated'] );
		$this->assertSame( 'Superseded', $dep['reason'] );
		$this->assertSame( 'st02_new', $dep['replacement_section_key'] );
	}
}
