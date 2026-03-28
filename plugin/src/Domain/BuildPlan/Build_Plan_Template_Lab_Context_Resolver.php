<?php
/**
 * Resolves optional template-lab linkage for build-plan creation (approved snapshot + canonical key only).
 *
 * Build-plan generation stays approval-gated; this object only attaches informational context when a chat session
 * holds an approved template-lab snapshot reference consistent with the apply record.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Read_Port;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\AI_Run_Template_Lab_Apply_State_Port;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Infrastructure\Config\Template_Lab_Access;

/**
 * Produces sanitized {@see Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT} rows or structured reject codes.
 */
final class Build_Plan_Template_Lab_Context_Resolver {

	private AI_Chat_Session_Repository_Interface $chat;

	private AI_Run_Template_Lab_Apply_State_Port $apply_state;

	private AI_Run_Artifact_Read_Port $artifacts;

	public function __construct(
		AI_Chat_Session_Repository_Interface $chat,
		AI_Run_Template_Lab_Apply_State_Port $apply_state,
		AI_Run_Artifact_Read_Port $artifacts
	) {
		$this->chat        = $chat;
		$this->apply_state = $apply_state;
		$this->artifacts   = $artifacts;
	}

	public const CODE_OK                   = 'ok';
	public const CODE_SKIPPED_EMPTY        = 'skipped_empty';
	public const CODE_SESSION_MISSING      = 'session_missing';
	public const CODE_FORBIDDEN            = 'forbidden';
	public const CODE_NOT_APPROVED         = 'not_approved';
	public const CODE_BAD_REF              = 'bad_ref';
	public const CODE_FINGERPRINT_MISMATCH = 'fingerprint_mismatch';
	public const CODE_CANONICAL_NOT_LINKED = 'canonical_not_linked';

	/**
	 * @return array{code: self::CODE_*, context: array<string, int|string>}
	 */
	public function resolve_for_actor( int $actor_user_id, string $chat_session_id ): array {
		$sid = trim( $chat_session_id );
		if ( $sid === '' ) {
			return array(
				'code'    => self::CODE_SKIPPED_EMPTY,
				'context' => array(),
			);
		}
		$session = $this->chat->get_session( $sid );
		if ( $session === null ) {
			return array(
				'code'    => self::CODE_SESSION_MISSING,
				'context' => array(),
			);
		}
		if ( ! Template_Lab_Access::actor_may_use_chat_session( $actor_user_id, $session ) ) {
			return array(
				'code'    => self::CODE_FORBIDDEN,
				'context' => array(),
			);
		}
		$ref = $session['approved_snapshot_ref'] ?? null;
		if ( ! is_array( $ref ) || $ref === array() ) {
			return array(
				'code'    => self::CODE_NOT_APPROVED,
				'context' => array(),
			);
		}
		$state = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] ?? '' );
		if ( $state !== Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED ) {
			return array(
				'code'    => self::CODE_NOT_APPROVED,
				'context' => array(),
			);
		}
		$run_post_id = (int) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID ] ?? 0 );
		$fp          = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::ARTIFACT_FINGERPRINT ] ?? '' );
		$target      = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' );
		if ( $run_post_id <= 0 || $fp === '' || ! Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target ) ) {
			return array(
				'code'    => self::CODE_BAD_REF,
				'context' => array(),
			);
		}
		$trace = $this->artifacts->get( $run_post_id, Artifact_Category_Keys::TEMPLATE_LAB_TRACE );
		if ( ! is_array( $trace ) || (string) ( $trace['artifact_fingerprint'] ?? '' ) !== $fp ) {
			return array(
				'code'    => self::CODE_FINGERPRINT_MISMATCH,
				'context' => array(),
			);
		}
		$apply_rec     = $this->apply_state->get_template_lab_canonical_apply_record( $run_post_id );
		$canonical_key = '';
		if ( is_array( $apply_rec )
			&& (string) ( $apply_rec['artifact_fingerprint'] ?? '' ) === $fp
			&& (string) ( $apply_rec['target_kind'] ?? '' ) === $target ) {
			$canonical_key = (string) ( $apply_rec['canonical_internal_key'] ?? '' );
		}
		if ( $canonical_key === '' ) {
			return array(
				'code'    => self::CODE_CANONICAL_NOT_LINKED,
				'context' => array(),
			);
		}
		$schema_ref = (string) ( $trace['schema_ref'] ?? '' );
		$raw        = array(
			Build_Plan_Template_Lab_Context::FIELD_RUN_POST_ID            => $run_post_id,
			Build_Plan_Template_Lab_Context::FIELD_TARGET_KIND            => $target,
			Build_Plan_Template_Lab_Context::FIELD_CANONICAL_INTERNAL_KEY => $canonical_key,
			Build_Plan_Template_Lab_Context::FIELD_ARTIFACT_FINGERPRINT => $fp,
			Build_Plan_Template_Lab_Context::FIELD_SCHEMA_REF           => $schema_ref,
			Build_Plan_Template_Lab_Context::FIELD_CHAT_SESSION_ID      => $sid,
			Build_Plan_Template_Lab_Context::FIELD_LINKED_AT_UNIX       => time(),
		);
		return array(
			'code'    => self::CODE_OK,
			'context' => Build_Plan_Template_Lab_Context::sanitize( $raw ),
		);
	}
}
