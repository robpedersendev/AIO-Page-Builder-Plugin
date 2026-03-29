<?php
/**
 * Section Templates directory screen: hierarchical browse by purpose family, CTA/variant (spec §49.6, section-template-directory-ia-extension).
 * Breadcrumbs, filters, search, pagination, helper-doc access, field-summary visibility, status/version.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Screens\Sections\Industry_Section_Library_Filter_Controller;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Library_Read_Model_Builder;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Directory_State_Builder;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Admin\Services\Helper_Doc_Url_Resolver;

/**
 * Renders the Section Templates directory: root (purpose tree), purpose (L3 CTA/variant nodes), or list (section rows).
 * No section detail preview in this screen; View and helper links are capability-gated links to their respective screens.
 */
final class Section_Templates_Directory_Screen {

	public const SLUG = 'aio-page-builder-section-templates';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Section Templates', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_SECTION_TEMPLATES;
	}

	/**
	 * Renders directory: capability check, state build, breadcrumbs, filters, then view-specific content.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$state_builder                              = $this->get_state_builder();
		$paged                                      = $this->get_positive_int_query_arg( 'paged', 1 );
		$per_page                                   = $this->get_positive_int_query_arg(
			'per_page',
			\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::DEFAULT_PER_PAGE,
			\AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::MAX_PER_PAGE
		);
		$request                                    = array(
			'purpose_family'       => isset( $_GET['purpose_family'] ) ? \sanitize_key( \wp_unslash( $_GET['purpose_family'] ) ) : '',
			'cta_classification'   => isset( $_GET['cta_classification'] ) ? \sanitize_key( \wp_unslash( $_GET['cta_classification'] ) ) : '',
			'variation_family_key' => isset( $_GET['variation_family_key'] ) ? \sanitize_key( \wp_unslash( $_GET['variation_family_key'] ) ) : '',
			'all'                  => isset( $_GET['all'] ) && \sanitize_key( \wp_unslash( $_GET['all'] ) ) === '1',
			'status'               => isset( $_GET['status'] ) ? \sanitize_key( \wp_unslash( $_GET['status'] ) ) : '',
			'search'               => isset( $_GET['search'] ) ? \sanitize_text_field( \wp_unslash( $_GET['search'] ) ) : '',
			'paged'                => $paged,
			'per_page'             => $per_page,
			'industry_view'        => isset( $_GET['industry_view'] ) ? \sanitize_key( \wp_unslash( $_GET['industry_view'] ) ) : Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY,
		);
		$state                                      = $state_builder->build_state( $request );
		$state                                      = $this->enrich_state_with_industry( $state, $request );
		$state['industry_section_overrides_by_key'] = ( new Industry_Section_Override_Service() )->list_overrides();

		$view = (string) ( $state['view'] ?? 'root' );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-section-templates-directory" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<?php
			$docs_base = \apply_filters( 'aio_page_builder_docs_base_url', '' );
			$guide_ref = ( is_string( $docs_base ) && $docs_base !== '' )
				? '<a href="' . \esc_url( rtrim( $docs_base, '/' ) . '/guides/template-library-operator-guide.md' ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Template Library Operator Guide', 'aio-page-builder' ) . '</a>'
				: \esc_html__( 'Template Library Operator Guide (docs/guides/template-library-operator-guide.md)', 'aio-page-builder' );
			?>
			<p class="aio-description" aria-label="<?php \esc_attr_e( 'Help reference', 'aio-page-builder' ); ?>">
			<?php
			echo \wp_kses(
				sprintf( /* translators: %s: link or path to operator guide */ __( 'For full guidance on browsing, compare, and compositions, see the %s.', 'aio-page-builder' ), $guide_ref ),
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			);
			?>
													</p>
			<?php $this->render_breadcrumbs( $state ); ?>
			<?php $this->render_filters( $state ); ?>
			<?php
			if ( $view === 'root' ) {
				$this->render_root( $state );
			} elseif ( $view === 'purpose' ) {
				$this->render_purpose( $state );
			} else {
				$this->render_list( $state );
			}
			?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Enriches state with industry filter and badges when view is list or search. Safe fallback when industry unavailable.
	 *
	 * @param array<string, mixed> $state
	 * @param array<string, mixed> $request
	 * @return array<string, mixed>
	 */
	private function enrich_state_with_industry( array $state, array $request ): array {
		$view = (string) ( $state['view'] ?? 'root' );
		if ( $view !== 'list' && $view !== 'search' ) {
			$state['industry_view']          = Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY;
			$state['industry_badges_by_key'] = array();
			return $state;
		}
		$profile_repo  = null;
		$pack_registry = null;
		if ( $this->container && $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ) ) {
			$store = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
			if ( $store instanceof \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository ) {
				$profile_repo = $store;
			}
		}
		if ( $this->container && $this->container->has( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ) ) {
			$r = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY );
			if ( $r instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry ) {
				$pack_registry = $r;
			}
		}
		$read_model_builder = new Industry_Section_Library_Read_Model_Builder( null, new \AIOPageBuilder\Domain\Industry\Profile\Industry_Weighted_Recommendation_Engine() );
		$controller         = new Industry_Section_Library_Filter_Controller( $read_model_builder, $profile_repo, $pack_registry );
		return $controller->enrich_state( $state, $request );
	}

	private function get_state_builder(): Section_Template_Directory_State_Builder {
		$query_service = null;
		if ( $this->container && $this->container->has( 'large_library_query_service' ) ) {
			$query_service = $this->container->get( 'large_library_query_service' );
		}
		if ( ! $query_service instanceof \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service ) {
			$section_repo  = $this->container && $this->container->has( 'section_template_repository' )
				? $this->container->get( 'section_template_repository' )
				: new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
			$page_repo     = $this->container && $this->container->has( 'page_template_repository' )
				? $this->container->get( 'page_template_repository' )
				: new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
			$query_service = new \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service( $section_repo, $page_repo );
		}
		return new Section_Template_Directory_State_Builder( $query_service );
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_breadcrumbs( array $state ): void {
		$breadcrumbs = $state['breadcrumbs'] ?? array();
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
	private function render_filters( array $state ): void {
		$filters = $state['filters'] ?? array();
		$purpose = (string) ( $filters['purpose_family'] ?? '' );
		$cta     = (string) ( $filters['cta_classification'] ?? '' );
		$variant = (string) ( $filters['variation_family_key'] ?? '' );
		$status  = (string) ( $filters['status'] ?? '' );
		$search  = (string) ( $filters['search'] ?? '' );
		?>
		<?php
		$industry_view = (string) ( $state['industry_view'] ?? Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY );
		?>
		<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-directory-filters">
			<input type="hidden" name="page" value="<?php echo \esc_attr( Template_Library_Hub_Urls::HUB_PAGE_SLUG ); ?>" />
			<input type="hidden" name="<?php echo \esc_attr( Template_Library_Hub_Urls::QUERY_TAB ); ?>" value="<?php echo \esc_attr( Template_Library_Hub_Urls::TAB_SECTION ); ?>" />
			<?php if ( $purpose !== '' ) : ?>
				<input type="hidden" name="purpose_family" value="<?php echo \esc_attr( $purpose ); ?>" />
			<?php endif; ?>
			<?php if ( $cta !== '' ) : ?>
				<input type="hidden" name="cta_classification" value="<?php echo \esc_attr( $cta ); ?>" />
			<?php endif; ?>
			<?php if ( $variant !== '' ) : ?>
				<input type="hidden" name="variation_family_key" value="<?php echo \esc_attr( $variant ); ?>" />
			<?php endif; ?>
			<label for="aio-section-filter-industry"><?php \esc_html_e( 'Industry fit', 'aio-page-builder' ); ?></label>
			<select id="aio-section-filter-industry" name="industry_view">
				<option value="<?php echo \esc_attr( Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY ); ?>" <?php selected( $industry_view, Industry_Section_Library_Read_Model_Builder::VIEW_FULL_LIBRARY ); ?>><?php \esc_html_e( 'Show all', 'aio-page-builder' ); ?></option>
				<option value="<?php echo \esc_attr( Industry_Section_Library_Read_Model_Builder::VIEW_RECOMMENDED_PLUS_WEAK ); ?>" <?php selected( $industry_view, Industry_Section_Library_Read_Model_Builder::VIEW_RECOMMENDED_PLUS_WEAK ); ?>><?php \esc_html_e( 'Recommended + weak fit', 'aio-page-builder' ); ?></option>
				<option value="<?php echo \esc_attr( Industry_Section_Library_Read_Model_Builder::VIEW_RECOMMENDED_ONLY ); ?>" <?php selected( $industry_view, Industry_Section_Library_Read_Model_Builder::VIEW_RECOMMENDED_ONLY ); ?>><?php \esc_html_e( 'Recommended only', 'aio-page-builder' ); ?></option>
			</select>
			<label for="aio-section-filter-search"><?php \esc_html_e( 'Search', 'aio-page-builder' ); ?></label>
			<input type="search" id="aio-section-filter-search" name="search" value="<?php echo \esc_attr( $search ); ?>" placeholder="<?php \esc_attr_e( 'Name or key…', 'aio-page-builder' ); ?>" />
			<label for="aio-section-filter-status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></label>
			<select id="aio-section-filter-status" name="status">
				<option value=""><?php \esc_html_e( 'Any', 'aio-page-builder' ); ?></option>
				<option value="active" <?php selected( $status, 'active' ); ?>><?php \esc_html_e( 'Active', 'aio-page-builder' ); ?></option>
				<option value="draft" <?php selected( $status, 'draft' ); ?>><?php \esc_html_e( 'Draft', 'aio-page-builder' ); ?></option>
				<option value="inactive" <?php selected( $status, 'inactive' ); ?>><?php \esc_html_e( 'Inactive', 'aio-page-builder' ); ?></option>
				<option value="deprecated" <?php selected( $status, 'deprecated' ); ?>><?php \esc_html_e( 'Deprecated', 'aio-page-builder' ); ?></option>
			</select>
			<button type="submit" class="button"><?php \esc_html_e( 'Apply', 'aio-page-builder' ); ?></button>
		</form>
		<?php
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_root( array $state ): void {
		$tree = $state['tree'] ?? array();
		if ( count( $tree ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No section purpose families available.', 'aio-page-builder' ) . '</p>';
			return;
		}
		echo '<ul class="aio-purpose-tree">';
		foreach ( $tree as $node ) {
			$url   = (string) ( $node['url'] ?? '#' );
			$label = (string) ( $node['label'] ?? '' );
			$count = (int) ( $node['count'] ?? 0 );
			echo '<li><a href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . ' <span class="count">(' . (int) $count . ')</span></a></li>';
		}
		echo '</ul>';
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_purpose( array $state ): void {
		$l3_nodes = $state['l3_nodes'] ?? array();
		if ( count( $l3_nodes ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No CTA or variant groups in this purpose family.', 'aio-page-builder' ) . '</p>';
			return;
		}
		echo '<ul class="aio-l3-nodes">';
		foreach ( $l3_nodes as $node ) {
			$url   = (string) ( $node['url'] ?? '#' );
			$label = (string) ( $node['label'] ?? '' );
			$count = (int) ( $node['count'] ?? 0 );
			echo '<li><a href="' . \esc_url( $url ) . '">' . \esc_html( $label ) . ' <span class="count">(' . (int) $count . ')</span></a></li>';
		}
		echo '</ul>';
	}

	/**
	 * @param array<string, mixed> $state
	 * @return void
	 */
	private function render_list( array $state ): void {
		$list_result    = $state['list_result'] ?? array();
		$rows           = $list_result['rows'] ?? array();
		$pagination     = $list_result['pagination'] ?? array();
		$base_url       = (string) ( $state['base_url'] ?? '' );
		$filters        = $state['filters'] ?? array();
		$purpose        = (string) ( $filters['purpose_family'] ?? '' );
		$cta            = (string) ( $filters['cta_classification'] ?? '' );
		$variant        = (string) ( $filters['variation_family_key'] ?? '' );
		$paged          = (int) ( $filters['paged'] ?? 1 );
		$per_page       = (int) ( $filters['per_page'] ?? 25 );
		$search         = (string) ( $filters['search'] ?? '' );
		$purpose_labels = $state['purpose_labels'] ?? array();
		$cta_labels     = $state['cta_labels'] ?? array();

		$query_args = Template_Library_Hub_Urls::query_args_for_tab( Template_Library_Hub_Urls::TAB_SECTION );
		if ( $purpose !== '' ) {
			$query_args['purpose_family'] = $purpose;
		}
		if ( $cta !== '' ) {
			$query_args['cta_classification'] = $cta;
		}
		if ( $variant !== '' ) {
			$query_args['variation_family_key'] = $variant;
		}
		if ( ! empty( $filters['all'] ) ) {
			$query_args['all'] = '1';
		}
		if ( $search !== '' ) {
			$query_args['search'] = $search;
		}
		$industry_view = (string) ( $state['industry_view'] ?? '' );
		if ( $industry_view !== '' ) {
			$query_args['industry_view'] = $industry_view;
		}
		$query_args['per_page'] = $per_page;

		if ( count( $rows ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No sections match the current filters.', 'aio-page-builder' ) . '</p>';
			return;
		}
		$industry_badges_by_key            = $state['industry_badges_by_key'] ?? array();
		$industry_section_overrides_by_key = $state['industry_section_overrides_by_key'] ?? array();
		$industry_weighted_by_key          = $state['industry_weighted_by_key'] ?? array();
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Helper Doc', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$key           = (string) ( $row['internal_key'] ?? '' );
					$name          = (string) ( $row['name'] ?? '' );
					$pf_slug       = (string) ( $row['section_purpose_family'] ?? '' );
					$pf_label      = isset( $purpose_labels[ $pf_slug ] ) ? $purpose_labels[ $pf_slug ] : $pf_slug;
					$cta_slug      = (string) ( $row['cta_classification'] ?? '' );
					$var_slug      = (string) ( $row['variation_family_key'] ?? '' );
					$cta_var       = $cta_slug !== '' ? ( $cta_labels[ $cta_slug ] ?? $cta_slug ) : ( $var_slug !== '' ? \ucfirst( \str_replace( array( '_', '-' ), ' ', $var_slug ) ) : '—' );
					$status        = (string) ( $row['status'] ?? '' );
					$version       = (string) ( $row['version'] ?? '1' );
					$placement     = (string) ( $row['placement_tendency'] ?? '' );
					$variant_count = (int) ( $row['variant_count'] ?? 0 );
					$helper_ref    = (string) ( $row['helper_ref'] ?? '' );
					$field_ref     = (string) ( $row['field_blueprint_ref'] ?? '' );
					$detail_args   = array(
						'page'    => Section_Template_Detail_Screen::SLUG,
						'section' => $key,
					);
					if ( $pf_slug !== '' ) {
						$detail_args['purpose_family'] = $pf_slug;
					}
					$view_url = \add_query_arg( $detail_args, \admin_url( 'admin.php' ) );
					if ( $this->container && $this->container->has( 'admin_router' ) ) {
						$view_url = (string) $this->container->get( 'admin_router' )->url( 'section_template_detail', $detail_args );
					}
					$preview_url = $view_url;

					$helper_state = array(
						'available' => false,
						'url'       => '',
						'message'   => Helper_Doc_Url_Resolver::UNAVAILABLE_MESSAGE,
						'doc_id'    => '',
					);
					if ( $this->container && $this->container->has( 'helper_doc_url_resolver' ) ) {
						$resolver = $this->container->get( 'helper_doc_url_resolver' );
						if ( $resolver instanceof Helper_Doc_Url_Resolver ) {
							$helper_state = $resolver->resolve( $key, $version, $helper_ref !== '' ? $helper_ref : null );
						}
					}
					$in_compare          = \in_array( $key, Template_Compare_Screen::get_compare_list( 'section' ), true );
					$item_view           = isset( $industry_badges_by_key[ $key ] ) ? $industry_badges_by_key[ $key ] : null;
					$section_override    = isset( $industry_section_overrides_by_key[ $key ] ) && is_array( $industry_section_overrides_by_key[ $key ] ) ? $industry_section_overrides_by_key[ $key ] : null;
					$show_use_anyway     = $item_view !== null && $section_override === null && \in_array( $item_view->get_recommendation_status(), array( Industry_Section_Recommendation_Resolver::FIT_DISCOURAGED, Industry_Section_Recommendation_Resolver::FIT_ALLOWED_WEAK ), true );
					$weighted            = isset( $industry_weighted_by_key[ $key ] ) && is_array( $industry_weighted_by_key[ $key ] ) ? $industry_weighted_by_key[ $key ] : null;
					$conflict_results    = ( $weighted !== null && ! empty( $weighted['conflict_results'] ) ) ? $weighted['conflict_results'] : array();
					$explanation_summary = ( $weighted !== null && isset( $weighted['explanation_summary'] ) ) ? (string) $weighted['explanation_summary'] : '';
					?>
					<tr>
						<td><code><?php echo \esc_html( $key ); ?></code></td>
						<td><?php echo \esc_html( $name ); ?></td>
						<td>
							<?php echo \esc_html( $pf_label !== '' ? $pf_label : '—' ); ?>
							<?php if ( $cta_var !== '—' ) : ?>
								<div class="description"><?php echo \esc_html( $cta_var ); ?></div>
							<?php endif; ?>
						</td>
						<td><?php echo \esc_html( $version ); ?></td>
						<td>
							<?php if ( ! empty( $helper_state['available'] ) && (string) ( $helper_state['url'] ?? '' ) !== '' ) : ?>
								<a href="<?php echo \esc_url( (string) $helper_state['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php \esc_html_e( 'Open helper doc', 'aio-page-builder' ); ?></a>
							<?php else : ?>
								<span class="aio-helper-doc-unavailable"><?php \esc_html_e( 'Not available', 'aio-page-builder' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo \esc_html( $status ); ?></td>
						<td>
							<a href="<?php echo \esc_url( $view_url ); ?>"><?php \esc_html_e( 'View detail', 'aio-page-builder' ); ?></a>
							<?php if ( ! empty( $helper_state['available'] ) && (string) ( $helper_state['url'] ?? '' ) !== '' ) : ?>
								| <a href="<?php echo \esc_url( (string) $helper_state['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php \esc_html_e( 'Open helper doc', 'aio-page-builder' ); ?></a>
							<?php else : ?>
								| <span class="aio-helper-doc-unavailable" title="<?php echo \esc_attr( Helper_Doc_Url_Resolver::UNAVAILABLE_MESSAGE ); ?>"><?php \esc_html_e( 'Open helper doc', 'aio-page-builder' ); ?></span>
							<?php endif; ?>
							| <a href="<?php echo \esc_url( $preview_url ); ?>"><?php \esc_html_e( 'Structural preview', 'aio-page-builder' ); ?></a>
							<?php if ( $in_compare ) : ?>
								| <a class="aio-compare-action" href="<?php echo \esc_url( Template_Compare_Screen::get_compare_remove_url( 'section', $key ) ); ?>" data-aio-compare-type="section" data-aio-compare-key="<?php echo \esc_attr( $key ); ?>" data-aio-compare-op="remove"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
							<?php else : ?>
								| <a class="aio-compare-action" href="<?php echo \esc_url( Template_Compare_Screen::get_compare_add_url( 'section', $key ) ); ?>" data-aio-compare-type="section" data-aio-compare-key="<?php echo \esc_attr( $key ); ?>" data-aio-compare-op="add"><?php \esc_html_e( 'Add to compare', 'aio-page-builder' ); ?></a>
							<?php endif; ?>
							<?php if ( $section_override !== null ) : ?>
								| <span class="aio-section-overridden" title="<?php echo \esc_attr( (string) ( $section_override['reason'] ?? '' ) ); ?>"><?php \esc_html_e( 'Overridden', 'aio-page-builder' ); ?></span>
							<?php elseif ( $show_use_anyway ) : ?>
								| <form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>" class="aio-inline-form" style="display:inline;">
									<input type="hidden" name="action" value="aio_save_industry_section_override" />
									<?php \wp_nonce_field( \AIOPageBuilder\Admin\Actions\Save_Industry_Section_Override_Action::NONCE_ACTION, \AIOPageBuilder\Admin\Actions\Save_Industry_Section_Override_Action::NONCE_NAME ); ?>
									<input type="hidden" name="section_key" value="<?php echo \esc_attr( $key ); ?>" />
									<input type="hidden" name="state" value="accepted" />
									<input type="hidden" name="_wp_http_referer" value="<?php echo \esc_attr( $this->get_current_request_uri() ); ?>" />
									<button type="submit" class="button-link"><?php \esc_html_e( 'Use anyway', 'aio-page-builder' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$this->render_pagination( $pagination, $query_args, $base_url );
	}

	/**
	 * @param array{page?: int, total_pages?: int} $pagination
	 * @param array<string, string|int>            $query_args
	 * @param string                               $base_url
	 * @return void
	 */
	private function render_pagination( array $pagination, array $query_args, string $base_url ): void {
		$page        = (int) ( $pagination['page'] ?? 1 );
		$total_pages = (int) ( $pagination['total_pages'] ?? 1 );
		if ( $total_pages <= 1 ) {
			return;
		}
		echo '<nav class="aio-pagination" aria-label="' . \esc_attr__( 'Pagination', 'aio-page-builder' ) . '"><ul class="aio-pagination-list">';
		if ( $page > 1 ) {
			$prev_args = array_merge( $query_args, array( 'paged' => $page - 1 ) );
			echo '<li><a href="' . \esc_url( \add_query_arg( $prev_args, $base_url ) ) . '">' . \esc_html__( 'Previous', 'aio-page-builder' ) . '</a></li>';
		}
		echo '<li><span class="aio-pagination-info">' . \esc_html(
			sprintf(
				/* translators: 1: current page, 2: total pages */
				\__( 'Page %1$s of %2$s', 'aio-page-builder' ),
				\number_format_i18n( $page ),
				\number_format_i18n( $total_pages )
			)
		) . '</span></li>';
		if ( $page < $total_pages ) {
			$next_args = array_merge( $query_args, array( 'paged' => $page + 1 ) );
			echo '<li><a href="' . \esc_url( \add_query_arg( $next_args, $base_url ) ) . '">' . \esc_html__( 'Next', 'aio-page-builder' ) . '</a></li>';
		}
		echo '</ul></nav>';
	}

	/**
	 * Reads a positive integer query arg with optional upper bound.
	 *
	 * @param string   $key Query arg name.
	 * @param int      $default Default value.
	 * @param int|null $max Optional maximum.
	 * @return int
	 */
	private function get_positive_int_query_arg( string $key, int $default, ?int $max = null ): int {
		$value = \filter_input( INPUT_GET, $key, FILTER_VALIDATE_INT );

		if ( ! is_int( $value ) || $value < 1 ) {
			return $default;
		}

		if ( $max !== null ) {
			return min( $value, $max );
		}

		return $value;
	}

	/**
	 * Returns a sanitized current request URI for referer fields.
	 *
	 * @return string
	 */
	private function get_current_request_uri(): string {
		$request_uri = \filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );

		return is_string( $request_uri ) ? $request_uri : '';
	}
}
