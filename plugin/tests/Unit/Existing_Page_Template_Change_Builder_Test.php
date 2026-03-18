<?php
/**
 * Unit tests for Existing_Page_Template_Change_Builder: replacement/update summaries,
 * template-family visibility, section-family summaries (Prompt 193, spec §32, §32.7).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Existing_Page_Template_Change_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Recommendations/Template_Explanation_Builder_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Existing_Page_Template_Change_Builder.php';

/**
 * Stub explanation builder for existing-page template change tests.
 */
class Existing_Page_Template_Change_Builder_Test_Stub implements \AIOPageBuilder\Domain\BuildPlan\Recommendations\Template_Explanation_Builder_Interface {
	public function build_explanation( string $template_key, array $item_payload = array() ): array {
		if ( $template_key === '' ) {
			return array();
		}
		return array(
			'template_key'            => $template_key,
			'name'                    => 'Services hub',
			'template_family'         => 'services',
			'template_category_class' => 'hub',
			'cta_direction_summary'   => 'Contact, request quote',
			'section_count'           => 8,
			'deprecation_status'      => 'active',
		);
	}
}

final class Existing_Page_Template_Change_Builder_Test extends TestCase {

	private Existing_Page_Template_Change_Builder $builder;

	protected function setUp(): void {
		parent::setUp();
		$this->builder = new Existing_Page_Template_Change_Builder( new Existing_Page_Template_Change_Builder_Test_Stub() );
	}

	public function test_build_for_item_returns_required_keys(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action'       => 'rebuild_from_template',
				'template_key' => 'pt_services_01',
				'reason'       => 'Align with new structure',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertArrayHasKey( Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY, $out );
		$this->assertArrayHasKey( Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY, $out );
	}

	public function test_replacement_reason_summary_full_replacement(): void {
		$item   = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action' => 'replace_with_new_page',
				'reason' => 'Full refresh needed',
			),
		);
		$out    = $this->builder->build_for_item( $item );
		$reason = $out[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ];
		$this->assertTrue( $reason['is_replacement'] );
		$this->assertFalse( $reason['is_rebuild'] );
		$this->assertSame( 'replace_with_new_page', $reason['action'] );
		$this->assertStringContainsString( 'replacement', $reason['action_label'] );
	}

	public function test_replacement_reason_summary_in_place_rebuild(): void {
		$item   = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action' => 'rebuild_from_template',
				'reason' => 'Update sections only',
			),
		);
		$out    = $this->builder->build_for_item( $item );
		$reason = $out[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ];
		$this->assertFalse( $reason['is_replacement'] );
		$this->assertTrue( $reason['is_rebuild'] );
		$this->assertStringContainsString( 'rebuild', $reason['action_label'] );
	}

	public function test_existing_page_template_change_summary_has_family_and_cta(): void {
		$item    = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action'       => 'rebuild_from_template',
				'template_key' => 'pt_services_01',
				'reason'       => 'Test',
			),
		);
		$out     = $this->builder->build_for_item( $item );
		$summary = $out[ Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY ];
		$this->assertSame( 'pt_services_01', $summary['template_key'] );
		$this->assertSame( 'services', $summary['template_family'] );
		$this->assertSame( 'hub', $summary['template_category_class'] );
		$this->assertSame( 'Contact, request quote', $summary['cta_direction_summary'] );
		$this->assertSame( 8, $summary['section_count'] );
	}

	public function test_empty_template_key_produces_empty_template_summary(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action' => 'keep',
				'reason' => 'No change',
			),
		);
		$out  = $this->builder->build_for_item( $item );
		$this->assertSame( array(), $out[ Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY ] );
		$reason = $out[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ];
		$this->assertFalse( $reason['is_replacement'] );
		$this->assertFalse( $reason['is_rebuild'] );
	}

	public function test_reason_short_truncated_when_long(): void {
		$long   = str_repeat( 'a', 200 );
		$item   = array(
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'action' => 'replace_with_new_page',
				'reason' => $long,
			),
		);
		$out    = $this->builder->build_for_item( $item );
		$reason = $out[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ];
		$this->assertLessThanOrEqual( 120, strlen( $reason['reason_short'] ) );
		$this->assertStringEndsWith( '...', $reason['reason_short'] );
	}
}
