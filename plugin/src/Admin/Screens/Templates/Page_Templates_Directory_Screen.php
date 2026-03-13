<?php
/**
 * Page Templates directory screen: hierarchical browse by category, family, list (spec §49.7, page-template-directory-ia-extension).
 * Breadcrumbs, filters, search, pagination, section-order preview, one-pager access, authorized composition controls.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Directory_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders the Page Templates directory: root (category tree), category (family list), or list (template rows).
 * No detail preview in this screen; one-pager and composition are capability-gated links/placeholders.
 */
final class Page_Templates_Directory_Screen {

	public const SLUG = 'aio-page-builder-page-templates';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Page Templates', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::VIEW_BUILD_PLANS;
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
			'category_class' => isset( $_GET['category_class'] ) ? \sanitize_key( (string) $_GET['category_class'] ) : '',
			'family'         => isset( $_GET['family'] ) ? \sanitize_key( (string) $_GET['family'] ) : '',
			'status'         => isset( $_GET['status'] ) ? \sanitize_key( (string) $_GET['status'] ) : '',
			'search'         => isset( $_GET['search'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['search'] ) ) : '',
			'paged'          => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1,
			'per_page'       => isset( $_GET['per_page'] ) ? max( 1, min( 100, (int) $_GET['per_page'] ) ) : \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service::DEFAULT_PER_PAGE,
		);
		$state = $state_builder->build_state( $request );

		$view = (string) ( $state['view'] ?? 'root' );
		?>
		<div class="wrap aio-page-builder-screen aio-page-templates-directory" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<?php $this->render_breadcrumbs( $state ); ?>
			<?php $this->render_filters( $state ); ?>
			<?php
			if ( $view === 'root' ) {
				$this->render_root( $state );
			} elseif ( $view === 'category' ) {
				$this->render_category( $state );
			} else {
				$this->render_list( $state );
			}
			?>
		</div>
		<?php
	}

	private function get_state_builder(): Page_Template_Directory_State_Builder {
		$query_service = null;
		if ( $this->container && $this->container->has( 'large_library_query_service' ) ) {
			$query_service = $this->container->get( 'large_library_query_service' );
		}
		if ( ! $query_service instanceof \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service ) {
			$query_service = new \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service(
				$this->container && $this->container->has( 'page_template_repository' ) ? $this->container->get( 'page_template_repository' ) : null,
				null
			);
		}
		return new Page_Template_Directory_State_Builder( $query_service );
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
		$base_url = (string) ( $state['base_url'] ?? \admin_url( 'admin.php?page=' . self::SLUG ) );
		$filters  = $state['filters'] ?? array();
		$cat      = (string) ( $filters['category_class'] ?? '' );
		$family   = (string) ( $filters['family'] ?? '' );
		$status   = (string) ( $filters['status'] ?? '' );
		$search   = (string) ( $filters['search'] ?? '' );
		$labels   = $state['category_labels'] ?? array();
		?>
		<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-directory-filters">
			<input type="hidden" name="page" value="<?php echo \esc_attr( self::SLUG ); ?>" />
			<?php if ( $cat !== '' ) : ?>
				<input type="hidden" name="category_class" value="<?php echo \esc_attr( $cat ); ?>" />
			<?php endif; ?>
			<?php if ( $family !== '' ) : ?>
				<input type="hidden" name="family" value="<?php echo \esc_attr( $family ); ?>" />
			<?php endif; ?>
			<label for="aio-filter-search"><?php \esc_html_e( 'Search', 'aio-page-builder' ); ?></label>
			<input type="search" id="aio-filter-search" name="search" value="<?php echo \esc_attr( $search ); ?>" placeholder="<?php \esc_attr_e( 'Name or key…', 'aio-page-builder' ); ?>" />
			<label for="aio-filter-status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></label>
			<select id="aio-filter-status" name="status">
				<option value=""><?php \esc_html_e( 'Any', 'aio-page-builder' ); ?></option>
				<option value="stable" <?php selected( $status, 'stable' ); ?>><?php \esc_html_e( 'Stable', 'aio-page-builder' ); ?></option>
				<option value="draft" <?php selected( $status, 'draft' ); ?>><?php \esc_html_e( 'Draft', 'aio-page-builder' ); ?></option>
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
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No page template categories available.', 'aio-page-builder' ) . '</p>';
			return;
		}
		echo '<ul class="aio-category-tree">';
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
	private function render_category( array $state ): void {
		$families = $state['families'] ?? array();
		if ( count( $families ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No families in this category.', 'aio-page-builder' ) . '</p>';
			return;
		}
		echo '<ul class="aio-family-list">';
		foreach ( $families as $node ) {
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
		$cat         = (string) ( $filters['category_class'] ?? '' );
		$family      = (string) ( $filters['family'] ?? '' );
		$paged       = (int) ( $filters['paged'] ?? 1 );
		$per_page    = (int) ( $filters['per_page'] ?? 25 );
		$search      = (string) ( $filters['search'] ?? '' );
		$can_manage  = ! empty( $state['can_manage_templates'] );
		$category_labels = $state['category_labels'] ?? array();

		$query_args = array( 'page' => self::SLUG );
		if ( $cat !== '' ) {
			$query_args['category_class'] = $cat;
		}
		if ( $family !== '' ) {
			$query_args['family'] = $family;
		}
		if ( $search !== '' ) {
			$query_args['search'] = $search;
		}
		$query_args['per_page'] = $per_page;

		if ( count( $rows ) === 0 ) {
			echo '<p class="aio-admin-notice">' . \esc_html__( 'No templates match the current filters.', 'aio-page-builder' ) . '</p>';
			return;
		}
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Internal key', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Family', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Sections', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$key   = (string) ( $row['internal_key'] ?? '' );
					$name  = (string) ( $row['name'] ?? '' );
					$cat_slug = (string) ( $row['template_category_class'] ?? '' );
					$cat_label = isset( $category_labels[ $cat_slug ] ) ? $category_labels[ $cat_slug ] : $cat_slug;
					$fam_slug = (string) ( $row['template_family'] ?? '' );
					$fam_label = $fam_slug !== '' ? \ucfirst( \str_replace( array( '_', '-' ), ' ', $fam_slug ) ) : '';
					$status = (string) ( $row['status'] ?? '' );
					$version = (string) ( $row['version'] ?? '1' );
					$section_count = (int) ( $row['section_count'] ?? 0 );
					$one_pager_url = ''; // * One-pager link: placeholder until detail/one-pager screen exists.
					$view_url = \add_query_arg( array_merge( $query_args, array( 'template' => $key ) ), $base_url );
					?>
					<tr>
						<td><?php echo \esc_html( $name ); ?></td>
						<td><code><?php echo \esc_html( $key ); ?></code></td>
						<td><?php echo \esc_html( $cat_label ); ?></td>
						<td><?php echo \esc_html( $fam_label ); ?></td>
						<td><?php echo \esc_html( $status ); ?></td>
						<td><?php echo \esc_html( $version ); ?></td>
						<td><?php echo (int) $section_count; ?></td>
						<td>
							<?php if ( $one_pager_url !== '' ) : ?>
								<a href="<?php echo \esc_url( $one_pager_url ); ?>"><?php \esc_html_e( 'One-pager', 'aio-page-builder' ); ?></a>
								|
							<?php endif; ?>
							<a href="<?php echo \esc_url( $view_url ); ?>"><?php \esc_html_e( 'View', 'aio-page-builder' ); ?></a>
							<?php if ( $can_manage ) : ?>
								| <span class="aio-composition-control"><?php \esc_html_e( 'Composition', 'aio-page-builder' ); ?></span>
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
