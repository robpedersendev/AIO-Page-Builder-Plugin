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

use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Detail_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;

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
		return Capabilities::VIEW_BUILD_PLANS;
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
		$request = array(
			'category_class' => isset( $_GET['category_class'] ) ? \sanitize_key( (string) $_GET['category_class'] ) : '',
			'family'         => isset( $_GET['family'] ) ? \sanitize_key( (string) $_GET['family'] ) : '',
			'reduced_motion' => isset( $_GET['reduced_motion'] ) && (string) $_GET['reduced_motion'] === '1',
		);

		$state_builder = $this->get_state_builder();
		$state = $state_builder->build_state( $template_key, $request );

		if ( ! empty( $state['not_found'] ) ) {
			$this->render_not_found( $state );
			return;
		}

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
		$side_panel = $state['side_panel'] ?? array();
		$name       = (string) ( $side_panel['name'] ?? $state['template_key'] ?? '' );
		$template_key = (string) ( $state['template_key'] ?? '' );
		$desc       = (string) ( $side_panel['description'] ?? '' );
		$purpose_cta = (string) ( $side_panel['purpose_cta_direction'] ?? '' );
		$category   = (string) ( $side_panel['category'] ?? '' );
		$differentiation = (string) ( $side_panel['differentiation_notes'] ?? '' );
		$used_sections = $state['used_sections'] ?? array();
		$one_pager_link = (string) ( $state['one_pager_link'] ?? '' );
		$in_compare = $template_key !== '' && \in_array( $template_key, Template_Compare_Screen::get_compare_list( 'page' ), true );
		?>
		<section class="aio-metadata-section">
			<h2 class="aio-metadata-title"><?php echo \esc_html( $name ); ?></h2>
			<?php if ( $template_key !== '' ) : ?>
				<p class="aio-compare-actions">
					<a href="<?php echo \esc_url( \add_query_arg( array( 'page' => Template_Compare_Screen::SLUG, 'type' => 'page' ), \admin_url( 'admin.php' ) ) ); ?>"><?php \esc_html_e( 'Compare workspace', 'aio-page-builder' ); ?></a>
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
						$sk = (string) ( $item['section_key'] ?? '' );
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
		</section>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_preview_panel( array $state ): void {
		$html = (string) ( $state['rendered_preview_html'] ?? '' );
		?>
		<section class="aio-preview-section" aria-label="<?php \esc_attr_e( 'Rendered preview', 'aio-page-builder' ); ?>">
			<h2 class="aio-preview-title"><?php \esc_html_e( 'Preview', 'aio-page-builder' ); ?></h2>
			<p class="aio-preview-notice"><?php \esc_html_e( 'This preview uses synthetic data and the same rendering pipeline as live pages. Omission and animation behavior apply.', 'aio-page-builder' ); ?></p>
			<div class="aio-preview-content">
				<?php echo \wp_kses_post( $html ); ?>
			</div>
		</section>
		<?php
	}

	private function get_state_builder(): Page_Template_Detail_State_Builder {
		$page_repo   = $this->container && $this->container->has( 'page_template_repository' ) ? $this->container->get( 'page_template_repository' ) : null;
		$section_repo = $this->container && $this->container->has( 'section_template_repository' ) ? $this->container->get( 'section_template_repository' ) : null;
		if ( ! $page_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository ) {
			$page_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
		}
		if ( ! $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository ) {
			$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		}

		$page_provider   = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Repository_Adapter( $page_repo );
		$section_provider = new \AIOPageBuilder\Domain\Registries\PageTemplate\UI\Section_Template_Repository_Adapter( $section_repo );

		$preview_generator = new \AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator();
		$side_panel_builder = new \AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder();
		$context_builder = $this->container && $this->container->has( 'section_render_context_builder' ) ? $this->container->get( 'section_render_context_builder' ) : null;
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

		return new Page_Template_Detail_State_Builder(
			$page_provider,
			$section_provider,
			$preview_generator,
			$side_panel_builder,
			$context_builder,
			$section_renderer,
			$assembly_pipeline,
			$lpagery_compatibility,
			$preview_cache
		);
	}
}
