<?php
/**
 * Unit tests for Industry_Pack_Registry merge layering.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\Industry;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

\defined( 'ABSPATH' ) || \define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once \dirname( __DIR__, 3 ) . '/fixtures/industry-pack-registry-wp-stubs.php';

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';

final class IndustryPackRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['__aio_opts'] = array();
	}

	public function test_registry_merges_applied_bundle_over_builtin_deterministically(): void {
		$builtin = Industry_Pack_Registry::get_builtin_pack_definitions();
		$this->assertNotEmpty( $builtin, 'Expected builtin industry packs to exist.' );

		$first    = $builtin[0];
		$pack_key = (string) $first[ Industry_Pack_Schema::FIELD_INDUSTRY_KEY ];

		$bundle_id = 'bundle-1';
		$settings  = new Settings_Service();
		$settings->set(
			Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE,
			array( 'apply_order' => array( $bundle_id ) )
		);

		$overlay                                     = $first;
		$overlay[ Industry_Pack_Schema::FIELD_NAME ] = 'Overlay Name';
		update_option(
			'aio_pb_industry_bundle_payload_' . sanitize_key( $bundle_id ),
			array(
				\AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array(
					\AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service::PAYLOAD_PACKS,
				),
				\AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array( $overlay ),
			)
		);

		$registry = new Industry_Pack_Registry( new Industry_Pack_Validator(), $settings, null );
		$record   = $registry->get( $pack_key );

		$this->assertIsArray( $record );
		$this->assertSame( $pack_key, $record['pack_key'] );
		$this->assertSame( 'Overlay Name', $record['name'] );
		$this->assertSame( Industry_Pack_Registry::SOURCE_APPLIED, $record['source_type'] );

		$merge_order = $registry->merge_order();
		$this->assertSame( 'builtin', $merge_order[0]['layer'] );
		$this->assertSame( array( $bundle_id ), $merge_order[1]['refs'] );
	}
}
