<?php
/**
 * Assistant UI for selecting an industry starter bundle (Prompt 388, 449).
 * Surfaces bundles for the active industry (and optional subtype), explains contents, lets user select or decline.
 * Subtype-aware: shows parent vs subtype bundles and supports clearing back to parent. Selection persists in profile; no auto-execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Actions\Create_Plan_From_Starter_Bundle_Action;
use AIOPageBuilder\Admin\ViewModels\Industry\Subtype_Starter_Bundle_Selection_View_Model;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

/**
 * Builds state and renders the starter bundle selection block for Industry Profile (and optionally onboarding).
 * Selection is advisory; no pages or site structure are built from selection alone.
 */
final class Industry_Starter_Bundle_Assistant {

	public const FIELD_NAME = 'aio_selected_starter_bundle_key';

	/** @var Industry_Profile_Repository|null */
	private ?Industry_Profile_Repository $profile_repo;

	/** @var Industry_Starter_Bundle_Registry|null */
	private ?Industry_Starter_Bundle_Registry $bundle_registry;

	/** @var Industry_Subtype_Registry|null Optional; when set, build_state returns subtype-aware view model (Prompt 449). */
	private ?Industry_Subtype_Registry $subtype_registry;

	public function __construct(
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Starter_Bundle_Registry $bundle_registry = null,
		?Industry_Subtype_Registry $subtype_registry = null
	) {
		$this->profile_repo     = $profile_repo;
		$this->bundle_registry   = $bundle_registry;
		$this->subtype_registry  = $subtype_registry;
	}

	/**
	 * Builds state for the assistant: bundles for primary industry (and subtype when set), current selection, field name.
	 * When subtype_registry is set, state includes subtype_bundle_view_model for subtype-aware UI (parent vs subtype, clear to parent).
	 *
	 * @param array<string, mixed> $profile Current industry profile (primary_industry_key, selected_starter_bundle_key, industry_subtype_key).
	 * @return array{has_primary: bool, primary_industry_key: string, bundles: list<array<string, mixed>>, selected_key: string, field_name: string, subtype_bundle_view_model?: Subtype_Starter_Bundle_Selection_View_Model}
	 */
	public function build_state( array $profile ): array {
		$view_model = Subtype_Starter_Bundle_Selection_View_Model::from_profile( $profile, $this->bundle_registry, $this->subtype_registry );
		$bundles = $view_model->display_bundles !== array()
			? $view_model->display_bundles
			: array_map( function ( array $b ): array {
				return array(
					Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_BUNDLE_KEY       => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' ),
					Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_LABEL           => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? '' ),
					Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_SUMMARY         => (string) ( $b[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ?? '' ),
					Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_IS_SUBTYPE_BUNDLE => false,
					Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_GROUP_LABEL     => '',
				);
			}, $view_model->parent_bundles );
		return array(
			'has_primary'                 => $view_model->has_primary,
			'primary_industry_key'       => $view_model->primary_industry_key,
			'bundles'                    => $bundles,
			'selected_key'               => $view_model->selected_key,
			'field_name'                 => $view_model->field_name,
			'subtype_bundle_view_model'  => $view_model,
		);
	}

