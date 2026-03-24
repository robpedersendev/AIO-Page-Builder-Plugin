<?php
/**
 * Compositions screen: list governed custom compositions and large-library composition builder (Prompt 177, spec §14, §49.6).
 * Category-aware section selection, CTA-safe insertion guidance, preview and one-pager readiness. No freeform builder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Templates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Compositions\UI\Composition_Builder_State_Builder;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Infrastructure\AdminRouting\Template_Library_Hub_Urls;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders Compositions list and composition builder with filtered section selection and CTA awareness.
 */
final class Compositions_Screen {

	public const SLUG = 'aio-page-builder-compositions';

	/** List view cap for performance at scale (Prompt 188, template-admin-performance-hardening-report). */
	private const LIST_LIMIT = 100;

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Compositions', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_COMPOSITIONS;
	}

	/**
	 * Renders list or builder view; capability-gated.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_or_site_admin( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to view this page.', 'aio-page-builder' ), 403 );
		}

		$view = isset( $_GET['view'] ) ? \sanitize_key( (string) $_GET['view'] ) : 'list';
		if ( $view === 'build' ) {
			$this->render_build_view( $embed_in_hub );
			return;
		}
		$this->render_list_view( $embed_in_hub );
	}

	private function render_list_view( bool $embed_in_hub = false ): void {
		$compositions = $this->get_compositions_list();
		$base_url     = Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_COMPOSITIONS );
		$build_url    = Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_COMPOSITIONS, array( 'view' => 'build' ) );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-compositions-screen" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-description">
				<?php \esc_html_e( 'Governed custom page-template compositions assembled from section templates. Use Build to create or edit with category and CTA-aware section selection.', 'aio-page-builder' ); ?>
			</p>
			<?php
			$docs_base = \apply_filters( 'aio_page_builder_docs_base_url', '' );
			$guide_ref = ( is_string( $docs_base ) && $docs_base !== '' )
				? '<a href="' . \esc_url( rtrim( $docs_base, '/' ) . '/guides/template-library-operator-guide.md' ) . '" target="_blank" rel="noopener">' . \esc_html__( 'Template Library Operator Guide', 'aio-page-builder' ) . '</a>'
				: \esc_html__( 'Template Library Operator Guide (docs/guides/template-library-operator-guide.md)', 'aio-page-builder' );
			?>
			<p class="aio-description" aria-label="<?php \esc_attr_e( 'Help reference', 'aio-page-builder' ); ?>">
			<?php
			echo \wp_kses(
				sprintf( /* translators: %s: link or path to operator guide */ __( 'For full guidance, see the %s.', 'aio-page-builder' ), $guide_ref ),
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
			<p>
				<a href="<?php echo \esc_url( $build_url ); ?>" class="button button-primary"><?php \esc_html_e( 'Build composition', 'aio-page-builder' ); ?></a>
			</p>
			<?php if ( count( $compositions ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No compositions yet. Use Build to create one from the section library.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'ID', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Validation', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Sections', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Source template', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $compositions as $comp ) : ?>
							<?php
							$comp_id       = (string) ( $comp[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
							$name          = (string) ( $comp[ Composition_Schema::FIELD_NAME ] ?? $comp_id );
							$status        = (string) ( $comp[ Composition_Schema::FIELD_STATUS ] ?? '' );
							$val_status    = (string) ( $comp[ Composition_Schema::FIELD_VALIDATION_STATUS ] ?? '' );
							$ordered       = $comp[ Composition_Schema::FIELD_ORDERED_SECTION_LIST ] ?? array();
							$section_count = is_array( $ordered ) ? count( $ordered ) : 0;
							$source_ref    = (string) ( $comp[ Composition_Schema::FIELD_SOURCE_TEMPLATE_REF ] ?? '' );
							$edit_url      = Template_Library_Hub_Urls::tab_url(
								Template_Library_Hub_Urls::TAB_COMPOSITIONS,
								array(
									'view'           => 'build',
									'composition_id' => $comp_id,
								)
							);
							?>
							<tr>
								<td><?php echo \esc_html( $name ); ?></td>
								<td><code><?php echo \esc_html( $comp_id ); ?></code></td>
								<td><?php echo \esc_html( $status ); ?></td>
								<td><?php echo \esc_html( $val_status ); ?></td>
								<td><?php echo (int) $section_count; ?></td>
								<td><?php echo $source_ref !== '' ? \esc_html( $source_ref ) : '—'; ?></td>
								<td><a href="<?php echo \esc_url( $edit_url ); ?>"><?php \esc_html_e( 'Edit', 'aio-page-builder' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	private function render_build_view( bool $embed_in_hub = false ): void {
		$request             = array(
			'view'                 => 'build',
			'composition_id'       => isset( $_GET['composition_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['composition_id'] ) ) : '',
			'purpose_family'       => isset( $_GET['purpose_family'] ) ? \sanitize_key( (string) $_GET['purpose_family'] ) : '',
			'category'             => isset( $_GET['category'] ) ? \sanitize_key( (string) $_GET['category'] ) : '',
			'cta_classification'   => isset( $_GET['cta_classification'] ) ? \sanitize_key( (string) $_GET['cta_classification'] ) : '',
			'variation_family_key' => isset( $_GET['variation_family_key'] ) ? \sanitize_key( (string) $_GET['variation_family_key'] ) : '',
			'search'               => isset( $_GET['search'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['search'] ) ) : '',
			'status'               => isset( $_GET['status'] ) ? \sanitize_key( (string) $_GET['status'] ) : '',
			'paged'                => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1,
			'per_page'             => isset( $_GET['per_page'] ) ? max( 1, min( 100, (int) $_GET['per_page'] ) ) : 25,
		);
		$current_composition = null;
		if ( $request['composition_id'] !== '' ) {
			$repo                = $this->get_composition_repository();
			$current_composition = $repo->get_definition_by_key( $request['composition_id'] );
		}
		$state_builder = $this->get_builder_state_builder();
		$state         = $state_builder->build_state( $request, $current_composition );

		$base_url          = (string) ( $state['base_url'] ?? '' );
		$filter_state      = $state['filter_state'] ?? array();
		$section_result    = $state['section_result'] ?? array();
		$rows              = $section_result['rows'] ?? array();
		$ordered_display   = $state['ordered_sections_display'] ?? array();
		$cta_warnings      = $state['cta_warnings'] ?? array();
		$insertion_hint    = (string) ( $state['insertion_hint'] ?? '' );
		$validation_status = (string) ( $state['validation_status'] ?? '' );
		$validation_codes  = $state['validation_codes'] ?? array();
		$preview_readiness = (bool) ( $state['preview_readiness'] ?? true );
		$one_pager_ready   = (bool) ( $state['one_pager_ready'] ?? false );
		$list_url          = Template_Library_Hub_Urls::tab_url( Template_Library_Hub_Urls::TAB_COMPOSITIONS );
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-composition-builder" role="main" aria-label="<?php echo \esc_attr__( 'Composition builder', 'aio-page-builder' ); ?>">
			<h1><?php \esc_html_e( 'Composition builder', 'aio-page-builder' ); ?></h1>
		<?php endif; ?>
			<p><a href="<?php echo \esc_url( $list_url ); ?>">&larr; <?php \esc_html_e( 'Back to Compositions', 'aio-page-builder' ); ?></a></p>

			<?php if ( $current_composition !== null ) : ?>
				<p class="aio-composition-meta">
					<strong><?php \esc_html_e( 'Editing:', 'aio-page-builder' ); ?></strong>
					<?php echo \esc_html( (string) ( $current_composition[ Composition_Schema::FIELD_NAME ] ?? '' ) ); ?>
					(<?php echo \esc_html( (string) ( $current_composition[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' ) ); ?>)
					— <?php \esc_html_e( 'Status', 'aio-page-builder' ); ?>: <?php echo \esc_html( (string) ( $current_composition[ Composition_Schema::FIELD_STATUS ] ?? '' ) ); ?>
					<?php if ( $validation_status !== '' ) : ?>
						| <?php \esc_html_e( 'Validation', 'aio-page-builder' ); ?>: <?php echo \esc_html( $validation_status ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( count( $cta_warnings ) > 0 ) : ?>
				<div class="notice notice-warning inline">
					<p><strong><?php \esc_html_e( 'CTA guidance', 'aio-page-builder' ); ?></strong></p>
					<ul>
						<?php foreach ( $cta_warnings as $w ) : ?>
							<li><?php echo \esc_html( (string) ( $w['message'] ?? '' ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<p class="aio-insertion-hint"><?php echo \esc_html( $insertion_hint ); ?></p>

			<div class="aio-composition-readiness">
				<span class="aio-badge <?php echo $preview_readiness ? 'aio-badge-ok' : 'aio-badge-warning'; ?>">
					<?php $preview_readiness ? \esc_html_e( 'Preview ready', 'aio-page-builder' ) : \esc_html_e( 'Preview: add section preview data', 'aio-page-builder' ); ?>
				</span>
				<span class="aio-badge <?php echo $one_pager_ready ? 'aio-badge-ok' : 'aio-badge-muted'; ?>">
					<?php $one_pager_ready ? \esc_html_e( 'One-pager ready', 'aio-page-builder' ) : \esc_html_e( 'One-pager: add when saving', 'aio-page-builder' ); ?>
				</span>
			</div>

			<?php if ( is_array( $validation_codes ) && count( $validation_codes ) > 0 ) : ?>
				<p class="aio-validation-codes"><strong><?php \esc_html_e( 'Validation codes', 'aio-page-builder' ); ?>:</strong> <?php echo \esc_html( implode( ', ', $validation_codes ) ); ?></p>
			<?php endif; ?>

			<h2><?php \esc_html_e( 'Current sections', 'aio-page-builder' ); ?></h2>
			<?php if ( count( $ordered_display ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No sections in this composition yet. Use the section library below to add sections. Save is handled via Compositions API or Settings.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<ol class="aio-ordered-sections-list">
					<?php foreach ( $ordered_display as $row ) : ?>
						<li>
							<code><?php echo \esc_html( (string) ( $row['section_key'] ?? '' ) ); ?></code>
							— <?php echo \esc_html( (string) ( $row['name'] ?? '' ) ); ?>
							<?php if ( ! empty( $row['is_cta'] ) ) : ?>
								<span class="aio-cta-label"><?php \esc_html_e( 'CTA', 'aio-page-builder' ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<h2><?php \esc_html_e( 'Section library (filtered)', 'aio-page-builder' ); ?></h2>
			<?php $this->render_builder_filters( $base_url, $filter_state ); ?>
			<?php if ( count( $rows ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No sections match the current filters. Adjust filters or clear search.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Key', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Category', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Purpose family', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'CTA', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><code><?php echo \esc_html( (string) ( $row['internal_key'] ?? '' ) ); ?></code></td>
								<td><?php echo \esc_html( (string) ( $row['name'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['category'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['section_purpose_family'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['cta_classification'] ?? '' ) ); ?></td>
								<td><?php echo \esc_html( (string) ( $row['status'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$pagination  = $section_result['pagination'] ?? array();
				$total_pages = (int) ( $pagination['total_pages'] ?? 1 );
				$paged       = (int) ( $filter_state['paged'] ?? 1 );
				if ( $total_pages > 1 ) :
					$prev_url = $paged > 1 ? $this->builder_page_url( $base_url, $filter_state, $paged - 1 ) : '';
					$next_url = $paged < $total_pages ? $this->builder_page_url( $base_url, $filter_state, $paged + 1 ) : '';
					?>
					<p class="aio-pagination">
						<?php if ( $prev_url !== '' ) : ?>
							<a href="<?php echo \esc_url( $prev_url ); ?>"><?php \esc_html_e( '&larr; Previous', 'aio-page-builder' ); ?></a>
						<?php endif; ?>
						<?php if ( $next_url !== '' ) : ?>
							<a href="<?php echo \esc_url( $next_url ); ?>"><?php \esc_html_e( 'Next &rarr;', 'aio-page-builder' ); ?></a>
						<?php endif; ?>
						<span class="aio-pagination-info"><?php echo \esc_html( sprintf( /* translators: 1: current page, 2: total pages */ __( 'Page %1$d of %2$d', 'aio-page-builder' ), $paged, $total_pages ) ); ?></span>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * @param string               $base_url     Base URL for form action.
	 * @param array<string, mixed> $filter_state Current filter key/value state.
	 */
	private function render_builder_filters( string $base_url, array $filter_state ): void {
		$purpose        = (string) ( $filter_state['purpose_family'] ?? '' );
		$category       = (string) ( $filter_state['category'] ?? '' );
		$cta            = (string) ( $filter_state['cta_classification'] ?? '' );
		$variant        = (string) ( $filter_state['variation_family_key'] ?? '' );
		$search         = (string) ( $filter_state['search'] ?? '' );
		$status         = (string) ( $filter_state['status'] ?? '' );
		$composition_id = isset( $_GET['composition_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['composition_id'] ) ) : '';
		?>
		<form method="get" action="<?php echo \esc_url( \admin_url( 'admin.php' ) ); ?>" class="aio-directory-filters">
			<input type="hidden" name="page" value="<?php echo \esc_attr( Template_Library_Hub_Urls::HUB_PAGE_SLUG ); ?>" />
			<input type="hidden" name="<?php echo \esc_attr( Template_Library_Hub_Urls::QUERY_TAB ); ?>" value="<?php echo \esc_attr( Template_Library_Hub_Urls::TAB_COMPOSITIONS ); ?>" />
			<input type="hidden" name="view" value="build" />
			<?php if ( $composition_id !== '' ) : ?>
				<input type="hidden" name="composition_id" value="<?php echo \esc_attr( $composition_id ); ?>" />
			<?php endif; ?>
			<label for="aio-comp-filter-purpose"><?php \esc_html_e( 'Purpose family', 'aio-page-builder' ); ?></label>
			<input type="text" id="aio-comp-filter-purpose" name="purpose_family" value="<?php echo \esc_attr( $purpose ); ?>" placeholder="hero, proof, cta…" />
			<label for="aio-comp-filter-cta"><?php \esc_html_e( 'CTA', 'aio-page-builder' ); ?></label>
			<select id="aio-comp-filter-cta" name="cta_classification">
				<option value=""><?php \esc_html_e( 'Any', 'aio-page-builder' ); ?></option>
				<option value="primary_cta" <?php selected( $cta, 'primary_cta' ); ?>><?php \esc_html_e( 'Primary CTA', 'aio-page-builder' ); ?></option>
				<option value="contact_cta" <?php selected( $cta, 'contact_cta' ); ?>><?php \esc_html_e( 'Contact CTA', 'aio-page-builder' ); ?></option>
				<option value="navigation_cta" <?php selected( $cta, 'navigation_cta' ); ?>><?php \esc_html_e( 'Navigation CTA', 'aio-page-builder' ); ?></option>
				<option value="none" <?php selected( $cta, 'none' ); ?>><?php \esc_html_e( 'None', 'aio-page-builder' ); ?></option>
			</select>
			<label for="aio-comp-filter-search"><?php \esc_html_e( 'Search', 'aio-page-builder' ); ?></label>
			<input type="search" id="aio-comp-filter-search" name="search" value="<?php echo \esc_attr( $search ); ?>" placeholder="<?php \esc_attr_e( 'Name or key…', 'aio-page-builder' ); ?>" />
			<label for="aio-comp-filter-status"><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></label>
			<select id="aio-comp-filter-status" name="status">
				<option value=""><?php \esc_html_e( 'Any', 'aio-page-builder' ); ?></option>
				<option value="active" <?php selected( $status, 'active' ); ?>><?php \esc_html_e( 'Active', 'aio-page-builder' ); ?></option>
				<option value="draft" <?php selected( $status, 'draft' ); ?>><?php \esc_html_e( 'Draft', 'aio-page-builder' ); ?></option>
			</select>
			<button type="submit" class="button"><?php \esc_html_e( 'Apply filters', 'aio-page-builder' ); ?></button>
		</form>
		<?php
	}

	/**
	 * @param string               $base_url     Base URL for list links.
	 * @param array<string, mixed> $filter_state Current filter state (preserved in query).
	 * @param int                  $paged        Page number.
	 */
	private function builder_page_url( string $base_url, array $filter_state, int $paged ): string {
		$params = array(
			'view'  => 'build',
			'paged' => $paged,
		);
		if ( isset( $_GET['composition_id'] ) && (string) $_GET['composition_id'] !== '' ) {
			$params['composition_id'] = \sanitize_text_field( \wp_unslash( (string) $_GET['composition_id'] ) );
		}
		foreach ( array( 'purpose_family', 'category', 'cta_classification', 'variation_family_key', 'search', 'status', 'per_page' ) as $k ) {
			if ( isset( $filter_state[ $k ] ) && (string) $filter_state[ $k ] !== '' ) {
				$params[ $k ] = $filter_state[ $k ];
			}
		}
		return \add_query_arg( $params, $base_url );
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function get_compositions_list(): array {
		$repo = $this->get_composition_repository();
		return $repo->list_all_definitions( self::LIST_LIMIT, 0 );
	}

	private function get_composition_repository(): Composition_Repository {
		if ( $this->container && $this->container->has( 'composition_repository' ) ) {
			$repo = $this->container->get( 'composition_repository' );
			if ( $repo instanceof Composition_Repository ) {
				return $repo;
			}
		}
		return new Composition_Repository();
	}

	private function get_builder_state_builder(): Composition_Builder_State_Builder {
		$query_service = null;
		$section_repo  = null;
		if ( $this->container ) {
			if ( $this->container->has( 'large_library_query_service' ) ) {
				$query_service = $this->container->get( 'large_library_query_service' );
			}
			if ( $this->container->has( 'section_template_repository' ) ) {
				$section_repo = $this->container->get( 'section_template_repository' );
			}
		}
		if ( ! $query_service instanceof \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service ) {
			$section_repo  = $section_repo !== null ? $section_repo : new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
			$page_repo     = $this->container && $this->container->has( 'page_template_repository' )
				? $this->container->get( 'page_template_repository' )
				: new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();
			$query_service = new \AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service( $section_repo, $page_repo );
		}
		$section_repo = $section_repo !== null ? $section_repo : new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$builder      = new Composition_Builder_State_Builder( $query_service, $section_repo );
		if ( $this->container && $this->container->has( 'large_composition_validator' ) ) {
			$builder->set_large_validator( $this->container->get( 'large_composition_validator' ) );
		}
		return $builder;
	}
}
