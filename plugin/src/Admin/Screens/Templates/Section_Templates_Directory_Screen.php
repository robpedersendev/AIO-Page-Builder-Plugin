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

use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Directory_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;

/**
 * Renders the Section Templates directory: root (purpose tree), purpose (L3 CTA/variant nodes), or list (section rows).
 * No section detail preview in this screen; View and helper links are capability-gated; detail screen is out of scope.
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
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$state_builder = $this->get_state_builder();
		$request = array(
			'purpose_family'       => isset( $_GET['purpose_family'] ) ? \sanitize_key( (string) $_GET['purpose_family'] ) : '',
			'cta_classification'  => isset( $_GET['cta_classification'] ) ? \sanitize_key( (string) $_GET['cta_classification'] ) : '',
			'variation_family_key' => isset( $_GET['variation_family_key'] ) ? \sanitize_key( (string) $_GET['variation_family_key'] ) : '',
			'all'                 => isset( $_GET['all'] ) && (string) $_GET['all'] === '1',
			'status'              => isset( $_GET['status'] ) ? \sanitize_key( (string) $_GET['status'] ) : '',
			'search'              => isset( $_GET['search'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['search'] ) ) : '',
			'paged'               => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1,
			'per_page'            => isset( $_GET['per_page'] ) ? max( 1, min( \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::MAX_PER_PAGE, (int) $_GET['per_page'] ) ) : \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::DEFAULT_PER_PAGE,
		);
		$state = $state_builder->build_state( $request );

		$view = (string) ( $state['view'] ?? 'root' );
		?>
		<div class="wrap aio-page-builder-screen aio-section-templates-directory" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
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
		</div>
		<?php
	}

	private function get_state_builder(): Section_Template_Directory_State_Builder {
		$query_service = null;
		if ( $this->container && $this->container->has( 'large_library_query_service' ) ) {
			$query_service = $this->container->get( 'large_library_query_service' );
		}
		if ( ! $query_service instanceof \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service ) {
			$section_repo = $this->container && $this->container->has( 'section_template_repository' )
				? $this->container->get( 'section_template_repository' )
				: new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
			$page_repo = $this->container && $this->container->has( 'page_template_repository' )
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
		<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-directory-filters">
			<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
			<?php if ( $purpose !== '' ) : ?>
				<input type="hidden" name="purpose_family" value="<?php echo \esc_attr( $purpose ); ?>" />
			<?php endif; ?>
			<?php if ( $cta !== '' ) : ?>
				<input type="hidden" name="cta_classification" value="<?php echo \esc_attr( $cta ); ?>" />
			<?php endif; ?>
			<?php if ( $variant !== '' ) : ?>
				<input type="hidden" name="variation_family_key" value="<?php echo \esc_attr( $variant ); ?>" />
			<?php endif; ?>
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
		$list_result = $state['list_result'] ?? array();
		$rows        = $list_result['rows'] ?? array();
		$pagination  = $list_result['pagination'] ?? array();
		$base_url    = (string) ( $state['base_url'] ?? '' );
		$filters     = $state['filters'] ?? array();
		$purpose     = (string) ( $filters['purpose_family'] ?? '' );
		$cta         = (string) ( $filters['cta_classification'] ?? '' );
		$variant     = (string) ( $filters['variation_family_key'] ?? '' );
		$paged       = (int) ( $filters['paged'] ?? 1 );
		$per_page    = (int) ( $filters['per_page'] ?? 25 );
		$search      = (string) ( $filters['search'] ?? '' );
		$purpose_labels = $state['purpose_labels'] ?? array();
		$cta_labels    = $state['cta_labels'] ?? array();

		$query_args = array( 'page' => self::SLUG );
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
		$query_args['per_page'] = $per_page;

		if ( count( $rows ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No sections match the current filters.', 'aio-page-builder' ) . '</p>';
			return;
		}
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Internal key', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Purpose family', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'CTA / Variant', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Placement', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Variants', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Helper / Field', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$key        = (string) ( $row['internal_key'] ?? '' );
					$name       = (string) ( $row['name'] ?? '' );
					$pf_slug    = (string) ( $row['section_purpose_family'] ?? '' );
					$pf_label   = isset( $purpose_labels[ $pf_slug ] ) ? $purpose_labels[ $pf_slug ] : $pf_slug;
					$cta_slug   = (string) ( $row['cta_classification'] ?? '' );
					$var_slug   = (string) ( $row['variation_family_key'] ?? '' );
					$cta_var    = $cta_slug !== '' ? ( $cta_labels[ $cta_slug ] ?? $cta_slug ) : ( $var_slug !== '' ? \ucfirst( \str_replace( array( '_', '-' ), ' ', $var_slug ) ) : '—' );
					$status     = (string) ( $row['status'] ?? '' );
					$version    = (string) ( $row['version'] ?? '1' );
					$placement  = (string) ( $row['placement_tendency'] ?? '' );
					$variant_count = (int) ( $row['variant_count'] ?? 0 );
					$helper_ref  = (string) ( $row['helper_ref'] ?? '' );
					$field_ref  = (string) ( $row['field_blueprint_ref'] ?? '' );
					$detail_args = array( 'page' => Section_Template_Detail_Screen::SLUG, 'section' => $key );
					if ( $pf_slug !== '' ) {
						$detail_args['purpose_family'] = $pf_slug;
					}
					$view_url   = \add_query_arg( $detail_args, \admin_url( 'admin.php' ) );
					$helper_url = ''; // * Helper-doc link: populated on detail screen when helper_doc_url resolver exists.
					$in_compare = \in_array( $key, Template_Compare_Screen::get_compare_list( 'section' ), true );
					?>
					<tr>
						<td><?php echo \esc_html( $name ); ?></td>
						<td><code><?php echo \esc_html( $key ); ?></code></td>
						<td><?php echo \esc_html( $pf_label ); ?></td>
						<td><?php echo \esc_html( $cta_var ); ?></td>
						<td><?php echo \esc_html( $status ); ?></td>
						<td><?php echo \esc_html( $version ); ?></td>
						<td><?php echo \esc_html( $placement !== '' ? \str_replace( array( '_', '-' ), ' ', $placement ) : '—' ); ?></td>
						<td><?php echo (int) $variant_count; ?></td>
						<td>
							<?php if ( $helper_ref !== '' ) : ?>
								<span class="aio-helper-ref" title="<?php echo \esc_attr( $helper_ref ); ?>"><?php \esc_html_e( 'Helper', 'aio-page-builder' ); ?></span>
								<?php if ( $field_ref !== '' ) : ?> | <?php endif; ?>
							<?php endif; ?>
							<?php if ( $field_ref !== '' ) : ?>
								<span class="aio-field-ref" title="<?php echo \esc_attr( $field_ref ); ?>"><?php \esc_html_e( 'Field summary', 'aio-page-builder' ); ?></span>
							<?php endif; ?>
							<?php if ( $helper_ref === '' && $field_ref === '' ) : ?>
								—
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo \esc_url( $view_url ); ?>"><?php \esc_html_e( 'View', 'aio-page-builder' ); ?></a>
							<?php if ( $in_compare ) : ?>
								| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_remove_url( 'section', $key ) ); ?>"><?php \esc_html_e( 'Remove from compare', 'aio-page-builder' ); ?></a>
							<?php else : ?>
								| <a href="<?php echo \esc_url( Template_Compare_Screen::get_compare_add_url( 'section', $key ) ); ?>"><?php \esc_html_e( 'Add to compare', 'aio-page-builder' ); ?></a>
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
	 * @param array<string, string|int> $query_args
	 * @param string $base_url
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
		echo '<li><span class="aio-pagination-info">' . sprintf( /* translators: 1: current page, 2: total pages */ \esc_html__( 'Page %1$d of %2$d', 'aio-page-builder' ), $page, $total_pages ) . '</span></li>';
		if ( $page < $total_pages ) {
			$next_args = array_merge( $query_args, array( 'paged' => $page + 1 ) );
			echo '<li><a href="' . \esc_url( \add_query_arg( $next_args, $base_url ) ) . '">' . \esc_html__( 'Next', 'aio-page-builder' ) . '</a></li>';
		}
		echo '</ul></nav>';
	}
}
