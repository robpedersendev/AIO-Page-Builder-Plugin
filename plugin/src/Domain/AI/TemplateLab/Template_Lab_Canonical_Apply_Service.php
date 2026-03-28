<?php
/**
 * Approval-gated canonical writes from template-lab AI runs (normalized artifact only; no chat text, no provider HTTP).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Translation\Composition_AI_Draft_Translator;
use AIOPageBuilder\Domain\AI\Translation\Page_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\AI\Translation\Section_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

final class Template_Lab_Canonical_Apply_Service {

	private AI_Chat_Session_Repository_Interface $chat;

	private AI_Run_Template_Lab_Apply_State_Port $run_apply_state;

	private AI_Run_Artifact_Read_Port $artifacts;

	private Template_Lab_Canonical_Registry_Persist_Port $registry_persist;

	private Composition_AI_Draft_Translator $composition_translator;

	private Page_Template_AI_Draft_Translator $page_translator;

	private Section_Template_AI_Draft_Translator $section_translator;

	public function __construct(
		AI_Chat_Session_Repository_Interface $chat,
		AI_Run_Template_Lab_Apply_State_Port $run_apply_state,
		AI_Run_Artifact_Read_Port $artifacts,
		Template_Lab_Canonical_Registry_Persist_Port $registry_persist,
		Composition_AI_Draft_Translator $composition_translator,
		Page_Template_AI_Draft_Translator $page_translator,
		Section_Template_AI_Draft_Translator $section_translator
	) {
		$this->chat                   = $chat;
		$this->run_apply_state        = $run_apply_state;
		$this->artifacts              = $artifacts;
		$this->registry_persist       = $registry_persist;
		$this->composition_translator = $composition_translator;
		$this->page_translator        = $page_translator;
		$this->section_translator    = $section_translator;
	}

	/**
	 * Marks the session snapshot reference as approved after validating run + fingerprint (redacted logging only).
	 *
	 * @return array{ok: bool, code: string}
	 */
	public function approve_pending_snapshot( int $actor_user_id, string $session_id ): array {
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_SNAPSHOT_APPROVE_ATTEMPT,
			'session_id_len=' . (string) strlen( $session_id )
		);
		$session = $this->chat->get_session( $session_id );
		if ( $session === null ) {
			return array( 'ok' => false, 'code' => 'session_missing' );
		}
		if ( ! $this->actor_may_use_session( $actor_user_id, $session ) ) {
			return array( 'ok' => false, 'code' => 'forbidden' );
		}
		$ref = $session['approved_snapshot_ref'] ?? null;
		if ( ! is_array( $ref ) || $ref === array() ) {
			return array( 'ok' => false, 'code' => 'bad_snapshot_ref' );
		}
		$run_post_id = (int) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID ] ?? 0 );
		$fp          = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT ] ?? '' );
		$target      = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' );
		if ( $run_post_id <= 0 || $fp === '' || ! Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target ) ) {
			return array( 'ok' => false, 'code' => 'bad_snapshot_ref' );
		}
		$state = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] ?? Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_PENDING );
		if ( $state === Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_SNAPSHOT_APPROVE_IDEMPOTENT, 'run_post_id=' . (string) $run_post_id );
			return array( 'ok' => true, 'code' => 'ok' );
		}
		if ( ! $this->fingerprint_matches_trace( $run_post_id, $fp ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_SNAPSHOT_APPROVE_FAIL, 'reason=fingerprint run_post_id=' . (string) $run_post_id );
			return array( 'ok' => false, 'code' => 'fingerprint_mismatch' );
		}
		$norm = $this->artifacts->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		if ( ! is_array( $norm ) || $norm === array() ) {
			return array( 'ok' => false, 'code' => 'missing_normalized_output' );
		}
		$ref[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] = Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED;
		if ( ! $this->chat->link_approved_snapshot( $session_id, $ref ) ) {
			return array( 'ok' => false, 'code' => 'persist_failed' );
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_SNAPSHOT_APPROVED,
			'run_post_id=' . (string) $run_post_id . ' target_kind=' . $target
		);
		return array( 'ok' => true, 'code' => 'ok' );
	}

	public function apply_approved_snapshot( int $actor_user_id, string $session_id, string $target_kind ): Template_Lab_Canonical_Apply_Result {
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_ATTEMPT,
			'target_kind=' . $target_kind . ' session_id_len=' . (string) strlen( $session_id )
		);
		if ( ! Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_kind ) ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_BAD_TARGET );
		}
		$session = $this->chat->get_session( $session_id );
		if ( $session === null ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_SESSION_MISSING );
		}
		if ( ! $this->actor_may_use_session( $actor_user_id, $session ) ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_FORBIDDEN );
		}
		$ref = $session['approved_snapshot_ref'] ?? null;
		if ( ! is_array( $ref ) || $ref === array() ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_BAD_REF );
		}
		$state = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] ?? '' );
		if ( $state !== Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_NOT_APPROVED );
		}
		$ref_target = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' );
		if ( $ref_target !== $target_kind ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_BAD_TARGET );
		}
		$run_post_id = (int) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID ] ?? 0 );
		$fp          = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT ] ?? '' );
		if ( $run_post_id <= 0 || $fp === '' ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_BAD_REF );
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_SNAPSHOT_RESOLVED,
			'run_post_id=' . (string) $run_post_id . ' target_kind=' . $target_kind
		);
		if ( ! $this->fingerprint_matches_trace( $run_post_id, $fp ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_FAIL, 'reason=fingerprint' );
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_FINGERPRINT );
		}
		$prior = $this->run_apply_state->get_template_lab_canonical_apply_record( $run_post_id );
		if ( is_array( $prior )
			&& (string) ( $prior['artifact_fingerprint'] ?? '' ) === $fp
			&& (string) ( $prior['target_kind'] ?? '' ) === $target_kind
			&& (string) ( $prior['canonical_internal_key'] ?? '' ) !== ''
		) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_IDEMPOTENT,
				'run_post_id=' . (string) $run_post_id . ' target_kind=' . $target_kind
			);
			return Template_Lab_Canonical_Apply_Result::already_applied(
				(string) $prior['canonical_internal_key'],
				(int) ( $prior['canonical_post_id'] ?? 0 )
			);
		}
		$norm = $this->artifacts->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		if ( ! is_array( $norm ) || $norm === array() ) {
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_NO_ARTIFACT );
		}
		$draft = $this->build_draft_with_provenance( $norm, $ref, $run_post_id );
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			$t = $this->composition_translator->translate( $draft );
		} elseif ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			$t = $this->page_translator->translate( $draft );
		} else {
			$t = $this->section_translator->translate( $draft );
		}
		if ( ! $t->is_ok() ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_FAIL,
				'reason=translation target_kind=' . $target_kind
			);
			return Template_Lab_Canonical_Apply_Result::failure(
				Template_Lab_Canonical_Apply_Result::CODE_TRANSLATION,
				$t->get_errors()
			);
		}
		$def  = $t->get_definition();
		$save = $this->registry_persist->persist_definition( $target_kind, $def );
		if ( $save['post_id'] <= 0 || $save['internal_key'] === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_FAIL, 'reason=persist target_kind=' . $target_kind );
			return Template_Lab_Canonical_Apply_Result::failure( Template_Lab_Canonical_Apply_Result::CODE_PERSIST );
		}
		$this->run_apply_state->save_template_lab_canonical_apply_record(
			$run_post_id,
			array(
				'artifact_fingerprint'   => $fp,
				'target_kind'            => $target_kind,
				'canonical_internal_key' => $save['internal_key'],
				'canonical_post_id'      => $save['post_id'],
				'applied_at_unix'        => time(),
			)
		);
		Named_Debug_Log::event(
			Named_Debug_Log_Event::TEMPLATE_LAB_CANONICAL_APPLY_OK,
			'target_kind=' . $target_kind . ' canonical_post_id=' . (string) $save['post_id']
		);
		return Template_Lab_Canonical_Apply_Result::ok( $save['internal_key'], $save['post_id'] );
	}

	/**
	 * @param array<string, mixed> $session
	 */
	private function actor_may_use_session( int $actor_user_id, array $session ): bool {
		$owner = (int) ( $session['owner_user_id'] ?? 0 );
		if ( $actor_user_id > 0 && $owner === $actor_user_id ) {
			return true;
		}
		return \current_user_can( 'manage_options' );
	}

	private function fingerprint_matches_trace( int $run_post_id, string $fingerprint ): bool {
		$trace = $this->artifacts->get( $run_post_id, Artifact_Category_Keys::TEMPLATE_LAB_TRACE );
		if ( ! is_array( $trace ) ) {
			return false;
		}
		return (string) ( $trace['artifact_fingerprint'] ?? '' ) === $fingerprint;
	}

	/**
	 * @param array<string, mixed> $norm
	 * @param array<string, mixed> $ref
	 * @return array<string, mixed>
	 */
	private function build_draft_with_provenance( array $norm, array $ref, int $run_post_id ): array {
		// * Canonical writes must originate from an approved snapshot handle, not raw chat or provider HTTP (translator enforces schema).
		$safe_ref = array(
			Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID         => $run_post_id,
			Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT => (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT ] ?? '' ),
			Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND       => (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' ),
			Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE      => Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED,
		);
		$draft               = $norm;
		$draft['approved_snapshot_ref'] = $safe_ref;
		$draft['ai_run_post_id']          = $run_post_id;
		if ( isset( $draft[ Composition_Schema::FIELD_COMPOSITION_ID ] ) ) {
			if ( isset( $draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ) && is_array( $draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] ) ) {
				$reg = $draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ];
			} else {
				$reg = array();
			}
			$reg['template_lab_source']           = 'approved_snapshot_apply';
			$reg['approved_artifact_fingerprint'] = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT ] ?? '' );
			$draft[ Composition_Schema::FIELD_REGISTRY_SNAPSHOT_REF ] = $reg;
		}
		return $draft;
	}
}
