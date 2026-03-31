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
use AIOPageBuilder\Admin\Actions\Template_Lab_Chat_Admin_Actions;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;
use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Session_Admin_Helper;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_Admin_Capability_Summary_Builder;
use AIOPageBuilder\Domain\Storage\AI_Chat\AI_Chat_Session_Repository_Interface;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\Messages\Template_Lab_Admin_User_Messages;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Router_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Template_Lab_Access;
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
		if ( ! Template_Lab_Access::can_access_template_lab_shell() ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), '', array( 'response' => 403 ) );
		}

		$repo = $this->container->get( 'ai_chat_session_repository' );
		if ( ! $repo instanceof AI_Chat_Session_Repository_Interface ) {
			echo '<p>' . \esc_html__( 'Chat session storage is unavailable.', 'aio-page-builder' ) . '</p>';
			return;
		}

		$uid        = (int) \get_current_user_id();
		$f_status   = isset( $_GET['aio_tl_f_status'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['aio_tl_f_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$f_task     = isset( $_GET['aio_tl_f_task'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['aio_tl_f_task'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$f_approved = isset( $_GET['aio_tl_f_approved'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['aio_tl_f_approved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$f_search   = isset( $_GET['aio_tl_f_search'] ) ? \sanitize_text_field( (string) \wp_unslash( $_GET['aio_tl_f_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sessions   = $uid > 0
			? $repo->list_for_owner_with_filters(
				$uid,
				array(
					'status'    => $f_status,
					'task_type' => $f_task,
					'approved'  => $f_approved,
					'search'    => $f_search,
				),
				25,
				0
			)
			: array();
		$active     = isset( $_GET['session_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$detail     = $active !== '' ? $repo->get_session( $active ) : null;
		$rest_base  = \esc_url( \rest_url( 'aio-page-builder/v1' ) );
		$rest_nonce = \wp_create_nonce( 'wp_rest' );

		if ( ! $embed_in_hub ) :
			?>
		<div class="wrap aio-page-builder-screen aio-template-lab-screen">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php else : ?>
		<div class="aio-template-lab-screen" role="region" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
		<?php endif; ?>
			<?php $this->render_admin_notices(); ?>
			<?php $this->render_provider_capability_summary(); ?>
			<?php $this->render_generation_readiness(); ?>
			<p class="aio-description">
				<?php \esc_html_e( 'Structured template-lab workspace: chat helps draft and review. Canonical templates, compositions, and build plans change only after you explicitly approve a snapshot and run a separate apply step.', 'aio-page-builder' ); ?>
			</p>
			<p class="aio-admin-notice">
				<?php \esc_html_e( 'Generate draft → Approve snapshot → Apply to composition or page template (apply is not executed from this screen).', 'aio-page-builder' ); ?>
			</p>
			<div class="notice notice-info inline" role="note">
				<p>
					<?php \esc_html_e( 'Generation calls external AI providers when configured; usage may incur provider cost. Nothing here becomes canonical until you approve and apply.', 'aio-page-builder' ); ?>
					<?php
					$priv_url = '';
					if ( Capabilities::current_user_can_for_route( Capabilities::MANAGE_REPORTING_AND_PRIVACY ) ) {
						$priv_url = Admin_Screen_Hub::tab_url( Privacy_Reporting_Settings_Screen::SLUG, 'privacy_reporting', array() );
					}
					$prov_url = '';
					if ( Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
						$prov_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_providers', array() );
					}
					?>
					<?php if ( $prov_url !== '' ) : ?>
						<a href="<?php echo \esc_url( $prov_url ); ?>"><?php \esc_html_e( 'AI provider settings', 'aio-page-builder' ); ?></a>
					<?php endif; ?>
					<?php if ( $prov_url !== '' && $priv_url !== '' ) : ?>
						<?php echo ' '; ?>
					<?php endif; ?>
					<?php if ( $priv_url !== '' ) : ?>
						<a href="<?php echo \esc_url( $priv_url ); ?>"><?php \esc_html_e( 'Privacy & reporting', 'aio-page-builder' ); ?></a>
					<?php endif; ?>
					<?php if ( $prov_url === '' && $priv_url === '' ) : ?>
						<?php \esc_html_e( 'Ask a site administrator about provider setup and operational reporting disclosures.', 'aio-page-builder' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<p>
				<span class="screen-reader-text"><?php \esc_html_e( 'REST API', 'aio-page-builder' ); ?></span>
				<code><?php echo \esc_html( $rest_base ); ?></code>
			</p>
			<input type="hidden" id="aio-template-lab-rest-nonce" value="<?php echo \esc_attr( $rest_nonce ); ?>" />

			<div class="aio-template-lab-columns" style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
				<div class="aio-template-lab-sessions" style="flex:1;min-width:220px;max-width:360px;" role="region" aria-label="<?php \esc_attr_e( 'Template lab sessions', 'aio-page-builder' ); ?>">
					<h2><?php \esc_html_e( 'Sessions', 'aio-page-builder' ); ?></h2>
					<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-template-lab-session-filters" style="margin-bottom:12px;">
						<input type="hidden" name="page" value="<?php echo \esc_attr( Template_Library_Hub_Urls::HUB_PAGE_SLUG ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( Template_Library_Hub_Urls::QUERY_TAB ); ?>" value="<?php echo \esc_attr( Template_Library_Hub_Urls::TAB_TEMPLATE_LAB ); ?>" />
						<?php if ( $active !== '' ) : ?>
							<input type="hidden" name="session_id" value="<?php echo \esc_attr( $active ); ?>" />
						<?php endif; ?>
						<p>
							<label for="aio_tl_f_status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></label><br />
							<input type="text" name="aio_tl_f_status" id="aio_tl_f_status" value="<?php echo \esc_attr( $f_status ); ?>" class="regular-text" />
						</p>
						<p>
							<label for="aio_tl_f_task"><?php \esc_html_e( 'Task type', 'aio-page-builder' ); ?></label><br />
							<input type="text" name="aio_tl_f_task" id="aio_tl_f_task" value="<?php echo \esc_attr( $f_task ); ?>" class="regular-text" />
						</p>
						<p>
							<label for="aio_tl_f_approved"><?php \esc_html_e( 'Approved snapshot', 'aio-page-builder' ); ?></label><br />
							<select name="aio_tl_f_approved" id="aio_tl_f_approved">
								<option value=""><?php \esc_html_e( 'Any', 'aio-page-builder' ); ?></option>
								<option value="1" <?php \selected( $f_approved, '1' ); ?>><?php \esc_html_e( 'Present', 'aio-page-builder' ); ?></option>
								<option value="0" <?php \selected( $f_approved, '0' ); ?>><?php \esc_html_e( 'Not present', 'aio-page-builder' ); ?></option>
							</select>
						</p>
						<p>
							<label for="aio_tl_f_search"><?php \esc_html_e( 'Search session id', 'aio-page-builder' ); ?></label><br />
							<input type="search" name="aio_tl_f_search" id="aio_tl_f_search" value="<?php echo \esc_attr( $f_search ); ?>" class="regular-text" />
						</p>
						<p><button type="submit" class="button" data-aio-ux-action="template_lab_apply_filters" data-aio-ux-section="template_lab_filters" data-aio-ux-hub="<?php echo \esc_attr( Page_Templates_Directory_Screen::SLUG ); ?>" data-aio-ux-tab="template_lab"><?php \esc_html_e( 'Apply filters', 'aio-page-builder' ); ?></button></p>
					</form>
					<?php if ( $sessions === array() ) : ?>
						<p>
							<?php
							echo ( $f_status !== '' || $f_task !== '' || $f_approved !== '' || $f_search !== '' )
								? \esc_html__( 'No sessions match the current filters.', 'aio-page-builder' )
								: \esc_html__( 'No sessions yet. Create one with the button below or via the REST API.', 'aio-page-builder' );
							?>
						</p>
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
				<div class="aio-template-lab-workspace" style="flex:2;min-width:280px;" role="region" aria-label="<?php \esc_attr_e( 'Working session', 'aio-page-builder' ); ?>">
					<h2><?php \esc_html_e( 'Working session', 'aio-page-builder' ); ?></h2>
					<?php if ( $detail === null ) : ?>
						<p><?php \esc_html_e( 'Select a session from the list or open one via a direct link.', 'aio-page-builder' ); ?></p>
					<?php else : ?>
						<p><strong><?php \esc_html_e( 'Session', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( (string) ( $detail['session_id'] ?? '' ) ); ?></p>
						<?php
						$fork_src = (string) ( $detail['fork_source_session_id'] ?? '' );
						if ( $fork_src !== '' ) :
							?>
						<p class="description">
							<?php
							echo \esc_html(
								sprintf(
									/* translators: %s: source session id */
									__( 'Forked from session %s (original history unchanged).', 'aio-page-builder' ),
									$fork_src
								)
							);
							?>
						</p>
							<?php
						endif;
						?>
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
						$run_post_id    = (int) ( $ref[ Template_Lab_Approved_Snapshot_Ref_Keys::RUN_POST_ID ] ?? 0 );
						$apply_rec      = null;
						if ( $run_post_id > 0 && $this->container->has( 'ai_run_repository' ) ) {
							$rr = $this->container->get( 'ai_run_repository' );
							if ( $rr instanceof AI_Run_Repository ) {
								$apply_rec = $rr->get_template_lab_canonical_apply_record( $run_post_id );
							}
						}
						$applied_ok  = is_array( $apply_rec ) && (string) ( $apply_rec['canonical_internal_key'] ?? '' ) !== '';
						$can_approve = $has_snap
							&& Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k )
							&& $approval_state !== Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED
							&& Template_Lab_Access::current_user_can_approve_or_apply_for_target( $target_k );
						$can_apply   = $has_snap
							&& $approval_state === Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED
							&& Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k )
							&& Template_Lab_Access::current_user_can_approve_or_apply_for_target( $target_k );
						?>
						<p><strong><?php \esc_html_e( 'Apply status', 'aio-page-builder' ); ?>:</strong>
							<?php
							echo $applied_ok
								? \esc_html__( 'This run snapshot was applied to canonical storage (see registry for the live record).', 'aio-page-builder' )
								: \esc_html__( 'Not applied to canonical storage yet (approval alone does not publish templates).', 'aio-page-builder' );
							?>
						</p>
						<?php
						$norm_preview = null;
						if ( $has_snap && Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k ) && $run_post_id > 0 && $this->container->has( 'ai_run_artifact_service' ) ) {
							$asvc = $this->container->get( 'ai_run_artifact_service' );
							if ( $asvc instanceof AI_Run_Artifact_Service ) {
								$rawn         = $asvc->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
								$norm_preview = is_array( $rawn ) ? $rawn : null;
							}
						}
						if ( is_array( $norm_preview ) && $norm_preview !== array() ) :
							$sum_lines = Template_Lab_Session_Admin_Helper::approved_snapshot_summary_lines( $target_k, $norm_preview );
							if ( $sum_lines !== array() ) :
								?>
						<div class="aio-template-lab-snapshot-summary" style="margin:10px 0;padding:8px 12px;border:1px solid #c3c4c7;background:#f6f7f7;">
							<h3 class="hndle"><?php \esc_html_e( 'Pre-apply summary (schema-oriented)', 'aio-page-builder' ); ?></h3>
							<ul class="ul-disc">
								<?php foreach ( $sum_lines as $sl ) : ?>
									<li><?php echo \esc_html( $sl ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
								<?php
							endif;
						endif;
						if ( $can_apply && is_array( $norm_preview ) && $this->container->has( 'composition_repository' )
							&& $this->container->has( 'page_template_repository' ) && $this->container->has( 'section_template_repository' ) ) :
							$comp = $this->container->get( 'composition_repository' );
							$pg   = $this->container->get( 'page_template_repository' );
							$sec  = $this->container->get( 'section_template_repository' );
							if ( $comp instanceof Composition_Repository && $pg instanceof Page_Template_Repository && $sec instanceof Section_Template_Repository ) {
								$ex  = Template_Lab_Session_Admin_Helper::load_existing_definition_for_compare( $target_k, $norm_preview, $comp, $pg, $sec );
								$cmp = Template_Lab_Session_Admin_Helper::compare_preview_lines( $target_k, $norm_preview, $ex );
								if ( $cmp !== array() ) {
									?>
						<div class="aio-template-lab-compare-preview" style="margin:10px 0;padding:8px 12px;border:1px dashed #646970;background:#fff;">
							<h3 class="hndle"><?php \esc_html_e( 'Compare preview (summary)', 'aio-page-builder' ); ?></h3>
							<ul class="ul-disc">
									<?php foreach ( $cmp as $cl ) : ?>
								<li><?php echo \esc_html( $cl ); ?></li>
									<?php endforeach; ?>
							</ul>
						</div>
									<?php
								}
							}
						endif;
						?>
						<?php if ( $can_approve ) : ?>
							<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-template-lab-approve-form">
								<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::ACTION_APPROVE ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_SESSION ); ?>" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
								<?php Template_Lab_Canonical_Admin_Actions::nonce_field( 'approve' ); ?>
								<?php
								\submit_button(
									__( 'Approve snapshot for apply', 'aio-page-builder' ),
									'secondary',
									'aio_tl_approve_submit',
									false,
									array(
										'id'              => 'aio_tl_approve_submit',
										'data-aio-ux-action' => 'template_lab_approve_snapshot',
										'data-aio-ux-section' => 'template_lab_session',
										'data-aio-ux-hub' => Page_Templates_Directory_Screen::SLUG,
										'data-aio-ux-tab' => 'template_lab',
									)
								);
								?>
							</form>
							<?php
						elseif ( $has_snap && Template_Lab_Approved_Snapshot_Ref_Keys::is_valid_target_kind( $target_k )
							&& $approval_state !== Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED
							&& ! Template_Lab_Access::current_user_can_approve_or_apply_for_target( $target_k ) ) :
							?>
							<p class="description"><?php \esc_html_e( 'You can view this session, but approving this snapshot requires the registry capability for the selected target.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
						<?php if ( $can_apply ) : ?>
							<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-template-lab-apply-form">
								<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::ACTION_APPLY ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_SESSION ); ?>" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
								<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Canonical_Admin_Actions::FIELD_TARGET ); ?>" value="<?php echo \esc_attr( $target_k ); ?>" />
								<?php Template_Lab_Canonical_Admin_Actions::nonce_field( 'apply' ); ?>
								<?php
								\submit_button(
									__( 'Apply approved snapshot to canonical registry', 'aio-page-builder' ),
									'primary',
									'aio_tl_apply_submit',
									false,
									array(
										'id'              => 'aio_tl_apply_submit',
										'data-aio-ux-action' => 'template_lab_apply_snapshot',
										'data-aio-ux-section' => 'template_lab_session',
										'data-aio-ux-hub' => Page_Templates_Directory_Screen::SLUG,
										'data-aio-ux-tab' => 'template_lab',
									)
								);
								?>
							</form>
						<?php elseif ( $has_snap && $approval_state === Template_Lab_Approved_Snapshot_Ref_Keys::APPROVAL_APPROVED && ! Template_Lab_Access::current_user_can_approve_or_apply_for_target( $target_k ) ) : ?>
							<p class="description"><?php \esc_html_e( 'Apply is not available for your role on this target type.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
						<h3><?php \esc_html_e( 'Transcript (previews only)', 'aio-page-builder' ); ?></h3>
						<ul class="aio-chat-transcript" style="list-style:none;padding-left:0;" aria-label="<?php \esc_attr_e( 'Chat transcript previews', 'aio-page-builder' ); ?>">
							<?php
							$msgs = isset( $detail['messages'] ) && is_array( $detail['messages'] ) ? $detail['messages'] : array();
							foreach ( $msgs as $m ) :
								if ( ! is_array( $m ) ) {
									continue;
								}
								$role = (string) ( $m['role'] ?? '' );
								$prev = (string) ( $m['content_preview'] ?? '' );
								$cls  = 'aio-chat-row aio-chat-row--' . \sanitize_html_class( $role !== '' ? $role : 'unknown' );
								?>
								<li style="margin-bottom:8px;" class="<?php echo \esc_attr( $cls ); ?>">
									<strong><?php echo \esc_html( Template_Lab_Session_Admin_Helper::transcript_row_label( $role ) ); ?>:</strong>
									<?php echo \esc_html( $prev ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<h3><?php \esc_html_e( 'Prompt (server fallback)', 'aio-page-builder' ); ?></h3>
					<p class="description"><?php \esc_html_e( 'Posts through admin-post with a nonce (same application service as REST). JS/REST remains optional.', 'aio-page-builder' ); ?></p>
					<?php if ( $detail !== null ) : ?>
						<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Chat_Admin_Actions::ACTION_SUBMIT_PROMPT ); ?>" />
							<input type="hidden" name="aio_tl_prompt_session" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
							<?php Template_Lab_Chat_Admin_Actions::nonce_field( 'prompt' ); ?>
							<label for="aio_tl_prompt_text" class="screen-reader-text"><?php \esc_html_e( 'Prompt', 'aio-page-builder' ); ?></label>
							<textarea class="large-text" name="aio_tl_prompt_text" id="aio_tl_prompt_text" rows="4" required></textarea>
							<?php
							\submit_button(
								__( 'Submit prompt (records session + run shell)', 'aio-page-builder' ),
								'secondary',
								'submit',
								false,
								array(
									'data-aio-ux-action'  => 'template_lab_submit_prompt',
									'data-aio-ux-section' => 'template_lab_prompt',
									'data-aio-ux-hub'     => Page_Templates_Directory_Screen::SLUG,
									'data-aio-ux-tab'     => 'template_lab',
								)
							);
							?>
						</form>
					<?php else : ?>
						<p class="description"><?php \esc_html_e( 'Open a session to submit a prompt without REST.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>

					<?php
					if ( $detail !== null ) :
						$msg_c = isset( $detail['messages'] ) && is_array( $detail['messages'] ) ? count( $detail['messages'] ) : 0;
						$has_s = is_array( $detail['approved_snapshot_ref'] ?? null ) && $detail['approved_snapshot_ref'] !== array();
						if ( $msg_c > 0 || $has_s ) :
							?>
					<h3><?php \esc_html_e( 'Fork session', 'aio-page-builder' ); ?></h3>
					<p class="description"><?php \esc_html_e( 'Start a new working session from this one. Transcripts and approvals are not copied; you must approve any new snapshot separately.', 'aio-page-builder' ); ?></p>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo \esc_attr( Template_Lab_Chat_Admin_Actions::ACTION_FORK_SESSION ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( Template_Lab_Chat_Admin_Actions::FIELD_FORK_SOURCE ); ?>" value="<?php echo \esc_attr( (string) ( $detail['session_id'] ?? '' ) ); ?>" />
							<?php Template_Lab_Chat_Admin_Actions::nonce_field( 'fork' ); ?>
							<?php
							\submit_button(
								__( 'Duplicate / fork from this session', 'aio-page-builder' ),
								'secondary',
								'submit',
								false,
								array(
									'data-aio-ux-action'  => 'template_lab_fork_session',
									'data-aio-ux-section' => 'template_lab_fork',
									'data-aio-ux-hub'     => Page_Templates_Directory_Screen::SLUG,
									'data-aio-ux-tab'     => 'template_lab',
								)
							);
							?>
					</form>
							<?php
						endif;
					endif;
					?>

					<h3><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></h3>
					<p class="description"><?php \esc_html_e( 'Provider generation is still driven by your orchestration/REST wiring; this screen records intent and approval/apply gates.', 'aio-page-builder' ); ?></p>
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
		$ap     = isset( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPROVE ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPROVE ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ap_msg = Template_Lab_Admin_User_Messages::approve_result_messages();
		if ( $ap !== '' && isset( $ap_msg[ $ap ] ) ) {
			$cls = $ap === 'ok' ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $ap_msg[ $ap ] ) . '</p></div>';
		}
		$ay     = isset( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPLY ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Canonical_Admin_Actions::QUERY_APPLY ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ay_msg = Template_Lab_Admin_User_Messages::apply_result_messages();
		if ( $ay !== '' && isset( $ay_msg[ $ay ] ) ) {
			$cls = ( $ay === 'ok' || $ay === 'already_applied' ) ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $ay_msg[ $ay ] ) . '</p></div>';
		}
		$cc     = isset( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_CREATE ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_CREATE ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cc_msg = Template_Lab_Admin_User_Messages::session_create_messages();
		if ( $cc !== '' && isset( $cc_msg[ $cc ] ) ) {
			$cls = $cc === 'ok' ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $cc_msg[ $cc ] ) . '</p></div>';
		}
		$cp     = isset( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_PROMPT ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_PROMPT ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cp_msg = Template_Lab_Admin_User_Messages::prompt_submit_messages();
		if ( $cp !== '' && isset( $cp_msg[ $cp ] ) ) {
			$cls = $cp === 'ok' ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $cp_msg[ $cp ] ) . '</p></div>';
		}
		$fk     = isset( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_FORK ] ) ? \sanitize_key( (string) \wp_unslash( $_GET[ Template_Lab_Chat_Admin_Actions::QUERY_FORK ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$fk_msg = Template_Lab_Admin_User_Messages::session_fork_messages();
		if ( $fk !== '' && isset( $fk_msg[ $fk ] ) ) {
			$cls = $fk === 'ok' ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . \esc_attr( $cls ) . ' is-dismissible"><p>' . \esc_html( $fk_msg[ $fk ] ) . '</p></div>';
		}
	}

	private function render_generation_readiness(): void {
		if ( ! $this->container->has( 'ai_provider_router' ) ) {
			return;
		}
		$r = $this->container->get( 'ai_provider_router' );
		if ( ! $r instanceof AI_Provider_Router_Interface ) {
			return;
		}
		$lines = Template_Lab_Session_Admin_Helper::generation_readiness_lines( $r );
		if ( $lines === array() ) {
			return;
		}
		?>
		<div class="aio-template-lab-gen-readiness" style="margin:1em 0;padding:8px 12px;border:1px solid #c3c4c7;background:#f0f6fc;">
			<h2 class="hndle"><?php \esc_html_e( 'Template-lab generation readiness', 'aio-page-builder' ); ?></h2>
			<ul class="ul-disc">
				<?php foreach ( $lines as $ln ) : ?>
					<li><?php echo \esc_html( $ln ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php if ( Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) : ?>
				<p><a href="<?php echo \esc_url( Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'providers' ) ); ?>"><?php \esc_html_e( 'Open AI provider settings', 'aio-page-builder' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
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
