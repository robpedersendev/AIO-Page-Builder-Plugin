<?php
/**
 * Template Compare workspace: side-by-side comparison of section or page templates (Prompt 180, spec §49.6, §49.7, §50.1–50.3).
 * Observational only; no execution, Build Plan mutation, or apply-to-page flow. Compare-list stored in user meta.
 * Compare list size capped at Template_Compare_State_Builder::MAX_COMPARE_ITEMS (10) for performance (Prompt 188, template-admin-performance-hardening-report).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Shared\UI\Template_Compare_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders compare workspace: type switcher, compare-list add/remove handling, side-by-side metadata and compact preview excerpts.
 */
final class Template_Compare_Screen {

	public const SLUG = 'aio-page-builder-template-compare';

	/** User meta key for section template compare list (array of keys). */
	public const USER_META_SECTION = '_aio_compare_section_templates';

	/** User meta key for page template compare list (array of keys). */
	public const USER_META_PAGE = '_aio_compare_page_templates';

	/** Nonce action for add/remove. */
	private const NONCE_ACTION = 'aio_template_compare';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Template Compare', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_PAGE_TEMPLATES;
	}

	/**
	 * Returns the current user's compare list for the given type (for directory/detail link display).
	 *
	 * @param string $type 'section' or 'page'.
	 * @return list<string>
	 */
	public static function get_compare_list( string $type ): array {
		$meta_key = $type === 'page' ? self::USER_META_PAGE : self::USER_META_SECTION;
		$user_id  = \get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}
		$raw = \get_user_meta( $user_id, $meta_key, true );
		if ( ! \is_array( $raw ) ) {
			return array();
		}
		$list = array_values( array_filter( array_map( 'sanitize_key', $raw ), fn( string $k ): bool => $k !== '' ) );
		return array_slice( $list, 0, Template_Compare_State_Builder::MAX_COMPARE_ITEMS );
	}

	/**
	 * URL to add a template to the compare list (includes nonce). Use for "Add to compare" link.
	 *
	 * @param string $type 'section' or 'page'.
	 * @param string $key  Template internal key.
	 * @return string
	 */
	public static function get_compare_add_url( string $type, string $key ): string {
		$key = \sanitize_key( $key );
		if ( $key === '' ) {
			return \admin_url( 'admin.php?page=' . self::SLUG );
		}
		$url = \add_query_arg(
			array(
				'page' => self::SLUG,
				'type' => $type === 'page' ? 'page' : 'section',
				'add'  => $key,
			),
			\admin_url( 'admin.php' )
		);
		return \wp_nonce_url( $url, self::NONCE_ACTION, '_wpnonce' );
	}

	/**
	 * URL to remove a template from the compare list (includes nonce). Use for "Remove from compare" link.
	 *
	 * @param string $type 'section' or 'page'.
	 * @param string $key  Template internal key.
	 * @return string
	 */
	public static function get_compare_remove_url( string $type, string $key ): string {
		$key = \sanitize_key( $key );
		if ( $key === '' ) {
			return \admin_url( 'admin.php?page=' . self::SLUG );
		}
		$url = \add_query_arg(
			array(
				'page'   => self::SLUG,
				'type'   => $type === 'page' ? 'page' : 'section',
				'remove' => $key,
			),
			\admin_url( 'admin.php' )
		);
		return \wp_nonce_url( $url, self::NONCE_ACTION, '_wpnonce' );
	}

	/**
	 * Renders compare workspace: capability check, handle add/remove then redirect, build state, render.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$type = isset( $_GET['type'] ) && $_GET['type'] === 'page' ? 'page' : 'section';
		$redirect = $this->maybe_handle_add_remove( $type );
		if ( $redirect !== null ) {
			\wp_safe_redirect( $redirect );
			exit;
		}

		$compare_list = self::get_compare_list( $type );
		$state_builder = $this->container && $this->container->has( 'template_compare_state_builder' )
			? $this->container->get( 'template_compare_state_builder' )
			: null;
		if ( ! $state_builder instanceof Template_Compare_State_Builder ) {
			$state = array(
				'type'               => $type,
				'compare_list_keys'   => array(),
				'template_compare_rows' => array(),
				'base_url_sections'   => \admin_url( 'admin.php?page=aio-page-builder-section-templates' ),
				'base_url_pages'      => \admin_url( 'admin.php?page=aio-page-builder-page-templates' ),
				'compare_screen_url'  => \admin_url( 'admin.php?page=' . self::SLUG ),
				'empty_message'      => __( 'Add templates from the Section or Page Templates directory or detail screen to compare them.', 'aio-page-builder' ),
			);
		} else {
			$state = $state_builder->build_state( $type, $compare_list );
		}

		?>
		<div class="wrap aio-page-builder-screen aio-template-compare" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<?php $this->render_type_switcher( $state ); ?>
			<?php
			if ( count( $state['template_compare_rows'] ?? array() ) === 0 ) {
				$this->render_empty_state( $state );
			} else {
				$this->render_compare_matrix( $state );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Handles add/remove request; returns redirect URL or null if no action.
	 *
	 * @param string $type
	 * @return string|null Redirect URL or null.
	 */
	private function maybe_handle_add_remove( string $type ): ?string {
		$add    = isset( $_GET['add'] ) ? \sanitize_key( (string) $_GET['add'] ) : '';
		$remove = isset( $_GET['remove'] ) ? \sanitize_key( (string) $_GET['remove'] ) : '';
		if ( $add === '' && $remove === '' ) {
			return null;
		}
		if ( ! \current_user_can( $this->get_capability() ) ) {
			return null;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ), self::NONCE_ACTION ) ) {
			return null;
		}
		$user_id = \get_current_user_id();
		if ( $user_id <= 0 ) {
			return null;
		}
		$meta_key = $type === 'page' ? self::USER_META_PAGE : self::USER_META_SECTION;
		$list     = self::get_compare_list( $type );
		if ( $add !== '' ) {
			if ( ! \in_array( $add, $list, true ) && count( $list ) < Template_Compare_State_Builder::MAX_COMPARE_ITEMS ) {
				$list[] = $add;
				\update_user_meta( $user_id, $meta_key, $list );
			}
		} elseif ( $remove !== '' ) {
			$list = array_values( array_filter( $list, fn( string $k ): bool => $k !== $remove ) );
			\update_user_meta( $user_id, $meta_key, $list );
		}
		$redirect = \add_query_arg( array( 'page' => self::SLUG, 'type' => $type ), \admin_url( 'admin.php' ) );
		return $redirect;
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_type_switcher( array $state ): void {
		$type   = (string) ( $state['type'] ?? 'section' );
		$base   = (string) ( $state['compare_screen_url'] ?? '' );
		$url_section = $base !== '' ? \add_query_arg( 'type', 'section', $base ) : '';
		$url_page   = $base !== '' ? \add_query_arg( 'type', 'page', $base ) : '';
		?>
		<nav class="aio-compare-type-nav" aria-label="<?php \esc_attr_e( 'Compare type', 'aio-page-builder' ); ?>">
			<a href="<?php echo $url_section !== '' ? \esc_url( $url_section ) : '#'; ?>" class="<?php echo $type === 'section' ? 'active' : ''; ?>"><?php \esc_html_e( 'Section templates', 'aio-page-builder' ); ?></a>
			| <a href="<?php echo $url_page !== '' ? \esc_url( $url_page ) : '#'; ?>" class="<?php echo $type === 'page' ? 'active' : ''; ?>"><?php \esc_html_e( 'Page templates', 'aio-page-builder' ); ?></a>
		</nav>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_empty_state( array $state ): void {
		$msg   = (string) ( $state['empty_message'] ?? __( 'Add templates to compare from the directory or detail screen.', 'aio-page-builder' ) );
		$url_s = (string) ( $state['base_url_sections'] ?? \admin_url( 'admin.php?page=aio-page-builder-section-templates' ) );
		$url_p = (string) ( $state['base_url_pages'] ?? \admin_url( 'admin.php?page=aio-page-builder-page-templates' ) );
		?>
		<p class="aio-admin-notice"><?php echo \esc_html( $msg ); ?></p>
		<ul>
			<li><a href="<?php echo \esc_url( $url_s ); ?>"><?php \esc_html_e( 'Section Templates directory', 'aio-page-builder' ); ?></a></li>
			<li><a href="<?php echo \esc_url( $url_p ); ?>"><?php \esc_html_e( 'Page Templates directory', 'aio-page-builder' ); ?></a></li>
		</ul>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_compare_matrix( array $state ): void {
		$rows = $state['template_compare_rows'] ?? array();
		$type = (string) ( $state['type'] ?? 'section' );
		if ( count( $rows ) === 0 ) {
			return;
		}
		$labels = array(
			'name'                 => __( 'Name', 'aio-page-builder' ),
			'purpose_family'       => $type === 'page' ? __( 'Category / Purpose', 'aio-page-builder' ) : __( 'Purpose', 'aio-page-builder' ),
			'cta_direction'        => __( 'CTA direction', 'aio-page-builder' ),
			'used_sections'        => __( 'Used sections', 'aio-page-builder' ),
			'compatibility_notes'   => __( 'Compatibility', 'aio-page-builder' ),
			'animation_tier'       => __( 'Animation tier', 'aio-page-builder' ),
			'helper_ref'           => __( 'Helper ref', 'aio-page-builder' ),
			'one_pager_ref'        => __( 'One-pager', 'aio-page-builder' ),
			'preview_excerpt'      => __( 'Preview', 'aio-page-builder' ),
		);
		?>
		<div class="aio-compare-matrix-wrapper">
			<table class="wp-list-table widefat fixed striped aio-compare-matrix" aria-label="<?php \esc_attr_e( 'Template comparison', 'aio-page-builder' ); ?>">
				<thead>
					<tr>
						<th scope="col" class="aio-compare-row-label"><?php \esc_html_e( 'Attribute', 'aio-page-builder' ); ?></th>
						<?php foreach ( $rows as $row ) : ?>
							<th scope="col" class="aio-compare-col">
								<?php echo \esc_html( (string) ( $row['name'] ?? '' ) ); ?>
								<br><code><?php echo \esc_html( (string) ( $row['template_key'] ?? '' ) ); ?></code>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_keys( $labels ) as $attr ) : ?>
						<tr>
							<td class="aio-compare-row-label"><strong><?php echo \esc_html( $labels[ $attr ] ?? $attr ); ?></strong></td>
							<?php foreach ( $rows as $row ) : ?>
								<td class="aio-compare-cell">
									<?php $this->render_cell( $attr, $row ); ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td class="aio-compare-row-label"><strong><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></strong></td>
						<?php foreach ( $rows as $row ) : ?>
							<td class="aio-compare-cell">
								<a href="<?php echo \esc_url( (string) ( $row['detail_url'] ?? '#' ) ); ?>"><?php \esc_html_e( 'View', 'aio-page-builder' ); ?></a>
								| <a href="<?php echo \esc_url( self::get_compare_remove_url( $type, (string) ( $row['template_key'] ?? '' ) ) ); ?>"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
							</td>
						<?php endforeach; ?>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param string              $attr
	 * @param array<string, mixed> $row
	 * @return void
	 */
	private function render_cell( string $attr, array $row ): void {
		$val = $row[ $attr ] ?? null;
		if ( $attr === 'used_sections' && \is_array( $val ) ) {
			echo \esc_html( \implode( ', ', $val ) ?: '—' );
			return;
		}
		if ( $attr === 'compatibility_notes' && \is_array( $val ) ) {
			echo \esc_html( \wp_json_encode( $val ) ?: '—' );
			return;
		}
		if ( \is_scalar( $val ) ) {
			echo \esc_html( (string) $val ?: '—' );
			return;
		}
		echo '—';
	}
}
