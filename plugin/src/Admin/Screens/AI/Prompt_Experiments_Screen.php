<?php
/**
 * Compare AI setups admin screen (spec §26, §58.3, Prompt 121). Saved comparison definitions and per-alternative run summaries.
 * Capability: aio_manage_ai_providers. No secrets; comparison-tagged runs are labeled in AI Runs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\AI\PromptPacks\Experiments\Prompt_Experiment_Service;
use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Prompt_Pack_Repository;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Lists saved comparisons, run summaries; add/edit definitions. Tagged runs appear in AI Runs with a badge.
 */
final class Prompt_Experiments_Screen {

	public const SLUG = 'aio-page-builder-prompt-experiments';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'Compare AI setups', 'aio-page-builder' );
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
		$variants = $this->parse_variants_from_post();
		$def      = array(
			'id'          => $id,
			'name'        => $name,
			'description' => $desc,
			'variants'    => $variants,
		);
		$result   = $service->save_definition( $def );
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
			\wp_die( \esc_html__( 'You do not have permission to manage AI setup comparisons.', 'aio-page-builder' ), 403 );
		}
		$service     = $this->get_service();
		$definitions = $service ? $service->list_definitions() : array();
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-prompt-experiments" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<?php $this->render_admin_notices(); ?>
			<?php $this->render_intro_panel( $embed_in_hub ); ?>

			<?php if ( empty( $definitions ) ) : ?>
				<div class="notice notice-warning inline aio-prompt-experiments-empty" style="margin: 1em 0;">
					<p><strong><?php \esc_html_e( 'You have not saved any comparisons yet.', 'aio-page-builder' ); ?></strong></p>
					<p><?php \esc_html_e( 'After you save, your comparison appears above this form. Each comparison lists several alternatives (different prompt packs and/or providers) so you can review results side by side in AI Runs.', 'aio-page-builder' ); ?></p>
				</div>
				<h2 class="title" style="margin-top: 1.5em;"><?php \esc_html_e( 'How to get started', 'aio-page-builder' ); ?></h2>
				<ol class="aio-prompt-experiments-steps" style="margin-left: 1.25em; max-width: 46rem;">
					<li><?php \esc_html_e( 'Choose a clear name and optional notes so you remember what you are testing.', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'Add two or more rows—each row is one alternative: a prompt pack (the instruction set) plus a provider (who sends the request).', 'aio-page-builder' ); ?></li>
					<li><?php \esc_html_e( 'Click Save. When comparison runs exist, counts per alternative show up in the card above.', 'aio-page-builder' ); ?></li>
					<li>
						<?php
						echo \esc_html(
							sprintf(
								/* translators: %s: tab label "AI Runs" */
								__( 'Use the %s tab to open runs that were tagged as part of a comparison. They show a small badge so you can match them to the alternatives you defined here.', 'aio-page-builder' ),
								__( 'AI Runs', 'aio-page-builder' )
							)
						);
						?>
					</li>
				</ol>
			<?php else : ?>
				<section class="aio-experiments-list" aria-labelledby="aio-experiments-heading">
					<h2 id="aio-experiments-heading"><?php \esc_html_e( 'Saved comparisons', 'aio-page-builder' ); ?></h2>
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
								<p class="description"><?php \esc_html_e( 'No runs have been tagged for this comparison yet.', 'aio-page-builder' ); ?></p>
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
				<h2 id="aio-add-heading"><?php \esc_html_e( 'Add or change a comparison', 'aio-page-builder' ); ?></h2>
				<p class="description" style="max-width: 46rem;"><?php \esc_html_e( 'Saving only stores this list on your site. It does not call the AI. When another workflow records a run as part of a comparison, you will see it under AI Runs with a comparison badge.', 'aio-page-builder' ); ?></p>
				<?php $this->render_form( $service, $definitions ); ?>
			</section>
		<?php if ( ! $embed_in_hub ) : ?>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Success, delete, and validation messages after redirects.
	 *
	 * @return void
	 */
	private function render_admin_notices(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only query args for display notices.
		$saved = isset( $_GET['saved'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['saved'] ) ) : '';
		if ( $saved === '1' ) {
			echo '<div class="notice notice-success is-dismissible" role="status"><p>' . \esc_html__( 'Comparison saved.', 'aio-page-builder' ) . '</p></div>';
		}
		$deleted = isset( $_GET['deleted'] ) ? \sanitize_key( (string) \wp_unslash( $_GET['deleted'] ) ) : '';
		if ( $deleted === '1' ) {
			echo '<div class="notice notice-success is-dismissible" role="status"><p>' . \esc_html__( 'Comparison deleted.', 'aio-page-builder' ) . '</p></div>';
		}
		$err = isset( $_GET['error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['error'] ) ) : '';
		if ( $err !== '' ) {
			echo '<div class="notice notice-error" role="alert"><p>' . \esc_html( $err ) . '</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Explains purpose, workflow, and limits in plain language.
	 *
	 * @param bool $embed_in_hub Whether the screen is embedded in the AI hub (no outer wrap).
	 * @return void
	 */
	private function render_intro_panel( bool $embed_in_hub ): void {
		$ai_runs_url = Admin_Screen_Hub::tab_url( AI_Runs_Screen::HUB_PAGE_SLUG, 'ai_runs' );
		?>
		<div class="notice notice-info inline aio-prompt-experiments-intro" style="margin: 1em 0; max-width: 52rem;">
			<p><strong><?php \esc_html_e( 'What you can do here', 'aio-page-builder' ); ?></strong></p>
			<p><?php \esc_html_e( 'Save named lists of AI setups you want to compare. Each row is one alternative: a prompt pack (the instructions the model follows) and a provider (which connection sends the job). Your normal defaults stay untouched.', 'aio-page-builder' ); ?></p>
			<p><strong><?php \esc_html_e( 'Why it helps', 'aio-page-builder' ); ?></strong></p>
			<ul style="margin-left: 1.25em; list-style: disc;">
				<li><?php \esc_html_e( 'Try different packs or providers without changing production settings.', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'Runs that are tagged as part of a comparison show up in AI Runs with a badge, so you can line them up with the alternatives you defined.', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'Each saved card below shows run counts per alternative and a quick view of statuses.', 'aio-page-builder' ); ?></li>
			</ul>
			<p><strong><?php \esc_html_e( 'What it does not do', 'aio-page-builder' ); ?></strong></p>
			<ul style="margin-left: 1.25em; list-style: disc;">
				<li><?php \esc_html_e( 'It does not switch your site to a new default pack or provider.', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'It does not call the AI by itself—other features must record comparison runs when they run.', 'aio-page-builder' ); ?></li>
			</ul>
			<p>
				<a class="button button-secondary" href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'Open AI Runs', 'aio-page-builder' ); ?></a>
				<?php if ( ! $embed_in_hub ) : ?>
					<span class="description" style="margin-left: 0.5em;"><?php \esc_html_e( 'Tagged comparison runs show a badge in the list.', 'aio-page-builder' ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Short help for the alternatives table (dropdowns + optional manual pack fields).
	 *
	 * @return void
	 */
	private function render_alternatives_help(): void {
		?>
		<details class="aio-prompt-experiments-format-details" style="margin: 0.75em 0 1em;">
			<summary style="cursor: pointer; font-weight: 600;"><?php \esc_html_e( 'Field reference', 'aio-page-builder' ); ?></summary>
			<ul class="description" style="margin: 0.75em 0 0 1.25em; list-style: disc; max-width: 48rem;">
				<li><?php \esc_html_e( 'Alternative id: short code stored on runs (e.g. v1).', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'Label: name shown in AI Runs and summaries.', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'Prompt pack: choose a registered pack and version, or expand “Pack not in the list” to type the internal key and version.', 'aio-page-builder' ); ?></li>
				<li><?php \esc_html_e( 'Provider: same provider id as under AI Providers (e.g. openai).', 'aio-page-builder' ); ?></li>
			</ul>
		</details>
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
		$pack_choices = $this->collect_prompt_pack_choices();
		$provider_ids = $this->collect_provider_id_choices();
		$row_variants = isset( $current['variants'] ) && is_array( $current['variants'] ) ? $current['variants'] : array();
		$nonce        = \wp_nonce_field( 'aio_save_experiment', 'aio_experiment_nonce', true, false );
		?>
		<form method="post" action="" id="aio-cmp-form">
			<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Nonce field HTML from wp_nonce_field(). ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="aio_exp_name"><?php \esc_html_e( 'Comparison name', 'aio-page-builder' ); ?></label></th>
					<td><input type="text" id="aio_exp_name" name="aio_experiment_name" value="<?php echo \esc_attr( (string) ( $current['name'] ?? '' ) ); ?>" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="aio_exp_desc"><?php \esc_html_e( 'Description', 'aio-page-builder' ); ?></label></th>
					<td><input type="text" id="aio_exp_desc" name="aio_experiment_description" value="<?php echo \esc_attr( (string) ( $current['description'] ?? '' ) ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php \esc_html_e( 'Alternatives to compare', 'aio-page-builder' ); ?></th>
					<td>
						<p class="description" id="aio_exp_variants_desc"><?php \esc_html_e( 'Add one row per alternative. Use the dropdowns for prompt pack and provider, or type a pack key and version if it is not listed.', 'aio-page-builder' ); ?></p>
						<?php $this->render_alternatives_help(); ?>
						<?php if ( $pack_choices === array() ) : ?>
							<p class="notice notice-warning inline" style="margin: 0.5em 0; padding: 0.5em 0.75em;"><?php \esc_html_e( 'No prompt packs were found in the library. You can still enter internal key and version under each row.', 'aio-page-builder' ); ?></p>
						<?php endif; ?>
						<?php
						$this->render_comparison_alternatives_table(
							$row_variants,
							$pack_choices,
							$provider_ids
						);
						$this->render_comparison_row_script();
						?>
					</td>
				</tr>
			</table>
			<?php if ( (string) ( $current['id'] ?? '' ) !== '' ) : ?>
				<input type="hidden" name="aio_experiment_id" value="<?php echo \esc_attr( (string) $current['id'] ); ?>" />
			<?php endif; ?>
			<p class="submit"><input type="submit" name="aio_save_experiment" class="button button-primary" value="<?php \esc_attr_e( 'Save comparison', 'aio-page-builder' ); ?>" /></p>
		</form>
		<?php
	}

	/**
	 * Builds variant payloads from structured POST rows (and optional manual pack fields).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function parse_variants_from_post(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in get_post_redirect_url() before this runs.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Rows sanitized per field below.
		if ( ! isset( $_POST['aio_cmp_alt'] ) || ! is_array( $_POST['aio_cmp_alt'] ) ) {
			return array();
		}
		$rows = \wp_unslash( $_POST['aio_cmp_alt'] );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$variants = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$variant_id = isset( $row['variant_id'] ) ? \sanitize_text_field( (string) $row['variant_id'] ) : '';
			$label      = isset( $row['label'] ) ? \sanitize_text_field( (string) $row['label'] ) : '';
			$pack_sel   = isset( $row['pack'] ) ? (string) $row['pack'] : '';
			$ik_manual  = isset( $row['internal_key'] ) ? \sanitize_text_field( (string) $row['internal_key'] ) : '';
			$ver_manual = isset( $row['version'] ) ? \sanitize_text_field( (string) $row['version'] ) : '';
			$provider   = isset( $row['provider_id'] ) ? \sanitize_key( (string) $row['provider_id'] ) : '';
			$ik         = '';
			$ver        = '';
			if ( $ik_manual !== '' && $ver_manual !== '' ) {
				$ik  = $ik_manual;
				$ver = $ver_manual;
			} elseif ( $pack_sel !== '' ) {
				$decoded = self::decode_pack_option_value( $pack_sel );
				if ( $decoded !== null ) {
					$ik  = $decoded[0];
					$ver = $decoded[1];
				}
			}
			if ( $variant_id === '' && $label === '' && $ik === '' && $ver === '' && $provider === '' ) {
				continue;
			}
			if ( $variant_id === '' || $ik === '' || $ver === '' || $provider === '' ) {
				continue;
			}
			$variants[] = array(
				'variant_id'      => $variant_id,
				'label'           => $label,
				'prompt_pack_ref' => array(
					'internal_key' => $ik,
					'version'      => $ver,
				),
				'provider_id'     => $provider,
			);
		}
		return $variants;
	}

	private function get_prompt_pack_repository(): ?Prompt_Pack_Repository {
		if ( ! $this->container || ! $this->container->has( 'prompt_pack_repository' ) ) {
			return null;
		}
		$r = $this->container->get( 'prompt_pack_repository' );
		return $r instanceof Prompt_Pack_Repository ? $r : null;
	}

	/**
	 * @return list<array{internal_key: string, version: string, label: string}>
	 */
	private function collect_prompt_pack_choices(): array {
		$repo = $this->get_prompt_pack_repository();
		if ( ! $repo ) {
			return array();
		}
		$seen = array();
		$out  = array();
		foreach ( array( Prompt_Pack_Schema::STATUS_ACTIVE, Prompt_Pack_Schema::STATUS_INACTIVE, Prompt_Pack_Schema::STATUS_DEPRECATED ) as $status ) {
			foreach ( $repo->list_definitions_by_status( $status, 500, 0 ) as $def ) {
				if ( ! is_array( $def ) ) {
					continue;
				}
				$ik  = (string) ( $def[ Prompt_Pack_Schema::ROOT_INTERNAL_KEY ] ?? '' );
				$ver = (string) ( $def[ Prompt_Pack_Schema::ROOT_VERSION ] ?? '' );
				if ( $ik === '' || $ver === '' ) {
					continue;
				}
				$sig = $ik . "\0" . $ver;
				if ( isset( $seen[ $sig ] ) ) {
					continue;
				}
				$seen[ $sig ] = true;
				$name         = (string) ( $def[ Prompt_Pack_Schema::ROOT_NAME ] ?? $ik );
				$out[]        = array(
					'internal_key' => $ik,
					'version'      => $ver,
					'label'        => sprintf(
						/* translators: 1: pack display name, 2: internal key, 3: version */
						__( '%1$s (%2$s @ %3$s)', 'aio-page-builder' ),
						$name,
						$ik,
						$ver
					),
				);
			}
		}
		usort(
			$out,
			static function ( array $a, array $b ): int {
				return strcmp( $a['internal_key'] . $a['version'], $b['internal_key'] . $b['version'] );
			}
		);
		return $out;
	}

	/**
	 * @return list<string>
	 */
	private function collect_provider_id_choices(): array {
		$ids = array();
		if ( $this->container && $this->container->has( 'openai_provider_driver' ) ) {
			$ids[] = 'openai';
		}
		if ( $this->container && $this->container->has( 'anthropic_provider_driver' ) ) {
			$ids[] = 'anthropic';
		}
		return $ids !== array() ? $ids : array( 'openai', 'anthropic' );
	}

	private static function encode_pack_option_value( string $internal_key, string $version ): string {
		return \rawurlencode( $internal_key ) . ':::' . \rawurlencode( $version );
	}

	/**
	 * @return array{0: string, 1: string}|null
	 */
	private static function decode_pack_option_value( string $raw ): ?array {
		$parts = \explode( ':::', $raw, 2 );
		if ( \count( $parts ) !== 2 ) {
			return null;
		}
		return array( \rawurldecode( $parts[0] ), \rawurldecode( $parts[1] ) );
	}

	/**
	 * @param array<int, array<string, mixed>>                                  $variants
	 * @param list<array{internal_key: string, version: string, label: string}> $pack_choices
	 * @param list<string>                                                      $provider_ids
	 * @return void
	 */
	private function render_comparison_alternatives_table( array $variants, array $pack_choices, array $provider_ids ): void {
		$rows = $variants;
		while ( \count( $rows ) < 2 ) {
			$rows[] = array();
		}
		$next_index = \count( $rows );
		?>
		<table class="widefat striped aio-cmp-alt-table" style="max-width: 960px;">
			<thead>
				<tr>
					<th scope="col"><?php \esc_html_e( 'Alternative id', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Label', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></th>
					<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php \esc_html_e( 'Row actions', 'aio-page-builder' ); ?></span></th>
				</tr>
			</thead>
			<tbody id="aio-cmp-alt-tbody" data-next-index="<?php echo \esc_attr( (string) $next_index ); ?>">
				<?php
				foreach ( array_values( $rows ) as $i => $row ) {
					$this->render_comparison_alternative_row( (int) $i, $row, $pack_choices, $provider_ids );
				}
				?>
			</tbody>
		</table>
		<p style="margin-top: 0.75em;">
			<button type="button" class="button button-secondary" id="aio-cmp-add-row"><?php \esc_html_e( 'Add alternative', 'aio-page-builder' ); ?></button>
		</p>
		<table class="aio-cmp-alt-template-wrap" style="display: none;" aria-hidden="true">
			<tbody id="aio-cmp-row-template">
				<?php $this->render_comparison_alternative_row( '__INDEX__', array(), $pack_choices, $provider_ids, true ); ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param int|string                                                        $index Row index or '__INDEX__' for JS template.
	 * @param array<string, mixed>                                              $variant
	 * @param list<array{internal_key: string, version: string, label: string}> $pack_choices
	 * @param list<string>                                                      $provider_ids
	 * @param bool                                                              $for_template Whether this row is the clone source (no selected values).
	 * @return void
	 */
	private function render_comparison_alternative_row( $index, array $variant, array $pack_choices, array $provider_ids, bool $for_template = false ): void {
		$ix            = (string) $index;
		$name_prefix   = 'aio_cmp_alt[' . $ix . ']';
		$vid           = $for_template ? '' : (string) ( $variant['variant_id'] ?? '' );
		$label         = $for_template ? '' : (string) ( $variant['label'] ?? '' );
		$ref           = ( ! $for_template && isset( $variant['prompt_pack_ref'] ) && is_array( $variant['prompt_pack_ref'] ) ) ? $variant['prompt_pack_ref'] : array();
		$cur_ik        = (string) ( $ref['internal_key'] ?? '' );
		$cur_ver       = (string) ( $ref['version'] ?? '' );
		$cur_provider  = $for_template ? '' : (string) ( $variant['provider_id'] ?? '' );
		$encoded_cur   = ( $cur_ik !== '' && $cur_ver !== '' ) ? self::encode_pack_option_value( $cur_ik, $cur_ver ) : '';
		$match_in_list = false;
		if ( $encoded_cur !== '' ) {
			foreach ( $pack_choices as $ch ) {
				if ( $ch['internal_key'] === $cur_ik && $ch['version'] === $cur_ver ) {
					$match_in_list = true;
					break;
				}
			}
		}
		?>
		<tr class="aio-cmp-alt-row">
			<td>
				<?php if ( ! $for_template ) : ?>
					<label class="screen-reader-text" for="aio_cmp_vid_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Alternative id', 'aio-page-builder' ); ?></label>
					<input type="text" class="regular-text" id="aio_cmp_vid_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[variant_id]' ); ?>" value="<?php echo \esc_attr( $vid ); ?>" autocomplete="off" />
				<?php else : ?>
					<span class="screen-reader-text"><?php \esc_html_e( 'Alternative id', 'aio-page-builder' ); ?></span>
					<input type="text" class="regular-text" name="<?php echo \esc_attr( $name_prefix . '[variant_id]' ); ?>" value="" autocomplete="off" />
				<?php endif; ?>
			</td>
			<td>
				<?php if ( ! $for_template ) : ?>
					<label class="screen-reader-text" for="aio_cmp_lab_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Label', 'aio-page-builder' ); ?></label>
					<input type="text" class="regular-text" id="aio_cmp_lab_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[label]' ); ?>" value="<?php echo \esc_attr( $label ); ?>" autocomplete="off" />
				<?php else : ?>
					<span class="screen-reader-text"><?php \esc_html_e( 'Label', 'aio-page-builder' ); ?></span>
					<input type="text" class="regular-text" name="<?php echo \esc_attr( $name_prefix . '[label]' ); ?>" value="" autocomplete="off" />
				<?php endif; ?>
			</td>
			<td>
				<?php if ( ! $for_template ) : ?>
					<label class="screen-reader-text" for="aio_cmp_pack_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></label>
					<select id="aio_cmp_pack_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[pack]' ); ?>" class="aio-cmp-pack-select">
				<?php else : ?>
					<span class="screen-reader-text"><?php \esc_html_e( 'Prompt pack', 'aio-page-builder' ); ?></span>
					<select name="<?php echo \esc_attr( $name_prefix . '[pack]' ); ?>" class="aio-cmp-pack-select">
				<?php endif; ?>
					<option value=""><?php \esc_html_e( '— Select —', 'aio-page-builder' ); ?></option>
					<?php if ( $encoded_cur !== '' && ! $match_in_list && ! $for_template ) : ?>
						<option value="<?php echo \esc_attr( $encoded_cur ); ?>" selected><?php echo \esc_html( sprintf( __( 'Current: %1$s @ %2$s', 'aio-page-builder' ), $cur_ik, $cur_ver ) ); ?></option>
					<?php endif; ?>
					<?php foreach ( $pack_choices as $ch ) : ?>
						<?php
						$enc = self::encode_pack_option_value( $ch['internal_key'], $ch['version'] );
						$sel = ( ! $for_template && $encoded_cur === $enc );
						?>
						<option value="<?php echo \esc_attr( $enc ); ?>" <?php echo $sel ? ' selected="selected"' : ''; ?>><?php echo \esc_html( $ch['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<details class="aio-cmp-manual-pack" style="margin-top: 0.5em;">
					<summary><?php \esc_html_e( 'Pack not in the list?', 'aio-page-builder' ); ?></summary>
					<p class="description" style="margin: 0.35em 0;"><?php \esc_html_e( 'If both fields below are filled, they are used instead of the dropdown.', 'aio-page-builder' ); ?></p>
					<p style="margin: 0.25em 0;">
						<?php if ( ! $for_template ) : ?>
							<label for="aio_cmp_ik_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Internal key', 'aio-page-builder' ); ?></label><br />
							<input type="text" class="large-text code" id="aio_cmp_ik_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[internal_key]' ); ?>" value="<?php echo \esc_attr( $match_in_list ? '' : $cur_ik ); ?>" autocomplete="off" />
						<?php else : ?>
							<?php \esc_html_e( 'Internal key', 'aio-page-builder' ); ?><br />
							<input type="text" class="large-text code" name="<?php echo \esc_attr( $name_prefix . '[internal_key]' ); ?>" value="" autocomplete="off" />
						<?php endif; ?>
					</p>
					<p style="margin: 0.25em 0;">
						<?php if ( ! $for_template ) : ?>
							<label for="aio_cmp_ver_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Version', 'aio-page-builder' ); ?></label><br />
							<input type="text" class="regular-text code" id="aio_cmp_ver_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[version]' ); ?>" value="<?php echo \esc_attr( $match_in_list ? '' : $cur_ver ); ?>" autocomplete="off" />
						<?php else : ?>
							<?php \esc_html_e( 'Version', 'aio-page-builder' ); ?><br />
							<input type="text" class="regular-text code" name="<?php echo \esc_attr( $name_prefix . '[version]' ); ?>" value="" autocomplete="off" />
						<?php endif; ?>
					</p>
				</details>
			</td>
			<td>
				<?php if ( ! $for_template ) : ?>
					<label class="screen-reader-text" for="aio_cmp_prov_<?php echo \esc_attr( $ix ); ?>"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></label>
					<select id="aio_cmp_prov_<?php echo \esc_attr( $ix ); ?>" name="<?php echo \esc_attr( $name_prefix . '[provider_id]' ); ?>">
				<?php else : ?>
					<span class="screen-reader-text"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></span>
					<select name="<?php echo \esc_attr( $name_prefix . '[provider_id]' ); ?>">
				<?php endif; ?>
					<option value=""><?php \esc_html_e( '— Select —', 'aio-page-builder' ); ?></option>
					<?php foreach ( $provider_ids as $pid ) : ?>
						<option value="<?php echo \esc_attr( $pid ); ?>" <?php echo ( ! $for_template && $cur_provider === $pid ) ? ' selected="selected"' : ''; ?>><?php echo \esc_html( $this->provider_id_label( $pid ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<button type="button" class="button-link aio-cmp-remove-row" aria-label="<?php \esc_attr_e( 'Remove this alternative', 'aio-page-builder' ); ?>"><?php \esc_html_e( 'Remove', 'aio-page-builder' ); ?></button>
			</td>
		</tr>
		<?php
	}

	private function provider_id_label( string $provider_id ): string {
		switch ( $provider_id ) {
			case 'openai':
				return __( 'OpenAI', 'aio-page-builder' );
			case 'anthropic':
				return __( 'Anthropic', 'aio-page-builder' );
			default:
				return $provider_id;
		}
	}

	private function render_comparison_row_script(): void {
		?>
		<script>
		(function () {
			var tbody = document.getElementById('aio-cmp-alt-tbody');
			var templateTbody = document.getElementById('aio-cmp-row-template');
			var addBtn = document.getElementById('aio-cmp-add-row');
			if (!tbody || !templateTbody || !addBtn) {
				return;
			}
			function minRows() {
				return 2;
			}
			function nextIndex() {
				var n = parseInt(tbody.getAttribute('data-next-index'), 10);
				return isNaN(n) ? tbody.querySelectorAll('tr.aio-cmp-alt-row').length : n;
			}
			function setNextIndex(i) {
				tbody.setAttribute('data-next-index', String(i));
			}
			function updateRemoveState() {
				var rows = tbody.querySelectorAll('tr.aio-cmp-alt-row');
				var dis = rows.length <= minRows();
				rows.forEach(function (row) {
					var btn = row.querySelector('.aio-cmp-remove-row');
					if (btn) {
						btn.disabled = dis;
						btn.setAttribute('aria-disabled', dis ? 'true' : 'false');
					}
				});
			}
			function renumberRows() {
				var rows = tbody.querySelectorAll('tr.aio-cmp-alt-row');
				rows.forEach(function (row, idx) {
					row.querySelectorAll('input, select').forEach(function (el) {
						var n = el.getAttribute('name');
						if (n && n.indexOf('aio_cmp_alt[') === 0) {
							el.setAttribute('name', n.replace(/aio_cmp_alt\[[^\]]+]/, 'aio_cmp_alt[' + idx + ']'));
						}
						var id = el.getAttribute('id');
						if (id && /^aio_cmp_/.test(id)) {
							el.setAttribute('id', id.replace(/_[^_]+$/, '_' + idx));
						}
					});
					row.querySelectorAll('label[for^="aio_cmp_"]').forEach(function (lab) {
						var f = lab.getAttribute('for');
						if (f) {
							lab.setAttribute('for', f.replace(/_[^_]+$/, '_' + idx));
						}
					});
				});
				setNextIndex(rows.length);
				updateRemoveState();
			}
			addBtn.addEventListener('click', function () {
				var tplRow = templateTbody.querySelector('tr.aio-cmp-alt-row');
				if (!tplRow) {
					return;
				}
				var idx = nextIndex();
				var clone = tplRow.cloneNode(true);
				clone.querySelectorAll('input, select').forEach(function (el) {
					if (el.tagName === 'SELECT') {
						el.selectedIndex = 0;
					} else {
						el.value = '';
					}
					var n = el.getAttribute('name');
					if (n) {
						el.setAttribute('name', n.replace('__INDEX__', String(idx)));
					}
					var id = el.getAttribute('id');
					if (id) {
						el.setAttribute('id', id.replace('__INDEX__', String(idx)));
					}
				});
				clone.querySelectorAll('label[for]').forEach(function (lab) {
					var f = lab.getAttribute('for');
					if (f) {
						lab.setAttribute('for', f.replace('__INDEX__', String(idx)));
					}
				});
				var det = clone.querySelector('details.aio-cmp-manual-pack');
				if (det) {
					det.open = false;
				}
				tbody.appendChild(clone);
				setNextIndex(idx + 1);
				updateRemoveState();
			});
			tbody.addEventListener('click', function (e) {
				var t = e.target;
				if (!t || !t.classList || !t.classList.contains('aio-cmp-remove-row')) {
					return;
				}
				var row = t.closest('tr.aio-cmp-alt-row');
				if (!row || tbody.querySelectorAll('tr.aio-cmp-alt-row').length <= minRows()) {
					return;
				}
				row.parentNode.removeChild(row);
				renumberRows();
			});
			updateRemoveState();
		})();
		</script>
		<?php
	}
}
