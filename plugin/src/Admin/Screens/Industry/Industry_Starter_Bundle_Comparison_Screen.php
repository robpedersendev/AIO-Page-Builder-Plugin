<?php
/**
 * Read-only comparison/diff screen for starter bundles (Prompt 450).
 * Compare parent, subtype, and alternate bundle variants side by side. Admin-only; no auto-apply.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Starter_Bundle_Diff_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders side-by-side diff of two or more starter bundles (page families, template/section refs, CTA/LPagery/preset).
 */
final class Industry_Starter_Bundle_Comparison_Screen {

	public const SLUG = 'aio-page-builder-industry-bundle-comparison';

	/** GET param: comma-separated bundle keys to compare. */
	public const PARAM_BUNDLE_KEYS = 'bundle_keys';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Bundle comparison', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_LOGS;
	}

	/**
	 * Builds state: available bundles, selected keys (from GET or empty), diff result.
	 *
	 * @return array<string, mixed>
	 */
	private function get_state(): array {
		$bundle_registry = null;
		if ( $this->container instanceof Service_Container && $this->container->has( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ) ) {
			$r = $this->container->get( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY );
			$bundle_registry = $r instanceof Industry_Starter_Bundle_Registry ? $r : null;
		}
		$all_bundles = array();
		if ( $bundle_registry !== null ) {
			foreach ( $bundle_registry->list_all() as $b ) {
				if ( ( $b[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '' ) === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
					$all_bundles[] = array(
						'bundle_key' => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ),
						'label'      => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? '' ),
						'industry_key' => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ?? '' ),
						'subtype_key'  => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] ?? '' ),
					);
				}
			}
		}
		$selected_keys = array();
		if ( isset( $_GET[ self::PARAM_BUNDLE_KEYS ] ) && is_string( $_GET[ self::PARAM_BUNDLE_KEYS ] ) ) {
			$raw = trim( sanitize_text_field( wp_unslash( $_GET[ self::PARAM_BUNDLE_KEYS ] ) ) );
			if ( $raw !== '' ) {
				$selected_keys = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
			}
		}
		$diff_result = array(
			Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES   => array(),
			Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS => array(),
		);
		if ( count( $selected_keys ) >= 2 && $bundle_registry !== null ) {
			$service = new Industry_Starter_Bundle_Diff_Service( $bundle_registry );
			$diff_result = $service->compare( array_values( $selected_keys ) );
		}
		return array(
			'all_bundles'   => $all_bundles,
			'selected_keys' => $selected_keys,
			'bundles'       => $diff_result[ Industry_Starter_Bundle_Diff_Service::RESULT_BUNDLES ],
			'diff_rows'     => $diff_result[ Industry_Starter_Bundle_Diff_Service::RESULT_DIFF_ROWS ],
			'profile_url'   => admin_url( 'admin.php?page=' . Industry_Profile_Settings_Screen::SLUG ),
			'current_url'   => admin_url( 'admin.php?page=' . self::SLUG ),
		);
	}

	/**
	 * Renders the screen. Capability enforced by menu registration.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to access the bundle comparison screen.', 'aio-page-builder' ), 403 );
		}
		$state = $this->get_state();
		$bundles = $state['bundles'];
		$diff_rows = $state['diff_rows'];
		$selected_keys = $state['selected_keys'];
		?>
		<div class="wrap aio-page-builder-screen aio-industry-bundle-comparison" role="main" aria-label="<?php echo esc_attr( $this->get_title() ); ?>">
			<h1><?php echo esc_html( $this->get_title() ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Compare two or more starter bundles (page emphasis, template refs, section refs, CTA and preset notes). Read-only; choose a bundle in Industry Profile to apply.', 'aio-page-builder' ); ?>
			</p>

			<form method="get" action="<?php echo esc_url( $state['current_url'] ); ?>" class="aio-bundle-comparison-form" style="margin: 1em 0;">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>" />
				<label for="aio-bundle-keys"><?php esc_html_e( 'Bundle keys to compare (comma-separated, 2–6)', 'aio-page-builder' ); ?></label>
				<input type="text" id="aio-bundle-keys" name="<?php echo esc_attr( self::PARAM_BUNDLE_KEYS ); ?>" value="<?php echo esc_attr( implode( ', ', $selected_keys ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. plumber_starter, plumber_residential_starter', 'aio-page-builder' ); ?>" style="width: 100%; max-width: 480px; margin-right: 0.5em;" />
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Compare', 'aio-page-builder' ); ?></button>
			</form>

			<?php if ( ! empty( $state['all_bundles'] ) ) : ?>
				<p class="description">
					<?php esc_html_e( 'Available bundles:', 'aio-page-builder' ); ?>
					<?php
					$links = array();
					foreach ( $state['all_bundles'] as $b ) {
						$key = $b['bundle_key'];
						$label = $b['label'];
						$links[] = '<code>' . esc_html( $key ) . '</code> (' . esc_html( $label ) . ')';
					}
					echo implode( ' &middot; ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per item
					?>
				</p>
			<?php endif; ?>

			<?php if ( count( $bundles ) >= 2 && ! empty( $diff_rows ) ) : ?>
				<table class="wp-list-table widefat fixed striped aio-bundle-diff-table" style="margin-top: 1.5em;">
					<thead>
						<tr>
							<th scope="col" style="width: 18%;"><?php esc_html_e( 'Field', 'aio-page-builder' ); ?></th>
							<?php foreach ( $bundles as $b ) : ?>
								<th scope="col"><?php echo esc_html( $b['label'] ); ?> <code><?php echo esc_html( $b['bundle_key'] ); ?></code></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $diff_rows as $row ) : ?>
							<tr class="<?php echo $row['changed'] ? 'aio-diff-row-changed' : ''; ?>">
								<td><strong><?php echo esc_html( $row['label'] ); ?></strong><?php echo $row['changed'] ? ' <span class="aio-diff-badge" aria-label="' . esc_attr__( 'Differs', 'aio-page-builder' ) . '">*</span>' : ''; ?></td>
								<?php foreach ( $row['values'] as $idx => $val ) : ?>
									<td>
										<?php
										if ( is_array( $val ) ) {
											echo esc_html( implode( ', ', $val ) );
										} else {
											echo esc_html( (string) $val );
										}
										?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description" style="margin-top: 0.5em;">
					<a href="<?php echo esc_url( $state['profile_url'] ); ?>"><?php esc_html_e( 'Industry Profile', 'aio-page-builder' ); ?></a>
					<?php esc_html_e( '— set your selected starter bundle there. This screen does not apply changes.', 'aio-page-builder' ); ?>
				</p>
			<?php elseif ( count( $selected_keys ) === 1 ) : ?>
				<p class="notice notice-info inline" style="margin: 1em 0;"><?php esc_html_e( 'Select at least two bundle keys to compare.', 'aio-page-builder' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
