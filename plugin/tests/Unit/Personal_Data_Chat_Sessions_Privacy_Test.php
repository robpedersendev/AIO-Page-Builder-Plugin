<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Keys;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Eraser;
use AIOPageBuilder\Infrastructure\Privacy\Personal_Data_Exporter;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Personal_Data_Chat_Sessions_Privacy_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Minimal wpdb stub so Job_Queue_Repository and privacy hooks can prepare SQL in unit tests.
		$GLOBALS['wpdb'] = new class() {

			public string $prefix = 'wp_';

			public function prepare( string $query, mixed ...$args ): string|false {
				unset( $args );
				return '/*stub*/ ' . $query;
			}

			/**
			 * @param string $query
			 * @param string $output
			 * @return array<int, mixed>
			 */
			public function get_results( $query, $output = OBJECT ): array {
				unset( $query, $output );
				return array();
			}
		};
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['wpdb'],
			$GLOBALS['_aio_get_user_by_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_wp_deleted_posts'],
			$GLOBALS['_aio_wpdb_prepare_return']
		);
		parent::tearDown();
	}

	public function test_exporter_no_user_returns_empty(): void {
		$GLOBALS['_aio_get_user_by_return'] = null;
		$out                                = Personal_Data_Exporter::export( 'nobody@example.com', 1 );
		$this->assertSame( array(), $out['data'] );
		$this->assertTrue( $out['done'] );
	}

	public function test_exporter_includes_chat_summary_row(): void {
		$u                                  = new \WP_User( 77, 'u@example.com' );
		$GLOBALS['_aio_get_user_by_return'] = $u;
		$p                                  = new \WP_Post(
			array(
				'ID'                => 7701,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_title'        => 'acs_test',
				'post_status'       => 'publish',
				'post_modified_gmt' => '2022-06-01 10:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']     = array( $p );
		$payload                            = wp_json_encode(
			array(
				AI_Chat_Session_Keys::P_TASK_TYPE => 'template_lab',
				AI_Chat_Session_Keys::P_MESSAGES  => array(),
				AI_Chat_Session_Keys::P_APPROVED_SNAPSHOT_REF => array( 'id' => 'snap' ),
			)
		);
		$GLOBALS['_aio_post_meta']['7701']  = array(
			'_aio_internal_key'                => 'acs_test',
			'_aio_status'                      => 'active',
			AI_Chat_Session_Keys::META_OWNER   => '77',
			AI_Chat_Session_Keys::META_PAYLOAD => is_string( $payload ) ? $payload : '{}',
		);
		$out                                = Personal_Data_Exporter::export( 'u@example.com', 1 );
		$groups                             = array_column( $out['data'], 'group_id' );
		$this->assertContains( Personal_Data_Exporter::GROUP_CHAT_SESSIONS, $groups );
	}

	public function test_eraser_deletes_chat_posts_for_owner(): void {
		$u                                   = new \WP_User( 88, 'v@example.com' );
		$GLOBALS['_aio_get_user_by_return']  = $u;
		$GLOBALS['_aio_wp_deleted_posts']    = array();
		$GLOBALS['_aio_wp_query_posts']      = array(
			new \WP_Post(
				array(
					'ID'                => 8801,
					'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
					'post_title'        => 'acs_x',
					'post_status'       => 'publish',
					'post_modified_gmt' => '2023-01-01 00:00:00',
				)
			),
		);
		$GLOBALS['_aio_post_meta']['8801']   = array(
			'_aio_internal_key'                => 'acs_x',
			'_aio_status'                      => 'active',
			AI_Chat_Session_Keys::META_OWNER   => '88',
			AI_Chat_Session_Keys::META_PAYLOAD => '{}',
		);
		$GLOBALS['_aio_wpdb_prepare_return'] = array();
		$out                                 = Personal_Data_Eraser::erase( 'v@example.com', 1 );
		$this->assertTrue( $out['items_removed'] );
		$this->assertContains( 8801, $GLOBALS['_aio_wp_deleted_posts'] ?? array() );
	}
}
