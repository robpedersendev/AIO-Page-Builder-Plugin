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

/**
 * Forces a wrong CHAR_LENGTH on first probe so persist_single uses direct DB insert fallback.
 */
final class Build_Plan_Length_Test_Wpdb extends \wpdb {

	public string $postmeta = 'wp_postmeta';

	public string $last_error = '';

	/**
	 * @param mixed ...$args
	 */
	public function prepare( $query, ...$args ): string {
		if ( \function_exists( 'aio_page_builder_test_wpdb_prepare' ) ) {
			return aio_page_builder_test_wpdb_prepare( (string) $query, ...$args );
		}
		return (string) $query;
	}

	/**
	 * @param string|null $query
	 * @param int         $x
	 * @param int         $y
	 * @return string|null
	 */
	public function get_var( $query = null, $x = 0, $y = 0 ) {
		unset( $x, $y );
		if ( ! is_string( $query ) ) {
			return null;
		}
		if ( str_contains( $query, 'CHAR_LENGTH(meta_value)' )
			&& str_contains( $query, '_aio_plan_definition' )
			&& ! str_contains( $query, '__part_' ) ) {
			$v = $GLOBALS['_aio_test_plan_meta_char_len'] ?? null;
			return is_int( $v ) ? (string) $v : null;
		}
		if ( str_contains( $query, 'SELECT meta_value' )
			&& str_contains( $query, '_aio_plan_definition' )
			&& ! str_contains( $query, '__part_' )
			&& ! str_contains( $query, '__chunk_count' )
			&& preg_match( '/post_id = (\d+)/', $query, $m ) ) {
			$pid = (string) (int) $m[1];
			$key = Build_Plan_Repository::META_PLAN_DEFINITION;
			$raw = isset( $GLOBALS['_aio_post_meta'][ $pid ][ $key ] ) ? $GLOBALS['_aio_post_meta'][ $pid ][ $key ] : '';
			if ( ! is_string( $raw ) || $raw === '' ) {
				return null;
			}
			return \function_exists( 'wp_slash' ) ? \wp_slash( $raw ) : $raw;
		}
		return null;
	}

	/**
	 * @param string               $table
	 * @param array<string, mixed> $data
	 * @param array<string>|null   $format
	 */
	public function insert( $table, $data, $format = null ): int {
		unset( $table, $format );
		$pid = (int) ( $data['post_id'] ?? 0 );
		$key = (string) ( $data['meta_key'] ?? '' );
		$val = $data['meta_value'] ?? '';
		$id  = (string) $pid;
		if ( ! isset( $GLOBALS['_aio_post_meta'][ $id ] ) ) {
			$GLOBALS['_aio_post_meta'][ $id ] = array();
		}
		$store_val                                = is_string( $val ) && \function_exists( 'wp_unslash' ) ? \wp_unslash( $val ) : $val;
		$GLOBALS['_aio_post_meta'][ $id ][ $key ] = $store_val;
		if ( $key === Build_Plan_Repository::META_PLAN_DEFINITION && is_string( $store_val ) ) {
			$slashed                                 = \function_exists( 'wp_slash' ) ? \wp_slash( $store_val ) : $store_val;
			$GLOBALS['_aio_test_plan_meta_char_len'] = strlen( $slashed );
		}
		return 1;
	}
}

final class Build_Plan_Repository_Save_Length_Fallback_Test extends TestCase {

	private Build_Plan_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                          = new Build_Plan_Repository();
		$GLOBALS['_aio_post_meta']           = array();
		$GLOBALS['_aio_get_post_type_by_id'] = array(
			9101 => Object_Type_Keys::BUILD_PLAN,
		);
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test double for repository CHAR_LENGTH probe.
		$GLOBALS['wpdb']                         = new Build_Plan_Length_Test_Wpdb();
		$GLOBALS['_aio_test_plan_meta_char_len'] = 3;
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_get_post_type_by_id'], $GLOBALS['wpdb'], $GLOBALS['_aio_test_plan_meta_char_len'] );
		parent::tearDown();
	}

	public function test_save_uses_direct_insert_when_char_length_mismatches(): void {
		$def = array(
			Build_Plan_Schema::KEY_PLAN_ID    => 'plan_len_fb',
			Build_Plan_Schema::KEY_STATUS     => 'pending_review',
			Build_Plan_Schema::KEY_PLAN_TITLE => 'T',
			Build_Plan_Schema::KEY_STEPS      => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 's1',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Overview',
					Build_Plan_Item_Schema::KEY_ORDER     => 0,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
			),
		);
		$this->assertTrue( $this->repo->save_plan_definition( 9101, $def ) );
		$read = $this->repo->get_plan_definition( 9101 );
		$this->assertSame( 'plan_len_fb', (string) ( $read[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) );
		$this->assertCount( 1, $read[ Build_Plan_Schema::KEY_STEPS ] ?? array() );
	}
}
