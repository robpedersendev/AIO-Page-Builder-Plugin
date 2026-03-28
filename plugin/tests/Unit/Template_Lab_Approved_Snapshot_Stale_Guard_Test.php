<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Stale_Guard;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

final class Template_Lab_Approved_Snapshot_Stale_Guard_Test extends TestCase {

	public function test_composition_fresh_when_sections_exist(): void {
		$sections = $this->createMock( Section_Template_Repository::class );
		$sections->method( 'get_definition_by_key' )->willReturn( array( 'internal_key' => 'st_hero' ) );
		$g    = new Template_Lab_Approved_Snapshot_Stale_Guard( $sections );
		$norm = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
		$this->assertSame( '', $g->registry_drift_reason( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION, $norm ) );
	}

	public function test_composition_stale_when_section_missing(): void {
		$sections = $this->createMock( Section_Template_Repository::class );
		$sections->method( 'get_definition_by_key' )->willReturn( null );
		$g    = new Template_Lab_Approved_Snapshot_Stale_Guard( $sections );
		$norm = array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'gone_section',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
		$this->assertStringContainsString( 'gone_section', $g->registry_drift_reason( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION, $norm ) );
	}
}
