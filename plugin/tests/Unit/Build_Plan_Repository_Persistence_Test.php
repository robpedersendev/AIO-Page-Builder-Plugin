<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Build_Plan_Repository_Persistence_Test extends TestCase {

	private Build_Plan_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                = new Build_Plan_Repository();
		$GLOBALS['_aio_post_meta'] = array();
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_update_post_meta_force_false_for_key'],
			$GLOBALS['_aio_get_post_type_by_id'],
			$GLOBALS['wpdb']
		);
		parent::tearDown();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function definition_with_overview_step( string $plan_id, string $extra_field = '' ): array {
		$def = array(
			Build_Plan_Schema::KEY_PLAN_ID    => $plan_id,
			Build_Plan_Schema::KEY_PLAN_TITLE => 'T',
			Build_Plan_Schema::KEY_STEPS      => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => $plan_id . '_step_overview',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Overview',
					Build_Plan_Item_Schema::KEY_ORDER     => 0,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
			),
		);
		if ( $extra_field !== '' ) {
			$def['aio_test_pad'] = $extra_field;
		}
		return $def;
	}

	public function test_save_uses_direct_db_insert_when_update_post_meta_fails_for_build_plan(): void {
		$post_id                             = 91001;
		$GLOBALS['_aio_get_post_type_by_id'] = array( $post_id => Object_Type_Keys::BUILD_PLAN );
		$GLOBALS['_aio_update_post_meta_force_false_for_key'] = Build_Plan_Repository::META_PLAN_DEFINITION;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Minimal wpdb stub for direct postmeta insert path.
		$GLOBALS['wpdb'] = new class() {
			public string $postmeta = 'wp_postmeta';

			public string $last_error = '';

			/**
			 * @param string              $table Table name.
			 * @param array<string,mixed> $data Row.
			 * @param array<int,string>   $format Formats.
			 * @return int
			 */
			public function insert( $table, $data, $format ) { // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
				$pid = (int) ( $data['post_id'] ?? 0 );
				$key = (string) ( $data['meta_key'] ?? '' );
				$val = (string) ( $data['meta_value'] ?? '' );
				$id  = (string) $pid;
				if ( ! isset( $GLOBALS['_aio_post_meta'][ $id ] ) ) {
					$GLOBALS['_aio_post_meta'][ $id ] = array();
				}
				$GLOBALS['_aio_post_meta'][ $id ][ $key ] = \function_exists( 'wp_unslash' ) ? \wp_unslash( $val ) : stripslashes( $val );
				return 1;
			}
		};

		$def = $this->definition_with_overview_step( 'plan-fallback' );
		$this->assertTrue( $this->repo->save_plan_definition( $post_id, $def ) );
		$round = $this->repo->get_plan_definition( $post_id );
		$this->assertSame( 'plan-fallback', (string) ( $round[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
		$this->assertCount( 1, $round[ Build_Plan_Schema::KEY_STEPS ] ?? array() );
	}

	public function test_save_clears_orphan_plan_definition_part_meta(): void {
		$post_id                             = 91003;
		$GLOBALS['_aio_get_post_type_by_id'] = array( $post_id => Object_Type_Keys::BUILD_PLAN );
		$m                                   = Build_Plan_Repository::META_PLAN_DEFINITION;
		$initial                             = $this->definition_with_overview_step( 'before' );
		$enc                                 = \wp_json_encode( $initial );
		$this->assertIsString( $enc );
		$GLOBALS['_aio_post_meta'] = array(
			(string) $post_id => array(
				$m              => $enc,
				$m . '__part_0' => '{"orphan fragment',
			),
		);
		$next                      = $this->definition_with_overview_step( 'after-clear' );
		$this->assertTrue( $this->repo->save_plan_definition( $post_id, $next ) );
		$store = $GLOBALS['_aio_post_meta'][ (string) $post_id ] ?? array();
		$this->assertArrayNotHasKey( $m . '__part_0', $store );
		$this->assertSame(
			'after-clear',
			(string) ( $this->repo->get_plan_definition( $post_id )[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' )
		);
	}

	public function test_save_chunked_plan_round_trips(): void {
		$post_id                             = 91002;
		$GLOBALS['_aio_get_post_type_by_id'] = array( $post_id => Object_Type_Keys::BUILD_PLAN );

		$pad_len = 1000;
		$def     = null;
		for ( $i = 0; $i < 200; $i++ ) {
			$pad_len  += 4000;
			$candidate = $this->definition_with_overview_step( 'plan-chunk', str_repeat( 'Z', $pad_len ) );
			$json      = \wp_json_encode( $candidate, JSON_UNESCAPED_UNICODE );
			if ( is_string( $json ) && strlen( $json ) > 524288 ) {
				$def = $candidate;
				break;
			}
		}
		$this->assertNotNull( $def, 'Failed to build oversized plan definition for chunk test.' );

		$this->assertTrue( $this->repo->save_plan_definition( $post_id, $def ) );
		$store = $GLOBALS['_aio_post_meta'][ (string) $post_id ] ?? array();
		$this->assertArrayHasKey( Build_Plan_Repository::META_PLAN_DEFINITION . '__chunk_count', $store );
		$this->assertArrayHasKey( Build_Plan_Repository::META_PLAN_DEFINITION . '__part_0', $store );

		$round = $this->repo->get_plan_definition( $post_id );
		$this->assertSame( 'plan-chunk', (string) ( $round[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
		$this->assertCount( 1, $round[ Build_Plan_Schema::KEY_STEPS ] ?? array() );
		$this->assertSame( $def['aio_test_pad'] ?? '', $round['aio_test_pad'] ?? '' );
	}
}
