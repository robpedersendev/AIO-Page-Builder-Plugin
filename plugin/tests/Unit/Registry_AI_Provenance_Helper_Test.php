<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Registry_AI_Provenance_Helper;
use PHPUnit\Framework\TestCase;

final class Registry_AI_Provenance_Helper_Test extends TestCase {

	public function test_composition_manual_without_snapshot_ref(): void {
		$this->assertFalse( Registry_AI_Provenance_Helper::composition_has_ai_trace( array() ) );
	}

	public function test_composition_ai_when_run_post_in_snapshot_ref(): void {
		$d = array(
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => array( 'ai_run_post_id' => 12 ),
		);
		$this->assertTrue( Registry_AI_Provenance_Helper::composition_has_ai_trace( $d ) );
	}

	public function test_page_template_ai_from_run_post_id(): void {
		$this->assertTrue(
			Registry_AI_Provenance_Helper::page_template_has_ai_trace( array( 'provenance_ai_run_post_id' => 3 ) )
		);
	}

	public function test_page_template_ai_from_non_empty_approved_snapshot_ref(): void {
		$this->assertTrue(
			Registry_AI_Provenance_Helper::page_template_has_ai_trace(
				array( 'provenance_approved_snapshot_ref' => array( 'k' => 'v' ) )
			)
		);
	}

	public function test_section_template_manual(): void {
		$this->assertFalse( Registry_AI_Provenance_Helper::section_template_has_ai_trace( array() ) );
	}
}
