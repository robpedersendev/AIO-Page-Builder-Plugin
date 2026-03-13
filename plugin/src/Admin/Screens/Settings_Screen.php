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
		$hero_intro_batch_seed_result = isset( $_GET['aio_hero_intro_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_hero_intro_batch_seed_result'] ) : '';
		$trust_proof_batch_seed_result = isset( $_GET['aio_trust_proof_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_trust_proof_batch_seed_result'] ) : '';
		$fb_value_batch_seed_result = isset( $_GET['aio_fb_value_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_fb_value_batch_seed_result'] ) : '';
		$ptf_batch_seed_result = isset( $_GET['aio_ptf_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_ptf_batch_seed_result'] ) : '';
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

			<?php if ( $hero_intro_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Hero/intro library batch (SEC-01) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $hero_intro_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding hero/intro library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Hero/intro library batch (SEC-01)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed the hero and introductory section templates (conversion, credibility, educational, local, directory, product, legal, editorial, compact, media-forward, split) with field blueprints and metadata. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_hero_intro_library_batch" />
				<?php \wp_nonce_field( 'aio_seed_hero_intro_library_batch', 'aio_seed_hero_intro_batch_nonce' ); ?>
				<?php \submit_button( __( 'Seed hero/intro library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_hero_intro_batch_submit', false ); ?>
			</form>

			<?php if ( $trust_proof_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Trust/proof library batch (SEC-02) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $trust_proof_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding trust/proof library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Trust/proof library batch (SEC-02)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed trust, proof, and authority section templates (testimonials, reviews, credentials, guarantee, case teaser, outcome stats, badges, certifications, client logos, authority, reassurance, FAQ microproof, partners, rating, quote, trust band) with field blueprints and metadata. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_trust_proof_library_batch" />
				<?php \wp_nonce_field( 'aio_seed_trust_proof_library_batch', 'aio_seed_trust_proof_batch_nonce' ); ?>
				<?php \submit_button( __( 'Seed trust/proof library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_trust_proof_batch_submit', false ); ?>
			</form>

			<?php if ( $fb_value_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Feature/benefit/value library batch (SEC-03) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $fb_value_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding feature/benefit/value library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Feature/benefit/value library batch (SEC-03)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed feature, benefit, offer, package, differentiator, and value-proposition section templates (feature grid, benefit band, offer comparison, package summary, differentiator, before/after, why choose us, product spec, service offering, value prop, compact feature, benefit detail, offer highlight, local/directory value, resource explainer) with field blueprints and metadata. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_feature_benefit_value_batch" />
				<?php \wp_nonce_field( 'aio_seed_feature_benefit_value_batch', 'aio_seed_fb_value_batch_nonce' ); ?>
				<?php \submit_button( __( 'Seed feature/benefit/value library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_fb_value_batch_submit', false ); ?>
			</form>

			<?php if ( $ptf_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Process/timeline/FAQ library batch (SEC-05) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $ptf_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding process/timeline/FAQ library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Process/timeline/FAQ library batch (SEC-05)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed process, steps, timeline, and FAQ section templates (step lists, horizontal/vertical flows, buying process, onboarding, service flow, expectations, timeline, policy explainer, FAQ standard/accordion/by category, how it works, comparison steps) with field blueprints and metadata. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_process_timeline_faq_batch" />
				<?php \wp_nonce_field( 'aio_seed_process_timeline_faq_batch', 'aio_seed_ptf_batch_nonce' ); ?>
				<?php \submit_button( __( 'Seed process/timeline/FAQ library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_ptf_batch_submit', false ); ?>
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
