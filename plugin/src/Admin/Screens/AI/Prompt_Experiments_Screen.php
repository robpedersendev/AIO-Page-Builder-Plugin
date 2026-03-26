<?php
/**
 * Prompt experiments admin screen (spec §26, §58.3, Prompt 121). Experiment definitions and comparison summaries.
 * Capability: aio_manage_ai_providers. No secrets; experiment runs are labeled in AI Runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\AI\PromptPacks\Experiments\Prompt_Experiment_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists experiment definitions, comparison summaries; add/edit definitions. Experiment runs appear in AI Runs with labels.
 */
final class Prompt_Experiments_Screen {

	public const SLUG = 'aio-page-builder-prompt-experiments';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Prompt Experiments', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_AI_PROVIDERS;
	}

	/**
	 * POST save: redirect URL for admin_init (see Admin_Early_Redirect_Coordinator).
	 *
	 * @return string|null
	 */
	public function get_post_redirect_url(): ?string {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			return null;
		}
		if ( ! isset( $_POST['aio_save_experiment'] ) || ! isset( $_POST['aio_experiment_nonce'] ) ) {
			return null;
		}
		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_POST['aio_experiment_nonce'] ) ), 'aio_save_experiment' ) ) {
			return null;
		}
		$service = $this->get_service();
		if ( ! $service ) {
			return null;
		}
		$name     = isset( $_POST['aio_experiment_name'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_experiment_name'] ) ) : '';
		$desc     = isset( $_POST['aio_experiment_description'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_experiment_description'] ) ) : '';
		$id       = isset( $_POST['aio_experiment_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_experiment_id'] ) ) : '';
		$raw      = isset( $_POST['aio_experiment_variants'] ) ? \sanitize_textarea_field( \wp_unslash( (string) $_POST['aio_experiment_variants'] ) ) : '';
		$variants = array();
		foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 5 ) );
			if ( count( $parts ) >= 5 ) {
				$variants[] = array(
					'variant_id'      => $parts[0],
					'label'           => $parts[1],
					'prompt_pack_ref' => array(
						'internal_key' => $parts[2],
						'version'      => $parts[3],
					),
					'provider_id'     => $parts[4],
				);
			}
		}
		$def    = array(
			'id'          => $id,
			'name'        => $name,
			'description' => $desc,
			'variants'    => $variants,
		);
		$result = $service->save_definition( $def );
		return $result['ok']
			? Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'experiments', array( 'saved' => '1' ) )
			: Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'experiments', array( 'error' => $result['message'] ) );
	}

	/**
	 * GET delete experiment: redirect URL for admin_init.
	 *
	 * @return string|null
	 */
	public function get_delete_redirect_url(): ?string {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			return null;
		}
		$delete = isset( $_GET['delete'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['delete'] ) ) : '';
		if ( $delete === '' || ! isset( $_GET['_wpnonce'] ) ) {
			return null;
		}
		if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ), 'aio_delete_experiment_' . $delete ) ) {
			return null;
		}
		$service = $this->get_service();
		if ( ! $service || ! $service->delete_definition( $delete ) ) {
			return null;
		}
		return Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'experiments', array( 'deleted' => '1' ) );
	}

	/**
	 * Renders the Prompt Experiments screen.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage prompt experiments.', 'aio-page-builder' ), 403 );
		}
		$service     = $this->get_service();
		$definitions = $service ? $service->list_definitions() : array();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-prompt-experiments" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="description"><?php \esc_html_e( 'Define prompt-pack and provider/model experiment variants. Experiment runs are recorded separately and labeled in AI Runs. No automatic promotion to production.', 'aio-page-builder' ); ?></p>
			<?php if ( ! $embed_in_hub ) : ?>
				<?php $ai_runs_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' ); ?>
				<p><a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'View AI Runs', 'aio-page-builder' ); ?></a></p>
			<?php endif; ?>

			<?php if ( empty( $definitions ) ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No experiments defined. Add one below.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<section class="aio-experiments-list" aria-labelledby="aio-experiments-heading">
					<h2 id="aio-experiments-heading"><?php \esc_html_e( 'Experiments', 'aio-page-builder' ); ?></h2>
					<?php foreach ( $definitions as $def ) : ?>
						<?php
						$exp_id  = isset( $def['id'] ) ? \sanitize_text_field( (string) $def['id'] ) : '';
						$name    = isset( $def['name'] ) ? (string) $def['name'] : '';
						$summary = $service ? $service->get_comparison_summary( $exp_id ) : array(
							'experiment_id' => $exp_id,
							'variants'      => array(),
						);
						?>
						<div class="aio-experiment-card" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #ccc; border-radius: 4px;">
							<h3><?php echo \esc_html( $name ); ?> <code><?php echo \esc_html( $exp_id ); ?></code></h3>
							<?php if ( ! empty( $summary['variants'] ) ) : ?>
								<table class="widefat striped" style="max-width: 600px;">
									<thead>
										<tr>
											<th scope="col"><?php \esc_html_e( 'Variant', 'aio-page-builder' ); ?></th>
											<th scope="col"><?php \esc_html_e( 'Total runs', 'aio-page-builder' ); ?></th>
											<th scope="col"><?php \esc_html_e( 'By status', 'aio-page-builder' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $summary['variants'] as $v ) : ?>
											<tr>
												<td><?php echo \esc_html( (string) ( $v['variant_label'] ?? $v['variant_id'] ?? '' ) ); ?></td>
												<td><?php echo \esc_html( (string) ( $v['total'] ?? 0 ) ); ?></td>
												<td><?php echo \esc_html( wp_json_encode( $v['runs'] ?? array() ) ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php else : ?>
								<p class="description"><?php \esc_html_e( 'No experiment runs yet for this definition.', 'aio-page-builder' ); ?></p>
							<?php endif; ?>
							<p>
								<a href="<?php echo \esc_url( Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'experiments', array( 'edit' => $exp_id ) ) ); ?>"><?php \esc_html_e( 'Edit', 'aio-page-builder' ); ?></a>
								| <a href="
								<?php
								echo \esc_url(
									Admin_Screen_Hub::tab_url(
										AI_Runs_Screen::HUB_PAGE_SLUG,
										'experiments',
										array(
											'delete'   => $exp_id,
											'_wpnonce' => \wp_create_nonce( 'aio_delete_experiment_' . $exp_id ),
										)
									)
								);
								?>
											" onclick="return confirm('<?php echo \esc_attr( __( 'Delete this experiment definition?', 'aio-page-builder' ) ); ?>');"><?php \esc_html_e( 'Delete', 'aio-page-builder' ); ?></a>
							</p>
						</div>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<section class="aio-add-experiment" aria-labelledby="aio-add-heading">
				<h2 id="aio-add-heading"><?php \esc_html_e( 'Add or edit experiment', 'aio-page-builder' ); ?></h2>
				<?php $this->render_form( $service, $definitions ); ?>
			</section>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	private function get_service(): ?Prompt_Experiment_Service {
		if ( ! $this->container || ! $this->container->has( 'prompt_experiment_service' ) ) {
			return null;
		}
		$svc = $this->container->get( 'prompt_experiment_service' );
		return $svc instanceof Prompt_Experiment_Service ? $svc : null;
	}

	/**
	 * @param Prompt_Experiment_Service|null   $service
	 * @param array<int, array<string, mixed>> $definitions
	 * @return void
	 */
	private function render_form( ?Prompt_Experiment_Service $service, array $definitions ): void {
		$edit_id = isset( $_GET['edit'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['edit'] ) ) : '';
		$current = array(
			'id'          => '',
			'name'        => '',
			'description' => '',
			'variants'    => array(),
		);
		if ( $edit_id !== '' && $service ) {
			$found = $service->get_definition( $edit_id );
			if ( $found !== null ) {
				$current = $found;
			}
		}
		$nonce = \wp_nonce_field( 'aio_save_experiment', 'aio_experiment_nonce', true, false );
		?>
		<form method="post" action="">
			<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nonce field HTML from wp_nonce_field(). ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="aio_exp_name"><?php \esc_html_e( 'Name', 'aio-page-builder' ); ?></label></th>
					<td><input type="text" id="aio_exp_name" name="aio_experiment_name" value="<?php echo \esc_attr( (string) ( $current['name'] ?? '' ) ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="aio_exp_desc"><?php \esc_html_e( 'Description', 'aio-page-builder' ); ?></label></th>
					<td><input type="text" id="aio_exp_desc" name="aio_experiment_description" value="<?php echo \esc_attr( (string) ( $current['description'] ?? '' ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Variants', 'aio-page-builder' ); ?></th>
					<td>
						<p class="description"><?php \esc_html_e( 'One variant per line: variant_id|label|internal_key|version|provider_id (e.g. v1|Baseline|aio/build-plan-draft|1.0.0|openai)', 'aio-page-builder' ); ?></p>
						<textarea name="aio_experiment_variants" id="aio_exp_variants" rows="6" class="large-text">
						<?php
						$variants = $current['variants'] ?? array();
						foreach ( $variants as $v ) {
							$ref = $v['prompt_pack_ref'] ?? array();
							$ik  = $ref['internal_key'] ?? '';
							$ver = $ref['version'] ?? '';
							echo \esc_textarea( ( $v['variant_id'] ?? '' ) . '|' . ( $v['label'] ?? '' ) . '|' . $ik . '|' . $ver . '|' . ( $v['provider_id'] ?? '' ) . "\n" );
						}
						?>
						</textarea>
					</td>
				</tr>
			</table>
			<?php if ( (string) ( $current['id'] ?? '' ) !== '' ) : ?>
				<input type="hidden" name="aio_experiment_id" value="<?php echo \esc_attr( (string) $current['id'] ); ?>" />
			<?php endif; ?>
			<p class="submit"><input type="submit" name="aio_save_experiment" class="button button-primary" value="<?php \esc_attr_e( 'Save experiment', 'aio-page-builder' ); ?>" /></p>
		</form>
		<?php
	}
}
