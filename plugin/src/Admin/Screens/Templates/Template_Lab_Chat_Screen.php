<?php
/**
 * Template-lab assisted composition workspace (scoped chat UX; canonical state only after explicit apply).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Actions\Template_Lab_Canonical_Admin_Actions;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_Admin_Capability_Summary_Builder;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class Template_Lab_Chat_Screen {

	public const SLUG = 'aio-page-builder-template-lab';

	private Service_Container $container;

	public function __construct( Service_Container $container ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Template lab assistant', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_COMPOSITIONS;
	}

	/**
	 * @param bool $embed_in_hub When true, skip outer wrap (hub provides heading).
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), '', array( 'response' => 403 ) );
		}

		$repo = $this->container->get( 'ai_chat_session_repository' );
		if ( ! $repo instanceof AI_Chat_Session_Repository_Interface ) {
			echo '<p>' . \esc_html__( 'Chat session storage is unavailable.', 'aio-page-builder' ) . '</p>';
			return;
		}

		$uid        = (int) \get_current_user_id();
		$sessions   = $uid > 0 ? $repo->list_recent_for_owner( $uid, 25, 0 ) : array();
		$active     = isset( $_GET['session_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$detail     = $active !== '' ? $repo->get_session( $active ) : null;
		$rest_base  = \esc_url( \rest_url( 'aio-page-builder/v1' ) );
		$rest_nonce = \wp_create_nonce( 'wp_rest' );

		if ( ! $embed_in_hub ) :
			?>
		<div class="wrap aio-page-builder-screen aio-template-lab-screen">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php else : ?>
		<div class="aio-template-lab-screen">
		<?php endif; ?>
			<?php $this->render_admin_notices(); ?>
			<?php $this->render_provider_capability_summary(); ?>
			<p class="aio-description">
				<?php \esc_html_e( 'Structured template-lab workspace: chat helps draft and review. Canonical templates, compositions, and build plans change only after you explicitly approve a snapshot and run a separate apply step.', 'aio-page-builder' ); ?>
			</p>
			<p class="aio-admin-notice">
				<?php \esc_html_e( 'Generate draft → Approve snapshot → Apply to composition or page template (apply is not executed from this screen).', 'aio-page-builder' ); ?>
			</p>
			<p>
				<span class="screen-reader-text"><?php \esc_html_e( 'REST API', 'aio-page-builder' ); ?></span>
				<code><?php echo \esc_html( $rest_base ); ?></code>
			</p>
			<input type="hidden" id="aio-template-lab-rest-nonce" value="<?php echo \esc_attr( $rest_nonce ); ?>" />

			<div class="aio-template-lab-columns" style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
				<div class="aio-template-lab-sessions" style="flex:1;min-width:220px;max-width:360px;">
					<h2><?php \esc_html_e( 'Sessions', 'aio-page-builder' ); ?></h2>
					<?php if ( $sessions === array() ) : ?>
						<p><?php \esc_html_e( 'No sessions yet. Create one via the REST API or a future control here.', 'aio-page-builder' ); ?></p>
					<?php else : ?>
						<ul class="ul-disc">
							<?php foreach ( $sessions as $row ) : ?>
								<?php
								$sid = (string) ( $row['session_id'] ?? '' );
								$url = \add_query_arg(
									array(
										'session_id' => $sid,
									),
									Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB )
								);
								?>
								<li>
									<a href="<?php echo \esc_url( $url ); ?>"><?php echo \esc_html( $sid ); ?></a>
									— <span><?php echo \esc_html( (string) ( $row['status'] ?? '' ) ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="aio-template-lab-workspace" style="flex:2;min-width:280px;">
					<h2><?php \esc_html_e( 'Working session', 'aio-page-builder' ); ?></h2>
					<?php if ( $detail === null ) : ?>
						<p><?php \esc_html_e( 'Select a session from the list or open one via a direct link.', 'aio-page-builder' ); ?></p>
					<?php else : ?>
						<p><strong><?php \esc_html_e( 'Session', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( (string) ( $detail['session_id'] ?? '' ) ); ?></p>
						<p><strong><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( (string) ( $detail['status'] ?? '' ) ); ?></p>
						<?php
						$has_snap = is_array( $detail['approved_snapshot_ref'] ?? null ) && $detail['approved_snapshot_ref'] !== array();
						?>
						<p><strong><?php \esc_html_e( 'Approved snapshot', 'aio-page-builder' ); ?>:</strong>
							<?php echo $has_snap ? \esc_html__( 'Linked (reference on file; not applied automatically).', 'aio-page-builder' ) : \esc_html__( 'None yet.', 'aio-page-builder' ); ?>
						</p>
						<?php
						$ref            = is_array( $detail['approved_snapshot_ref'] ?? null ) ? $detail['approved_snapshot_ref'] : array();
						$approval_state = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_STATE ] ?? Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_PENDING );
						$target_k       = (string) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_KIND ] ?? '' );
						$can_approve    = $has_snap
							&& Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k )
							&& $approval_state !== Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED;
						$can_apply      = $has_snap
							&& $approval_state === Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED
							&& Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k );
						?>
						<?php if ( $can_approve ) : ?>
							<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-template-lab-approve-form">
								<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::ACTION_APPROVE ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_SESSION ); ?>" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
								<?php Template_Lab_Canonical_Admin_Actions::nonce_field( 'approve' ); ?>
								<?php \submit_button( __( 'Approve snapshot for apply', 'aio-page-builder' ), 'secondary', 'submit', false ); ?>
							</form>
						<?php endif; ?>
						<?php if ( $can_apply ) : ?>
							<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-template-lab-apply-form">
								<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::ACTION_APPLY ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_SESSION ); ?>" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_TARGET ); ?>" value="<?php echo \esc_attr( $target_k ); ?>" />
								<?php Template_Lab_Canonical_Admin_Actions::nonce_field( 'apply' ); ?>
								<?php \submit_button( __( 'Apply approved snapshot to canonical registry', 'aio-page-builder' ), 'primary', 'submit', false ); ?>
							</form>
						<?php endif; ?>
						<h3><?php \esc_html_e( 'Transcript (previews only)', 'aio-page-builder' ); ?></h3>
						<ul class="aio-chat-transcript" style="list-style:none;padding-left:0;">
							<?php
							$msgs = isset( $detail['messages'] ) && is_array( $detail['messages'] ) ? $detail['messages'] : array();
							foreach ( $msgs as $m ) :
								if ( ! is_array( $m ) ) {
									continue;
								}
								$role = (string) ( $m['role'] ?? '' );
								$prev = (string) ( $m['content_preview'] ?? '' );
								?>
								<li style="margin-bottom:8px;">
									<strong><?php echo \esc_html( $role ); ?>:</strong>
									<?php echo \esc_html( $prev ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<h3><?php \esc_html_e( 'Prompt (shell)', 'aio-page-builder' ); ?></h3>
					<p class="description"><?php \esc_html_e( 'Submit prompts through the REST route POST …/chat-sessions/{id}/prompt when wired from JS; this shell stays server-safe without embedding provider keys.', 'aio-page-builder' ); ?></p>
					<textarea class="large-text" rows="3" disabled aria-disabled="true" placeholder="<?php echo \esc_attr__( 'Prompt entry connects via REST / nonces — not enabled as raw POST here.', 'aio-page-builder' ); ?>"></textarea>

					<h3><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></h3>
					<p>
						<button type="button" class="button" disabled><?php \esc_html_e( 'Generate draft (provider)', 'aio-page-builder' ); ?></button>
					</p>
				</div>
			</div>
		</div>
		<?php
		if ( ! $embed_in_hub ) :
			?>
		</div>
			<?php
		endif;
	}

	private function render_admin_notices(): void {
		$ap = isset( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPROVE ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPROVE ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ap_msg = array(
			'ok'               => __( 'Snapshot approved for apply.', 'aio-page-builder' ),
			'bad_nonce'        => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized'     => __( 'You are not allowed to approve this snapshot.', 'aio-page-builder' ),
			'bad_request'      => __( 'Could not approve: invalid request.', 'aio-page-builder' ),
			'session_missing'  => __( 'Session not found.', 'aio-page-builder' ),
			'fingerprint_mismatch' => __( 'Snapshot does not match the AI run fingerprint.', 'aio-page-builder' ),
			'missing_normalized_output' => __( 'No validated draft artifact is available for this run.', 'aio-page-builder' ),
			'persist_failed'   => __( 'Could not save approval state.', 'aio-page-builder' ),
			'error'            => __( 'Approval failed.', 'aio-page-builder' ),
		);
		if ( $ap !== '' && isset( $ap_msg[ $ap ] ) ) {
			$cls = $ap === 'ok' ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $ap_msg[ $ap ] ) . '</p></div>';
		}
		$ay = isset( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPLY ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPLY ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ay_msg = array(
			'ok'                      => __( 'Canonical registry updated from the approved snapshot.', 'aio-page-builder' ),
			'already_applied'         => __( 'This snapshot was already applied; no duplicate write.', 'aio-page-builder' ),
			'bad_nonce'               => __( 'Security check failed. Try again.', 'aio-page-builder' ),
			'unauthorized'            => __( 'You are not allowed to run apply for this target.', 'aio-page-builder' ),
			'forbidden'               => __( 'You are not allowed to run apply for this target.', 'aio-page-builder' ),
			'bad_request'             => __( 'Could not apply: invalid request.', 'aio-page-builder' ),
			'session_missing'         => __( 'Session not found.', 'aio-page-builder' ),
			'not_approved'            => __( 'Snapshot is not approved yet.', 'aio-page-builder' ),
			'bad_ref'                 => __( 'Snapshot reference is incomplete.', 'aio-page-builder' ),
			'bad_snapshot_ref'        => __( 'Snapshot reference is incomplete.', 'aio-page-builder' ),
			'invalid_target_kind'     => __( 'Invalid apply target.', 'aio-page-builder' ),
			'bad_target'              => __( 'Apply target does not match the approved snapshot.', 'aio-page-builder' ),
			'fingerprint'             => __( 'Snapshot fingerprint no longer matches the run.', 'aio-page-builder' ),
			'missing_normalized_output' => __( 'No normalized draft artifact for this run.', 'aio-page-builder' ),
			'translation_failed'      => __( 'Draft could not be translated to canonical shape.', 'aio-page-builder' ),
			'persist_failed'          => __( 'Could not persist canonical record.', 'aio-page-builder' ),
			'error'                   => __( 'Apply failed.', 'aio-page-builder' ),
		);
		if ( $ay !== '' && isset( $ay_msg[ $ay ] ) ) {
			$cls = ( $ay === 'ok' || $ay === 'already_applied' ) ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $ay_msg[ $ay ] ) . '</p></div>';
		}
	}

	private function render_provider_capability_summary(): void {
		if ( ! $this->container->has( 'ai_provider_capability_summary_builder' ) ) {
			return;
		}
		$b = $this->container->get( 'ai_provider_capability_summary_builder' );
		if ( ! $b instanceof AI_Provider_Admin_Capability_Summary_Builder ) {
			return;
		}
		$rows = $b->build_rows();
		if ( $rows === array() ) {
			return;
		}
		?>
		<div class="aio-template-lab-provider-cap-summary" style="margin:1em 0;padding:8px 12px;border:1px solid #c3c4c7;background:#fcfcfc;">
			<h2 class="hndle"><?php \esc_html_e( 'Provider capabilities (read-only)', 'aio-page-builder' ); ?></h2>
			<p class="description"><?php \esc_html_e( 'Summary from registered drivers. No API keys or secret material.', 'aio-page-builder' ); ?></p>
			<table class="widefat striped" style="max-width:720px;">
				<thead>
					<tr>
						<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Credential', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Structured output', 'aio-page-builder' ); ?></th>
						<th scope="col"><?php \esc_html_e( 'Models', 'aio-page-builder' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><code><?php echo \esc_html( (string) ( $r['provider_id'] ?? '' ) ); ?></code></td>
						<td><?php echo ! empty( $r['credential_configured'] ) ? \esc_html__( 'Stored', 'aio-page-builder' ) : \esc_html__( 'Not stored', 'aio-page-builder' ); ?></td>
						<td><?php echo ! empty( $r['structured_output_supported'] ) ? \esc_html__( 'Yes', 'aio-page-builder' ) : \esc_html__( 'No', 'aio-page-builder' ); ?></td>
						<td><?php echo \esc_html( (string) (int) ( $r['models_count'] ?? 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
