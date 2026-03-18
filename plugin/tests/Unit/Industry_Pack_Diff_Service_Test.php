<?php
/**
 * Unit tests for Industry_Pack_Diff_Service (Prompt 418).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Diff_Result;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Diff_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Pack_Diff_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Pack_Diff_Service.php';

final class Industry_Pack_Diff_Service_Test extends TestCase {

	private function pack( string $key, string $status = 'active', string $version = '1', array $extra = array() ): array {
		return array_merge(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => $key,
				Industry_Pack_Schema::FIELD_NAME           => $key,
				Industry_Pack_Schema::FIELD_SUMMARY        => 'Summary',
				Industry_Pack_Schema::FIELD_STATUS         => $status,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => $version,
			),
			$extra
		);
	}

	public function test_unchanged_packs_produce_empty_diff(): void {
		$packs   = array(
			$this->pack( 'realtor' ),
			$this->pack( 'plumber' ),
		);
		$service = new Industry_Pack_Diff_Service();
		$result  = $service->diff( $packs, $packs );
		$this->assertInstanceOf( Industry_Pack_Diff_Result::class, $result );
		$this->assertSame( array(), $result->get_added() );
		$this->assertSame( array(), $result->get_removed() );
		$this->assertSame( array(), $result->get_changed() );
		$this->assertSame( 0, $result->get_summary()['added_count'] );
		$this->assertSame( 0, $result->get_summary()['removed_count'] );
		$this->assertSame( 0, $result->get_summary()['changed_count'] );
		$this->assertSame( 'none', $result->get_summary()['impact_level'] );
	}

	public function test_added_and_removed_packs_reflected(): void {
		$left    = array( $this->pack( 'realtor' ) );
		$right   = array( $this->pack( 'realtor' ), $this->pack( 'plumber' ), $this->pack( 'cosmetology_nail' ) );
		$service = new Industry_Pack_Diff_Service();
		$result  = $service->diff( $left, $right );
		$this->assertCount( 2, $result->get_added() );
		$this->assertContains( 'plumber', $result->get_added() );
		$this->assertContains( 'cosmetology_nail', $result->get_added() );
		$this->assertSame( array(), $result->get_removed() );
		$this->assertSame( 2, $result->get_summary()['added_count'] );

		$result2 = $service->diff( $right, $left );
		$this->assertSame( array(), $result2->get_added() );
		$this->assertCount( 2, $result2->get_removed() );
		$this->assertSame( 2, $result2->get_summary()['removed_count'] );
	}

	public function test_status_and_ref_changes_produce_changed_entry(): void {
		$left    = array( $this->pack( 'realtor', 'active', '1', array( Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF => 'realtor_warm' ) ) );
		$right   = array(
			$this->pack(
				'realtor',
				'deprecated',
				'1',
				array(
					Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF => 'realtor_v2',
					Industry_Pack_Schema::FIELD_REPLACEMENT_REF  => 'realtor_v2',
				)
			),
		);
		$service = new Industry_Pack_Diff_Service();
		$result  = $service->diff( $left, $right );
		$this->assertSame( array(), $result->get_added() );
		$this->assertSame( array(), $result->get_removed() );
		$this->assertCount( 1, $result->get_changed() );
		$ch = $result->get_changed()[0];
		$this->assertSame( 'realtor', $ch['industry_key'] );
		$this->assertSame(
			array(
				'from' => 'active',
				'to'   => 'deprecated',
			),
			$ch['status_change']
		);
		$this->assertArrayHasKey( 'token_preset_ref', $ch['refs_changed'] ?? array() );
		$this->assertSame( 1, $result->get_summary()['changed_count'] );
	}

	public function test_invalid_entries_skipped_with_notes(): void {
		$left    = array( array( 'no_key' => true ), $this->pack( 'realtor' ) );
		$right   = array( $this->pack( 'realtor' ) );
		$service = new Industry_Pack_Diff_Service();
		$result  = $service->diff( $left, $right );
		$this->assertSame( array(), $result->get_added() );
		$this->assertSame( array(), $result->get_removed() );
		$this->assertNotEmpty( $result->get_notes() );
	}

	public function test_to_array_contains_all_fields(): void {
		$service = new Industry_Pack_Diff_Service();
		$result  = $service->diff(
			array( $this->pack( 'a' ) ),
			array( $this->pack( 'b' ) ),
			array(
				'left_label'  => 'L',
				'right_label' => 'R',
			)
		);
		$arr     = $result->to_array();
		$this->assertArrayHasKey( 'compared_at', $arr );
		$this->assertArrayHasKey( 'left_label', $arr );
		$this->assertArrayHasKey( 'right_label', $arr );
		$this->assertArrayHasKey( 'added', $arr );
		$this->assertArrayHasKey( 'removed', $arr );
		$this->assertArrayHasKey( 'changed', $arr );
		$this->assertArrayHasKey( 'summary', $arr );
		$this->assertSame( 'L', $arr['left_label'] );
		$this->assertSame( 'R', $arr['right_label'] );
	}
}
