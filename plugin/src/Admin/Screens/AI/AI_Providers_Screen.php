<?php
/**
 * AI Providers admin screen (spec §49.9): provider list, credential status, model defaults,
 * connection-test result, last successful use, disclosure. Capability: aio_manage_ai_providers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings;
use AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service;
use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Admin\Admin_Screen_Hub;
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_State_Store;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Renders provider management UI. No raw keys; credential status only. Mutating actions are capability- and nonce-protected.
 */
final class AI_Providers_Screen {

	public const SLUG = 'aio-page-builder-ai-providers';

	/** Hub tab key for {@see AI_Runs_Screen::HUB_PAGE_SLUG} (forms must POST here; legacy {@see self::SLUG} is removed from $submenu). */
	private const HUB_TAB = 'providers';

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_title(): string {
		return __( 'AI Providers', 'aio-page-builder' );
	}

	public function get_capability(): string {
		return Capabilities::MANAGE_AI_PROVIDERS;
	}

	/**
	 * Resolves POST (connection test, credential, spend cap) into a redirect URL. Call from admin_init before output
	 * (see Plugin::maybe_handle_ai_providers_post_redirect); running after headers causes wp_safe_redirect to fail and exit mid-page.
	 *
	 * @return string|null Full redirect URL or null when this request is not a handled POST.
	 */
	public function get_post_redirect_url(): ?string {
		$url = $this->maybe_resolve_test_connection_redirect();
		if ( $url !== null ) {
			return $url;
		}
		$url = $this->maybe_resolve_update_credential_redirect();
		if ( $url !== null ) {
			return $url;
		}
		return $this->maybe_resolve_save_spend_cap_redirect();
	}

	/**
	 * Renders the AI Providers screen. Capability is enforced by menu registration; screen checks again.
	 *
	 * @return void
	 */
	public function render( bool $embed_in_hub = false ): void {
		if ( ! Capabilities::current_user_can_for_route( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage AI providers.', 'aio-page-builder' ), 403 );
		}
		$this->render_connection_test_notice();
		$state = $this->get_state();
		$this->render_disclosure( $state['disclosure_blocks'] );
		$this->render_provider_list( $state['provider_rows'], $state['ai_runs_url'], $embed_in_hub );
		$this->render_spend_cap_section( $state['provider_rows'], $embed_in_hub );
	}

	private function render_connection_test_notice(): void {
		$message = isset( $_GET['aio_provider_message'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_provider_message'] ) ) : '';
		$status  = isset( $_GET['aio_provider_status'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_provider_status'] ) ) : '';
		if ( $message === '' || $status === '' ) {
			return;
		}
		$class = $status === 'success' ? 'notice-success' : 'notice-error';
		echo '<div class="notice ' . \esc_attr( $class ) . ' is-dismissible"><p>' . \esc_html( $message ) . '</p></div>';
	}

	/**
	 * Connection test POST: returns redirect URL or null.
	 *
	 * @return string|null
	 */
	private function maybe_resolve_test_connection_redirect(): ?string {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_test_ai_provider_connection' || $provider_id === '' ) {
			return null;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			return null;
		}
		$nonce_action = 'aio_pb_test_ai_provider_connection_' . $provider_id;
		\check_admin_referer( $nonce_action );
		Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_AI_PROVIDER_CONNECTION_TEST_POST, 'provider=' . $provider_id );
		if ( ! $this->container ) {
			return null;
		}
		$driver = $this->get_driver_for_provider_id( $provider_id );
		if ( $driver === null ) {
			return $this->build_redirect_url( 'error', __( 'Provider not found.', 'aio-page-builder' ) );
		}
		try {
			$test_service = $this->container->get( 'provider_connection_test_service' );
			$result       = $test_service->run_test( $driver );
			$this->persist_provider_state_after_test( $provider_id, $result->is_success() );
			$message = $result->is_success()
				? __( 'Connection test succeeded.', 'aio-page-builder' )
				: $result->get_user_message();
			return $this->build_redirect_url( $result->is_success() ? 'success' : 'error', $message );
		} catch ( \Throwable $e ) {
			$this->persist_provider_state_after_test( $provider_id, false );
			return $this->build_redirect_url( 'error', __( 'Connection test failed.', 'aio-page-builder' ) );
		}
	}

	private function get_driver_for_provider_id( string $provider_id ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $this->container->has( 'openai_provider_driver' ) ) {
			return $this->container->get( 'openai_provider_driver' );
		}
		if ( $provider_id === 'anthropic' && $this->container->has( 'anthropic_provider_driver' ) ) {
			return $this->container->get( 'anthropic_provider_driver' );
		}
		return null;
	}

	/**
	 * Update credential POST: returns redirect URL or null.
	 *
	 * @return string|null
	 */
	private function maybe_resolve_update_credential_redirect(): ?string {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_update_ai_provider_credential' || $provider_id === '' ) {
			return null;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			return null;
		}
		$nonce_action = 'aio_pb_update_ai_provider_credential_' . $provider_id;
		\check_admin_referer( $nonce_action );
		Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_AI_PROVIDER_CREDENTIAL_UPDATE_POST, 'provider=' . $provider_id );
		if ( ! $this->container ) {
			return null;
		}
		$credential = isset( $_POST['provider_credential'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['provider_credential'] ) ) : '';
		$credential = trim( $credential );
		if ( $credential === '' ) {
			return $this->build_redirect_url( 'error', __( 'Credential is required.', 'aio-page-builder' ) );
		}
		$secret_store = $this->container->get( 'provider_secret_store' );
		$secret_store->set_credential( $provider_id, $credential );
		$this->persist_provider_state_after_credential_update( $provider_id );
		return $this->build_redirect_url( 'success', __( 'Credential updated.', 'aio-page-builder' ) );
	}

