<?php
/**
 * Section template detail screen: metadata, field summary, helper-doc, compatibility, and rendered preview (spec §49.6, §50.1–50.3, §17).
 * Uses synthetic preview data and the real section renderer. Observational only; no insertion or publishing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Entity_Style_Form_Builder;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Detail_State_Builder;
use AIOPageBuilder\Domain\Registries\Shared\Canonical_AI_Lineage_Display_Helper;
use AIOPageBuilder\Domain\Registries\Shared\Registry_AI_Provenance_Helper;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Version_Snapshot_Repository;
use AIOPageBuilder\Domain\Styling\Entity_Style_UI_State_Builder;
use AIOPageBuilder\Domain\Preview\Template_Live_Preview_State_Builder_Factory;
use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;
use AIOPageBuilder\Domain\Preview\UI\Template_Preview_Presenter;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Admin\Services\Helper_Doc_Url_Resolver;

/**
 * Renders a single section template detail: name, description, purpose family, CTA classification,
 * compatibility notes, field summary, helper-doc access, and a rendered preview using the real renderer and synthetic data.
 */
final class Section_Template_Detail_Screen {

	public const SLUG = 'aio-page-builder-section-template-detail';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Section Template Detail', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SECTION_TEMPLATES;
	}

	/**
	 * Renders detail view: capability check, state build, then metadata and preview.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$section_key = isset( $_GET['section'] ) ? \sanitize_key( (string) $_GET['section'] ) : '';
		$request     = array(
			'purpose_family' => isset( $_GET['purpose_family'] ) ? \sanitize_key( (string) $_GET['purpose_family'] ) : '',
			'reduced_motion' => isset( $_GET['reduced_motion'] ) && (string) $_GET['reduced_motion'] === '1',
		);

		$entity_style_last_result = null;
		if ( $section_key !== '' ) {
			$url = $this->resolve_entity_style_save_redirect( $section_key, $entity_style_last_result );
			if ( $url !== null ) {
				\wp_safe_redirect( $url );
				exit;
			}
		}

		$state_builder = $this->get_state_builder();
		$state         = $state_builder->build_state( $section_key, $request );

		if ( ! empty( $state['not_found'] ) ) {
			$this->render_not_found( $state );
			return;
		}

		if ( $section_key !== '' ) {
			$entity_style_state = $this->get_entity_style_state( $section_key, $entity_style_last_result );
			if ( $entity_style_state !== null ) {
				$state['entity_style'] = $entity_style_state;
			}
		}

		$state = $this->merge_industry_preview_state( $state );

		?>
		<div class="wrap aio-page-builder-screen aio-section-template-detail" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<?php $this->render_breadcrumbs( $state['breadcrumbs'] ); ?>
			<div class="aio-detail-layout">
				<aside class="aio-detail-side-panel" aria-label="<?php \esc_attr_e( 'Section metadata', 'aio-page-builder' ); ?>">
					<?php $this->render_metadata_panel( $state ); ?>
				</aside>
				<div class="aio-detail-preview-panel">
					<?php $this->render_preview_panel( $state ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_not_found( array $state ): void {
		$breadcrumbs = $state['breadcrumbs'] ?? array();
		$back_url    = isset( $breadcrumbs[0]['url'] ) ? (string) $breadcrumbs[0]['url'] : \admin_url( 'admin.php?page=' . Section_Templates_Directory_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-section-template-detail">
			<?php $this->render_breadcrumbs( $breadcrumbs ); ?>
			<p class="aio-admin-notice aio-notice-error">
				<?php \esc_html_e( 'Section template not found.', 'aio-page-builder' ); ?>
			</p>
			<p>
				<a href="<?php echo \esc_url( $back_url ); ?>" class="button"><?php \esc_html_e( 'Back to Section Templates', 'aio-page-builder' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * @param list<array{label: string, url: string}> $breadcrumbs
	 * @return void
	 */
	private function render_breadcrumbs( array $breadcrumbs ): void {
		if ( count( $breadcrumbs ) === 0 ) {
			return;
		}
		echo '<nav class="aio-breadcrumbs" aria-label="' . \esc_attr__( 'Breadcrumb', 'aio-page-builder' ) . '"><ol class="aio-breadcrumb-list">';
		$last = count( $breadcrumbs ) - 1;
		foreach ( $breadcrumbs as $i => $seg ) {
			$label = (string) ( $seg['label'] ?? '' );
			$url   = (string) ( $seg['url'] ?? '' );
			echo '<li class="aio-breadcrumb-item">';
			if ( $url !== '' && $i < $last ) {
				echo '<a href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . '</a>';
			} else {
				echo '<span aria-current="page">' . \esc_html( $label ) . '</span>';
			}
			if ( $i < $last ) {
				echo ' <span class="aio-breadcrumb-sep" aria-hidden="true">/</span> ';
			}
			echo '</li>';
		}
		echo '</ol></nav>';
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_metadata_panel( array $state ): void {
		$side_panel          = $state['side_panel'] ?? array();
		$name                = (string) ( $side_panel['name'] ?? $state['section_key'] ?? '' );
		$section_key         = (string) ( $state['section_key'] ?? '' );
		$desc                = (string) ( $side_panel['description'] ?? '' );
		$purpose             = (string) ( $side_panel['purpose_family'] ?? '' );
		$cta                 = (string) ( $side_panel['cta_classification'] ?? '' );
		$placement           = (string) ( $side_panel['placement_tendency'] ?? '' );
		$field_ref           = (string) ( $side_panel['field_blueprint_ref'] ?? '' );
		$helper_ref          = (string) ( $state['helper_ref'] ?? '' );
		$version_summary     = \is_array( $state['version_summary'] ?? null ) ? (array) $state['version_summary'] : array();
		$deprecation_summary = \is_array( $state['deprecation_summary'] ?? null ) ? (array) $state['deprecation_summary'] : array();
		$version             = (string) ( $version_summary['version'] ?? '' );
		$is_deprecated       = ! empty( $deprecation_summary['is_deprecated'] );

		$helper_state = array(
			'available' => false,
			'url'       => '',
			'message'   => Helper_Doc_Url_Resolver::UNAVAILABLE_MESSAGE,
			'doc_id'    => '',
		);
		if ( $section_key !== '' && $this->container && $this->container->has( 'helper_doc_url_resolver' ) ) {
			$resolver = $this->container->get( 'helper_doc_url_resolver' );
			if ( $resolver instanceof Helper_Doc_Url_Resolver ) {
				$helper_state = $resolver->resolve( $section_key, $version !== '' ? $version : null, $helper_ref !== '' ? $helper_ref : null );
			}
		}
		$compat        = $state['compatibility_notes'] ?? array();
		$field_summary = $state['field_summary'] ?? array();
		$in_compare    = $section_key !== '' && \in_array( $section_key, Template_Compare_Screen::get_compare_list( 'section' ), true );
		?>
		<section class="aio-metadata-section">
			<h2 class="aio-metadata-title"><?php echo \esc_html( $name ); ?></h2>
			<?php if ( $section_key !== '' ) : ?>
				<p class="aio-compare-actions">
					<a href="<?php echo \esc_url( Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_COMPARE, array( 'type' => 'section' ) ) ); ?>"><?php \esc_html_e( 'Compare workspace', 'aio-page-builder' ); ?></a>
					<?php if ( $in_compare ) : ?>
						| <a class="aio-compare-action" href="<?php echo \esc_url( Template_Compare_Screen::get_compare_remove_url( 'section', $section_key ) ); ?>" data-aio-compare-type="section" data-aio-compare-key="<?php echo \esc_attr( $section_key ); ?>" data-aio-compare-op="remove"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
					<?php else : ?>
						| <a class="aio-compare-action" href="<?php echo \esc_url( Template_Compare_Screen::get_compare_add_url( 'section', $section_key ) ); ?>" data-aio-compare-type="section" data-aio-compare-key="<?php echo \esc_attr( $section_key ); ?>" data-aio-compare-op="add"><?php \esc_html_e( 'Add to compare', 'aio-page-builder' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $desc !== '' ) : ?>
				<p class="aio-metadata-description"><?php echo \esc_html( $desc ); ?></p>
			<?php endif; ?>
			<dl class="aio-metadata-list">
				<?php if ( $purpose !== '' ) : ?>
					<dt><?php \esc_html_e( 'Purpose family', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $purpose ) ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $cta !== '' ) : ?>
					<dt><?php \esc_html_e( 'CTA classification', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $cta ) ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $placement !== '' ) : ?>
					<dt><?php \esc_html_e( 'Placement tendency', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $placement ) ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $version !== '' ) : ?>
					<dt><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( $version ); ?></dd>
				<?php endif; ?>
				<dt><?php \esc_html_e( 'Deprecation', 'aio-page-builder' ); ?></dt>
				<dd><?php echo $is_deprecated ? \esc_html__( 'Deprecated', 'aio-page-builder' ) : \esc_html__( 'Active', 'aio-page-builder' ); ?></dd>
				<?php
				$ai_src_sec = '';
				if ( $this->container !== null && $this->container->has( 'section_template_repository' ) && $section_key !== '' ) {
					$srepo = $this->container->get( 'section_template_repository' );
					if ( $srepo instanceof Section_Template_Repository ) {
						$row_s = $srepo->get_by_key( $section_key );
						$def_s = is_array( $row_s ) && isset( $row_s['definition'] ) && is_array( $row_s['definition'] ) ? $row_s['definition'] : null;
						if ( is_array( $def_s ) ) {
							$ai_src_sec = Registry_AI_Provenance_Helper::source_badge_label_for_section_template( $def_s );
						}
					}
				}
				?>
				<?php if ( $ai_src_sec !== '' ) : ?>
					<dt><?php \esc_html_e( 'AI provenance', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( $ai_src_sec ); ?></dd>
				<?php endif; ?>
				<?php if ( $field_ref !== '' ) : ?>
					<dt><?php \esc_html_e( 'Field blueprint', 'aio-page-builder' ); ?></dt>
					<dd><code><?php echo \esc_html( $field_ref ); ?></code></dd>
				<?php endif; ?>
			</dl>
			<?php
			if ( $this->container !== null && $section_key !== ''
				&& $this->container->has( 'section_template_repository' ) && $this->container->has( 'version_snapshot_repository' ) ) {
				$srepo = $this->container->get( 'section_template_repository' );
				$vs    = $this->container->get( 'version_snapshot_repository' );
				if ( $srepo instanceof Section_Template_Repository && $vs instanceof Version_Snapshot_Repository ) {
					$row_s = $srepo->get_by_key( $section_key );
					if ( is_array( $row_s ) && isset( $row_s['definition'] ) && is_array( $row_s['definition'] ) ) {
						$pid   = (int) ( $row_s['id'] ?? 0 );
						$state = Canonical_AI_Lineage_Display_Helper::build_state(
							Canonical_AI_Lineage_Display_Helper::TARGET_SECTION_TEMPLATE,
							$pid,
							$section_key,
							$row_s['definition'],
							$vs
						);
						Canonical_AI_Lineage_Display_Helper::render_notice( $state );
					}
				}
			}
			?>
			<?php if ( $helper_ref !== '' || ! empty( $helper_state['available'] ) ) : ?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Helper documentation', 'aio-page-builder' ); ?></h3>
				<?php if ( ! empty( $helper_state['available'] ) && (string) ( $helper_state['url'] ?? '' ) !== '' ) : ?>
					<p><a href="<?php echo \esc_url( (string) $helper_state['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php \esc_html_e( 'Open helper doc', 'aio-page-builder' ); ?></a></p>
				<?php else : ?>
					<p class="aio-helper-doc-unavailable"><?php echo \esc_html( (string) ( $helper_state['message'] ?? Helper_Doc_Url_Resolver::UNAVAILABLE_MESSAGE ) ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			$industry_preview = $state['industry_preview'] ?? null;
			if ( \is_array( $industry_preview ) && ! empty( $industry_preview['has_industry'] ) ) {
				$this->render_industry_preview_block( $industry_preview );
			}
			?>
			<?php if ( \is_array( $compat ) && count( $compat ) > 0 ) : ?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Compatibility notes', 'aio-page-builder' ); ?></h3>
				<ul class="aio-compatibility-list">
					<?php foreach ( $compat as $note ) : ?>
						<li><?php echo \esc_html( \is_string( $note ) ? $note : (string) \wp_json_encode( $note ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( \is_array( $field_summary ) && count( $field_summary ) > 0 ) : ?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Field summary', 'aio-page-builder' ); ?></h3>
				<table class="widefat striped aio-field-summary-table">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Label', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Type', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $field_summary as $row ) : ?>
							<?php
							$fn = (string) ( $row['name'] ?? '' );
							$fl = (string) ( $row['label'] ?? $fn );
							$ft = (string) ( $row['type'] ?? '' );
							?>
							<tr>
								<td><code><?php echo \esc_html( $fn ); ?></code></td>
								<td><?php echo \esc_html( $fl ); ?></td>
								<td><?php echo \esc_html( $ft ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<?php
			$form_state = $state['form_section_field_state'] ?? null;
			if ( \is_array( $form_state ) && ! empty( $form_state['is_form_section'] ) ) :
				$labels     = $form_state['labels'] ?? array();
				$prov_label = $labels['form_provider'] ?? __( 'Form provider', 'aio-page-builder' );
				$id_label   = $labels['form_id'] ?? __( 'Form identifier', 'aio-page-builder' );
				?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Form binding', 'aio-page-builder' ); ?></h3>
				<dl class="aio-metadata-list aio-form-binding-list">
					<dt><?php echo \esc_html( $prov_label ); ?></dt>
					<dd><code><?php echo \esc_html( (string) ( $form_state['form_provider'] ?? '' ) ); ?></code><?php echo ! empty( $form_state['registered_provider_ids'] ) ? ' <span class="aio-meta-hint">(' . \esc_html( implode( ', ', $form_state['registered_provider_ids'] ) ) . ')</span>' : ''; ?></dd>
					<dt><?php echo \esc_html( $id_label ); ?></dt>
					<dd><code><?php echo \esc_html( (string) ( $form_state['form_id'] ?? '' ) ); ?></code></dd>
					<?php if ( ! empty( $form_state['shortcode_preview'] ) ) : ?>
						<dt><?php \esc_html_e( 'Shortcode', 'aio-page-builder' ); ?></dt>
						<dd><code><?php echo \esc_html( (string) $form_state['shortcode_preview'] ); ?></code></dd>
					<?php endif; ?>
				</dl>
				<?php if ( ! empty( $form_state['messages'] ) ) : ?>
					<ul class="aio-form-binding-messages" role="alert">
						<?php foreach ( (array) $form_state['messages'] as $msg ) : ?>
							<li class="aio-notice-warning"><?php echo \esc_html( $msg ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			$entity_style = $state['entity_style'] ?? null;
			if ( \is_array( $entity_style ) && ! empty( $entity_style ) ) {
				$this->render_entity_style_panel( $entity_style );
			}
			?>
		</section>
		<?php
	}

	/**
	 * Renders per-entity styling form (Prompt 253). Save via sanitizer; no raw CSS.
	 *
	 * @param array<string, mixed> $entity_style State from Entity_Style_UI_State_Builder.
	 * @return void
	 */
	private function render_entity_style_panel( array $entity_style ): void {
		$save_action    = (string) ( $entity_style['save_action'] ?? '' );
		$nonce_action   = (string) ( $entity_style['nonce_action'] ?? '' );
		$errors         = $entity_style['validation_errors'] ?? array();
		$token_by_group = $entity_style['token_fields_by_group'] ?? array();
		$comp_by_comp   = $entity_style['component_fields_by_component'] ?? array();
		$entity_key     = (string) ( $entity_style['entity_key'] ?? '' );
		$detail_url     = \add_query_arg(
			array(
				'page'    => self::SLUG,
				'section' => $entity_key,
			),
			\admin_url( 'admin.php' )
		);
		?>
		<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Per-entity styling', 'aio-page-builder' ); ?></h3>
		<?php
		$style_msg = isset( $_GET[ self::ENTITY_STYLE_QUERY_MSG ] ) ? \sanitize_text_field( \wp_unslash( $_GET[ self::ENTITY_STYLE_QUERY_MSG ] ) ) : '';
		if ( $style_msg === 'saved' ) :
			?>
			<p class="notice notice-success is-dismissible" style="margin: 0.5em 0;"><span><?php \esc_html_e( 'Styling saved.', 'aio-page-builder' ); ?></span></p>
		<?php endif; ?>
		<?php if ( \is_array( $errors ) && count( $errors ) > 0 ) : ?>
			<ul class="aio-entity-style-errors" role="alert">
				<?php foreach ( $errors as $msg ) : ?>
					<li class="aio-notice-warning"><?php echo \esc_html( \is_string( $msg ) ? $msg : '' ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<form method="post" action="<?php echo \esc_url( $detail_url ); ?>" class="aio-entity-style-form">
			<?php \wp_nonce_field( $nonce_action, $nonce_action ); ?>
			<input type="hidden" name="action" value="<?php echo \esc_attr( $save_action ); ?>" />
			<?php foreach ( $token_by_group as $group => $fields ) : ?>
				<fieldset class="aio-entity-style-group">
					<legend><?php echo \esc_html( \ucfirst( $group ) ); ?></legend>
					<?php foreach ( $fields as $field ) : ?>
						<p>
							<label for="<?php echo \esc_attr( \sanitize_key( ( $field['name_attr'] ?? '' ) ) ); ?>"><?php echo \esc_html( $field['label'] ?? '' ); ?></label>
							<input type="text" id="<?php echo \esc_attr( \sanitize_key( ( $field['name_attr'] ?? '' ) ) ); ?>" name="<?php echo \esc_attr( $field['name_attr'] ?? '' ); ?>" value="<?php echo \esc_attr( $field['value'] ?? '' ); ?>" maxlength="<?php echo \esc_attr( (string) ( $field['max_length'] ?? 256 ) ); ?>" class="regular-text" />
						</p>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>
			<?php foreach ( $comp_by_comp as $component_id => $fields ) : ?>
				<fieldset class="aio-entity-style-component">
					<legend><?php echo \esc_html( \ucfirst( \str_replace( array( '-', '_' ), ' ', $component_id ) ) ); ?></legend>
					<?php foreach ( $fields as $field ) : ?>
						<p>
							<label for="<?php echo \esc_attr( \sanitize_key( ( $field['name_attr'] ?? '' ) ) ); ?>"><?php echo \esc_html( $field['label'] ?? '' ); ?></label>
							<input type="text" id="<?php echo \esc_attr( \sanitize_key( ( $field['name_attr'] ?? '' ) ) ); ?>" name="<?php echo \esc_attr( $field['name_attr'] ?? '' ); ?>" value="<?php echo \esc_attr( $field['value'] ?? '' ); ?>" maxlength="<?php echo \esc_attr( (string) ( $field['max_length'] ?? 256 ) ); ?>" class="regular-text" />
						</p>
					<?php endforeach; ?>
				</fieldset>
			<?php endforeach; ?>
			<p><button type="submit" class="button button-primary"><?php \esc_html_e( 'Save styling', 'aio-page-builder' ); ?></button></p>
		</form>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_preview_panel( array $state ): void {
		$html        = (string) ( $state['rendered_preview_html'] ?? '' );
		$section_key = (string) ( $state['section_key'] ?? '' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only toggle for preview UI; compared to literal.
		$rm_ui         = isset( $_GET['reduced_motion'] ) && (string) \wp_unslash( $_GET['reduced_motion'] ) === '1';
		$style_context = $this->get_preview_style_context( 'section', $section_key );
		$presenter     = new Template_Preview_Presenter();
		$title         = $presenter->get_preview_title( $html !== '' );
		$label         = $presenter->get_preview_aria_label( $html !== '' );
		$live_url      = $this->build_live_preview_url_section( $section_key );
		$home_parsed   = \wp_parse_url( \home_url( '/' ) );
		$live_origin   = '';
		if ( \is_array( $home_parsed ) ) {
			$live_origin = ( isset( $home_parsed['scheme'] ) ? $home_parsed['scheme'] . '://' : '' ) . ( $home_parsed['host'] ?? '' );
			if ( $live_origin !== '' && isset( $home_parsed['port'] ) ) {
				$live_origin .= ':' . (string) $home_parsed['port'];
			}
		}
		?>
		<section class="aio-preview-section" aria-label="<?php echo \esc_attr( $label ); ?>">
			<h2 class="aio-preview-title"><?php echo \esc_html( $title ); ?></h2>
			<p class="aio-preview-notice"><?php \esc_html_e( 'This preview uses synthetic data. If no rendered preview is available, the view is a structural preview only.', 'aio-page-builder' ); ?></p>
			<?php if ( $live_url !== '' ) : ?>
				<div
					class="aio-live-preview-toolbar"
					role="region"
					aria-label="<?php echo \esc_attr__( 'Live preview controls', 'aio-page-builder' ); ?>"
				>
					<div class="aio-live-preview-viewport-switch" role="group" aria-label="<?php echo \esc_attr__( 'Preview viewport', 'aio-page-builder' ); ?>">
						<button type="button" class="button aio-live-preview-view-btn is-active" data-aio-viewport="desktop"><?php \esc_html_e( 'Desktop', 'aio-page-builder' ); ?></button>
						<button type="button" class="button aio-live-preview-view-btn" data-aio-viewport="tablet"><?php \esc_html_e( 'Tablet', 'aio-page-builder' ); ?></button>
						<button type="button" class="button aio-live-preview-view-btn" data-aio-viewport="mobile"><?php \esc_html_e( 'Mobile', 'aio-page-builder' ); ?></button>
					</div>
					<label class="aio-live-preview-rm">
						<input type="checkbox" class="aio-live-preview-rm-input" <?php \checked( $rm_ui ); ?> />
						<?php \esc_html_e( 'Reduced motion', 'aio-page-builder' ); ?>
					</label>
					<button type="button" class="button aio-live-preview-open-tab"><?php \esc_html_e( 'Open in new tab', 'aio-page-builder' ); ?></button>
					<button type="button" class="button aio-live-preview-regenerate"><?php \esc_html_e( 'Regenerate preview', 'aio-page-builder' ); ?></button>
					<button type="button" class="button aio-live-preview-focus-frame"><?php \esc_html_e( 'Focus preview', 'aio-page-builder' ); ?></button>
				</div>
				<p class="screen-reader-text" aria-live="polite" id="aio-live-preview-status-section"><?php \esc_html_e( 'Live preview loading.', 'aio-page-builder' ); ?></p>
				<div
					class="aio-live-preview-frame-wrap aio-live-preview-viewport--desktop"
					data-aio-live-preview-wrap="1"
					data-aio-live-url="<?php echo \esc_url( $live_url ); ?>"
					data-aio-live-origin="<?php echo \esc_attr( $live_origin ); ?>"
				>
					<p class="aio-live-preview-hint"><?php \esc_html_e( 'Live site preview (theme styles, animations):', 'aio-page-builder' ); ?></p>
					<div class="aio-live-preview-loading" hidden><?php \esc_html_e( 'Loading preview…', 'aio-page-builder' ); ?></div>
					<iframe
						class="aio-live-preview-frame"
						title="<?php echo \esc_attr__( 'Live template preview — themed output for this section template', 'aio-page-builder' ); ?>"
						src="<?php echo \esc_url( $live_url ); ?>"
						loading="lazy"
						referrerpolicy="no-referrer"
					></iframe>
				</div>
			<?php endif; ?>
			<?php if ( $style_context !== null ) : ?>
				<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Inline preview context; base URL from trusted builder. ?>
				<link rel="stylesheet" href="<?php echo \esc_url( $style_context['base_stylesheet_url'] ); ?>" />
				<?php if ( $style_context['inline_css'] !== '' ) : ?>
					<style type="text/css" id="aio-preview-style-context"><?php echo $style_context['inline_css']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- From trusted Preview_Style_Context_Builder. ?></style>
				<?php endif; ?>
			<?php endif; ?>
			<div class="aio-preview-content">
				<?php echo \wp_kses_post( $html ); ?>
			</div>
		</section>
		<?php
	}

	/**
	 * Signed front-end URL for full-theme live preview, or empty when not available.
	 *
	 * @param string $section_key Section template key.
	 * @return string
	 */
	private function build_live_preview_url_section( string $section_key ): string {
		$section_key = \sanitize_key( $section_key );
		if ( $section_key === '' || ! \is_user_logged_in() ) {
			return '';
		}
		$uid = (int) \get_current_user_id();
		if ( $uid <= 0 ) {
			return '';
		}
		$pf = isset( $_GET['purpose_family'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['purpose_family'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only boolean flag; compared to literal.
		$rm    = isset( $_GET['reduced_motion'] ) && (string) \wp_unslash( $_GET['reduced_motion'] ) === '1';
		$extra = array();
		if ( $pf !== '' ) {
			$extra['purpose_family'] = $pf;
		}
		if ( $rm ) {
			$extra['reduced_motion'] = true;
		}
		$shell = isset( $_GET['aio_pb_preview_shell'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['aio_pb_preview_shell'] ) ) : '';
		if ( $shell === 'compat' ) {
			$extra['shell_mode'] = Template_Live_Preview_Ticket_Service::SHELL_COMPAT;
		}
		$issued = Template_Live_Preview_Ticket_Service::issue( Template_Live_Preview_Ticket_Service::TYPE_SECTION, $section_key, $uid, Template_Live_Preview_Ticket_Service::DEFAULT_TTL_SECONDS, $extra );
		if ( $issued['ticket'] === '' ) {
			return '';
		}
		return \add_query_arg(
			array(
				Template_Live_Preview_Ticket_Service::QUERY_FLAG => '1',
				Template_Live_Preview_Ticket_Service::QUERY_TICKET => $issued['ticket'],
			),
			\home_url( '/' )
		);
	}

	/**
	 * Returns preview style context (base URL + inline CSS) for the given type and entity key, or null if builder unavailable.
	 *
	 * @param string $context_type 'section' or 'page'.
	 * @param string $entity_key   Section or page template key.
	 * @return array{base_stylesheet_url: string, inline_css: string}|null
	 */
	private function get_preview_style_context( string $context_type, string $entity_key ): ?array {
		if ( $this->container === null || ! $this->container->has( 'preview_style_context_builder' ) ) {
			return null;
		}
		$builder = $this->container->get( 'preview_style_context_builder' );
		if ( ! $builder instanceof \AIOPageBuilder\Domain\Preview\Styling\Preview_Style_Context_Builder ) {
			return null;
		}
		return $builder->build_for_preview( $context_type, $entity_key );
	}

	private const ENTITY_STYLE_QUERY_MSG = 'aio_entity_style_msg';

	/**
	 * POST save redirect URL for admin_init (Admin_Early_Redirect_Coordinator).
	 *
	 * @return string|null
	 */
	public function get_entity_style_post_redirect_url(): ?string {
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			return null;
		}
		$section_key = isset( $_GET['section'] ) ? \sanitize_key( (string) $_GET['section'] ) : '';
		if ( $section_key === '' ) {
			return null;
		}
		$last = null;
		return $this->resolve_entity_style_save_redirect( $section_key, $last );
	}

	/**
	 * Processes POST save for per-entity styling. Returns redirect URL on success; sets $last_result on validation failure.
	 *
	 * @param string                                                      $section_key
	 * @param \AIOPageBuilder\Domain\Styling\Style_Validation_Result|null $last_result Set when validation fails (by reference).
	 * @return string|null Redirect URL on success; null when not handled or validation failed.
	 */
	private function resolve_entity_style_save_redirect( string $section_key, &$last_result ): ?string {
		if ( ! isset( $_POST['action'] ) || \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) !== Entity_Style_UI_State_Builder::SAVE_ACTION ) {
			return null;
		}
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			return null;
		}
		$nonce_key = Entity_Style_UI_State_Builder::NONCE_ACTION;
		if ( ! isset( $_POST[ $nonce_key ] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ $nonce_key ] ) ), $nonce_key ) ) {
			return null;
		}
		$raw = array();
		if ( isset( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] ) && \is_array( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashed then normalized/sanitized below.
			$raw = \wp_unslash( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] );
		}
		$normalizer = $this->container && $this->container->has( 'styles_json_normalizer' ) ? $this->container->get( 'styles_json_normalizer' ) : null;
		$sanitizer  = $this->container && $this->container->has( 'styles_json_sanitizer' ) ? $this->container->get( 'styles_json_sanitizer' ) : null;
		$repo       = $this->container && $this->container->has( 'entity_style_payload_repository' ) ? $this->container->get( 'entity_style_payload_repository' ) : null;
		if ( $normalizer === null || $sanitizer === null || $repo === null ) {
			return null;
		}
		$normalized = $normalizer->normalize_entity_payload( $raw );
		$result     = $sanitizer->sanitize_entity_payload( $normalized );
		if ( $result->is_valid() ) {
			$repo->persist_entity_payload_result( 'section_template', $section_key, $result );
			return \add_query_arg(
				array(
					'page'                       => self::SLUG,
					'section'                    => $section_key,
					self::ENTITY_STYLE_QUERY_MSG => 'saved',
				),
				\admin_url( 'admin.php' )
			);
		}
		$last_result = $result;
		return null;
	}

	/**
	 * Builds entity style UI state for the given section. Returns null if styling services unavailable.
	 *
	 * @param string                                                      $section_key
	 * @param \AIOPageBuilder\Domain\Styling\Style_Validation_Result|null $last_result
	 * @return array<string, mixed>|null
	 */
	private function get_entity_style_state( string $section_key, $last_result ): ?array {
		$token_registry = $this->container && $this->container->has( 'style_token_registry' ) ? $this->container->get( 'style_token_registry' ) : null;
		$comp_registry  = $this->container && $this->container->has( 'component_override_registry' ) ? $this->container->get( 'component_override_registry' ) : null;
		$payload_repo   = $this->container && $this->container->has( 'entity_style_payload_repository' ) ? $this->container->get( 'entity_style_payload_repository' ) : null;
		if ( $token_registry === null || $comp_registry === null || $payload_repo === null ) {
			return null;
		}
		$form_builder = new Entity_Style_Form_Builder( $token_registry, $comp_registry, $payload_repo );
		$ui_builder   = new Entity_Style_UI_State_Builder( $form_builder, $payload_repo );
		$result_obj   = $last_result instanceof \AIOPageBuilder\Domain\Styling\Style_Validation_Result ? $last_result : null;
		return $ui_builder->build_state( 'section_template', $section_key, $result_obj );
	}

	/**
	 * Merges industry-aware preview view model into state when resolver is available (Prompt 384).
	 *
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	private function merge_industry_preview_state( array $state ): array {
		$section_key = (string) ( $state['section_key'] ?? '' );
		$definition  = $state['definition'] ?? array();
		if ( $section_key === '' || ! \is_array( $definition ) ) {
			return $state;
		}
		$resolver = null;
		if ( $this->container && $this->container->has( 'industry_section_preview_resolver' ) ) {
			$resolver = $this->container->get( 'industry_section_preview_resolver' );
		}
		if ( ! $resolver instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Preview_Resolver ) {
			return $state;
		}
		$state['industry_preview'] = $resolver->resolve( $section_key, $definition, array() )->to_array();
		return $state;
	}

	/**
	 * Renders industry fit and helper guidance block (fit, composed helper excerpt, warnings, substitutes). Escape on output.
	 *
	 * @param array<string, mixed> $industry_preview View model to_array().
	 * @return void
	 */
	private function render_industry_preview_block( array $industry_preview ): void {
		$fit         = (string) ( $industry_preview['recommendation_fit'] ?? '' );
		$helper      = $industry_preview['composed_helper'] ?? array();
		$warnings    = $industry_preview['warning_flags'] ?? array();
		$substitutes = $industry_preview['substitute_suggestions'] ?? array();
		$primary     = (string) ( $industry_preview['primary_industry_key'] ?? '' );
		?>
		<section class="aio-industry-preview-section" aria-label="<?php \esc_attr_e( 'Industry fit and guidance', 'aio-page-builder' ); ?>">
			<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Industry fit', 'aio-page-builder' ); ?></h3>
			<?php if ( $primary !== '' ) : ?>
				<p class="aio-industry-primary"><span class="aio-industry-label"><?php \esc_html_e( 'Industry', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $primary ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $fit !== '' && $fit !== 'neutral' ) : ?>
				<p class="aio-industry-fit"><span class="aio-industry-label"><?php \esc_html_e( 'Fit', 'aio-page-builder' ); ?>:</span> <span class="aio-industry-fit-<?php echo \esc_attr( \sanitize_key( $fit ) ); ?>"><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $fit ) ) ); ?></span></p>
			<?php endif; ?>
			<?php if ( \is_array( $helper ) && ( ( $helper['overlay_applied'] ?? false ) || ( $helper['cta_usage_notes'] ?? '' ) !== '' || ( $helper['tone_notes'] ?? '' ) !== '' ) ) : ?>
				<div class="aio-industry-helper-excerpt">
					<?php if ( ! empty( $helper['tone_notes'] ) ) : ?>
						<p><strong><?php \esc_html_e( 'Tone', 'aio-page-builder' ); ?></strong>: <?php echo \esc_html( (string) $helper['tone_notes'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $helper['cta_usage_notes'] ) ) : ?>
						<p><strong><?php \esc_html_e( 'CTA notes', 'aio-page-builder' ); ?></strong>: <?php echo \esc_html( (string) $helper['cta_usage_notes'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( \is_array( $warnings ) && count( $warnings ) > 0 ) : ?>
				<ul class="aio-industry-warnings" role="alert">
					<?php foreach ( $warnings as $flag ) : ?>
						<li class="aio-notice-warning"><?php echo \esc_html( \is_string( $flag ) ? $flag : '' ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( \is_array( $substitutes ) && count( $substitutes ) > 0 ) : ?>
				<p class="aio-industry-substitutes-label"><?php \esc_html_e( 'Suggested alternatives', 'aio-page-builder' ); ?>:</p>
				<ul class="aio-industry-substitutes-list">
					<?php foreach ( array_slice( $substitutes, 0, 5 ) as $sug ) : ?>
						<?php
						$sug_key = isset( $sug['suggested_replacement_key'] ) ? (string) $sug['suggested_replacement_key'] : '';
						if ( $sug_key === '' ) {
							continue;
						}
						$detail_url = \add_query_arg(
							array(
								'page'    => self::SLUG,
								'section' => $sug_key,
							),
							\admin_url( 'admin.php' )
						);
						?>
						<li><a href="<?php echo \esc_url( $detail_url ); ?>"><?php echo \esc_html( $sug_key ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php
			$subtype_influence = $industry_preview['subtype_influence'] ?? array();
			if ( \is_array( $subtype_influence ) && ! empty( $subtype_influence['has_subtype'] ) ) :
				$st_label  = isset( $subtype_influence['subtype_label'] ) ? (string) $subtype_influence['subtype_label'] : '';
				$st_helper = ! empty( $subtype_influence['helper_refinement_applied'] );
				$st_notes  = isset( $subtype_influence['caution_notes'] ) && \is_array( $subtype_influence['caution_notes'] ) ? $subtype_influence['caution_notes'] : array();
				?>
				<div class="aio-industry-subtype-influence" aria-label="<?php \esc_attr_e( 'Subtype context', 'aio-page-builder' ); ?>">
					<p class="aio-industry-subtype-label"><span class="aio-industry-label"><?php \esc_html_e( 'Subtype', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( $st_label ); ?></p>
					<?php if ( $st_helper ) : ?>
						<p class="aio-industry-subtype-refinement"><?php \esc_html_e( 'Subtype helper overlay applied for this section.', 'aio-page-builder' ); ?></p>
					<?php endif; ?>
					<?php if ( count( $st_notes ) > 0 ) : ?>
						<ul class="aio-industry-subtype-notes">
							<?php foreach ( $st_notes as $note ) : ?>
								<li><?php echo \esc_html( \is_string( $note ) ? $note : '' ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php
			$goal_influence = $industry_preview['goal_influence'] ?? array();
			if ( \is_array( $goal_influence ) && ! empty( $goal_influence['has_goal'] ) ) :
				$goal_label = isset( $goal_influence['goal_label'] ) ? (string) $goal_influence['goal_label'] : '';
				$goal_notes = isset( $goal_influence['goal_caution_notes'] ) && \is_array( $goal_influence['goal_caution_notes'] ) ? $goal_influence['goal_caution_notes'] : array();
				?>
				<div class="aio-industry-goal-influence" aria-label="<?php \esc_attr_e( 'Conversion goal context', 'aio-page-builder' ); ?>">
					<p class="aio-industry-goal-label"><span class="aio-industry-label"><?php \esc_html_e( 'Conversion goal', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( $goal_label ); ?></p>
					<?php if ( count( $goal_notes ) > 0 ) : ?>
						<ul class="aio-industry-goal-notes">
							<?php foreach ( $goal_notes as $note ) : ?>
								<li><?php echo \esc_html( \is_string( $note ) ? $note : '' ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	private function get_state_builder(): Section_Template_Detail_State_Builder {
		return ( new Template_Live_Preview_State_Builder_Factory( $this->container ) )->create_section_builder();
	}
}
