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

use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Detail_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;

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

		$section_key = isset( $_GET['section'] ) ? \sanitize_key( (string) $_GET['section'] ) : '';
		$request = array(
			'purpose_family' => isset( $_GET['purpose_family'] ) ? \sanitize_key( (string) $_GET['purpose_family'] ) : '',
			'reduced_motion' => isset( $_GET['reduced_motion'] ) && (string) $_GET['reduced_motion'] === '1',
		);

		$state_builder = $this->get_state_builder();
		$state = $state_builder->build_state( $section_key, $request );

		if ( ! empty( $state['not_found'] ) ) {
			$this->render_not_found( $state );
			return;
		}

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
		$side_panel   = $state['side_panel'] ?? array();
		$name         = (string) ( $side_panel['name'] ?? $state['section_key'] ?? '' );
		$section_key  = (string) ( $state['section_key'] ?? '' );
		$desc         = (string) ( $side_panel['description'] ?? '' );
		$purpose      = (string) ( $side_panel['purpose_family'] ?? '' );
		$cta          = (string) ( $side_panel['cta_classification'] ?? '' );
		$placement    = (string) ( $side_panel['placement_tendency'] ?? '' );
		$field_ref    = (string) ( $side_panel['field_blueprint_ref'] ?? '' );
		$helper_ref   = (string) ( $state['helper_ref'] ?? '' );
		$helper_url   = (string) ( $state['helper_doc_url'] ?? '' );
		$compat       = $state['compatibility_notes'] ?? array();
		$field_summary = $state['field_summary'] ?? array();
		$in_compare   = $section_key !== '' && \in_array( $section_key, Template_Compare_Screen::get_compare_list( 'section' ), true );
		?>
		<section class="aio-metadata-section">
			<h2 class="aio-metadata-title"><?php echo \esc_html( $name ); ?></h2>
			<?php if ( $section_key !== '' ) : ?>
				<p class="aio-compare-actions">
					<a href="<?php echo \esc_url( \add_query_arg( array( 'page' => Template_Compare_Screen::SLUG, 'type' => 'section' ), \admin_url( 'admin.php' ) ) ); ?>"><?php \esc_html_e( 'Compare workspace', 'aio-page-builder' ); ?></a>
					<?php if ( $in_compare ) : ?>
						| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_remove_url( 'section', $section_key ) ); ?>"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
					<?php else : ?>
						| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_add_url( 'section', $section_key ) ); ?>"><?php \esc_html_e( 'Add to compare', 'aio-page-builder' ); ?></a>
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
				<?php if ( $field_ref !== '' ) : ?>
					<dt><?php \esc_html_e( 'Field blueprint', 'aio-page-builder' ); ?></dt>
					<dd><code><?php echo \esc_html( $field_ref ); ?></code></dd>
				<?php endif; ?>
			</dl>
			<?php if ( $helper_ref !== '' ) : ?>
				<h3 class="aio-metadata-subtitle"><?php \esc_html_e( 'Helper documentation', 'aio-page-builder' ); ?></h3>
				<?php if ( $helper_url !== '' ) : ?>
					<p><a href="<?php echo \esc_url( $helper_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo \esc_html( $helper_ref ); ?></a></p>
				<?php else : ?>
					<p><span class="aio-helper-ref" title="<?php echo \esc_attr( $helper_ref ); ?>"><?php echo \esc_html( $helper_ref ); ?></span></p>
				<?php endif; ?>
			<?php endif; ?>
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
			<p class="aio-preview-notice"><?php \esc_html_e( 'This preview uses synthetic data and the same section renderer as live pages. Omission and animation behavior apply.', 'aio-page-builder' ); ?></p>
			<div class="aio-preview-content">
				<?php echo \wp_kses_post( $html ); ?>
			</div>
		</section>
		<?php
	}

	private function get_state_builder(): Section_Template_Detail_State_Builder {
		$section_repo = $this->container && $this->container->has( 'section_template_repository' ) ? $this->container->get( 'section_template_repository' ) : null;
		if ( ! $section_repo instanceof \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository ) {
			$section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		}
		$section_provider = new \AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Repository_Adapter( $section_repo );

		$preview_generator  = new \AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator();
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
		$blueprint_service = null;
		if ( $this->container && $this->container->has( 'section_field_blueprint_service' ) ) {
			$svc = $this->container->get( 'section_field_blueprint_service' );
			if ( $svc instanceof \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service ) {
				$blueprint_service = $svc;
			}
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
		$versioning_service = null;
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

		return new Section_Template_Detail_State_Builder(
			$section_provider,
			$preview_generator,
			$side_panel_builder,
			$context_builder,
			$section_renderer,
			$assembly_pipeline,
			$blueprint_service,
			$lpagery_compatibility,
			$preview_cache,
			$versioning_service,
			$deprecation_service
		);
	}
}
