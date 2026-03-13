<?php
/**
 * Settings admin screen. Plugin settings and reporting disclosure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder Settings screen. Renders title and not-yet-implemented notice.
 */
final class Settings_Screen {

	public const SLUG = 'aio-page-builder-settings';

	/** Placeholder until capability mapping is finalized. */
	private const CAPABILITY = 'manage_options';

	public function get_title(): string {
		return __( 'Settings', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Renders the screen. No business logic; markup only.
	 *
	 * @return void
	 */
	public function render(): void {
		$seed_result           = isset( $_GET['aio_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_seed_result'] ) : '';
		$expansion_seed_result = isset( $_GET['aio_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_expansion_seed_result'] ) : '';
		$pt_comp_expansion_seed_result = isset( $_GET['aio_pt_comp_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_pt_comp_expansion_seed_result'] ) : '';
		?>
		<div class="wrap aio-page-builder-screen aio-page-builder-settings" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-page-builder-notice"><?php \esc_html_e( 'Not yet implemented. This screen will show plugin settings and reporting disclosure.', 'aio-page-builder' ); ?></p>

			<?php if ( $seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Form section and request page template seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding form templates failed. Check that the plugin has permission to create section and page template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Form templates', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed the form section template and request/contact page template so you can use form sections from NDR Form Manager (or other registered form providers).', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_form_templates" />
				<?php \wp_nonce_field( 'aio_seed_form_templates', 'aio_seed_form_templates_nonce' ); ?>
				<?php \submit_button( __( 'Seed form section and request page template', 'aio-page-builder' ), 'secondary', 'aio_seed_form_templates_submit', false ); ?>
			</form>

			<?php if ( $expansion_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Section expansion pack seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $expansion_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding section expansion pack failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Section expansion pack', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed the curated section templates (Stats/highlights, CTA/conversion, FAQ) with field blueprints and metadata. Idempotent: re-running overwrites existing definitions for the same keys.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_section_expansion_pack" />
				<?php \wp_nonce_field( 'aio_seed_section_expansion_pack', 'aio_seed_expansion_pack_nonce' ); ?>
				<?php \submit_button( __( 'Seed section expansion pack', 'aio-page-builder' ), 'secondary', 'aio_seed_expansion_pack_submit', false ); ?>
			</form>

			<?php if ( $pt_comp_expansion_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Page template and composition expansion pack seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $pt_comp_expansion_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding page template and composition expansion pack failed. Seed the section expansion pack first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Page template and composition expansion pack', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed curated page templates (landing with stats/CTA/FAQ, FAQ page) and example compositions with one-pager metadata. Requires section expansion pack. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_page_composition_expansion_pack" />
				<?php \wp_nonce_field( 'aio_seed_page_composition_expansion_pack', 'aio_seed_pt_comp_expansion_nonce' ); ?>
				<?php \submit_button( __( 'Seed page template and composition expansion pack', 'aio-page-builder' ), 'secondary', 'aio_seed_pt_comp_expansion_submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
