<?php
/**
 * Guided repair review screen (Prompt 527). Shows repair candidates, replacement previews, and confirmation actions.
 * industry-guided-repair-workflow-contract.md. Admin-only; no auto-repair; explicit confirmation required.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Admin\ViewModels\Industry\Industry_Guided_Repair_View_Model;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Repair_Suggestion_Engine;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders repair candidates from health check and override conflicts; supports migrate, apply ref, activate pack.
 * Override resolution links to Override Management.
 */
final class Industry_Guided_Repair_Screen {

	public const SLUG = 'aio-page-builder-industry-guided-repair';

	public const NONCE_ACTION_MIGRATE   = 'aio_guided_repair_migrate';
	public const NONCE_ACTION_APPLY_REF = 'aio_guided_repair_apply_ref';
	public const NONCE_ACTION_ACTIVATE  = 'aio_guided_repair_activate';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Guided Repair', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	private function get_view_model(): Industry_Guided_Repair_View_Model {
		$candidates = array();
		$hub        = Industry_Profile_Settings_Screen::SLUG;
		$links      = array(
			'health_report'       => Admin_Screen_Hub::subtab_url( $hub, 'reports', 'health' ),
			'override_management' => Admin_Screen_Hub::tab_url( $hub, 'overrides' ),
			'industry_profile'    => Admin_Screen_Hub::tab_url( $hub, 'profile' ),
		);

		$message      = '';
		$message_type = '';
		if ( isset( $_GET['aio_repair_result'] ) && is_string( $_GET['aio_repair_result'] ) ) {
			$r = \sanitize_text_field( \wp_unslash( $_GET['aio_repair_result'] ) );
			if ( $r === 'migrated' || $r === 'applied' || $r === 'activated' ) {
				$message      = __( 'Repair action completed. Re-run health check to confirm.', 'aio-page-builder' );
				$message_type = 'success';
			} elseif ( $r === 'error' ) {
				$message      = __( 'Repair action failed or was cancelled.', 'aio-page-builder' );
				$message_type = 'error';
			}
		}

		$health        = null;
		$repair_engine = null;
		if ( $this->container instanceof Service_Container ) {
			if ( $this->container->has( 'industry_health_check_service' ) ) {
				$health = $this->container->get( 'industry_health_check_service' );
			}
			if ( $this->container->has( 'industry_repair_suggestion_engine' ) ) {
				$repair_engine = $this->container->get( 'industry_repair_suggestion_engine' );
			}
		}

		if ( $health instanceof Industry_Health_Check_Service ) {
			$result   = $health->run();
			$errors   = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
			$warnings = isset( $result['warnings'] ) && is_array( $result['warnings'] ) ? $result['warnings'] : array();
			foreach ( $errors as $issue ) {
				$candidates[] = $this->build_candidate( $issue, Industry_Guided_Repair_View_Model::SOURCE_HEALTH_ERROR, $repair_engine );
			}
			foreach ( $warnings as $issue ) {
				$candidates[] = $this->build_candidate( $issue, Industry_Guided_Repair_View_Model::SOURCE_HEALTH_WARNING, $repair_engine );
			}
		}

		$conflict_detector = null;
		if ( $this->container instanceof Service_Container && $this->container->has( 'industry_override_conflict_detector' ) ) {
			$conflict_detector = $this->container->get( 'industry_override_conflict_detector' );
		}
		if ( $conflict_detector instanceof Industry_Override_Conflict_Detector ) {
			$conflicts = $conflict_detector->detect();
			foreach ( $conflicts as $c ) {
				$candidates[] = array(
					'source'            => Industry_Guided_Repair_View_Model::SOURCE_OVERRIDE_CONFLICT,
					'object_type'       => (string) ( $c['target_type'] ?? '' ),
					'key'               => (string) ( $c['target_key'] ?? '' ),
					'issue_summary'     => (string) ( $c['suggested_review_action'] ?? '' ),
					'related_refs'      => array(),
					'repair_suggestion' => null,
					'is_advisory_only'  => false,
					'action_type'       => Industry_Guided_Repair_View_Model::ACTION_LINK_OVERRIDE_MANAGEMENT,
					'conflict'          => $c,
					'profile_field'     => '',
					'suggested_value'   => '',
				);
			}
		}

