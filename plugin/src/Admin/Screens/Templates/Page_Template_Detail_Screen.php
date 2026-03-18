<?php
/**
 * Page template detail screen: metadata, used sections, one-pager link, and rendered preview (spec §49.7, §50.1–50.3, §17).
 * Uses synthetic preview data and the real rendering pipeline. Observational only; no publish or editor insertion.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Forms\Entity_Style_Form_Builder;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Detail_State_Builder;
use AIOPageBuilder\Domain\Styling\Entity_Style_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders a single page template detail: name, description, purpose, CTA direction, differentiation notes,
 * used-section list, one-pager access, and a rendered preview panel using the real renderer and synthetic data.
 */
final class Page_Template_Detail_Screen {

	public const SLUG = 'aio-page-builder-page-template-detail';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Page Template Detail', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_PAGE_TEMPLATES;
	}

	/**
	 * Renders detail view: capability check, state build, then metadata and preview.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$template_key = isset( $_GET['template'] ) ? \sanitize_key( (string) $_GET['template'] ) : '';
		$request      = array(
			'category_class' => isset( $_GET['category_class'] ) ? \sanitize_key( (string) $_GET['category_class'] ) : '',
			'family'         => isset( $_GET['family'] ) ? \sanitize_key( (string) $_GET['family'] ) : '',
			'reduced_motion' => isset( $_GET['reduced_motion'] ) && (string) $_GET['reduced_motion'] === '1',
		);

		$state_builder = $this->get_state_builder();
		$state         = $state_builder->build_state( $template_key, $request );

		if ( ! empty( $state['not_found'] ) ) {
			$this->render_not_found( $state );
			return;
		}

		$state = $this->merge_industry_preview_state( $state );

		?>
		<div class="wrap aio-page-builder-screen aio-page-template-detail" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<?php $this->render_breadcrumbs( $state['breadcrumbs'] ); ?>
			<div class="aio-detail-layout">
				<aside class="aio-detail-side-panel" aria-label="<?php \esc_attr_e( 'Template metadata', 'aio-page-builder' ); ?>">
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
		$back_url    = isset( $breadcrumbs[0]['url'] ) ? (string) $breadcrumbs[0]['url'] : \admin_url( 'admin.php?page=' . Page_Templates_Directory_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-page-template-detail">
			<?php $this->render_breadcrumbs( $breadcrumbs ); ?>
			<p class="aio-admin-notice aio-notice-error">
				<?php \esc_html_e( 'Page template not found.', 'aio-page-builder' ); ?>
			</p>
			<p>
				<a href="<?php echo \esc_url( $back_url ); ?>" class="button"><?php \esc_html_e( 'Back to Page Templates', 'aio-page-builder' ); ?></a>
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
		$side_panel      = $state['side_panel'] ?? array();
		$name            = (string) ( $side_panel['name'] ?? $state['template_key'] ?? '' );
		$template_key    = (string) ( $state['template_key'] ?? '' );
		$desc            = (string) ( $side_panel['description'] ?? '' );
		$purpose_cta     = (string) ( $side_panel['purpose_cta_direction'] ?? '' );
		$category        = (string) ( $side_panel['category'] ?? '' );
		$differentiation = (string) ( $side_panel['differentiation_notes'] ?? '' );
		$used_sections   = $state['used_sections'] ?? array();
		$one_pager_link  = (string) ( $state['one_pager_link'] ?? '' );
		$in_compare      = $template_key !== '' && \in_array( $template_key, Template_Compare_Screen::get_compare_list( 'page' ), true );
		?>
		<section class="aio-metadata-section">
			<h2 class="aio-metadata-title"><?php echo \esc_html( $name ); ?></h2>
			<?php if ( $template_key !== '' ) : ?>
				<p class="aio-compare-actions">
					<a href="
					<?php
					echo \esc_url(
						\add_query_arg(
							array(
								'page' => Template_Compare_Screen::SLUG,
								'type' => 'page',
							),
							\admin_url( 'admin.php' )
						)
					);
					?>
								"><?php \esc_html_e( 'Compare workspace', 'aio-page-builder' ); ?></a>
					<?php if ( $in_compare ) : ?>
						| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_remove_url( 'page', $template_key ) ); ?>"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
					<?php else : ?>
						| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_add_url( 'page', $template_key ) ); ?>"><?php \esc_html_e( 'Add to compare', 'aio-page-builder' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php if ( $desc !== '' ) : ?>
				<p class="aio-metadata-description"><?php echo \esc_html( $desc ); ?></p>
			<?php endif; ?>
			<dl class="aio-metadata-list">
				<?php if ( $category !== '' ) : ?>
					<dt><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $category ) ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $purpose_cta !== '' ) : ?>
					<dt><?php \esc_html_e( 'Purpose / CTA direction', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( \ucfirst( \str_replace( array( '_', '-' ), ' ', $purpose_cta ) ) ); ?></dd>
				<?php endif; ?>
				<?php if ( $differentiation !== '' ) : ?>
					<dt><?php \esc_html_e( 'Differentiation', 'aio-page-builder' ); ?></dt>
					<dd><?php echo \esc_html( $differentiation ); ?></dd>
				<?php endif; ?>
			</dl>
			<?php if ( count( $used_sections ) > 0 ) : ?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Used sections', 'aio-page-builder' ); ?></h3>
				<ol class="aio-used-sections-list">
					<?php foreach ( $used_sections as $item ) : ?>
						<?php
						$sk  = (string) ( $item['section_key'] ?? '' );
						$pos = isset( $item['position'] ) ? (int) $item['position'] : 0;
						?>
						<li><code><?php echo \esc_html( $sk ); ?></code> (<?php echo (int) $pos; ?>)</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<?php if ( $one_pager_link !== '' ) : ?>
				<p class="aio-one-pager-link">
					<a href="<?php echo \esc_url( $one_pager_link ); ?>" target="_blank" rel="noopener noreferrer"><?php \esc_html_e( 'One-pager (opens in new tab)', 'aio-page-builder' ); ?></a>
				</p>
			<?php endif; ?>
			<?php
			$industry_preview = $state['industry_preview'] ?? null;
			if ( \is_array( $industry_preview ) && ! empty( $industry_preview['has_industry'] ) ) {
				$this->render_industry_preview_block( $industry_preview );
			}
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
				'page'     => self::SLUG,
				'template' => $entity_key,
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
		$html          = (string) ( $state['rendered_preview_html'] ?? '' );
		$template_key  = (string) ( $state['template_key'] ?? '' );
		$style_context = $this->get_preview_style_context( 'page', $template_key );
		?>
		<section class="aio-preview-section" aria-label="<?php \esc_attr_e( 'Rendered preview', 'aio-page-builder' ); ?>">
			<h2 class="aio-preview-title"><?php \esc_html_e( 'Preview', 'aio-page-builder' ); ?></h2>
			<p class="aio-preview-notice"><?php \esc_html_e( 'This preview uses synthetic data and the same rendering pipeline as live pages. Omission and animation behavior apply.', 'aio-page-builder' ); ?></p>
			<?php if ( $style_context !== null ) : ?>
				<link rel="stylesheet" href="<?php echo \esc_url( $style_context['base_stylesheet_url'] ); ?>" />
				<?php if ( $style_context['inline_css'] !== '' ) : ?>
					<style type="text/css" id="aio-preview-style-context"><?php echo /* Safe: from sanitized emitters only */ $style_context['inline_css']; ?></style>
				<?php endif; ?>
			<?php endif; ?>
			<div class="aio-preview-content">
				<?php echo \wp_kses_post( $html ); ?>
			</div>
		</section>
		<?php
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
	 * Processes POST save for per-entity styling. Redirects on success; sets $last_result on validation failure.
	 *
	 * @param string                                                      $template_key
	 * @param \AIOPageBuilder\Domain\Styling\Style_Validation_Result|null $last_result Set when validation fails (by reference).
	 * @return bool True if redirect was sent; false to continue rendering.
	 */
	private function process_entity_style_save( string $template_key, &$last_result ): bool {
		if ( ! isset( $_POST['action'] ) || \sanitize_text_field( \wp_unslash( $_POST['action'] ) ) !== Entity_Style_UI_State_Builder::SAVE_ACTION ) {
			return false;
		}
		if ( ! \current_user_can( $this->get_capability() ) ) {
			return false;
		}
		$nonce_key = Entity_Style_UI_State_Builder::NONCE_ACTION;
		if ( ! isset( $_POST[ $nonce_key ] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ $nonce_key ] ) ), $nonce_key ) ) {
			return false;
		}
		$raw        = isset( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] ) && \is_array( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] )
			? \wp_unslash( $_POST[ Entity_Style_Form_Builder::FORM_KEY ] )
			: array();
		$normalizer = $this->container && $this->container->has( 'styles_json_normalizer' ) ? $this->container->get( 'styles_json_normalizer' ) : null;
		$sanitizer  = $this->container && $this->container->has( 'styles_json_sanitizer' ) ? $this->container->get( 'styles_json_sanitizer' ) : null;
		$repo       = $this->container && $this->container->has( 'entity_style_payload_repository' ) ? $this->container->get( 'entity_style_payload_repository' ) : null;
		if ( $normalizer === null || $sanitizer === null || $repo === null ) {
			return false;
		}
		$normalized = $normalizer->normalize_entity_payload( $raw );
		$result     = $sanitizer->sanitize_entity_payload( $normalized );
		if ( $result->is_valid() ) {
			$repo->persist_entity_payload_result( 'page_template', $template_key, $result );
			$url = \add_query_arg(
				array(
					'page'                       => self::SLUG,
					'template'                   => $template_key,
					self::ENTITY_STYLE_QUERY_MSG => 'saved',
				),
				\admin_url( 'admin.php' )
			);
			\wp_safe_redirect( $url );
			exit;
		}
		$last_result = $result;
		return false;
	}

	/**
	 * Builds entity style UI state for the given page template. Returns null if styling services unavailable.
	 *
	 * @param string                                                      $template_key
	 * @param \AIOPageBuilder\Domain\Styling\Style_Validation_Result|null $last_result
	 * @return array<string, mixed>|null
	 */
	private function get_entity_style_state( string $template_key, $last_result ): ?array {
		$token_registry = $this->container && $this->container->has( 'style_token_registry' ) ? $this->container->get( 'style_token_registry' ) : null;
		$comp_registry  = $this->container && $this->container->has( 'component_override_registry' ) ? $this->container->get( 'component_override_registry' ) : null;
		$payload_repo   = $this->container && $this->container->has( 'entity_style_payload_repository' ) ? $this->container->get( 'entity_style_payload_repository' ) : null;
		if ( $token_registry === null || $comp_registry === null || $payload_repo === null ) {
			return null;
		}
		$form_builder = new Entity_Style_Form_Builder( $token_registry, $comp_registry, $payload_repo );
		$ui_builder   = new Entity_Style_UI_State_Builder( $form_builder, $payload_repo );
		$result_obj   = $last_result instanceof \AIOPageBuilder\Domain\Styling\Style_Validation_Result ? $last_result : null;
		return $ui_builder->build_state( 'page_template', $template_key, $result_obj );
	}

	/**
	 * Merges industry-aware preview view model into state when resolver is available (Prompt 383).
	 *
	 * @param array<string, mixed> $state
	 * @return array<string, mixed>
	 */
	private function merge_industry_preview_state( array $state ): array {
		$template_key = (string) ( $state['template_key'] ?? '' );
		$definition   = $state['definition'] ?? array();
		if ( $template_key === '' || ! \is_array( $definition ) ) {
			return $state;
		}
		$resolver = null;
		if ( $this->container && $this->container->has( 'industry_page_template_preview_resolver' ) ) {
			$resolver = $this->container->get( 'industry_page_template_preview_resolver' );
		}
		if ( ! $resolver instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Preview_Resolver ) {
			return $state;
		}
		$state['industry_preview'] = $resolver->resolve( $template_key, $definition, array() )->to_array();
		return $state;
	}

	/**
	 * Renders industry fit and guidance block (fit, hierarchy, LPagery, one-pager excerpt, warnings, substitutes). Escape on output.
	 *
	 * @param array<string, mixed> $industry_preview View model to_array().
	 * @return void
	 */
	private function render_industry_preview_block( array $industry_preview ): void {
		$fit         = (string) ( $industry_preview['recommendation_fit'] ?? '' );
		$hierarchy   = (string) ( $industry_preview['hierarchy_fit'] ?? '' );
		$lpagery     = (string) ( $industry_preview['lpagery_posture'] ?? '' );
		$one_pager   = $industry_preview['composed_one_pager'] ?? array();
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
			<?php if ( $hierarchy !== '' ) : ?>
				<p class="aio-industry-hierarchy"><span class="aio-industry-label"><?php \esc_html_e( 'Hierarchy', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( $hierarchy ); ?></p>
			<?php endif; ?>
			<?php if ( $lpagery !== '' ) : ?>
				<p class="aio-industry-lpagery"><span class="aio-industry-label"><?php \esc_html_e( 'LPagery', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( $lpagery ); ?></p>
			<?php endif; ?>
			<?php if ( \is_array( $one_pager ) && ( ( $one_pager['overlay_applied'] ?? false ) || ( $one_pager['cta_strategy'] ?? '' ) !== '' || ( $one_pager['hierarchy_hints'] ?? '' ) !== '' ) ) : ?>
				<div class="aio-industry-one-pager-excerpt">
					<?php if ( ! empty( $one_pager['hierarchy_hints'] ) ) : ?>
						<p><strong><?php \esc_html_e( 'Hierarchy notes', 'aio-page-builder' ); ?></strong>: <?php echo \esc_html( (string) $one_pager['hierarchy_hints'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $one_pager['cta_strategy'] ) ) : ?>
						<p><strong><?php \esc_html_e( 'CTA strategy', 'aio-page-builder' ); ?></strong>: <?php echo \esc_html( (string) $one_pager['cta_strategy'] ); ?></p>
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
								'page'     => self::SLUG,
								'template' => $sug_key,
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
				$st_label    = isset( $subtype_influence['subtype_label'] ) ? (string) $subtype_influence['subtype_label'] : '';
				$st_onepager = ! empty( $subtype_influence['onepager_refinement_applied'] );
				$st_notes    = isset( $subtype_influence['caution_notes'] ) && \is_array( $subtype_influence['caution_notes'] ) ? $subtype_influence['caution_notes'] : array();
				?>
				<div class="aio-industry-subtype-influence" aria-label="<?php \esc_attr_e( 'Subtype context', 'aio-page-builder' ); ?>">
					<p class="aio-industry-subtype-label"><span class="aio-industry-label"><?php \esc_html_e( 'Subtype', 'aio-page-builder' ); ?>:</span> <?php echo \esc_html( $st_label ); ?></p>
					<?php if ( $st_onepager ) : ?>
						<p class="aio-industry-subtype-refinement"><?php \esc_html_e( 'Subtype one-pager overlay applied for this template.', 'aio-page-builder' ); ?></p>
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

	private function get_state_builder(): Page_Template_Detail_State_Builder {
		$page_repo    = $this->container && $this->container->has( 'page_template_repository' ) ? $this->container->get( 'page_template_repository' ) : null;
		$section_repo = $this->container && $this->container->has( 'section_template_repository' ) ? $this->container->get( 'section_template_repository' ) : null;
		if ( ! $page_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository ) {
			$page_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		}
		if ( ! $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository ) {
			$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		}

		$page_provider    = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Repository_Adapter( $page_repo );
		$section_provider = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Template_Repository_Adapter( $section_repo );

		$preview_generator = new \AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator();
		$industry_dummy    = new \AIOPageBuilder\Domain\Industry\Preview\Industry_Dummy_Data_Generator();
		$industry_key      = null;
		if ( $this->container && $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
				$profile = $store->get_profile();
				$primary = isset( $profile['primary_industry_key'] ) && \is_string( $profile['primary_industry_key'] ) ? \trim( $profile['primary_industry_key'] ) : '';
				if ( $primary !== '' ) {
					$industry_key = $primary;
				}
			}
		}
		$side_panel_builder = new \AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder();
		$context_builder    = $this->container && $this->container->has( 'section_render_context_builder' ) ? $this->container->get( 'section_render_context_builder' ) : null;
		if ( ! $context_builder instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder ) {
			$context_builder = new \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder();
		}
		$section_renderer = $this->container && $this->container->has( 'section_renderer_base' ) ? $this->container->get( 'section_renderer_base' ) : null;
		if ( ! $section_renderer instanceof \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base ) {
			$section_renderer = new \AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base();
		}
		$assembly_pipeline = $this->container && $this->container->has( 'native_block_assembly_pipeline' ) ? $this->container->get( 'native_block_assembly_pipeline' ) : null;
		if ( ! $assembly_pipeline instanceof \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline ) {
			$assembly_pipeline = new \AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline( null, null );
		}
		$lpagery_compatibility = null;
		if ( $this->container && $this->container->has( 'library_lpagery_compatibility_service' ) ) {
			$lpagery_compatibility = $this->container->get( 'library_lpagery_compatibility_service' );
		}
		$preview_cache = null;
		if ( $this->container && $this->container->has( 'preview_cache_service' ) ) {
			$preview_cache = $this->container->get( 'preview_cache_service' );
		}
		if ( $preview_cache !== null && ! $preview_cache instanceof \AIOPageBuilder\Domain\Preview\Preview_Cache_Service ) {
			$preview_cache = null;
		}
		$versioning_service  = null;
		$deprecation_service = null;
		if ( $this->container && $this->container->has( 'template_versioning_service' ) ) {
			$v = $this->container->get( 'template_versioning_service' );
			if ( $v instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service ) {
				$versioning_service = $v;
			}
		}
		if ( $this->container && $this->container->has( 'template_deprecation_service' ) ) {
			$d = $this->container->get( 'template_deprecation_service' );
			if ( $d instanceof \AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service ) {
				$deprecation_service = $d;
			}
		}
		$form_reference_aggregator = null;
		if ( $this->container && $this->container->has( 'form_provider_registry' ) ) {
			$reg = $this->container->get( 'form_provider_registry' );
			if ( $reg instanceof \AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry ) {
				$form_reference_aggregator = new \AIOPageBuilder\Domain\Rendering\FormProviders\Page_Form_Reference_Aggregator( $reg );
			}
		}

		return new Page_Template_Detail_State_Builder(
			$page_provider,
			$section_provider,
			$preview_generator,
			$side_panel_builder,
			$context_builder,
			$section_renderer,
			$assembly_pipeline,
			$lpagery_compatibility,
			$preview_cache,
			$versioning_service,
			$deprecation_service,
			$form_reference_aggregator,
			$industry_dummy,
			$industry_key
		);
	}
}