	/**
	 * @return string Full admin URL with flash query args for the providers tab.
	 */
	private function build_redirect_url( string $status, string $message ): string {
		return \add_query_arg(
			array(
				'aio_provider_message' => $message,
				'aio_provider_status'  => $status,
			),
			$this->get_hub_providers_base_url()
		);
	}

	/**
	 * Admin URL for the AI workspace hub on the Providers tab (registered submenu; safe for POST).
	 *
	 * @return string
	 */
	private function get_hub_providers_base_url(): string {
		return \add_query_arg(
			array(
				'page'                      => AI_Runs_Screen::HUB_PAGE_SLUG,
				Admin_Screen_Hub::QUERY_TAB => self::HUB_TAB,
			),
			\admin_url( 'admin.php' )
		);
	}

	/**
	 * @return array{provider_rows: array<int, array>, disclosure_blocks: array<int, array>, ai_runs_url: string}
	 */
	private function get_state(): array {
		$builder = $this->get_state_builder();
		if ( $builder !== null ) {
			return $builder->build();
		}
		return array(
			'provider_rows'     => array(),
			'disclosure_blocks' => array(
				array(
					'heading' => __( 'External transfer', 'aio-page-builder' ),
					'content' => __( 'When you use AI providers, your profile and site context are sent to the provider’s API. Responses are returned over the network. Do not include sensitive data in prompts. Keys are stored locally and never displayed in full after save.', 'aio-page-builder' ),
				),
				array(
					'heading' => __( 'Cost', 'aio-page-builder' ),
					'content' => __( 'AI requests consume tokens and may incur cost according to the provider’s pricing. Run connection tests and planning requests only when needed. This plugin does not manage billing or quotas.', 'aio-page-builder' ),
				),
			),
			'ai_runs_url'       => \add_query_arg(
				array(
					'page'                      => AI_Runs_Screen::HUB_PAGE_SLUG,
					Admin_Screen_Hub::QUERY_TAB => 'ai_runs',
				),
				\admin_url( 'admin.php' )
			),
		);
	}

	/**
	 * @return AI_Providers_UI_State_Builder|null Null when container is unavailable.
	 */
	private function get_state_builder(): ?AI_Providers_UI_State_Builder {
		if ( ! $this->container ) {
			return null;
		}
		if ( $this->container->has( 'ai_providers_ui_state_builder' ) ) {
			return $this->container->get( 'ai_providers_ui_state_builder' );
		}
		return new AI_Providers_UI_State_Builder(
			$this->container->get( 'provider_connection_test_service' ),
			$this->container->get( 'provider_secret_store' ),
			$this->container->get( 'provider_capability_resolver' ),
			$this->container->get( 'settings' ),
			$this->container
		);
	}

