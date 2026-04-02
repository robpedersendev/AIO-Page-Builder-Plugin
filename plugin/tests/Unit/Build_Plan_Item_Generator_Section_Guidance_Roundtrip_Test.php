<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Ensures JSON-shaped section_guidance strings survive full-definition encoding (spec §30.3 NPC payloads).
 */
final class Build_Plan_Item_Generator_Section_Guidance_Roundtrip_Test extends TestCase {

	public function test_json_like_section_guidance_round_trips_in_full_definition_encode(): void {
		$generator = new Build_Plan_Item_Generator();
		$records   = array(
			array(
				'proposed_page_title' => 'About',
				'proposed_slug'       => 'about',
				'purpose'             => 'Info',
				'template_key'        => 'page_default',
				'menu_eligible'       => true,
				'section_guidance'    => '[{"section_type":"hero","content_focus":"intro"}]',
				'confidence'          => 'high',
			),
		);
		$out       = $generator->generate_for_section(
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE,
			$records,
			'plan_sg_rt'
		);
		$this->assertCount( 1, $out['items'] );
		$item    = $out['items'][0];
		$payload = $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ?? null;
		$this->assertIsArray( $payload );
		$sg = $payload['section_guidance'] ?? null;
		$this->assertIsArray( $sg );
		$this->assertSame( 'hero', (string) ( $sg[0]['section_type'] ?? '' ) );

		$definition = array(
			Build_Plan_Schema::KEY_PLAN_ID    => 'plan_sg_rt',
			Build_Plan_Schema::KEY_STATUS     => 'pending_review',
			Build_Plan_Schema::KEY_PLAN_TITLE => 'T',
			Build_Plan_Schema::KEY_STEPS      => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 's_npc',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
					Build_Plan_Item_Schema::KEY_TITLE     => 'New pages',
					Build_Plan_Item_Schema::KEY_ORDER     => 0,
					Build_Plan_Item_Schema::KEY_ITEMS     => $out['items'],
				),
			),
		);
		$json       = \wp_json_encode( $definition );
		$this->assertIsString( $json );
		$round = \json_decode( $json, true );
		$this->assertIsArray( $round );
		$items = $round[ Build_Plan_Schema::KEY_STEPS ][0][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? null;
		$this->assertIsArray( $items );
		$sg2 = $items[0][ Build_Plan_Item_Schema::KEY_PAYLOAD ]['section_guidance'] ?? null;
		$this->assertIsArray( $sg2 );
		$this->assertSame( 'hero', (string) ( $sg2[0]['section_type'] ?? '' ) );
	}
}
