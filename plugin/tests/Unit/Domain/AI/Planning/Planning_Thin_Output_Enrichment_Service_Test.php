<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Planning;

use AIOPageBuilder\Domain\AI\Planning\Planning_Breadth_Constants;
use AIOPageBuilder\Domain\AI\Planning\Planning_Thin_Output_Enrichment_Service;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Planning\Planning_Thin_Output_Enrichment_Service
 */
final class Planning_Thin_Output_Enrichment_Service_Test extends TestCase {

	public function test_enrich_adds_pages_from_registry_when_below_minimum(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array() );

		$svc = new Planning_Thin_Output_Enrichment_Service( $registry );

		$rec = array();
		for ( $i = 0; $i < 35; $i++ ) {
			$rec[] = array(
				'template_key' => 'pt_test_tpl_' . (string) $i,
				'name'         => 'Page ' . (string) $i,
			);
		}

		$normalized = $this->minimal_normalized( 'new_site', array() );
		$out        = $svc->enrich(
			$normalized,
			array(
				'crawl_empty'                     => true,
				'subtype_bundle_refs'             => array(),
				'template_recommendation_context' => $rec,
			)
		);

		$pages = $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ];
		$this->assertGreaterThanOrEqual( Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET, count( $pages ) );
		$keys = array_map(
			static function ( $p ) {
				return is_array( $p ) && isset( $p['template_key'] ) ? $p['template_key'] : '';
			},
			$pages
		);
		$this->assertContains( 'pt_test_tpl_0', $keys );
		$this->assertContains( 'pt_test_tpl_9', $keys );
	}

	public function test_enrich_noop_when_already_enough_pages(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array() );
		$svc = new Planning_Thin_Output_Enrichment_Service( $registry );

		$rows = array();
		for ( $i = 0; $i < Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET; $i++ ) {
			$rows[] = array(
				'proposed_page_title' => 'P' . (string) $i,
				'proposed_slug'       => 'p' . (string) $i,
				'purpose'             => 'x',
				'template_key'        => 'pt_x_' . (string) $i,
				'menu_eligible'       => true,
				'section_guidance'    => array(),
				'confidence'          => 'medium',
			);
		}
		$normalized = $this->minimal_normalized( 'new_site', $rows );
		$out        = $svc->enrich(
			$normalized,
			array(
				'crawl_empty'                     => true,
				'subtype_bundle_refs'             => array(),
				'template_recommendation_context' => array(
					array(
						'template_key' => 'pt_extra',
						'name'         => 'Extra',
					),
				),
			)
		);
		$this->assertCount( Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET, $out[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] );
	}

	/**
	 * @param list<array<string, mixed>> $new_pages
	 * @return array<string, mixed>
	 */
	private function minimal_normalized( string $mode, array $new_pages ): array {
		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => Build_Plan_Draft_Schema::SCHEMA_REF,
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY      => array(
				Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT       => 't',
				Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE      => $mode,
				Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE => 'low',
			),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array(),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array(),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE => $new_pages,
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => array(),
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array(),
		);
	}
}