	/**
	 * @param array<int, array{heading: string, content: string}> $blocks
	 * @return void
	 */
	private function render_disclosure( array $blocks ): void {
		if ( count( $blocks ) === 0 ) {
			return;
		}
		?>
		<div class="aio-ai-providers-disclosure notice notice-info inline" style="margin: 1em 0;">
			<?php foreach ( $blocks as $block ) : ?>
				<p><strong><?php echo \esc_html( $block['heading'] ); ?>:</strong> <?php echo \esc_html( $block['content'] ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param array<int, array> $provider_rows
	 * @param string            $ai_runs_url
	 * @param bool              $embed_in_hub  When true, omit outer wrap and H1 (hub provides them).
	 * @return void
	 */
	private function render_provider_list( array $provider_rows, string $ai_runs_url, bool $embed_in_hub = false ): void {
		?>
		<?php if ( ! $embed_in_hub ) : ?>
		<div class="wrap aio-page-builder-screen aio-ai-providers" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
		<?php endif; ?>
			<p class="aio-ai-providers-description"><?php \esc_html_e( 'Configure and inspect AI provider credentials, model defaults, and connection status. Keys are never shown in full after save.', 'aio-page-builder' ); ?></p>
			<?php if ( ! $embed_in_hub ) : ?>
				<p><a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'View AI Runs', 'aio-page-builder' ); ?></a></p>
			<?php endif; ?>
			<?php if ( count( $provider_rows ) === 0 ) : ?>
				<p class="aio-admin-notice"><?php \esc_html_e( 'No providers available.', 'aio-page-builder' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php \esc_html_e( 'Provider', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Credential status', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Default model (planning)', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Last connection test', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Last successful use', 'aio-page-builder' ); ?></th>
							<th scope="col"><?php \esc_html_e( 'Actions', 'aio-page-builder' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $provider_rows as $row ) : ?>
							<?php
							$credential  = $row['credential_status'] ?? array(
								'label' => '—',
								'state' => '',
							);
							$cred_state  = isset( $credential['state'] ) ? (string) $credential['state'] : '';
							$model_state = $row['model_default_state'] ?? array( 'label' => '—' );
							$test        = $row['connection_test_summary'];
							$last_use    = $row['last_successful_use'] ?? null;
							?>
							<tr
								data-aio-provider-id="<?php echo \esc_attr( (string) ( $row['provider_id'] ?? '' ) ); ?>"
								data-aio-provider-credential-state="<?php echo \esc_attr( $cred_state ); ?>"
							>
								<td><strong><?php echo \esc_html( $row['label'] ?? $row['provider_id'] ); ?></strong></td>
								<td><?php echo \esc_html( $credential['label'] ); ?></td>
								<td><?php echo \esc_html( $model_state['label'] ); ?></td>
								<td>
									<?php if ( $test === null ) : ?>
										<span aria-label="<?php \esc_attr_e( 'No test yet', 'aio-page-builder' ); ?>">—</span>
									<?php else : ?>
										<span class="aio-connection-test-result <?php echo $test['success'] ? 'success' : 'failure'; ?>">
											<?php echo $test['success'] ? '✓' : '✗'; ?>
											<?php echo \esc_html( $test['user_message'] ); ?>
											(<?php echo \esc_html( $this->format_timestamp( $test['tested_at'] ) ); ?>)
										</span>
									<?php endif; ?>
								</td>
								<td><?php echo $last_use !== null ? \esc_html( $this->format_timestamp( $last_use ) ) : '—'; ?></td>
								<td>
									<form method="post" action="<?php echo \esc_url( $this->get_hub_providers_base_url() ); ?>" style="display:inline-block;margin-right:6px;">
										<input type="hidden" name="action" value="aio_pb_test_ai_provider_connection" />
										<input type="hidden" name="provider_id" value="<?php echo \esc_attr( $row['provider_id'] ); ?>" />
										<?php \wp_nonce_field( 'aio_pb_test_ai_provider_connection_' . $row['provider_id'] ); ?>
										<button type="submit" class="button button-small"><?php \esc_html_e( 'Test connection', 'aio-page-builder' ); ?></button>
									</form>
									<form method="post" action="<?php echo \esc_url( $this->get_hub_providers_base_url() ); ?>" style="display:inline-block;">
										<input type="hidden" name="action" value="aio_pb_update_ai_provider_credential" />
										<input type="hidden" name="provider_id" value="<?php echo \esc_attr( $row['provider_id'] ); ?>" />
										<?php \wp_nonce_field( 'aio_pb_update_ai_provider_credential_' . $row['provider_id'] ); ?>
										<input type="password" name="provider_credential" value="" autocomplete="off" placeholder="<?php \esc_attr_e( 'New key', 'aio-page-builder' ); ?>" style="max-width:160px;" />
										<button type="submit" class="button button-small"><?php \esc_html_e( 'Update credential', 'aio-page-builder' ); ?></button>
									</form>
								</td>
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

	/**
	 * Save spend cap POST: returns redirect URL or null.
	 *
	 * @return string|null
	 */
	private function maybe_resolve_save_spend_cap_redirect(): ?string {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_save_spend_cap' || $provider_id === '' ) {
			return null;
		}
		if ( ! Capabilities::current_user_can_for_route( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			return $this->build_redirect_url( 'error', __( 'You do not have permission to change spend cap settings.', 'aio-page-builder' ) );
		}
		\check_admin_referer( 'aio_pb_save_spend_cap_' . $provider_id );
		Named_Debug_Log::event( Named_Debug_Log_Event::ADMIN_AI_PROVIDER_SPEND_CAP_SAVE_POST, 'provider=' . $provider_id );
		if ( ! $this->container ) {
			return null;
		}
		$cap_raw  = isset( $_POST['monthly_cap_usd'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['monthly_cap_usd'] ) ) : '0';
		$cap_usd  = max( 0.0, (float) $cap_raw );
		$override = ! empty( $_POST['override_cap_exceeded'] );
		/** @var Provider_Spend_Cap_Settings $cap_settings */
		$cap_settings = $this->container->get( 'provider_spend_cap_settings' );
		$cap_settings->save_settings( $provider_id, $cap_usd, $override );
		return $this->build_redirect_url( 'success', __( 'Spend cap settings saved.', 'aio-page-builder' ) );
	}

	/**
	 * Renders per-provider spend summary notices and cap settings forms.
	 *
	 * @param array<int, array> $provider_rows
	 * @param bool              $embed_in_hub  When true, omit outer `wrap` on this section container.
	 * @return void
	 */
	private function render_spend_cap_section( array $provider_rows, bool $embed_in_hub = false ): void {
		if ( ! $this->container
			|| ! $this->container->has( 'provider_monthly_spend_service' )
			|| ! $this->container->has( 'provider_spend_cap_settings' ) ) {
			return;
		}
		/** @var Provider_Monthly_Spend_Service $spend_service */
		$spend_service = $this->container->get( 'provider_monthly_spend_service' );
		/** @var Provider_Spend_Cap_Settings $cap_settings */
		$cap_settings     = $this->container->get( 'provider_spend_cap_settings' );
		$spend_wrap_class = $embed_in_hub ? 'aio-ai-spend-cap-section' : 'wrap aio-ai-spend-cap-section';
		?>
		<div class="<?php echo \esc_attr( $spend_wrap_class ); ?>">
			<h2><?php \esc_html_e( 'Monthly Spend Caps', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Set a per-provider monthly spend cap to prevent unexpected costs. When exceeded, new AI runs are blocked unless the override is enabled. Cost tracking uses approximate rates; verify totals in your provider dashboard.', 'aio-page-builder' ); ?></p>
			<?php
			foreach ( $provider_rows as $row ) :
				$pid = $row['provider_id'] ?? '';
				if ( $pid === '' ) {
					continue;
				}
				$summary = $spend_service->get_spend_summary( $pid );
				?>
				<div class="aio-spend-cap-provider" style="margin-bottom:2em;padding:1em;border:1px solid #ccd0d4;background:#fff;">
					<h3 style="margin-top:0;"><?php echo \esc_html( (string) ( $row['label'] ?? $pid ) ); ?></h3>
					<?php if ( $summary['month_total'] > 0.0 || $summary['has_cap'] ) : ?>
						<?php if ( $summary['exceeded'] ) : ?>
							<div class="notice notice-error inline" style="margin:0 0 1em;"><p>
								<?php
								printf(
									/* translators: 1: Month-to-date spend (USD, formatted). 2: Monthly cap (USD, formatted). */
									\esc_html__( 'Monthly spend cap exceeded: $%1$s spent of $%2$s cap.', 'aio-page-builder' ),
									\esc_html( number_format( $summary['month_total'], 4 ) ),
									\esc_html( number_format( $summary['cap'], 2 ) )
								);
								if ( $summary['override_enabled'] ) {
									echo ' ';
									\esc_html_e( 'Override enabled — runs are still allowed.', 'aio-page-builder' );
								}
								?>
							</p></div>
						<?php elseif ( $summary['approaching'] ) : ?>
							<div class="notice notice-warning inline" style="margin:0 0 1em;"><p>
								<?php
								printf(
									/* translators: 1: Month-to-date spend (USD). 2: Monthly cap (USD). 3: Percent of cap used. */
									\esc_html__( 'Approaching monthly spend cap: $%1$s spent of $%2$s cap (%3$s%%).', 'aio-page-builder' ),
									\esc_html( number_format( $summary['month_total'], 4 ) ),
									\esc_html( number_format( $summary['cap'], 2 ) ),
									\esc_html( number_format( $summary['percent_used'] * 100, 1 ) )
								);
								?>
							</p></div>
						<?php else : ?>
							<p><strong><?php \esc_html_e( 'Month-to-date spend:', 'aio-page-builder' ); ?></strong>
							<?php echo \esc_html( '$' . number_format( $summary['month_total'], 4 ) ); ?>
							<?php if ( $summary['has_cap'] ) : ?>
								<?php echo \esc_html( sprintf( ' / $%s cap', number_format( $summary['cap'], 2 ) ) ); ?>
							<?php endif; ?>
							</p>
						<?php endif; ?>
					<?php else : ?>
						<p><em><?php \esc_html_e( 'No spend recorded this month.', 'aio-page-builder' ); ?></em></p>
					<?php endif; ?>

					<form method="post" action="<?php echo \esc_url( $this->get_hub_providers_base_url() ); ?>">
						<input type="hidden" name="action" value="aio_pb_save_spend_cap" />
						<input type="hidden" name="provider_id" value="<?php echo \esc_attr( $pid ); ?>" />
						<?php \wp_nonce_field( 'aio_pb_save_spend_cap_' . $pid ); ?>
						<table class="form-table" style="margin:0;">
							<tr>
								<th scope="row"><label for="monthly_cap_<?php echo \esc_attr( $pid ); ?>"><?php \esc_html_e( 'Monthly cap (USD)', 'aio-page-builder' ); ?></label></th>
								<td>
									<input
										type="number"
										id="monthly_cap_<?php echo \esc_attr( $pid ); ?>"
										name="monthly_cap_usd"
										value="<?php echo \esc_attr( (string) $cap_settings->get_cap( $pid ) ); ?>"
										min="0"
										max="<?php echo \esc_attr( (string) Provider_Spend_Cap_Settings::MAX_CAP_USD ); ?>"
										step="0.01"
										style="width:120px;"
									/>
									<p class="description"><?php \esc_html_e( 'Enter 0 to disable the cap.', 'aio-page-builder' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php \esc_html_e( 'Allow override when cap exceeded', 'aio-page-builder' ); ?></th>
								<td>
									<label>
										<input
											type="checkbox"
											name="override_cap_exceeded"
											value="1"
											<?php \checked( $cap_settings->is_override_enabled( $pid ) ); ?>
										/>
										<?php \esc_html_e( 'Continue allowing runs after cap is reached', 'aio-page-builder' ); ?>
									</label>
								</td>
							</tr>
						</table>
						<p><button type="submit" class="button button-primary"><?php \esc_html_e( 'Save cap settings', 'aio-page-builder' ); ?></button></p>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function format_timestamp( string $iso ): string {
		$ts = \strtotime( $iso );
		return $ts !== false ? \wp_date( 'Y-m-d H:i', $ts ) : $iso;
	}

	/**
	 * Persists per-provider UI state after a connection test: credential source, masked status, resolved default model,
	 * last_test_status, last_tested_at, and last_successful_use_at when the test succeeds.
	 *
	 * @param string $provider_id Provider slug.
	 * @param bool   $success     Whether the connection test succeeded.
	 * @return void
	 */
	private function persist_provider_state_after_test( string $provider_id, bool $success ): void {
		if ( ! $this->container ) {
			return;
		}
		$settings = $this->container->get( 'settings' );
		$store    = new AI_Provider_State_Store( $settings );
		$driver   = $this->get_driver_for_provider_id( $provider_id );
		$model    = '';
		if ( $driver !== null && $this->container->has( 'provider_capability_resolver' ) ) {
			$resolver = $this->container->get( 'provider_capability_resolver' );
			$model    = (string) ( $resolver->resolve_default_model_for_connection_test( $driver ) ?? '' );
		}
		$updates = array(
			'credential_ref_or_secure_value' => 'secret_store',
			'masked_status'                  => 'configured',
			'default_model'                  => $model,
			'last_test_status'               => $success ? 'success' : 'failure',
			'last_tested_at'                 => gmdate( 'c' ),
		);
		if ( $success ) {
			$updates['last_successful_use_at'] = gmdate( 'c' );
		}
		$store->merge( $provider_id, $updates );
	}

	private function persist_provider_state_after_credential_update( string $provider_id ): void {
		if ( ! $this->container ) {
			return;
		}
		$settings = $this->container->get( 'settings' );
		$store    = new AI_Provider_State_Store( $settings );
		$store->merge(
			$provider_id,
			array(
				'credential_ref_or_secure_value' => 'secret_store',
				'masked_status'                  => 'pending_validation',
				'default_model'                  => '',
			)
		);
	}
}
