<?php
/**
 * Security-focused tests for template-lab chat REST routes (capabilities, ownership, mutation safety).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Chat_Application_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Chat_Session_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Rest\AI_Chat_REST_Controller;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Chat_REST_Controller_Security_Test extends TestCase {

	private function make_controller(): AI_Chat_REST_Controller {
		$c = new Service_Container();
		$c->register(
			'ai_chat_session_repository',
			static fn() => new AI_Chat_Session_Repository()
		);
		$c->register(
			'template_lab_chat_application_service',
			static function () use ( $c ) {
				$runs = new AI_Run_Service( new AI_Run_Repository(), new AI_Run_Artifact_Service( new AI_Run_Repository() ) );
				return new Template_Lab_Chat_Application_Service( $c->get( 'ai_chat_session_repository' ), $runs );
			}
		);
		return new AI_Chat_REST_Controller( $c );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_current_user_can_caps'],
			$GLOBALS['_aio_current_uid'],
			$GLOBALS['_aio_is_logged_in'],
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_get_post_by_id']
		);
		parent::tearDown();
	}

	public function test_permission_callback_false_without_cap(): void {
		$GLOBALS['_aio_current_user_can_caps'] = array(
			Capabilities::MANAGE_COMPOSITIONS => false,
		);
		$this->assertFalse( $this->make_controller()->can_manage_template_lab() );
	}

	public function test_get_sessions_returns_401_when_not_authenticated(): void {
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$GLOBALS['_aio_current_uid']           = 0;
		$GLOBALS['_aio_is_logged_in']          = false;
		$res                                   = $this->make_controller()->get_sessions( new WP_REST_Request() );
		$this->assertInstanceOf( \WP_Error::class, $res );
		$this->assertSame( 'aio_chat_not_authenticated', $res->get_error_code() );
	}

	public function test_get_session_forbidden_for_wrong_owner(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9101;
		$GLOBALS['_aio_current_uid']           = 5;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$repo                                  = new AI_Chat_Session_Repository();
		$key                                   = $repo->create_session( array( 'actor_user_id' => 99 ) );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9101,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_status'       => 'publish',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9101 => $post );
		$req                                   = new WP_REST_Request( 'GET', '', array( 'session_id' => $key ) );
		$res                                   = $this->make_controller()->get_session( $req );
		$this->assertInstanceOf( \WP_Error::class, $res );
		$this->assertSame( 'aio_chat_forbidden', $res->get_error_code() );
	}

	public function test_append_message_denied_does_not_append(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9102;
		$GLOBALS['_aio_current_uid']           = 3;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$repo                                  = new AI_Chat_Session_Repository();
		$key                                   = $repo->create_session( array( 'actor_user_id' => 99 ) );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9102,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_status'       => 'publish',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9102 => $post );
		$before                                = $repo->get_session( $key );
		$this->assertIsArray( $before );
		$n   = count( $before['messages'] ?? array() );
		$req = new WP_REST_Request(
			'POST',
			'',
			array(
				'session_id'      => $key,
				'role'            => 'user',
				'content_preview' => 'x',
			)
		);
		$res = $this->make_controller()->append_message( $req );
		$this->assertInstanceOf( \WP_Error::class, $res );
		$after = $repo->get_session( $key );
		$this->assertIsArray( $after );
		$this->assertCount( $n, $after['messages'] ?? array() );
	}

	public function test_submit_prompt_forbidden_does_not_create_run(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9103;
		$GLOBALS['_aio_current_uid']           = 2;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$repo                                  = new AI_Chat_Session_Repository();
		$key                                   = $repo->create_session( array( 'actor_user_id' => 8 ) );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9103,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_status'       => 'publish',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9103 => $post );
		$meta_before                           = $GLOBALS['_aio_post_meta'] ?? array();
		$req                                   = new WP_REST_Request(
			'POST',
			'',
			array(
				'session_id' => $key,
				'text'       => 'hello',
			)
		);
		$res                                   = $this->make_controller()->submit_prompt( $req );
		$this->assertInstanceOf( \WP_Error::class, $res );
		$this->assertSame( $meta_before, $GLOBALS['_aio_post_meta'] ?? array() );
	}

	public function test_create_session_succeeds_for_authenticated_actor(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 9104;
		$GLOBALS['_aio_current_uid']           = 7;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$post                                  = new \WP_Post(
			array(
				'ID'                => 9104,
				'post_type'         => Object_Type_Keys::AI_CHAT_SESSION,
				'post_status'       => 'publish',
				'post_modified_gmt' => '2020-01-01 00:00:00',
			)
		);
		$GLOBALS['_aio_wp_query_posts']        = array( $post );
		$GLOBALS['_aio_get_post_by_id']        = array( 9104 => $post );
		$req                                   = new WP_REST_Request( 'POST', '', array() );
		$res                                   = $this->make_controller()->create_session( $req );
		$this->assertInstanceOf( \WP_REST_Response::class, $res );
		$this->assertSame( 201, $res->status );
	}
}
