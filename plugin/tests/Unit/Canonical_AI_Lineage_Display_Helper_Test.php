<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Canonical_AI_Lineage_Display_Helper;
use AIOPageBuilder\Domain\Registries\Shared\Registry_AI_Provenance_Helper;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

require_once dirname( __DIR__, 2 ) . '/src/Domain/Registries/Shared/Registry_AI_Provenance_Helper.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/Registries/Shared/Canonical_AI_Lineage_Display_Helper.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/Registries/Snapshots/Version_Snapshot_Schema.php';

final class Canonical_AI_Lineage_Display_Helper_Test extends TestCase {

	public function test_manual_definition_hides_notice_when_no_snapshots(): void {
		$def   = array(
			Composition_Schema::FIELD_COMPOSITION_ID => 'comp_x',
			Composition_Schema::FIELD_NAME           => 'X',
		);
		$state = Canonical_AI_Lineage_Display_Helper::build_state(
			Canonical_AI_Lineage_Display_Helper::TARGET_COMPOSITION,
			1,
			'comp_x',
			$def,
			null
		);
		$this->assertFalse( Registry_AI_Provenance_Helper::composition_has_ai_trace( $def ) );
		$this->assertFalse( $state['show'] );
	}

	public function test_shows_for_ai_traced_composition_without_snapshot_repo(): void {
		$def = array(
			Composition_Schema::FIELD_COMPOSITION_ID => 'comp_z',
			Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF => array( 'ai_run_post_id' => 42 ),
		);
		$this->assertTrue( Registry_AI_Provenance_Helper::composition_has_ai_trace( $def ) );
		$state = Canonical_AI_Lineage_Display_Helper::build_state(
			Canonical_AI_Lineage_Display_Helper::TARGET_COMPOSITION,
			0,
			'comp_z',
			$def,
			null
		);
		$this->assertTrue( $state['show'] );
		$this->assertNotSame( '', $state['headline'] );
		$this->assertNotEmpty( $state['lines'] );
	}
}
