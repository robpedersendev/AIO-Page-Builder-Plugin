<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context;
use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context_Resolver;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use PHPUnit\Framework\TestCase;

final class Build_Plan_Template_Lab_Context_Resolver_Test extends TestCase {

	public function test_empty_session_id_skips(): void {
		$r   = new Build_Plan_Template_Lab_Context_Resolver(
			$this->createMock( AI_Chat_Session_Repository_Interface::class ),
			$this->createMock( AI_Run_Template_Lab_Apply_State_Port::class ),
			$this->createMock( AI_Run_Artifact_Read_Port::class )
		);
		$out = $r->resolve_for_actor( 1, '  ' );
		$this->assertSame( Build_Plan_Template_Lab_Context_Resolver::CODE_SKIPPED_EMPTY, $out['code'] );
		$this->assertSame( array(), $out['context'] );
	}

	public function test_rejects_pending_snapshot(): void {
		$ref  = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 10,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fp',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_PENDING,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 2,
				'approved_snapshot_ref' => $ref,
			)
		);
		$r   = new Build_Plan_Template_Lab_Context_Resolver(
			$chat,
			$this->createMock( AI_Run_Template_Lab_Apply_State_Port::class ),
			$this->createMock( AI_Run_Artifact_Read_Port::class )
		);
		$out = $r->resolve_for_actor( 2, 'acs_1' );
		$this->assertSame( Build_Plan_Template_Lab_Context_Resolver::CODE_NOT_APPROVED, $out['code'] );
	}

	public function test_ok_when_apply_record_matches(): void {
		$ref  = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 50,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fpx',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 3,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn(
			array(
				'artifact_fingerprint'   => 'fpx',
				'target_kind'            => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
				'canonical_internal_key' => 'comp_x',
				'canonical_post_id'      => 100,
			)
		);
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturnCallback(
			static function ( int $pid, string $cat ) {
				if ( $cat === Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return array(
						'artifact_fingerprint' => 'fpx',
						'schema_ref'           => 'aio/tl-v1',
					);
				}
				return null;
			}
		);
		$r   = new Build_Plan_Template_Lab_Context_Resolver( $chat, $run, $artifacts );
		$out = $r->resolve_for_actor( 3, 'acs_ok' );
		$this->assertSame( Build_Plan_Template_Lab_Context_Resolver::CODE_OK, $out['code'] );
		$this->assertSame( 50, (int) ( $out['context'][ Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID ] ?? 0 ) );
		$this->assertSame( 'comp_x', (string) ( $out['context'][ Build_Plan_Template_Lab_Context::FIELD_CANONICAL_INTERNAL_KEY ] ?? '' ) );
		$this->assertSame( 'acs_ok', (string) ( $out['context'][ Build_Plan_Template_Lab_Context::FIELD_CHAT_SESSION_ID ] ?? '' ) );
	}

	public function test_rejects_when_canonical_apply_record_missing(): void {
		$ref  = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 60,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fpy',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 4,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn( null );
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturn(
			array(
				'artifact_fingerprint' => 'fpy',
				'schema_ref'           => 'aio/tl-v1',
			)
		);
		$r   = new Build_Plan_Template_Lab_Context_Resolver( $chat, $run, $artifacts );
		$out = $r->resolve_for_actor( 4, 'acs_no_apply' );
		$this->assertSame( Build_Plan_Template_Lab_Context_Resolver::CODE_CANONICAL_NOT_LINKED, $out['code'] );
	}
}
