<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Apply_Result;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Apply_Service;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Canonical_Registry_Persist_Port;
use AIOPageBuilder\Domain\AI\Translation\Composition_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Page_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Section_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Template_Lab_Canonical_Apply_Service_Test extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private function sample_normalized_composition(): array {
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_tl_apply_1',
			Composition_Schema::FIELD_NAME                 => 'TL Apply',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY      => 'st_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
			Composition_Schema::FIELD_STATUS               => 'draft',
			Composition_Schema::FIELD_VALIDATION_STATUS    => 'pending_validation',
		);
	}

	private function make_service(
		AI_Chat_Session_Repository_Interface $chat,
		AI_Run_Template_Lab_Apply_State_Port $run_state,
		AI_Run_Artifact_Read_Port $artifacts,
		Template_Lab_Canonical_Registry_Persist_Port $persist
	): Template_Lab_Canonical_Apply_Service {
		return new Template_Lab_Canonical_Apply_Service(
			$chat,
			$run_state,
			$artifacts,
			$persist,
			new Composition_AI_Draft_Translator(),
			new Page_Template_AI_Draft_Translator(),
			new Section_Template_AI_Draft_Translator()
		);
	}

	public function test_apply_composition_succeeds_when_approved_and_trace_matches(): void {
		$ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 900,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fp9',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'session_id'            => 'acs_x',
				'owner_user_id'         => 5,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn( null );
		$run->expects( $this->once() )->method( 'save_template_lab_canonical_apply_record' )->with(
			900,
			$this->callback(
				static function ( array $rec ): bool {
					return (string) ( $rec['artifact_fingerprint'] ?? '' ) === 'fp9'
						&& (string) ( $rec['target_kind'] ?? '' ) === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION
						&& (int) ( $rec['canonical_post_id'] ?? 0 ) === 501;
				}
			)
		);
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturnCallback(
			function ( int $pid, string $cat ) {
				if ( $cat === Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return array( 'artifact_fingerprint' => 'fp9' );
				}
				if ( $cat === Artifact_Category_Keys::NORMALIZED_OUTPUT ) {
					return $this->sample_normalized_composition();
				}
				return null;
			}
		);
		$persist = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$persist->expects( $this->once() )->method( 'persist_definition' )->willReturn(
			array( 'internal_key' => 'comp_tl_apply_1', 'post_id' => 501 )
		);
		$svc = $this->make_service( $chat, $run, $artifacts, $persist );
		$r   = $svc->apply_approved_snapshot( 5, 'acs_x', Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION );
		$this->assertTrue( $r->is_success() );
		$this->assertFalse( $r->is_already_applied() );
		$this->assertSame( Template_Lab_Canonical_Apply_Result::CODE_OK, $r->get_code() );
		$this->assertSame( 'comp_tl_apply_1', $r->get_canonical_internal_key() );
		$this->assertSame( 501, $r->get_canonical_post_id() );
	}

	public function test_apply_rejects_when_not_approved(): void {
		$ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 1,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fp',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_PENDING,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 1,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run       = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$persist   = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$svc       = $this->make_service( $chat, $run, $artifacts, $persist );
		$r         = $svc->apply_approved_snapshot( 1, 's', Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION );
		$this->assertFalse( $r->is_success() );
		$this->assertSame( Template_Lab_Canonical_Apply_Result::CODE_NOT_APPROVED, $r->get_code() );
	}

	public function test_apply_is_idempotent_when_prior_record_matches(): void {
		$ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 800,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fpx',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 2,
				'approved_snapshot_ref' => $ref,
			)
		);
		$run = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn(
			array(
				'artifact_fingerprint'   => 'fpx',
				'target_kind'            => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
				'canonical_internal_key' => 'comp_old',
				'canonical_post_id'      => 303,
			)
		);
		$run->expects( $this->never() )->method( 'save_template_lab_canonical_apply_record' );
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturnCallback(
			function ( int $pid, string $cat ) {
				if ( $cat === Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return array( 'artifact_fingerprint' => 'fpx' );
				}
				return $this->sample_normalized_composition();
			}
		);
		$persist = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$persist->expects( $this->never() )->method( 'persist_definition' );
		$svc = $this->make_service( $chat, $run, $artifacts, $persist );
		$r   = $svc->apply_approved_snapshot( 2, 's2', Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION );
		$this->assertTrue( $r->is_success() );
		$this->assertTrue( $r->is_already_applied() );
		$this->assertSame( 'comp_old', $r->get_canonical_internal_key() );
		$this->assertSame( 303, $r->get_canonical_post_id() );
	}

	public function test_apply_routes_page_template_through_persist_port(): void {
		$ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 701,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'fpp',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE,
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
		$run->method( 'get_template_lab_canonical_apply_record' )->willReturn( null );
		$run->expects( $this->once() )->method( 'save_template_lab_canonical_apply_record' );
		$ordered = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY      => 'st_a',
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
		);
		$page_draft = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => 'pt_tl_apply',
			Page_Template_Schema::FIELD_NAME             => 'TL Page',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Purpose',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'landing_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array( 'st_a' => array( 'required' => true ) ),
			Page_Template_Schema::FIELD_COMPATIBILITY    => array( 'lpagery' => 'ok' ),
			Page_Template_Schema::FIELD_ONE_PAGER        => array( Page_Template_Schema::ONE_PAGER_PURPOSE_SUMMARY => 'p' ),
			Page_Template_Schema::FIELD_VERSION          => array( 'major' => 1, 'minor' => 0 ),
			Page_Template_Schema::FIELD_STATUS           => 'draft',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
		);
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturnCallback(
			function ( int $pid, string $cat ) use ( $page_draft ) {
				if ( $cat === Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return array( 'artifact_fingerprint' => 'fpp' );
				}
				return $page_draft;
			}
		);
		$persist = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$persist->expects( $this->once() )->method( 'persist_definition' )->with(
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE,
			$this->anything()
		)->willReturn( array( 'internal_key' => 'pt_tl_apply', 'post_id' => 909 ) );
		$svc = $this->make_service( $chat, $run, $artifacts, $persist );
		$r   = $svc->apply_approved_snapshot( 3, 's3', Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE );
		$this->assertTrue( $r->is_success() );
		$this->assertSame( 'pt_tl_apply', $r->get_canonical_internal_key() );
	}

	public function test_approve_marks_session_when_fingerprint_and_output_ok(): void {
		$ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => 55,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => 'f55',
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION,
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_PENDING,
		);
		$chat = $this->createMock( AI_Chat_Session_Repository_Interface::class );
		$chat->method( 'get_session' )->willReturn(
			array(
				'owner_user_id'         => 9,
				'approved_snapshot_ref' => $ref,
			)
		);
		$chat->expects( $this->once() )->method( 'link_approved_snapshot' )->with(
			'sess1',
			$this->callback(
				static function ( array $r ): bool {
					return ( $r[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] ?? '' ) === Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED;
				}
			)
		)->willReturn( true );
		$run       = $this->createMock( AI_Run_Template_Lab_Apply_State_Port::class );
		$artifacts = $this->createMock( AI_Run_Artifact_Read_Port::class );
		$artifacts->method( 'get' )->willReturnCallback(
			function ( int $pid, string $cat ) {
				if ( $cat === Artifact_Category_Keys::TEMPLATE_LAB_TRACE ) {
					return array( 'artifact_fingerprint' => 'f55' );
				}
				return array( 'composition_id' => 'x' );
			}
		);
		$persist = $this->createMock( Template_Lab_Canonical_Registry_Persist_Port::class );
		$svc     = $this->make_service( $chat, $run, $artifacts, $persist );
		$out     = $svc->approve_pending_snapshot( 9, 'sess1' );
		$this->assertTrue( $out['ok'] ?? false );
	}
}
