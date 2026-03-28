<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Registry_Persist_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Template_Lab_Canonical_Registry_Persist_Service_Validation_Test extends TestCase {

	public function test_page_persist_skips_save_when_ordered_section_unknown(): void {
		$compositions      = new Composition_Repository();
		$page_templates    = new Page_Template_Repository();
		$section_templates = $this->createMock( Section_Template_Repository::class );
		$section_templates->method( 'get_by_key' )->willReturn( null );
		$svc = new Template_Lab_Canonical_Registry_Persist_Service( $compositions, $page_templates, $section_templates );
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => 'pt_x',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => 'no_such_section',
					Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
		$out = $svc->persist_definition( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE, $def );
		$this->assertSame( '', $out['internal_key'] ?? 'x' );
		$this->assertSame( 0, (int) ( $out['post_id'] ?? 1 ) );
	}

	public function test_section_persist_skips_save_when_semantics_invalid(): void {
		$compositions      = new Composition_Repository();
		$page_templates    = new Page_Template_Repository();
		$section_templates = $this->createMock( Section_Template_Repository::class );
		$section_templates->expects( $this->never() )->method( 'save_definition' );
		$svc = new Template_Lab_Canonical_Registry_Persist_Service( $compositions, $page_templates, $section_templates );
		$bad = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_bad',
			Section_Schema::FIELD_NAME         => 'Bad',
		);
		$out = $svc->persist_definition( Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_SECTION, $bad );
		$this->assertSame( '', $out['internal_key'] ?? 'x' );
		$this->assertSame( 0, (int) ( $out['post_id'] ?? 1 ) );
	}
}
