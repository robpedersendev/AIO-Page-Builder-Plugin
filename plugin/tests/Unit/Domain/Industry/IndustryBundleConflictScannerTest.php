<?php
/**
 * Unit tests for Industry_Bundle_Conflict_Scanner.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\Industry;

use AIOPageBuilder\Domain\Industry\Export\Industry_Pack_Bundle_Service;
use AIOPageBuilder\Domain\Industry\Import\Industry_Bundle_Conflict_Scanner;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use PHPUnit\Framework\TestCase;

\defined( 'ABSPATH' ) || \define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once \dirname( __DIR__, 3 ) . '/fixtures/industry-wp-sanitize-json-stub.php';

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Domain/Industry/Export/Industry_Pack_Bundle_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Import/Industry_Bundle_Conflict_Scanner.php';

final class IndustryBundleConflictScannerTest extends TestCase {

	public function test_scan_reports_conflict_when_same_key_has_different_hash(): void {
		$scanner = new Industry_Bundle_Conflict_Scanner();

		$local_item    = array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'plumber',
			Industry_Pack_Schema::FIELD_NAME           => 'Plumber',
			Industry_Pack_Schema::FIELD_SUMMARY        => 'A',
			Industry_Pack_Schema::FIELD_STATUS         => 'active',
			Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
		);
		$incoming_item = $local_item;
		$incoming_item[ Industry_Pack_Schema::FIELD_SUMMARY ] = 'B';

		$local_hashes = array(
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array(
				'plumber' => $scanner->content_hash( $local_item ),
			),
		);

		$bundle = array(
			Industry_Pack_Bundle_Service::MANIFEST_INCLUDED_CATEGORIES => array( Industry_Pack_Bundle_Service::PAYLOAD_PACKS ),
			Industry_Pack_Bundle_Service::PAYLOAD_PACKS => array( $incoming_item ),
		);

		$conflicts = $scanner->scan( $bundle, $local_hashes );
		$this->assertCount( 1, $conflicts );
		$this->assertSame( Industry_Pack_Bundle_Service::PAYLOAD_PACKS, $conflicts[0]['category'] );
		$this->assertSame( 'plumber', $conflicts[0]['object_key'] );
	}
}
