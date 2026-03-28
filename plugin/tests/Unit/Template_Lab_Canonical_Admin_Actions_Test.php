<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Actions\Template_Lab_Canonical_Admin_Actions;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Apply_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Registry_Persist_Port;
use AIOPageBuilder\Domain\AI\Translation\Composition_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Page_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Section_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

final class Template_Lab_Canonical_Admin_Actions_Test extends TestCase {

	protected function tearDown(): void {
		unset( $_POST, $GLOBALS['_aio_current_uid'], $GLOBALS['_aio_is_logged_in'], $GLOBALS['_aio_current_user_can_caps'] );
		parent::tearDown();
	}

	private function make_apply_service( AI_Chat_Session_Repository_Interface $chat ): Template_Lab_Canonical_Apply_Service {
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$art = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$per = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		return new Template_Lab_Canonical_Apply_Service(
			$chat,
			$run,
			$art,
			$per,
			new Composition_AI_Draft_Translator(),
			new Page_Template_AI_Draft_Translator(),
			new Section_Template_AI_Draft_Translator()
		);
	}

	private function register_container(
		AI_Chat_Session_Repository_Interface $chat,
		Template_Lab_Canonical_Apply_Service $svc
	): Service_Container {
		$c = new Service_Container();
		$c->register( 'ai_chat_session_repository', static fn() => $chat );
		$c->register( 'template_lab_canonical_apply_service', static fn() => $svc );
		return $c;
	}

	public function test_handle_approve_bad_nonce_redirects(): void {
		$_POST = array( 'aio_tl_approve_nonce' => 'invalid' );
		try {
			Template_Lab_Canonical_Admin_Actions::handle_approve( new Service_Container() );
			$this->fail( 'Expected redirect exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringStartsWith( 'wp_safe_redirect:', $e->getMessage() );
			$this->assertStringContainsString( 'aio_tl_approve=', $e->getMessage() );
			$this->assertStringContainsString( 'bad_nonce', $e->getMessage() );
		}
	}

	public function test_handle_apply_bad_nonce_redirects(): void {
		$_POST = array( 'aio_tl_apply_nonce' => 'nope' );
		try {
			Template_Lab_Canonical_Admin_Actions::handle_apply( new Service_Container() );
			$this->fail( 'Expected redirect exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'aio_tl_apply=', $e->getMessage() );
			$this->assertStringContainsString( 'bad_nonce', $e->getMessage() );
		}
	}

	public function test_handle_approve_succeeds_when_snapshot_already_approved(): void {
		$ref  = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID            => 50,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT  => 'fp50',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND         => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE         => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'session_id'            => 'sess_ok',
				'owner_user_id'         => 7,
				'approved_snapshot_ref' => $ref,
			)
		);
		$svc                                   = $this->make_apply_service( $chat );
		$c                                     = $this->register_container( $chat, $svc );
		$GLOBALS['_aio_current_uid']           = 7;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$_POST                                 = array(
			'aio_tl_approve_nonce' => \wp_create_nonce( Template_Lab_Canonical_Admin_Actions::NONCE_APPROVE ),
			Template_Lab_Canonical_Admin_Actions::FIELD_SESSION => 'sess_ok',
		);
		try {
			Template_Lab_Canonical_Admin_Actions::handle_approve( $c );
			$this->fail( 'Expected redirect exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'aio_tl_approve=', $e->getMessage() );
			$this->assertStringContainsString( 'ok', $e->getMessage() );
		}
	}

	public function test_handle_apply_unauthorized_without_capability(): void {
		$ref                                   = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID           => 1,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fp',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND        => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE        => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat                                  = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$svc                                   = $this->make_apply_service( $chat );
		$c                                     = $this->register_container( $chat, $svc );
		$GLOBALS['_aio_current_uid']           = 9;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                  => false,
			Capabilities::MANAGE_COMPOSITIONS => false,
		);
		$_POST                                 = array(
			'aio_tl_apply_nonce' => \wp_create_nonce( Template_Lab_Canonical_Admin_Actions::NONCE_APPLY ),
			Template_Lab_Canonical_Admin_Actions::FIELD_SESSION => 's1',
			Template_Lab_Canonical_Admin_Actions::FIELD_TARGET => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
		);
		try {
			Template_Lab_Canonical_Admin_Actions::handle_apply( $c );
			$this->fail( 'Expected redirect exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'aio_tl_apply=', $e->getMessage() );
			$this->assertStringContainsString( 'unauthorized', $e->getMessage() );
		}
	}

	public function test_handle_apply_idempotent_redirects_already_applied(): void {
		$ref  = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID           => 88,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fp88',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND        => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE        => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'session_id'            => 'sess_idem',
				'owner_user_id'         => 3,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn(
			array(
				'artifact_fingerprint'   => 'fp88',
				'target_kind'            => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
				'canonical_internal_key' => 'comp_x',
				'canonical_post_id'      => 400,
			)
		);
		$art = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$art->method( 'get' )->willReturnCallback(
			static function ( int $pid, string $cat ) {
				if ( $pid !== 88 || $cat !== Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return null;
				}
				return array( 'artifact_fingerprint' => 'fp88' );
			}
		);
		$per                                   = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$svc                                   = new Template_Lab_Canonical_Apply_Service(
			$chat,
			$run,
			$art,
			$per,
			new Composition_AI_Draft_Translator(),
			new Page_Template_AI_Draft_Translator(),
			new Section_Template_AI_Draft_Translator()
		);
		$c                                     = $this->register_container( $chat, $svc );
		$GLOBALS['_aio_current_uid']           = 3;
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array( Capabilities::MANAGE_COMPOSITIONS => true );
		$_POST                                 = array(
			'aio_tl_apply_nonce' => \wp_create_nonce( Template_Lab_Canonical_Admin_Actions::NONCE_APPLY ),
			Template_Lab_Canonical_Admin_Actions::FIELD_SESSION => 'sess_idem',
			Template_Lab_Canonical_Admin_Actions::FIELD_TARGET => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
		);
		try {
			Template_Lab_Canonical_Admin_Actions::handle_apply( $c );
			$this->fail( 'Expected redirect exception' );
		} catch ( \RuntimeException $e ) {
			$this->assertStringContainsString( 'aio_tl_apply=', $e->getMessage() );
			$this->assertStringContainsString( 'already_applied', $e->getMessage() );
		}
	}
}
