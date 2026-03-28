<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Keys;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Chat_Session_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Chat_Session_Repository_Test extends TestCase {

	private AI_Chat_Session_Repository $repo;

	protected function setUp(): void {
		parent::setUp();
		$this->repo                = new AI_Chat_Session_Repository();
		$GLOBALS['_aio_post_meta'] = array();
		unset(
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_get_post_by_id'],
			$GLOBALS['_aio_wp_query_posts']
		);
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_wp_update_post_return'],
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_get_post_by_id'],
			$GLOBALS['_aio_wp_query_posts']
		);
		parent::tearDown();
	}

	public function test_create_session_requires_no_network(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9001;
		$key                                   = $this->repo->create_session(
			array(
				'actor_user_id' => 7,
				'task_type'     => 'template_lab',
			)
		);
		$this->assertNotSame( '', $key );
		$this->assertStringStartsWith( 'acs_', $key );
		$this->assertSame( '7', $GLOBALS['_aio_post_meta']['9001']['_aio_chat_owner_user_id'] ?? '' );
	}

	public function test_append_to_missing_session_fails(): void {
		$this->assertFalse(
			$this->repo->append_message(
				'missing-session',
				array(
					'role'            => 'user',
					'content_preview' => 'hi',
				)
			)
		);
	}

	public function test_append_and_status_roundtrip(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9002;
		$key                                   = $this->repo->create_session( array( 'actor_user_id' => 3 ) );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9002,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_title'        => $key,
				'post_status'       => 'publish',
				'post_name'         => $key,
				'post_modified_gmt' => '2020-01-01 12:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9002 => $post );
		$this->assertTrue(
			$this->repo->append_message(
				$key,
				array(
					'role'            => 'user',
					'content_preview' => 'hello',
				)
			)
		);
		$this->assertTrue( $this->repo->update_status( $key, 'archived' ) );
		$s = $this->repo->get_session( $key );
		$this->assertIsArray( $s );
		$this->assertSame( 'archived', $s['status'] ?? '' );
		$this->assertCount( 1, $s['messages'] ?? array() );
	}

	public function test_link_approved_snapshot(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9003;
		$key                                   = $this->repo->create_session( array( 'actor_user_id' => 1 ) );
		$p3                                    = new \WP_Post(
			array(
				'ID'                => 9003,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_title'        => $key,
				'post_status'       => 'publish',
				'post_name'         => $key,
				'post_modified_gmt' => '2021-01-01 00:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $p3 );
		$GLOBALS['_aio_get_post_by_id']        = array( 9003 => $p3 );
		$this->assertTrue( $this->repo->link_approved_snapshot( $key, array( 'snapshot_id' => 'snap_1' ) ) );
		$s = $this->repo->get_session( $key );
		$this->assertIsArray( $s['approved_snapshot_ref'] ?? null );
		$this->assertSame( 'snap_1', (string) ( $s['approved_snapshot_ref']['snapshot_id'] ?? '' ) );
	}

	public function test_anonymize_transcript_no_match_is_idempotent_ok(): void {
		$this->assertTrue( $this->repo->anonymize_transcript( 'acs_nonexistent' ) );
	}

	public function test_anonymize_transcript_clears_messages_and_provider_thread(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9040;
		$key                                   = $this->repo->create_session( array( 'actor_user_id' => 2 ) );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9040,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_title'        => $key,
				'post_status'       => 'publish',
				'post_name'         => $key,
				'post_modified_gmt' => '2022-06-01 10:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9040 => $post );
		$this->assertTrue(
			$this->repo->append_message(
				$key,
				array(
					'role'            => 'user',
					'content_preview' => 'SECRET_USER_TEXT',
				)
			)
		);
		$this->assertTrue( $this->repo->set_provider_thread_ref( $key, 'provider_thread_xyz' ) );
		$before = $this->repo->get_session( $key );
		$this->assertIsArray( $before );
		$this->assertNotSame( '', (string) ( $before['provider_thread_ref'] ?? '' ) );
		$this->assertTrue( $this->repo->anonymize_transcript( $key ) );
		$after = $this->repo->get_session( $key );
		$this->assertIsArray( $after );
		$this->assertSame( '', (string) ( $after['provider_thread_ref'] ?? 'x' ) );
		$this->assertCount( 1, $after['messages'] ?? array() );
		$this->assertSame( '[erased]', (string) ( $after['messages'][0]['content_preview'] ?? '' ) );
		$this->assertGreaterThan( 0, (int) ( json_decode( (string) ( $GLOBALS['_aio_post_meta']['9040'][ AI_Chat_Session_Keys::META_PAYLOAD ] ?? '{}' ), true )[ AI_Chat_Session_Keys::P_TRANSCRIPT_ANONYMIZED_UNIX ] ?? 0 ) );
		$this->assertTrue( $this->repo->anonymize_transcript( $key ) );
		$after2 = $this->repo->get_session( $key );
		$this->assertSame( '[erased]', (string) ( $after2['messages'][0]['content_preview'] ?? '' ) );
	}
}
