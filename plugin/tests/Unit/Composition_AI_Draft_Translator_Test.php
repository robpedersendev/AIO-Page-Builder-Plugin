<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Translation\Composition_AI_Draft_Translator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Composition_AI_Draft_Translator_Test extends TestCase {

	public function test_valid_draft_translates(): void {
		$t = new Composition_AI_Draft_Translator();
		$r = $t->translate(
			array(
				Composition_Schema::FIELD_COMPOSITION_ID => 'comp_test_1',
				Composition_Schema::FIELD_NAME           => 'Test',
				Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
					array(
						Composition_Schema::SECTION_ITEM_KEY      => 'st_hero',
						Composition_Schema::SECTION_ITEM_POSITION => 0,
					),
				),
				Composition_Schema::FIELD_STATUS         => 'draft',
				Composition_Schema::FIELD_VALIDATION_STATUS => 'pending_validation',
				'approved_snapshot_ref'                  => array( 'snap' => 'a' ),
				'ai_run_post_id'                         => 42,
			)
		);
		$this->assertTrue( $r->is_ok() );
		$d = $r->get_definition();
		$this->assertSame( 'comp_test_1', $d[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
		$this->assertSame( 42, (int) ( $d[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ]['ai_run_post_id'] ?? 0 ) );
	}

	public function test_missing_field_fails(): void {
		$t = new Composition_AI_Draft_Translator();
		$r = $t->translate( array( Composition_Schema::FIELD_COMPOSITION_ID => 'x' ) );
		$this->assertFalse( $r->is_ok() );
	}
}