		return new Industry_Guided_Repair_View_Model( $candidates, $links, $message, $message_type );
	}

	/**
	 * @param array<string, mixed> $issue
	 * @param string               $source
	 * @param object|null          $repair_engine
	 * @return array{source: string, object_type: string, key: string, issue_summary: string, related_refs: list<string>, repair_suggestion: array|null, is_advisory_only: bool, action_type: string, conflict: array|null, profile_field: string, suggested_value: string}
	 */
	private function build_candidate( array $issue, string $source, $repair_engine ): array {
		$object_type = isset( $issue['object_type'] ) && is_string( $issue['object_type'] ) ? $issue['object_type'] : '';
		$key         = isset( $issue['key'] ) && is_string( $issue['key'] ) ? $issue['key'] : '';
		$related     = isset( $issue['related_refs'] ) && is_array( $issue['related_refs'] ) ? array_values( array_filter( array_map( 'strval', $issue['related_refs'] ) ) ) : array();
		$suggestion  = null;
		if ( $repair_engine instanceof Industry_Repair_Suggestion_Engine ) {
			$suggestion = $repair_engine->suggest_for_issue( $issue );
		}

		$action_type     = Industry_Guided_Repair_View_Model::ACTION_NONE;
		$profile_field   = '';
		$suggested_value = '';
		$is_advisory     = ( $suggestion === null );

		if ( is_array( $suggestion ) ) {
			$type    = isset( $suggestion['suggestion_type'] ) && is_string( $suggestion['suggestion_type'] ) ? $suggestion['suggestion_type'] : '';
			$sug_ref = isset( $suggestion['suggested_ref'] ) && is_string( $suggestion['suggested_ref'] ) ? $suggestion['suggested_ref'] : '';
			if ( $type === Industry_Repair_Suggestion_Engine::SUGGESTION_TYPE_DEPRECATED_REPLACEMENT && $sug_ref !== '' ) {
				$deprecated_key = $key;
				if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PROFILE && ( $key === 'primary_industry_key' || $key === 'secondary_industry_keys' ) ) {
					$deprecated_key = $related[0] ?? $key;
				}
				$action_type     = Industry_Guided_Repair_View_Model::ACTION_MIGRATE;
				$suggested_value = $deprecated_key;
			} elseif ( $type === Industry_Repair_Suggestion_Engine::SUGGESTION_TYPE_INACTIVE_ACTIVATE ) {
				$action_type     = Industry_Guided_Repair_View_Model::ACTION_ACTIVATE_PACK;
				$suggested_value = $key;
				if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PROFILE && $key === 'primary_industry_key' && isset( $related[0] ) ) {
					$suggested_value = $related[0];
				}
				$is_advisory = false;
			} elseif ( ( $type === Industry_Repair_Suggestion_Engine::SUGGESTION_TYPE_VALID_ALTERNATIVE || $type === Industry_Repair_Suggestion_Engine::SUGGESTION_TYPE_FALLBACK_BUNDLE ) && $sug_ref !== '' ) {
				if ( $object_type === Industry_Health_Check_Service::OBJECT_TYPE_PROFILE && $key === Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ) {
					$action_type     = Industry_Guided_Repair_View_Model::ACTION_APPLY_REF;
					$profile_field   = Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY;
					$suggested_value = $sug_ref;
					$is_advisory     = false;
				}
			}
		}

		return array(
			'source'            => $source,
			'object_type'       => $object_type,
			'key'               => $key,
			'issue_summary'     => isset( $issue['issue_summary'] ) && is_string( $issue['issue_summary'] ) ? $issue['issue_summary'] : '',
			'related_refs'      => $related,
			'repair_suggestion' => $suggestion,
			'is_advisory_only'  => $is_advisory,
			'action_type'       => $action_type,
			'conflict'          => null,
			'profile_field'     => $profile_field,
			'suggested_value'   => $suggested_value,
		);
	}

	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access guided repair.', 'aio-page-builder' ), 403 );
		}
		$vm         = $this->get_view_model();
		$candidates = $vm->get_candidates();
		$links      = $vm->get_links();
		$base       = Admin_Screen_Hub::tab_url( Industry_Profile_Settings_Screen::SLUG, 'repair' );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-guided-repair" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php \esc_html_e( 'Review repair suggestions for broken refs and stale overrides. Each action requires your confirmation. No automatic repairs.', 'aio-page-builder' ); ?>
			</p>
			<?php if ( $vm->get_message() !== '' ) : ?>
				<div class="notice notice-<?php echo $vm->get_message_type() === 'error' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo \esc_html( $vm->get_message() ); ?></p>
				</div>
			<?php endif; ?>
			<p>
				<a href="<?php echo \esc_url( $links['health_report'] ?? $base ); ?>"><?php \esc_html_e( 'Industry Health Report', 'aio-page-builder' ); ?></a>
				| <a href="<?php echo \esc_url( $links['override_management'] ?? $base ); ?>"><?php \esc_html_e( 'Industry Overrides', 'aio-page-builder' ); ?></a>
				| <a href="<?php echo \esc_url( $links['industry_profile'] ?? $base ); ?>"><?php \esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
			</p>

			<?php if ( count( $candidates ) === 0 ) : ?>
				<div class="notice notice-success inline">
					<p><?php \esc_html_e( 'No repair candidates. Health check and override conflict detector report no issues.', 'aio-page-builder' ); ?></p>
				</div>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Issue', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Suggested action', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Action', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $candidates as $i => $c ) : ?>
							<tr>
								<td><?php echo \esc_html( $c['source'] === Industry_Guided_Repair_View_Model::SOURCE_OVERRIDE_CONFLICT ? __( 'Override conflict', 'aio-page-builder' ) : ( $c['source'] === Industry_Guided_Repair_View_Model::SOURCE_HEALTH_ERROR ? __( 'Error', 'aio-page-builder' ) : __( 'Warning', 'aio-page-builder' ) ) ); ?></td>
								<td>
									<code><?php echo \esc_html( $c['object_type'] . ' / ' . $c['key'] ); ?></code>
									<p class="description"><?php echo \esc_html( $c['issue_summary'] ); ?></p>
								</td>
								<td>
									<?php
									if ( is_array( $c['repair_suggestion'] ) && isset( $c['repair_suggestion']['explanation'] ) ) {
										echo \esc_html( $c['repair_suggestion']['explanation'] );
										if ( ! empty( $c['repair_suggestion']['suggested_ref'] ) ) {
											echo ' <strong>' . \esc_html( (string) $c['repair_suggestion']['suggested_ref'] ) . '</strong>';
										}
									} elseif ( $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_LINK_OVERRIDE_MANAGEMENT && is_array( $c['conflict'] ) ) {
										echo \esc_html( (string) ( $c['conflict']['suggested_review_action'] ?? '' ) );
									} else {
										echo '—';
									}
									if ( $c['is_advisory_only'] && $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_NONE ) {
										echo ' <span class="description">(' . \esc_html__( 'Advisory only; choose manually in Industry Profile or Overrides.', 'aio-page-builder' ) . ')</span>';
									}
									?>
								</td>
								<td>
									<?php
									if ( $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_MIGRATE && $c['suggested_value'] !== '' ) :
										$migrate_url   = \admin_url( 'admin-post.php?action=aio_guided_repair_migrate' );
										$migrate_nonce = \wp_create_nonce( self::NONCE_ACTION_MIGRATE );
										?>
										<form method="post" action="<?php echo \esc_url( $migrate_url ); ?>" style="display:inline;">
											<?php \wp_nonce_field( self::NONCE_ACTION_MIGRATE, 'aio_guided_repair_migrate_nonce', true ); ?>
											<input type="hidden" name="deprecated_pack_key" value="<?php echo \esc_attr( $c['suggested_value'] ); ?>" />
											<button type="submit" class="button button-primary"><?php \esc_html_e( 'Migrate to replacement', 'aio-page-builder' ); ?></button>
										</form>
										<?php
									elseif ( $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_APPLY_REF && $c['profile_field'] !== '' && $c['suggested_value'] !== '' ) :
										$apply_url = \admin_url( 'admin-post.php?action=aio_guided_repair_apply_ref' );
										?>
										<form method="post" action="<?php echo \esc_url( $apply_url ); ?>" style="display:inline;">
											<?php \wp_nonce_field( self::NONCE_ACTION_APPLY_REF, 'aio_guided_repair_apply_ref_nonce', true ); ?>
											<input type="hidden" name="profile_field" value="<?php echo \esc_attr( $c['profile_field'] ); ?>" />
											<input type="hidden" name="profile_value" value="<?php echo \esc_attr( $c['suggested_value'] ); ?>" />
											<button type="submit" class="button button-primary"><?php \esc_html_e( 'Apply suggested ref', 'aio-page-builder' ); ?></button>
										</form>
										<?php
									elseif ( $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_ACTIVATE_PACK && $c['suggested_value'] !== '' ) :
										$act_url = \admin_url( 'admin-post.php?action=aio_guided_repair_activate' );
										?>
										<form method="post" action="<?php echo \esc_url( $act_url ); ?>" style="display:inline;">
											<?php \wp_nonce_field( self::NONCE_ACTION_ACTIVATE, 'aio_guided_repair_activate_nonce', true ); ?>
											<input type="hidden" name="industry_pack_key" value="<?php echo \esc_attr( $c['suggested_value'] ); ?>" />
											<button type="submit" class="button button-secondary"><?php \esc_html_e( 'Enable pack', 'aio-page-builder' ); ?></button>
										</form>
									<?php elseif ( $c['action_type'] === Industry_Guided_Repair_View_Model::ACTION_LINK_OVERRIDE_MANAGEMENT ) : ?>
										<a href="<?php echo \esc_url( $links['override_management'] ?? $base ); ?>" class="button"><?php \esc_html_e( 'Resolve in Override Management', 'aio-page-builder' ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