	/**
	 * Renders the starter bundle selection block. Call from Industry Profile (or onboarding) inside the same form that saves profile.
	 * When state includes subtype_bundle_view_model with both parent and subtype bundles, renders optgroups and optional "clear to parent" note.
	 *
	 * @param array{has_primary: bool, primary_industry_key: string, bundles: list<array<string, mixed>>, selected_key: string, field_name: string, subtype_bundle_view_model?: Subtype_Starter_Bundle_Selection_View_Model} $state From build_state().
	 * @return void
	 */
	public function render( array $state ): void {
		if ( ! $state['has_primary'] || empty( $state['bundles'] ) ) {
			return;
		}
		$bundles    = $state['bundles'];
		$selected   = $state['selected_key'];
		$field_name = $state['field_name'];
		$vm         = $state['subtype_bundle_view_model'] ?? null;
		$use_optgroup = $vm !== null && $vm->has_subtype_bundles && $vm->parent_bundles !== array();
		?>
		<tr class="aio-starter-bundle-assistant">
			<th scope="row"><label for="aio-selected-starter-bundle"><?php \esc_html_e( 'Starter bundle', 'aio-page-builder' ); ?></label></th>
			<td>
				<select name="<?php echo \esc_attr( $field_name ); ?>" id="aio-selected-starter-bundle" aria-describedby="aio-starter-bundle-description">
					<option value="" <?php selected( $selected, '' ); ?>><?php \esc_html_e( 'None — use full library', 'aio-page-builder' ); ?></option>
					<?php
					if ( $use_optgroup ) {
						$current_group = '';
						foreach ( $bundles as $bundle ) {
							$bundle_key  = (string) ( $bundle[ Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_BUNDLE_KEY ] ?? '' );
							$label       = (string) ( $bundle[ Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_LABEL ] ?? $bundle_key );
							$group_label = (string) ( $bundle[ Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_GROUP_LABEL ] ?? '' );
							if ( $group_label !== '' && $group_label !== $current_group ) {
								if ( $current_group !== '' ) {
									echo '</optgroup>';
								}
								echo '<optgroup label="' . \esc_attr( $group_label ) . '">';
								$current_group = $group_label;
							}
							?>
							<option value="<?php echo \esc_attr( $bundle_key ); ?>" <?php selected( $selected, $bundle_key ); ?>><?php echo \esc_html( $label ); ?></option>
							<?php
						}
						if ( $current_group !== '' ) {
							echo '</optgroup>';
						}
					} else {
						foreach ( $bundles as $bundle ) {
							$bundle_key = (string) ( $bundle[ Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_BUNDLE_KEY ] ?? $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' );
							$label      = (string) ( $bundle[ Subtype_Starter_Bundle_Selection_View_Model::DISPLAY_LABEL ] ?? $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? $bundle_key );
							?>
							<option value="<?php echo \esc_attr( $bundle_key ); ?>" <?php selected( $selected, $bundle_key ); ?>><?php echo \esc_html( $label ); ?></option>
						<?php }
					}
					?>
				</select>
				<p id="aio-starter-bundle-description" class="description">
					<?php \esc_html_e( 'Optional. A starter bundle recommends page and section templates for your industry. Choosing one does not build pages; it guides recommendations and planning.', 'aio-page-builder' ); ?>
				</p>
				<?php
				if ( $vm !== null && $vm->can_clear_to_parent ) {
					?>
					<p class="aio-starter-bundle-clear-to-parent description"><?php \esc_html_e( 'You can switch back to the default industry bundle above.', 'aio-page-builder' ); ?></p>
					<?php
				}
				if ( $selected !== '' ) {
					$create_plan_url = \add_query_arg(
						array(
							'action' => 'aio_create_plan_from_bundle',
							Create_Plan_From_Starter_Bundle_Action::PARAM_BUNDLE_KEY => $selected,
							Create_Plan_From_Starter_Bundle_Action::NONCE_NAME => \wp_create_nonce( Create_Plan_From_Starter_Bundle_Action::NONCE_ACTION ),
						),
						\admin_url( 'admin-post.php' )
					);
					?>
					<p class="aio-starter-bundle-create-plan">
						<a href="<?php echo \esc_url( $create_plan_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Create draft Build Plan from this bundle', 'aio-page-builder' ); ?></a>
					</p>
				<?php } ?>
				<?php if ( ! empty( $bundles ) ) : ?>
				<details class="aio-starter-bundle-details" style="margin-top: 0.5rem;">
					<summary><?php \esc_html_e( 'What’s in each bundle?', 'aio-page-builder' ); ?></summary>
					<ul class="aio-starter-bundle-list">
						<?php foreach ( $bundles as $bundle ) : ?>
							<?php
							$bundle_key = (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' );
							$label      = (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? $bundle_key );
							$summary    = (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ?? '' );
							?>
							<li><strong><?php echo \esc_html( $label ); ?></strong>: <?php echo \esc_html( $summary ); ?></li>
						<?php endforeach; ?>
					</ul>
				</details>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
