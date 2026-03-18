<?php
/**
 * Unit tests for GenerateBlocks_Mapping_Rules: eligibility, unsupported patterns, allowed blocks (spec §7.2, §17.2, Prompt 045).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\GenerateBlocks\GenerateBlocks_Mapping_Rules;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/GenerateBlocks/GenerateBlocks_Mapping_Rules.php';

final class GenerateBlocks_Mapping_Rules_Test extends TestCase {

	public function test_eligible_section_payload_returns_true(): void {
		$payload = array(
			'section_key'   => 'st01_hero',
			'wrapper_attrs' => array(
				'class'           => array( 'aio-s-st01_hero', 'aio-s-st01_hero--variant-default' ),
				'id'              => 'aio-section-st01_hero-0',
				'data_attributes' => array(),
			),
			'field_values'  => array(
				'headline'    => 'Welcome',
				'subheadline' => 'Text',
			),
		);
		$this->assertTrue( GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ) );
	}

	public function test_ineligible_when_section_key_missing(): void {
		$payload = array(
			'wrapper_attrs' => array( 'class' => array( 'aio-s-hero' ) ),
			'field_values'  => array( 'headline' => 'X' ),
		);
		$this->assertFalse( GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ) );
	}

	public function test_ineligible_when_section_key_empty(): void {
		$payload = array(
			'section_key'   => '',
			'wrapper_attrs' => array( 'class' => array( 'aio-s-hero' ) ),
			'field_values'  => array( 'headline' => 'X' ),
		);
		$this->assertFalse( GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ) );
	}

	public function test_ineligible_when_wrapper_class_empty(): void {
		$payload = array(
			'section_key'   => 'st01_hero',
			'wrapper_attrs' => array(
				'class'           => array(),
				'id'              => '',
				'data_attributes' => array(),
			),
			'field_values'  => array( 'headline' => 'X' ),
		);
		$this->assertFalse( GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ) );
	}

	public function test_unsupported_pattern_repeater_returns_false(): void {
		$payload = array(
			'section_key'   => 'st01_hero',
			'wrapper_attrs' => array( 'class' => array( 'aio-s-st01_hero' ) ),
			'field_values'  => array(
				'headline' => 'X',
				'items'    => array( array( 'title' => 'A' ) ),
			),
		);
		$this->assertFalse( GenerateBlocks_Mapping_Rules::is_eligible_for_gb( $payload ), 'Section with repeater/array field must not be eligible' );
	}

	public function test_unsupported_patterns_returns_bounded_list(): void {
		$list = GenerateBlocks_Mapping_Rules::unsupported_patterns();
		$this->assertIsArray( $list );
		$this->assertNotEmpty( $list );
		$this->assertArrayHasKey( 'repeater_or_group_fields', $list );
		$this->assertArrayHasKey( 'custom_block_types', $list );
	}

	public function test_allowed_block_names_includes_container_and_headline(): void {
		$names = GenerateBlocks_Mapping_Rules::allowed_block_names();
		$this->assertContains( GenerateBlocks_Mapping_Rules::BLOCK_CONTAINER, $names );
		$this->assertContains( GenerateBlocks_Mapping_Rules::BLOCK_HEADLINE, $names );
	}
}
