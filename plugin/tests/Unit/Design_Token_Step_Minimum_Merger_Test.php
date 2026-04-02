<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Design_Token_Plan_Item_Assembler;
use AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Design_Token_Step_Minimum_Merger;
use AIOPageBuilder\Domain\Styling\Style_Spec_Loader;
use AIOPageBuilder\Domain\Styling\Style_Token_Registry;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/src/Domain/Styling/Style_Spec_Loader.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/Styling/Style_Token_Registry.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Steps/Tokens/Design_Token_Required_Set.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Steps/Tokens/Design_Token_Plan_Item_Assembler.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Steps/Tokens/Design_Token_Step_Minimum_Merger.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';

final class Design_Token_Step_Minimum_Merger_Test extends TestCase {

	private string $plugin_root;

	protected function setUp(): void {
		parent::setUp();
		$this->plugin_root = dirname( __DIR__, 2 );
	}

	public function test_merge_appends_only_missing_required_pairs(): void {
		$loader  = new Style_Spec_Loader( $this->plugin_root . '/specs/' );
		$reg     = new Style_Token_Registry( $loader );
		$merger  = new Design_Token_Step_Minimum_Merger( $reg );
		$plan_id = 'plan-test-merge';
		$items   = array(
			Design_Token_Plan_Item_Assembler::item( $plan_id . '_dtr_0', 'color', 'primary', '#111111', 'x', 0, 'medium' ),
		);
		$out     = $merger->merge_required_into_items( $items, $plan_id );
		$this->assertGreaterThan( count( $items ), count( $out ) );
		$pairs = $this->pairs_from_items( $out );
		$this->assertArrayHasKey( "color\0primary", $pairs );
		$this->assertArrayHasKey( "color\0surface", $pairs );
		$this->assertArrayHasKey( "typography\0heading", $pairs );
	}

	/**
	 * @param array<int, array<string, mixed>> $items
	 * @return array<string, bool>
	 */
	private function pairs_from_items( array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN ) {
				continue;
			}
			$p = $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ?? array();
			if ( ! is_array( $p ) ) {
				continue;
			}
			$g = isset( $p['token_group'] ) && is_string( $p['token_group'] ) ? trim( $p['token_group'] ) : '';
			$n = isset( $p['token_name'] ) && is_string( $p['token_name'] ) ? trim( $p['token_name'] ) : '';
			if ( $g !== '' && $n !== '' ) {
				$out[ $g . "\0" . $n ] = true;
			}
		}
		return $out;
	}
}
