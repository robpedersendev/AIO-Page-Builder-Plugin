<?php
/**
 * Assistant UI for selecting an industry starter bundle (Prompt 388).
 * Surfaces bundles for the active industry, explains contents, lets user select or decline. Selection persists in profile; no auto-execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\Industry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

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

	public function __construct(
		?Industry_Profile_Repository $profile_repo = null,
		?Industry_Starter_Bundle_Registry $bundle_registry = null
	) {
		$this->profile_repo    = $profile_repo;
		$this->bundle_registry = $bundle_registry;
	}

	/**
	 * Builds state for the assistant: bundles for primary industry, current selection, field name.
	 *
	 * @param array<string, mixed> $profile Current industry profile (primary_industry_key, selected_starter_bundle_key).
	 * @return array{has_primary: bool, primary_industry_key: string, bundles: list<array<string, mixed>>, selected_key: string, field_name: string}
	 */
	public function build_state( array $profile ): array {
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$selected = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && \is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? \trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';

		$bundles = array();
		if ( $primary !== '' && $this->bundle_registry !== null ) {
			$for_industry = $this->bundle_registry->get_for_industry( $primary );
			foreach ( $for_industry as $bundle ) {
				if ( ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ?? '' ) === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
					$bundles[] = $bundle;
				}
			}
		}

		return array(
			'has_primary'          => $primary !== '',
			'primary_industry_key' => $primary,
			'bundles'              => $bundles,
			'selected_key'        => $selected,
			'field_name'          => self::FIELD_NAME,
		);
	}

	/**
	 * Renders the starter bundle selection block. Call from Industry Profile (or onboarding) inside the same form that saves profile.
	 *
	 * @param array{has_primary: bool, primary_industry_key: string, bundles: list<array<string, mixed>>, selected_key: string, field_name: string} $state From build_state().
	 * @return void
	 */
	public function render( array $state ): void {
		if ( ! $state['has_primary'] || empty( $state['bundles'] ) ) {
			return;
		}
		$bundles   = $state['bundles'];
		$selected  = $state['selected_key'];
		$field_name = $state['field_name'];
		?>
		<tr class="aio-starter-bundle-assistant">
			<th scope="row"><label for="aio-selected-starter-bundle"><?php \esc_html_e( 'Starter bundle', 'aio-page-builder' ); ?></label></th>
			<td>
				<select name="<?php echo \esc_attr( $field_name ); ?>" id="aio-selected-starter-bundle" aria-describedby="aio-starter-bundle-description">
					<option value="" <?php selected( $selected, '' ); ?>><?php \esc_html_e( 'None — use full library', 'aio-page-builder' ); ?></option>
					<?php foreach ( $bundles as $bundle ) : ?>
						<?php
						$bundle_key = (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ?? '' );
						$label      = (string) ( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ?? $bundle_key );
						?>
						<option value="<?php echo \esc_attr( $bundle_key ); ?>" <?php selected( $selected, $bundle_key ); ?>><?php echo \esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p id="aio-starter-bundle-description" class="description">
					<?php \esc_html_e( 'Optional. A starter bundle recommends page and section templates for your industry. Choosing one does not build pages; it guides recommendations and planning.', 'aio-page-builder' ); ?>
				</p>
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
