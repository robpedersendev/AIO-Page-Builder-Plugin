<?php
/**
 * Unit tests for Industry_Bundle_Apply_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\Industry;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Import\Industry_Bundle_Apply_Service;
use AIOPageBuilder\Domain\Industry\Import\Industry_Bundle_Conflict_Scanner;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

\defined( 'ABSPATH' ) || \define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once \dirname( __DIR__, 3 ) . '/fixtures/industry-bundle-apply-wp-stubs.php';

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Import/Industry_Bundle_Conflict_Scanner.php';
require_once $plugin_root . '/src/Domain/Industry/Import/Industry_Bundle_Apply_Service.php';

final class IndustryBundleApplyServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['__aio_opts'] = array();
	}

	public function test_apply_rejects_when_conflicts_exist_and_no_decisions_given(): void {
		$settings = new Settings_Service();
		$apply    = new Industry_Bundle_Apply_Service( $settings, new Industry_Bundle_Conflict_Scanner() );

		$item_local    = array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'plumber',
			Industry_Pack_Schema::FIELD_NAME           => 'Plumber',
			Industry_Pack_Schema::FIELD_SUMMARY        => 'A',
			Industry_Pack_Schema::FIELD_STATUS         => 'active',
			Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
		);
		$item_incoming = $item_local;
		$item_incoming[ Industry_Pack_Schema::FIELD_SUMMARY ] = 'B';

		// Seed local hashes by applying a synthetic prior payload.
		update_option(
			'aio_pb_industry_bundle_payload_prev',
			array(
				Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array( Industry_Pack_Bundle_Service::PAYLOAD_PACKS ),
				Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array( $item_local ),
			)
		);
		$settings->set( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE, array( 'apply_order' => array( 'prev' ) ) );

		$bundle = array(
			Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array( Industry_Pack_Bundle_Service::PAYLOAD_PACKS ),
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array( $item_incoming ),
			Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION => '1',
			Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION => '1',
			Industry_Pack_Bundle_Service::MANIFEST_CREATED_AT => gmdate( 'c' ),
		);

		$result = $apply->apply( $bundle, 'test', Industry_Bundle_Apply_Service::SCOPE_FULL_SITE_PACKAGE, array(), 1 );
		$this->assertFalse( $result['ok'] );
	}

	public function test_apply_stores_payload_and_merge_state(): void {
		$settings = new Settings_Service();
		$apply    = new Industry_Bundle_Apply_Service( $settings, new Industry_Bundle_Conflict_Scanner() );
		$settings->set( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE, array() );

		$item   = array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'custompack',
			Industry_Pack_Schema::FIELD_NAME           => 'Custom Pack',
			Industry_Pack_Schema::FIELD_SUMMARY        => 'A',
			Industry_Pack_Schema::FIELD_STATUS         => 'active',
			Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
		);
		$bundle = array(
			Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array( Industry_Pack_Bundle_Service::PAYLOAD_PACKS ),
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array( $item ),
			Industry_Pack_Bundle_Service::MANIFEST_BUNDLE_VERSION => '1',
			Industry_Pack_Bundle_Service::MANIFEST_SCHEMA_VERSION => '1',
			Industry_Pack_Bundle_Service::MANIFEST_CREATED_AT => gmdate( 'c' ),
		);

		$result = $apply->apply( $bundle, 'test', Industry_Bundle_Apply_Service::SCOPE_FULL_SITE_PACKAGE, array(), 1 );
		$this->assertTrue( $result['ok'] );

		$merge_state = $settings->get( Option_Names::PB_INDUSTRY_BUNDLE_MERGE_STATE );
		$this->assertArrayHasKey( 'apply_order', $merge_state );
		$this->assertCount( 1, $merge_state['apply_order'] );
		$this->assertSame( $result['bundle_id'], $merge_state['apply_order'][0] );

		$payload = get_option( 'aio_pb_industry_bundle_payload_' . sanitize_key( $result['bundle_id'] ), array() );
		$this->assertIsArray( $payload );
		$this->assertNotEmpty( $payload[ Industry_Pack_Bundle_Service::PAYLOAD_PACKS ] );
	}
}
