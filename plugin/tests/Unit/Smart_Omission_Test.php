<?php
/**
 * Unit tests for smart omission rendering (Prompt 174, smart-omission-rendering-contract).
 * Covers eligible omission, refused omission (required, structural heading, primary CTA), wrapper collapse, and example payloads.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Omission\Omission_Result;
use AIOPageBuilder\Domain\Rendering\Omission\Smart_Omission_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Omission/Omission_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Omission/Smart_Omission_Service.php';

final class Smart_Omission_Test extends TestCase {

	private function create_service(): Smart_Omission_Service {
		return new Smart_Omission_Service();
	}

	public function test_omission_result_holds_omitted_refused_fallbacks(): void {
		$result = new Omission_Result(
			array( 'eyebrow', 'subheadline' ),
			array( 'headline' => 'structural_heading' ),
			array( 'headline' => 'Untitled' )
		);
		$this->assertTrue( $result->was_omitted( 'eyebrow' ) );
		$this->assertTrue( $result->was_omitted( 'subheadline' ) );
		$this->assertFalse( $result->was_omitted( 'headline' ) );
		$this->assertTrue( $result->was_refused( 'headline' ) );
		$this->assertSame( 'structural_heading', $result->get_refusal_reason( 'headline' ) );
		$this->assertSame( 'Untitled', $result->get_fallbacks_applied()['headline'] ?? '' );
	}

	public function test_eligible_omission_optional_empty_omitted(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline'    => 'Welcome',
			'subheadline' => '',
			'eyebrow'     => '',
			'intro'       => 'Some intro',
		);
		$eligibility  = array(
			'headline'    => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'subheadline' => array(
				'optional' => true,
				'role'     => 'subheadline',
			),
			'eyebrow'     => array(
				'optional' => true,
				'role'     => 'eyebrow',
			),
			'intro'       => array(
				'optional' => true,
				'role'     => 'intro',
			),
		);
		$context      = array(
			'section_key' => 'hero_01',
			'position'    => 1,
			'supplies_h1' => false,
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );

		$fv = $applied['field_values'];
		$this->assertArrayHasKey( 'headline', $fv );
		$this->assertArrayHasKey( 'intro', $fv );
		$this->assertArrayNotHasKey( 'subheadline', $fv );
		$this->assertArrayNotHasKey( 'eyebrow', $fv );
		$om = $applied['omission_result'];
		$this->assertTrue( $om->was_omitted( 'subheadline' ) );
		$this->assertTrue( $om->was_omitted( 'eyebrow' ) );
	}

	/**
	 * Example omission result payload (real structure): optional subheadline and eyebrow omitted; headline and intro kept.
	 */
	public function test_example_omission_result_payload(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline'    => 'Our Services',
			'subheadline' => '',
			'eyebrow'     => '',
			'cta'         => array(
				'url'   => '#',
				'title' => 'Learn more',
			),
		);
		$eligibility  = array(
			'headline'    => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'subheadline' => array(
				'optional' => true,
				'role'     => 'subheadline',
			),
			'eyebrow'     => array(
				'optional' => true,
				'role'     => 'eyebrow',
			),
			'cta'         => array(
				'optional' => true,
				'role'     => 'cta',
			),
		);
		$context      = array(
			'section_key' => 'hero_02',
			'position'    => 0,
			'supplies_h1' => true,
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );

		$payload = $applied['omission_result']->to_array();
		$this->assertArrayHasKey( 'omitted_keys', $payload );
		$this->assertArrayHasKey( 'refused', $payload );
		$this->assertArrayHasKey( 'fallbacks_applied', $payload );
		$this->assertContains( 'subheadline', $payload['omitted_keys'] );
		$this->assertContains( 'eyebrow', $payload['omitted_keys'] );
		$this->assertArrayNotHasKey( 'headline', $payload['omitted_keys'] );
		$this->assertSame(
			array(
				'headline' => 'Our Services',
				'cta'      => array(
					'url'   => '#',
					'title' => 'Learn more',
				),
			),
			$applied['field_values']
		);
	}

	/**
	 * Refused-omission case payload: required headline empty → refused, fallback applied; primary CTA empty in CTA section → refused, fallback applied.
	 */
	public function test_refused_omission_case_payload(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline'    => '',
			'subheadline' => '',
			'primary_cta' => array(),
		);
		$eligibility  = array(
			'headline'    => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'subheadline' => array(
				'optional' => true,
				'role'     => 'subheadline',
			),
			'primary_cta' => array(
				'optional' => true,
				'role'     => 'cta',
			),
		);
		$context      = array(
			'section_key'       => 'cta_hero',
			'position'          => 0,
			'is_cta_classified' => true,
			'supplies_h1'       => true,
			'primary_cta_key'   => 'primary_cta',
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );

		$om = $applied['omission_result'];
		$this->assertTrue( $om->was_refused( 'headline' ) );
		$this->assertSame( 'required', $om->get_refusal_reason( 'headline' ) );
		$this->assertTrue( $om->was_refused( 'primary_cta' ) );
		$this->assertSame( 'primary_cta', $om->get_refusal_reason( 'primary_cta' ) );
		$this->assertTrue( $om->was_omitted( 'subheadline' ) );
		$fv = $applied['field_values'];
		$this->assertSame( 'Untitled', $fv['headline'] ?? '' );
		$this->assertSame( 'Learn more', $fv['primary_cta'] ?? '' );
	}

	public function test_structural_heading_refused_when_supplies_h1(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline'    => '',
			'subheadline' => '',
		);
		$eligibility  = array(
			'headline'    => array(
				'optional' => true,
				'role'     => 'headline',
			),
			'subheadline' => array(
				'optional' => true,
				'role'     => 'subheadline',
			),
		);
		$context      = array(
			'section_key' => 'hero',
			'position'    => 0,
			'supplies_h1' => true,
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );
		$om           = $applied['omission_result'];
		$this->assertTrue( $om->was_refused( 'headline' ) );
		$this->assertSame( 'structural_heading', $om->get_refusal_reason( 'headline' ) );
		$this->assertSame( 'Untitled', $applied['field_values']['headline'] ?? '' );
	}

	public function test_primary_cta_preserved_in_cta_section(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline' => 'Title',
			'cta'      => '',
		);
		$eligibility  = array(
			'headline' => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'cta'      => array(
				'optional' => true,
				'role'     => 'cta',
			),
		);
		$context      = array(
			'section_key'       => 'cta_sec',
			'is_cta_classified' => true,
			'primary_cta_key'   => 'cta',
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );
		$this->assertArrayHasKey( 'cta', $applied['field_values'] );
		$this->assertSame( 'Learn more', $applied['field_values']['cta'] );
		$this->assertTrue( $applied['omission_result']->was_refused( 'cta' ) );
	}

	public function test_repeater_empty_omitted_when_optional(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline' => 'Proof',
			'cards'    => array(),
		);
		$eligibility  = array(
			'headline' => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'cards'    => array(
				'optional' => true,
				'role'     => 'cards',
			),
		);
		$context      = array( 'section_key' => 'proof_01' );
		$applied      = $service->apply( $field_values, $eligibility, $context );
		$this->assertArrayNotHasKey( 'cards', $applied['field_values'] );
		$this->assertTrue( $applied['omission_result']->was_omitted( 'cards' ) );
	}

	public function test_eligibility_from_blueprint(): void {
		$service   = $this->create_service();
		$blueprint = array(
			'fields' => array(
				array(
					'name'     => 'headline',
					'required' => true,
				),
				array(
					'name'     => 'eyebrow',
					'required' => false,
				),
			),
		);
		$el        = $service->eligibility_from_blueprint( $blueprint );
		$this->assertFalse( $el['headline']['optional'] );
		$this->assertTrue( $el['eyebrow']['optional'] );
		$this->assertSame( 'headline', $el['headline']['role'] );
		$this->assertSame( 'eyebrow', $el['eyebrow']['role'] );
	}

	public function test_required_field_never_omitted(): void {
		$service      = $this->create_service();
		$field_values = array(
			'headline' => '',
			'intro'    => '',
		);
		$eligibility  = array(
			'headline' => array(
				'optional' => false,
				'role'     => 'headline',
			),
			'intro'    => array(
				'optional' => false,
				'role'     => 'intro',
			),
		);
		$context      = array(
			'section_key' => 's1',
			'supplies_h1' => false,
		);
		$applied      = $service->apply( $field_values, $eligibility, $context );
		$this->assertArrayHasKey( 'headline', $applied['field_values'] );
		$this->assertArrayHasKey( 'intro', $applied['field_values'] );
		$this->assertSame( 'Untitled', $applied['field_values']['headline'] );
		$this->assertSame( '—', $applied['field_values']['intro'] );
	}
}
