<?php
/**
 * Bulk industry override management screen (Prompt 436).
 * Lists overrides across section, page template, and Build Plan item; filter and bounded remove.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Conflict_Detector;
use AIOPageBuilder\Admin\Actions\Remove_Industry_Override_Action;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Templates_Directory_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Renders centralized override list with filters and per-row remove. Admin-only.
 */
final class Industry_Override_Management_Screen {

	public const SLUG = 'aio-page-builder-industry-overrides';

	/** @var Industry_Override_Read_Model_Builder|null */
	private $read_model_builder;

	public function __construct( ?Industry_Override_Read_Model_Builder $read_model_builder = null ) {
		$this->read_model_builder = $read_model_builder;
	}

	public function get_title(): string {
		return __( 'Industry Overrides', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SETTINGS;
	}

	/**
	 * Renders the screen: capability check, filters, table, remove actions.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to access industry override management.', 'aio-page-builder' ), 403 );
		}

		$builder = $this->read_model_builder ?? new Industry_Override_Read_Model_Builder();
		$filters = $this->get_filters_from_request();
		$rows    = $builder->build( $filters );

		$base_url = \admin_url( 'admin.php?page=' . self::SLUG );
		$message  = $this->get_result_message();
		?>
		<div class="wrap aio-page-builder-screen aio-industry-override-management" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1 class="wp-heading-inline"><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-override-management-description">
				<?php \esc_html_e( 'View and manage industry recommendation overrides for sections, page templates, and Build Plan items. Remove clears the override so the default recommendation applies again.', 'aio-page-builder' ); ?>
			</p>

			<?php if ( ( $message['text'] ?? '' ) !== '' ) : ?>
				<div class="notice notice-<?php echo ( $message['type'] ?? '' ) === 'error' ? 'error' : 'success'; ?> is-dismissible">
					<p><?php echo \esc_html( $message['text'] ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			$conflict_detector = new Industry_Override_Conflict_Detector( $builder, new \AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository() );
			$conflicts         = $conflict_detector->detect();
			if ( ! empty( $conflicts ) ) :
				?>
				<div class="notice notice-warning aio-override-conflicts-notice" role="region" aria-label="<?php \esc_attr_e( 'Override conflict suggestions', 'aio-page-builder' ); ?>">
					<p><strong><?php \esc_html_e( 'Suggested review:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( sprintf( _n( '%d override may be stale or point to a missing plan/item.', '%d overrides may be stale or point to missing plans/items.', count( $conflicts ), 'aio-page-builder' ), count( $conflicts ) ) ); ?></p>
					<ul class="aio-override-conflict-list" style="margin-left: 1.5em;">
						<?php foreach ( array_slice( $conflicts, 0, 10 ) as $c ) : ?>
							<li><?php echo \esc_html( (string) ( $c['override_ref'] ?? '' ) ); ?> — <?php echo \esc_html( (string) ( $c['suggested_review_action'] ?? '' ) ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( count( $conflicts ) > 10 ) : ?>
						<p class="description"><?php \esc_html_e( 'Additional conflicts appear in the diagnostics snapshot.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="get" action="<?php echo \esc_url( $base_url ); ?>" class="aio-override-filters" style="margin: 1em 0;">
				<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
				<label for="aio-filter-target-type"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></label>
				<select name="target_type" id="aio-filter-target-type">
					<option value=""><?php \esc_html_e( 'All', 'aio-page-builder' ); ?></option>
					<option value="<?php echo \esc_attr( Industry_Override_Schema::TARGET_TYPE_SECTION ); ?>" <?php selected( $filters[ Industry_Override_Read_Model_Builder::FILTER_TARGET_TYPE ] ?? '', Industry_Override_Schema::TARGET_TYPE_SECTION ); ?>><?php \esc_html_e( 'Section', 'aio-page-builder' ); ?></option>
					<option value="<?php echo \esc_attr( Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ); ?>" <?php selected( $filters[ Industry_Override_Read_Model_Builder::FILTER_TARGET_TYPE ] ?? '', Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ); ?>><?php \esc_html_e( 'Page template', 'aio-page-builder' ); ?></option>
					<option value="<?php echo \esc_attr( Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ); ?>" <?php selected( $filters[ Industry_Override_Read_Model_Builder::FILTER_TARGET_TYPE ] ?? '', Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ); ?>><?php \esc_html_e( 'Build Plan item', 'aio-page-builder' ); ?></option>
				</select>
				<label for="aio-filter-state"><?php \esc_html_e( 'State', 'aio-page-builder' ); ?></label>
				<select name="state" id="aio-filter-state">
					<option value=""><?php \esc_html_e( 'All', 'aio-page-builder' ); ?></option>
					<option value="<?php echo \esc_attr( Industry_Override_Schema::STATE_ACCEPTED ); ?>" <?php selected( $filters[ Industry_Override_Read_Model_Builder::FILTER_STATE ] ?? '', Industry_Override_Schema::STATE_ACCEPTED ); ?>><?php \esc_html_e( 'Accepted', 'aio-page-builder' ); ?></option>
					<option value="<?php echo \esc_attr( Industry_Override_Schema::STATE_REJECTED ); ?>" <?php selected( $filters[ Industry_Override_Read_Model_Builder::FILTER_STATE ] ?? '', Industry_Override_Schema::STATE_REJECTED ); ?>><?php \esc_html_e( 'Rejected', 'aio-page-builder' ); ?></option>
				</select>
				<label><input type="checkbox" name="reason_present" value="1" <?php checked( ! empty( $filters[ Industry_Override_Read_Model_Builder::FILTER_REASON_PRESENT ] ) ); ?> /> <?php \esc_html_e( 'With reason', 'aio-page-builder' ); ?></label>
				<button type="submit" class="button"><?php \esc_html_e( 'Filter', 'aio-page-builder' ); ?></button>
			</form>

			<?php if ( $rows === array() ) : ?>
				<p><?php \esc_html_e( 'No overrides match the current filters.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Target / Artifact', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'State', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Reason', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo \esc_html( $this->format_target_type( $row['target_type'] ?? '' ) ); ?></td>
								<td>
									<?php echo \esc_html( $row['target_key'] ?? '' ); ?>
									<?php echo $this->render_artifact_link( $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Link built with esc_url/esc_html in render_artifact_link. ?>
								</td>
								<td><?php echo $row['plan_id'] !== null && $row['plan_id'] !== '' ? \esc_html( $row['plan_id'] ) : '—'; ?></td>
								<td><?php echo \esc_html( $row['state'] ?? '' ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['reason'] ?? '' ) ); ?></td>
								<td>
									<?php $this->render_remove_form( $row ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_filters_from_request(): array {
		$filters = array();
		if ( isset( $_GET['target_type'] ) && is_string( $_GET['target_type'] ) ) {
			$v = trim( \sanitize_key( \wp_unslash( $_GET['target_type'] ) ) );
			if ( in_array( $v, array( Industry_Override_Schema::TARGET_TYPE_SECTION, Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE, Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ), true ) ) {
				$filters[ Industry_Override_Read_Model_Builder::FILTER_TARGET_TYPE ] = $v;
			}
		}
		if ( isset( $_GET['state'] ) && is_string( $_GET['state'] ) ) {
			$v = trim( \sanitize_key( \wp_unslash( $_GET['state'] ) ) );
			if ( in_array( $v, array( Industry_Override_Schema::STATE_ACCEPTED, Industry_Override_Schema::STATE_REJECTED ), true ) ) {
				$filters[ Industry_Override_Read_Model_Builder::FILTER_STATE ] = $v;
			}
		}
		if ( isset( $_GET['reason_present'] ) && $_GET['reason_present'] === '1' ) {
			$filters[ Industry_Override_Read_Model_Builder::FILTER_REASON_PRESENT ] = true;
		}
		if ( isset( $_GET['industry_context_ref'] ) && is_string( $_GET['industry_context_ref'] ) ) {
			$ref = trim( \sanitize_text_field( \wp_unslash( $_GET['industry_context_ref'] ) ) );
			if ( $ref !== '' ) {
				$filters[ Industry_Override_Read_Model_Builder::FILTER_INDUSTRY_CONTEXT_REF ] = $ref;
			}
		}
		return $filters;
	}

	/**
	 * @return array{type: string, text: string}
	 */
	private function get_result_message(): array {
		if ( ! isset( $_GET['aio_override_remove'] ) || ! is_string( $_GET['aio_override_remove'] ) ) {
			return array(
				'type' => '',
				'text' => '',
			);
		}
		$v = \sanitize_key( $_GET['aio_override_remove'] );
		if ( $v === 'removed' ) {
			return array(
				'type' => 'success',
				'text' => __( 'Override removed.', 'aio-page-builder' ),
			);
		}
		if ( $v === 'error' ) {
			return array(
				'type' => 'error',
				'text' => __( 'Could not remove override or permission denied.', 'aio-page-builder' ),
			);
		}
		return array(
			'type' => '',
			'text' => '',
		);
	}

	private function format_target_type( string $target_type ): string {
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_SECTION ) {
			return __( 'Section', 'aio-page-builder' );
		}
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ) {
			return __( 'Page template', 'aio-page-builder' );
		}
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ) {
			return __( 'Build Plan item', 'aio-page-builder' );
		}
		return $target_type;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return string HTML link or empty string.
	 */
	private function render_artifact_link( array $row ): string {
		$target_type = $row['target_type'] ?? '';
		$target_key  = $row['target_key'] ?? '';
		$plan_id     = $row['plan_id'] ?? null;

		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_SECTION ) {
			$url = \admin_url( 'admin.php?page=' . Section_Templates_Directory_Screen::SLUG );
			return ' <a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Section directory', 'aio-page-builder' ) . '</a>';
		}
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ) {
			$url = \admin_url( 'admin.php?page=' . Page_Templates_Directory_Screen::SLUG );
			return ' <a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Template directory', 'aio-page-builder' ) . '</a>';
		}
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM && $plan_id !== null && $plan_id !== '' ) {
			$url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( (string) $plan_id ) . '&step=2' );
			return ' <a href="' . \esc_url( $url ) . '">' . \esc_html__( 'Build Plan', 'aio-page-builder' ) . '</a>';
		}
		return '';
	}

	/**
	 * @param array<string, mixed> $row
	 * @return void
	 */
	private function render_remove_form( array $row ): void {
		$target_type = $row['target_type'] ?? '';
		$target_key  = $row['target_key'] ?? '';
		$plan_id     = $row['plan_id'] ?? null;

		$can_remove = false;
		if ( $target_type === Industry_Override_Schema::TARGET_TYPE_SECTION && \current_user_can( \AIOPageBuilder\Infrastructure\Config\Capabilities::MANAGE_SECTION_TEMPLATES ) ) {
			$can_remove = true;
		} elseif ( $target_type === Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE && \current_user_can( \AIOPageBuilder\Infrastructure\Config\Capabilities::MANAGE_PAGE_TEMPLATES ) ) {
			$can_remove = true;
		} elseif ( $target_type === Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM && \current_user_can( \AIOPageBuilder\Infrastructure\Config\Capabilities::APPROVE_BUILD_PLANS ) ) {
			$can_remove = true;
		}

		if ( ! $can_remove ) {
			echo '—';
			return;
		}

		$action_url = \admin_url( 'admin-post.php' );
		$nonce      = \wp_nonce_field( Remove_Industry_Override_Action::NONCE_ACTION, Remove_Industry_Override_Action::NONCE_NAME, true, false );
		?>
		<form method="post" action="<?php echo \esc_url( $action_url ); ?>" style="display:inline;">
			<input type="hidden" name="action" value="aio_remove_industry_override" />
			<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nonce field HTML from wp_nonce_field(). ?>
			<input type="hidden" name="target_type" value="<?php echo \esc_attr( $target_type ); ?>" />
			<input type="hidden" name="target_key" value="<?php echo \esc_attr( (string) $target_key ); ?>" />
			<?php if ( $plan_id !== null && $plan_id !== '' ) : ?>
				<input type="hidden" name="plan_id" value="<?php echo \esc_attr( (string) $plan_id ); ?>" />
			<?php endif; ?>
			<button type="submit" class="button button-small"><?php \esc_html_e( 'Remove', 'aio-page-builder' ); ?></button>
		</form>
		<?php
	}
}
