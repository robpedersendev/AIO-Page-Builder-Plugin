<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\BuildPlan\Lineage;

use AIOPageBuilder\Domain\BuildPlan\Lineage\Existing_Page_Lineage_Template_Drift_Advisor;
use AIOPageBuilder\Domain\BuildPlan\Lineage\Lineage_Previous_Version_Resolver_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Bulk_Action_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\BuildPlan\Lineage\Existing_Page_Lineage_Template_Drift_Advisor
 */
final class Existing_Page_Lineage_Template_Drift_Advisor_Test extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private function minimal_prior_definition( string $home_url, string $template_key ): array {
		return array(
			Build_Plan_Schema::KEY_STEPS => array(
				0                            => array(),
				Existing_Page_Update_Bulk_Action_Service::STEP_INDEX_EXISTING_PAGE_CHANGES => array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_PAYLOAD => array(
								'current_page_url' => $home_url,
								'template_key'     => $template_key,
							),
						),
					),
				),
			),
		);
	}

	public function test_note_empty_when_first_version(): void {
		$lineage = $this->createMock( Lineage_Previous_Version_Resolver_Interface::class );
		$repo    = $this->createMock( Build_Plan_Repository_Interface::class );
		$adv     = new Existing_Page_Lineage_Template_Drift_Advisor( $lineage, $repo );
		$def     = array(
			Build_Plan_Schema::KEY_PLAN_LINEAGE_ID   => 'lid-1',
			Build_Plan_Schema::KEY_PLAN_VERSION_SEQ  => 1,
		);
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'current_page_url' => 'https://example.com/',
				'template_key'     => 'tpl-b',
			),
		);
		$this->assertSame( '', $adv->note_for_item( $def, $item ) );
	}

	public function test_note_when_prior_recommended_different_template(): void {
		$lineage = $this->createMock( Lineage_Previous_Version_Resolver_Interface::class );
		$lineage->method( 'get_previous_version_post_id' )->with( 'lid-1', 2 )->willReturn( 42 );
		$repo = $this->createMock( Build_Plan_Repository_Interface::class );
		$repo->method( 'get_plan_definition' )->with( 42 )->willReturn(
			$this->minimal_prior_definition( 'https://example.com/', 'home-template-a' )
		);
		$adv = new Existing_Page_Lineage_Template_Drift_Advisor( $lineage, $repo );
		$def = array(
			Build_Plan_Schema::KEY_PLAN_LINEAGE_ID  => 'lid-1',
			Build_Plan_Schema::KEY_PLAN_VERSION_SEQ => 2,
		);
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'current_page_url' => 'https://example.com/',
				'template_key'     => 'home-template-b',
			),
		);
		$note = $adv->note_for_item( $def, $item );
		$this->assertStringContainsString( 'home-template-a', $note );
		$this->assertStringContainsString( 'home-template-b', $note );
	}

	public function test_note_empty_when_same_template_as_prior(): void {
		$lineage = $this->createMock( Lineage_Previous_Version_Resolver_Interface::class );
		$lineage->method( 'get_previous_version_post_id' )->willReturn( 42 );
		$repo = $this->createMock( Build_Plan_Repository_Interface::class );
		$repo->method( 'get_plan_definition' )->willReturn(
			$this->minimal_prior_definition( 'https://example.com/', 'same-tpl' )
		);
		$adv = new Existing_Page_Lineage_Template_Drift_Advisor( $lineage, $repo );
		$def = array(
			Build_Plan_Schema::KEY_PLAN_LINEAGE_ID  => 'lid-1',
			Build_Plan_Schema::KEY_PLAN_VERSION_SEQ => 2,
		);
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'current_page_url' => 'https://example.com/',
				'template_key'     => 'same-tpl',
			),
		);
		$this->assertSame( '', $adv->note_for_item( $def, $item ) );
	}
}
