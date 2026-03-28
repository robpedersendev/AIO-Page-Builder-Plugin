<?php
/**
 * Shared user-visible strings for template-lab admin-post and screen notices (keeps vocabulary consistent).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Messages;

defined( 'ABSPATH' ) || exit;

final class Template_Lab_Admin_User_Messages {

	/**
	 * @return array<string, string>
	 */
	public static function approve_result_messages(): array {
		return array(
			'ok'                        => __( 'Snapshot approved for apply.', 'aio-page-builder' ),
			'bad_nonce'                 => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized'              => __( 'You are not allowed to approve this snapshot.', 'aio-page-builder' ),
			'bad_request'               => __( 'Could not approve: invalid request.', 'aio-page-builder' ),
			'session_missing'           => __( 'Session not found.', 'aio-page-builder' ),
			'fingerprint_mismatch'      => __( 'Snapshot does not match the AI run fingerprint.', 'aio-page-builder' ),
			'missing_normalized_output' => __( 'No validated draft artifact is available for this run.', 'aio-page-builder' ),
			'persist_failed'            => __( 'Could not save approval state.', 'aio-page-builder' ),
			'error'                     => __( 'Approval failed.', 'aio-page-builder' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function apply_result_messages(): array {
		return array(
			'ok'                        => __( 'Canonical registry updated from the approved snapshot.', 'aio-page-builder' ),
			'already_applied'           => __( 'This snapshot was already applied; no duplicate write.', 'aio-page-builder' ),
			'bad_nonce'                 => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized'              => __( 'You are not allowed to run apply for this target.', 'aio-page-builder' ),
			'forbidden'                 => __( 'You are not allowed to run apply for this target.', 'aio-page-builder' ),
			'bad_request'               => __( 'Could not apply: invalid request.', 'aio-page-builder' ),
			'session_missing'           => __( 'Session not found.', 'aio-page-builder' ),
			'not_approved'              => __( 'Snapshot is not approved yet.', 'aio-page-builder' ),
			'bad_ref'                   => __( 'Snapshot reference is incomplete.', 'aio-page-builder' ),
			'bad_snapshot_ref'          => __( 'Snapshot reference is incomplete.', 'aio-page-builder' ),
			'invalid_target_kind'       => __( 'Invalid apply target.', 'aio-page-builder' ),
			'bad_target'                => __( 'Apply target does not match the approved snapshot.', 'aio-page-builder' ),
			'fingerprint'               => __( 'Snapshot fingerprint no longer matches the run.', 'aio-page-builder' ),
			'missing_normalized_output' => __( 'No normalized draft artifact for this run.', 'aio-page-builder' ),
			'translation_failed'        => __( 'Draft could not be translated to canonical shape.', 'aio-page-builder' ),
			'persist_failed'            => __( 'Could not persist canonical record.', 'aio-page-builder' ),
			'stale_snapshot_context'    => __( 'This approved snapshot no longer matches the current registry or validation context. Regenerate or re-approve before applying.', 'aio-page-builder' ),
			'error'                     => __( 'Apply failed.', 'aio-page-builder' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function session_create_messages(): array {
		return array(
			'ok'           => __( 'Session created.', 'aio-page-builder' ),
			'bad_nonce'    => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized' => __( 'You are not allowed to create a session.', 'aio-page-builder' ),
			'error'        => __( 'Could not create session.', 'aio-page-builder' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function session_fork_messages(): array {
		return array(
			'ok'           => __( 'New session forked from the selected session.', 'aio-page-builder' ),
			'bad_nonce'    => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized' => __( 'You are not allowed to fork this session.', 'aio-page-builder' ),
			'bad_request'  => __( 'Choose a session to fork.', 'aio-page-builder' ),
			'error'        => __( 'Could not fork session.', 'aio-page-builder' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function prompt_submit_messages(): array {
		return array(
			'ok'                => __( 'Prompt recorded (session updated).', 'aio-page-builder' ),
			'bad_nonce'         => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized'      => __( 'You are not allowed to submit a prompt.', 'aio-page-builder' ),
			'bad_request'       => __( 'Invalid prompt or session.', 'aio-page-builder' ),
			'session_not_found' => __( 'Session not found.', 'aio-page-builder' ),
			'forbidden'         => __( 'You cannot use this session.', 'aio-page-builder' ),
			'append_failed'     => __( 'Could not store the message.', 'aio-page-builder' ),
			'run_create_failed' => __( 'Could not create the AI run shell.', 'aio-page-builder' ),
			'error'             => __( 'Prompt submit failed.', 'aio-page-builder' ),
		);
	}
}
