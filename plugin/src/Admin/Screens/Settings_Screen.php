<?php
/**
 * Settings admin screen. Plugin settings and reporting disclosure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Bootstrap\Constants;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Settings screen: template library seed actions and link to privacy/reporting disclosure (spec §49.12).
 * Implemented (SPR-005): real intro with version and link to Privacy, Reporting & Settings; seed actions retained.
 */
final class Settings_Screen {

	public const SLUG = 'aio-page-builder-settings';

	/** Nested hub sub-tab under "General & seeding". */
	public const SETTINGS_SUBTAB_OVERVIEW = 'overview';

	public const SETTINGS_SUBTAB_SECTION_PAGE_TEMPLATES = 'section_page_templates';

	/** Gated by plugin capability for settings (spec §44.3). */
	private const CAPABILITY = Capabilities::MANAGE_SETTINGS;

	public function get_title(): string {
		return __( 'Settings', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return self::CAPABILITY;
	}

	/**
	 * Outputs a submit control with data-aio-ux-* for client UX trace batching.
	 *
	 * @param string $text    Button label.
	 * @param string $type    WordPress button type (primary, secondary, delete).
	 * @param string $name    Input name attribute.
	 * @param string $action  Stable trace action id (snake_case).
	 * @param string $section Stable section id for grouping.
	 */
	private function ux_traced_submit_button( string $text, string $type, string $name, string $action, string $section ): void {
		$attrs = sprintf(
			'data-aio-ux-action="%s" data-aio-ux-section="%s" data-aio-ux-hub="%s" data-aio-ux-tab="general"',
			\esc_attr( $action ),
			\esc_attr( $section ),
			\esc_attr( self::SLUG )
		);
		\submit_button( $text, $type, $name, false, $attrs );
	}

	/**
	 * Renders the screen. No business logic; markup only.
	 *
	 * @param string $settings_subtab Hub nested tab: overview or section/page template batches.
	 * @return void
	 */
	public function render( bool $embed_in_hub = false, string $settings_subtab = self::SETTINGS_SUBTAB_OVERVIEW ): void {
		$settings_subtab = \sanitize_key( $settings_subtab );
		if ( $settings_subtab === self::SETTINGS_SUBTAB_SECTION_PAGE_TEMPLATES ) {
			$this->render_section_page_template_seeding( $embed_in_hub );
			return;
		}
		$this->render_general_overview( $embed_in_hub );
	}

	/**
	 * Overview: version blurb, privacy link, form template seed.
	 *
	 * @return void
	 */
	private function render_general_overview( bool $embed_in_hub ): void {
		$seed_result = isset( $_GET['aio_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_seed_result'] ) : '';
		$privacy_url = Admin_Screen_Hub::tab_url( self::SLUG, 'privacy' );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-page-builder-settings" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description">
				<?php
				printf(
					/* translators: 1: plugin version, 2: opening link tag, 3: closing link tag */
					\esc_html__( 'Plugin version %1$s. This plugin sends operational reports to an approved destination. For full disclosure, retention, and privacy: %2$sPrivacy, Reporting & Settings%3$s.', 'aio-page-builder' ),
					\esc_html( Constants::plugin_version() ),
					'<a href="' . \esc_url( $privacy_url ) . '">',
					'</a>'
				);
				?>
			</p>

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
				<?php $this->ux_traced_submit_button( __( 'Seed form section and request page template', 'aio-page-builder' ), 'secondary', 'aio_seed_form_templates_submit', 'settings_seed_form_templates', 'settings_overview' ); ?>
			</form>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Section and page template batch seeding UI.
	 *
	 * @return void
	 */
	private function render_section_page_template_seeding( bool $embed_in_hub ): void {
		$seed_all_section_result                      = isset( $_GET['aio_seed_all_section_result'] ) ? \sanitize_key( (string) $_GET['aio_seed_all_section_result'] ) : '';
		$seed_all_page_result                         = isset( $_GET['aio_seed_all_page_result'] ) ? \sanitize_key( (string) $_GET['aio_seed_all_page_result'] ) : '';
		$expansion_seed_result                        = isset( $_GET['aio_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_expansion_seed_result'] ) : '';
		$hero_intro_batch_seed_result                 = isset( $_GET['aio_hero_intro_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_hero_intro_batch_seed_result'] ) : '';
		$trust_proof_batch_seed_result                = isset( $_GET['aio_trust_proof_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_trust_proof_batch_seed_result'] ) : '';
		$fb_value_batch_seed_result                   = isset( $_GET['aio_fb_value_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_fb_value_batch_seed_result'] ) : '';
		$ptf_batch_seed_result                        = isset( $_GET['aio_ptf_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_ptf_batch_seed_result'] ) : '';
		$mlp_batch_seed_result                        = isset( $_GET['aio_mlp_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_mlp_batch_seed_result'] ) : '';
		$lpu_batch_seed_result                        = isset( $_GET['aio_lpu_batch_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_lpu_batch_seed_result'] ) : '';
		$cta_super_seed_result                        = isset( $_GET['aio_cta_super_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_cta_super_seed_result'] ) : '';
		$pt_comp_expansion_seed_result                = isset( $_GET['aio_pt_comp_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_pt_comp_expansion_seed_result'] ) : '';
		$top_level_marketing_seed_result              = isset( $_GET['aio_top_level_marketing_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_top_level_marketing_seed_result'] ) : '';
		$top_level_legal_utility_seed_result          = isset( $_GET['aio_top_level_legal_utility_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_top_level_legal_utility_seed_result'] ) : '';
		$top_level_edu_resource_authority_seed_result = isset( $_GET['aio_top_level_edu_resource_authority_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_top_level_edu_resource_authority_seed_result'] ) : '';
		$top_level_variant_expansion_seed_result      = isset( $_GET['aio_top_level_variant_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_top_level_variant_expansion_seed_result'] ) : '';
		$hub_page_seed_result                         = isset( $_GET['aio_hub_page_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_hub_page_seed_result'] ) : '';
		$geographic_hub_seed_result                   = isset( $_GET['aio_geographic_hub_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_geographic_hub_seed_result'] ) : '';
		$nested_hub_seed_result                       = isset( $_GET['aio_nested_hub_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_nested_hub_seed_result'] ) : '';
		$hub_nested_hub_variant_expansion_seed_result = isset( $_GET['aio_hub_nested_hub_variant_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_hub_nested_hub_variant_expansion_seed_result'] ) : '';
		$child_detail_seed_result                     = isset( $_GET['aio_child_detail_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_child_detail_seed_result'] ) : '';
		$child_detail_product_seed_result             = isset( $_GET['aio_child_detail_product_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_child_detail_product_seed_result'] ) : '';
		$child_detail_profile_entity_seed_result      = isset( $_GET['aio_child_detail_profile_entity_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_child_detail_profile_entity_seed_result'] ) : '';
		$child_detail_variant_expansion_seed_result   = isset( $_GET['aio_child_detail_variant_expansion_seed_result'] ) ? \sanitize_key( (string) $_GET['aio_child_detail_variant_expansion_seed_result'] ) : '';
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-page-builder-settings" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>

			<?php if ( $seed_all_section_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'All section template batches seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $seed_all_section_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding one or more section batches failed. Check debug logs and registry permissions.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>
			<?php if ( $seed_all_page_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'All page template batches seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $seed_all_page_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding one or more page template batches failed. Seed the section library first; check debug logs.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Bulk seed', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Runs every section batch (expansion pack through gap-closing) or every page batch (page/composition expansion through page gap-closing) in order. Idempotent; can take a while.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0.5em 1em 0;display:inline-block;vertical-align:top;">
				<input type="hidden" name="action" value="aio_seed_all_section_templates" />
				<?php \wp_nonce_field( 'aio_seed_all_section_templates', 'aio_seed_all_section_templates_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed all section templates', 'aio-page-builder' ), 'primary', 'aio_seed_all_section_submit', 'settings_seed_all_section_templates', 'settings_section_page_batches' ); ?>
			</form>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;display:inline-block;vertical-align:top;">
				<input type="hidden" name="action" value="aio_seed_all_page_templates" />
				<?php \wp_nonce_field( 'aio_seed_all_page_templates', 'aio_seed_all_page_templates_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed all page templates', 'aio-page-builder' ), 'primary', 'aio_seed_all_page_submit', 'settings_seed_all_page_templates', 'settings_section_page_batches' ); ?>
			</form>

			<hr />

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
				<?php $this->ux_traced_submit_button( __( 'Seed section expansion pack', 'aio-page-builder' ), 'secondary', 'aio_seed_expansion_pack_submit', 'settings_seed_section_expansion_pack', 'settings_section_page_batches' ); ?>
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
				<?php $this->ux_traced_submit_button( __( 'Seed hero/intro library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_hero_intro_batch_submit', 'settings_seed_hero_intro_library_batch', 'settings_section_page_batches' ); ?>
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
				<?php $this->ux_traced_submit_button( __( 'Seed trust/proof library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_trust_proof_batch_submit', 'settings_seed_trust_proof_library_batch', 'settings_section_page_batches' ); ?>
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
				<?php $this->ux_traced_submit_button( __( 'Seed feature/benefit/value library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_fb_value_batch_submit', 'settings_seed_feature_benefit_value_batch', 'settings_section_page_batches' ); ?>
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
				<?php $this->ux_traced_submit_button( __( 'Seed process/timeline/FAQ library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_ptf_batch_submit', 'settings_seed_process_timeline_faq_batch', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $mlp_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Media/listing/profile/detail library batch (SEC-06) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $mlp_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding media/listing/profile/detail library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Media/listing/profile/detail library batch (SEC-06)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed cards, grids, listings, profiles, place highlights, recommendation bands, galleries, media bands, detail specs, comparison cards, related content, location info, directory entries, team grids, and product cards. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_media_listing_profile_batch" />
				<?php \wp_nonce_field( 'aio_seed_media_listing_profile_batch', 'aio_seed_mlp_batch_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed media/listing/profile/detail library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_mlp_batch_submit', 'settings_seed_media_listing_profile_batch', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $lpu_batch_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Legal/policy/utility library batch (SEC-07) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $lpu_batch_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding legal/policy/utility library batch failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Legal/policy/utility library batch (SEC-07)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed disclosure headers, policy body, legal summary, consent note, contact panel, contact detail, inquiry support, support escalation, accessibility help, utility CTA, trust disclosure, form intro, privacy highlight, terms TOC, and footer legal sections. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_legal_policy_utility_batch" />
				<?php \wp_nonce_field( 'aio_seed_legal_policy_utility_batch', 'aio_seed_lpu_batch_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed legal/policy/utility library batch', 'aio-page-builder' ), 'secondary', 'aio_seed_lpu_batch_submit', 'settings_seed_legal_policy_utility_batch', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $cta_super_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'CTA super-library (SEC-08) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $cta_super_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding CTA super-library failed. Check that the plugin can create section template posts.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'CTA super-library (SEC-08)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed the CTA super-library: consultation, booking, purchase, inquiry, contact, quote, directory nav, compare, trust, local, service/product detail, support, and policy/utility CTAs across subtle, strong, media-backed, proof-backed, and minimalist variants. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_cta_super_library_batch" />
				<?php \wp_nonce_field( 'aio_seed_cta_super_library_batch', 'aio_seed_cta_super_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed CTA super-library', 'aio-page-builder' ), 'secondary', 'aio_seed_cta_super_submit', 'settings_seed_cta_super_library_batch', 'settings_section_page_batches' ); ?>
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
				<?php $this->ux_traced_submit_button( __( 'Seed page template and composition expansion pack', 'aio-page-builder' ), 'secondary', 'aio_seed_pt_comp_expansion_submit', 'settings_seed_page_composition_expansion_pack', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $top_level_marketing_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Top-level marketing page template batch (PT-01) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $top_level_marketing_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding top-level marketing page template batch failed. Seed all section batches and page template expansion pack first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Top-level marketing page template batch (PT-01)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed top-level marketing and core business page templates: Home, About, FAQ, Contact, Services overview, Offerings overview (multiple variants each). Each template has ~10 non-CTA sections, ≥3 CTA sections, last section CTA, no adjacent CTAs. Requires section library and page template expansion pack. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_top_level_marketing_templates" />
				<?php \wp_nonce_field( 'aio_seed_top_level_marketing_templates', 'aio_seed_top_level_marketing_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed top-level marketing page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_top_level_marketing_submit', 'settings_seed_top_level_marketing_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $top_level_legal_utility_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Top-level legal/utility page template batch (PT-02) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $top_level_legal_utility_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding top-level legal/utility page template batch failed. Seed all section batches and page template expansion pack first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Top-level legal/utility page template batch (PT-02)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed top-level legal, trust, policy, accessibility, and utility page templates: Privacy, Terms, Accessibility, Support, Disclosure, Contact-utility (multiple variants). Each template has ~10 non-CTA sections, ≥3 CTA sections, last section CTA, no adjacent CTAs. Synthetic preview only. Requires section library. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_top_level_legal_utility_templates" />
				<?php \wp_nonce_field( 'aio_seed_top_level_legal_utility_templates', 'aio_seed_top_level_legal_utility_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed top-level legal/utility page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_top_level_legal_utility_submit', 'settings_seed_top_level_legal_utility_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $top_level_edu_resource_authority_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Top-level educational/resource/authority page template batch (PT-10) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $top_level_edu_resource_authority_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding top-level educational/resource/authority page template batch failed. Seed all section batches and page template expansion pack first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Top-level educational/resource/authority page template batch (PT-10)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed top-level educational, resource, authority, comparison-led, FAQ-heavy, and buyer-guide page templates. ~10 non-CTA, ≥3 CTA, mandatory bottom CTA, no adjacent CTAs. Synthetic preview only. Requires section library. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_top_level_educational_resource_authority_templates" />
				<?php \wp_nonce_field( 'aio_seed_top_level_educational_resource_authority_templates', 'aio_seed_top_level_edu_resource_authority_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed top-level educational/resource/authority page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_top_level_edu_resource_authority_submit', 'settings_seed_top_level_educational_resource_authority_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $top_level_variant_expansion_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Top-level variant expansion super-batch (PT-11) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $top_level_variant_expansion_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding top-level variant expansion super-batch failed. Seed all section batches and top-level template batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Top-level variant expansion super-batch (PT-11)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed additional top-level variants for Home, About, FAQ, Contact, Services, Offerings, legal/utility, and resource/authority families. Materially distinct flow, proof density, and CTA distribution. ~10 non-CTA, ≥3 CTA, bottom CTA, no adjacent CTAs. Synthetic preview only. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_top_level_variant_expansion_templates" />
				<?php \wp_nonce_field( 'aio_seed_top_level_variant_expansion_templates', 'aio_seed_top_level_variant_expansion_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed top-level variant expansion super-batch', 'aio-page-builder' ), 'secondary', 'aio_seed_top_level_variant_expansion_submit', 'settings_seed_top_level_variant_expansion_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $hub_page_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Hub page template batch (PT-03) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $hub_page_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding hub page template batch failed. Seed all section batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Hub page template batch (PT-03)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed hub page templates for Services, Products, Offerings, Directories, and Locations. Each template has ~10 non-CTA sections, ≥4 CTA sections, last section CTA, no adjacent CTAs. Supports drill-down and category navigation. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_hub_page_templates" />
				<?php \wp_nonce_field( 'aio_seed_hub_page_templates', 'aio_seed_hub_page_templates_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed hub page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_hub_page_templates_submit', 'settings_seed_hub_page_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $geographic_hub_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Geographic hub page template batch (PT-04) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $geographic_hub_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding geographic hub page template batch failed. Seed all section batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Geographic hub page template batch (PT-04)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed location/area/regional geographic hub templates: service area, regional, city directory, location overview, coverage listing, neighborhood, campus. Each has ~10 non-CTA, ≥4 CTA, last CTA, no adjacent CTAs. Synthetic preview only. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_geographic_hub_templates" />
				<?php \wp_nonce_field( 'aio_seed_geographic_hub_templates', 'aio_seed_geographic_hub_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed geographic hub page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_geographic_hub_submit', 'settings_seed_geographic_hub_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $nested_hub_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Nested hub page template batch (PT-06) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $nested_hub_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding nested hub page template batch failed. Seed section and hub batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Nested hub page template batch (PT-06)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed nested hub (sub-hub) page templates for service, product, directory, and location subcategories. Sits beneath hub pages; ~10 non-CTA, ≥4 CTA, last CTA, no adjacent CTAs. Parent-family compatibility metadata. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_nested_hub_templates" />
				<?php \wp_nonce_field( 'aio_seed_nested_hub_templates', 'aio_seed_nested_hub_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed nested hub page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_nested_hub_submit', 'settings_seed_nested_hub_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $hub_nested_hub_variant_expansion_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Hub and nested hub variant expansion super-batch (PT-12) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $hub_nested_hub_variant_expansion_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding hub and nested hub variant expansion super-batch failed. Seed section and hub/geographic/nested hub batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Hub and nested hub variant expansion super-batch (PT-12)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed additional hub and nested hub variants for services, products, offerings, directories, locations, and geographic families. Materially distinct listing strategy, comparison depth, proof density. ~10 non-CTA, ≥4 CTA, bottom CTA, no adjacent CTAs. Synthetic preview only. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_hub_nested_hub_variant_expansion_templates" />
				<?php \wp_nonce_field( 'aio_seed_hub_nested_hub_variant_expansion_templates', 'aio_seed_hub_nested_hub_variant_expansion_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed hub and nested hub variant expansion super-batch', 'aio-page-builder' ), 'secondary', 'aio_seed_hub_nested_hub_variant_expansion_submit', 'settings_seed_hub_nested_hub_variant_expansion_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $child_detail_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Child/detail page template batch (PT-07) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $child_detail_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding child/detail page template batch failed. Seed section and nested hub batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Child/detail page template batch (PT-07)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed child/detail page templates for services, offerings, locations, and informational detail pages (e.g. Gel Manicure, Signature Massage, Salt Lake City). ~10 non-CTA, ≥5 CTA, mandatory bottom CTA, no adjacent CTAs. Idempotent.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_child_detail_templates" />
				<?php \wp_nonce_field( 'aio_seed_child_detail_templates', 'aio_seed_child_detail_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed child/detail page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_child_detail_submit', 'settings_seed_child_detail_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $child_detail_product_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Product/catalog child/detail page template batch (PT-08) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $child_detail_product_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding product/catalog child/detail page template batch failed. Seed section and other template batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Product/catalog child/detail page template batch (PT-08)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed child/detail page templates for products, catalog entities, spec-heavy and item-level pages (e.g. 1TB Hard Drive, Furniture Piece). ~10 non-CTA, ≥5 CTA, mandatory bottom CTA, no adjacent CTAs. Synthetic preview only.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_child_detail_product_templates" />
				<?php \wp_nonce_field( 'aio_seed_child_detail_product_templates', 'aio_seed_child_detail_product_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed product/catalog child/detail page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_child_detail_product_submit', 'settings_seed_child_detail_product_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $child_detail_profile_entity_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Directory/profile/entity/resource child/detail page template batch (PT-09) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $child_detail_profile_entity_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding directory/profile/entity/resource child/detail page template batch failed. Seed section and other template batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Directory/profile/entity/resource child/detail page template batch (PT-09)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed child/detail page templates for directory members, staff/provider profiles, place/entity detail, organization profile, article/resource detail. ~10 non-CTA, ≥5 CTA, mandatory bottom CTA, no adjacent CTAs. Synthetic preview only.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_child_detail_profile_entity_templates" />
				<?php \wp_nonce_field( 'aio_seed_child_detail_profile_entity_templates', 'aio_seed_child_detail_profile_entity_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed directory/profile/entity/resource child/detail page template batch', 'aio-page-builder' ), 'secondary', 'aio_seed_child_detail_profile_entity_submit', 'settings_seed_child_detail_profile_entity_templates', 'settings_section_page_batches' ); ?>
			</form>

			<?php if ( $child_detail_variant_expansion_seed_result === 'success' ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php \esc_html_e( 'Child/detail variant expansion super-batch (PT-13) seeded successfully.', 'aio-page-builder' ); ?></p></div>
			<?php elseif ( $child_detail_variant_expansion_seed_result === 'error' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php \esc_html_e( 'Seeding child/detail variant expansion super-batch failed. Seed section and child/detail batches first.', 'aio-page-builder' ); ?></p></div>
			<?php endif; ?>

			<h2 class="title"><?php \esc_html_e( 'Child/detail variant expansion super-batch (PT-13)', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Seed child/detail variant expansion page templates for services, offerings, locations, products, directories, profiles, and resource detail. ~10 non-CTA, ≥5 CTA, mandatory bottom CTA, no adjacent CTAs. Synthetic preview only.', 'aio-page-builder' ); ?></p>
			<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="action" value="aio_seed_child_detail_variant_expansion_templates" />
				<?php \wp_nonce_field( 'aio_seed_child_detail_variant_expansion_templates', 'aio_seed_child_detail_variant_expansion_nonce' ); ?>
				<?php $this->ux_traced_submit_button( __( 'Seed child/detail variant expansion super-batch', 'aio-page-builder' ), 'secondary', 'aio_seed_child_detail_variant_expansion_submit', 'settings_seed_child_detail_variant_expansion_templates', 'settings_section_page_batches' ); ?>
			</form>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}
}
