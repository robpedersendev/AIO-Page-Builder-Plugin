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
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_State_Store;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders provider management UI. No raw keys; credential status only. Mutating actions are capability- and nonce-protected.
 */
final class AI_Providers_Screen {

	public const SLUG = 'aio-page-builder-ai-providers';

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
	 * Renders the AI Providers screen. Capability is enforced by menu registration; screen checks again.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! \current_user_can( $this->get_capability() ) ) {
			\wp_die( \esc_html__( 'You do not have permission to manage AI providers.', 'aio-page-builder' ), 403 );
		}
		$handled = $this->maybe_handle_test_connection()
			|| $this->maybe_handle_update_credential()
			|| $this->maybe_handle_save_spend_cap();
		if ( $handled ) {
			return;
		}
		$this->render_connection_test_notice();
		$state = $this->get_state();
		$this->render_disclosure( $state['disclosure_blocks'] );
		$this->render_provider_list( $state['provider_rows'], $state['ai_runs_url'] );
		$this->render_spend_cap_section( $state['provider_rows'] );
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
	 * Handles action=test_connection: nonce and capability check, runs test, redirects back with message.
	 *
	 * @return bool True if request was handled (redirect sent).
	 */
	private function maybe_handle_test_connection(): bool {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_test_ai_provider_connection' || $provider_id === '' ) {
			return false;
		}
		$nonce_action = 'aio_pb_test_ai_provider_connection_' . $provider_id;
		\check_admin_referer( $nonce_action );
		if ( ! $this->container ) {
			return false;
		}
		$driver = $this->get_driver_for_provider_id( $provider_id );
		if ( $driver === null ) {
			$this->redirect_back( 'error', __( 'Provider not found.', 'aio-page-builder' ) );
			return true;
		}
		try {
			$test_service = $this->container->get( 'provider_connection_test_service' );
			$result       = $test_service->run_test( $driver );
			$this->persist_provider_state_after_test( $provider_id, $result->is_success() );
			$message      = $result->is_success()
				? __( 'Connection test succeeded.', 'aio-page-builder' )
				: $result->get_user_message();
			$this->redirect_back( $result->is_success() ? 'success' : 'error', $message );
		} catch ( \Throwable $e ) {
			$this->persist_provider_state_after_test( $provider_id, false );
			$this->redirect_back( 'error', __( 'Connection test failed.', 'aio-page-builder' ) );
		}
		return true;
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
	 * Handles action=update_credential: nonce and capability already checked in render(); redirects to Onboarding.
	 *
	 * @return bool True if request was handled (redirect sent).
	 */
	private function maybe_handle_update_credential(): bool {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_update_ai_provider_credential' || $provider_id === '' ) {
			return false;
		}
		$nonce_action = 'aio_pb_update_ai_provider_credential_' . $provider_id;
		\check_admin_referer( $nonce_action );
		if ( ! $this->container ) {
			return false;
		}
		$credential = isset( $_POST['provider_credential'] ) ? (string) \wp_unslash( $_POST['provider_credential'] ) : '';
		$credential = trim( $credential );
		if ( $credential === '' ) {
			$this->redirect_back( 'error', __( 'Credential is required.', 'aio-page-builder' ) );
			return true;
		}
		$secret_store = $this->container->get( 'provider_secret_store' );
		$secret_store->set_credential( $provider_id, $credential );
		$this->persist_provider_state_after_credential_update( $provider_id );
		$this->redirect_back( 'success', __( 'Credential updated.', 'aio-page-builder' ) );
		return true;
	}

	private function redirect_back( string $status, string $message ): void {
		$url = \add_query_arg(
			array(
				'page'                 => self::SLUG,
				'aio_provider_message' => $message,
				'aio_provider_status'  => $status,
			),
			\admin_url( 'admin.php' )
		);
		\wp_safe_redirect( $url );
		exit;
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
			'ai_runs_url'       => \add_query_arg( array( 'page' => 'aio-page-builder-ai-runs' ), \admin_url( 'admin.php' ) ),
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
	 * @return void
	 */
	private function render_provider_list( array $provider_rows, string $ai_runs_url ): void {
		?>
		<div class="wrap aio-page-builder-screen aio-ai-providers" role="main" aria-label="<?php echo \esc_attr( $this->get_title() ); ?>">
			<h1><?php echo \esc_html( $this->get_title() ); ?></h1>
			<p class="aio-ai-providers-description"><?php \esc_html_e( 'Configure and inspect AI provider credentials, model defaults, and connection status. Keys are never shown in full after save.', 'aio-page-builder' ); ?></p>
			<p><a href="<?php echo \esc_url( $ai_runs_url ); ?>"><?php \esc_html_e( 'View AI Runs', 'aio-page-builder' ); ?></a></p>
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
							$credential      = $row['credential_status'] ?? array( 'label' => '—' );
							$model_state     = $row['model_default_state'] ?? array( 'label' => '—' );
							$test            = $row['connection_test_summary'];
							$last_use        = $row['last_successful_use'] ?? null;
							$test_btn_url    = $this->test_connection_url( $row['provider_id'] );
							$update_cred_url = $this->update_credential_url( $row['provider_id'] );
							?>
							<tr>
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
									<form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" style="display:inline-block;margin-right:6px;">
										<input type="hidden" name="action" value="aio_pb_test_ai_provider_connection" />
										<input type="hidden" name="provider_id" value="<?php echo \esc_attr( $row['provider_id'] ); ?>" />
										<?php \wp_nonce_field( 'aio_pb_test_ai_provider_connection_' . $row['provider_id'] ); ?>
										<button type="submit" class="button button-small"><?php \esc_html_e( 'Test connection', 'aio-page-builder' ); ?></button>
									</form>
									<form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG ) ); ?>" style="display:inline-block;">
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
		</div>
		<?php
	}

	/**
	 * Handles action=aio_pb_save_spend_cap: saves monthly spend cap settings per provider.
	 *
	 * @return bool True if handled and redirected.
	 */
	private function maybe_handle_save_spend_cap(): bool {
		$action      = isset( $_POST['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['action'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? \sanitize_key( (string) $_POST['provider_id'] ) : '';
		if ( $action !== 'aio_pb_save_spend_cap' || $provider_id === '' ) {
			return false;
		}
		if ( ! \current_user_can( Capabilities::MANAGE_AI_PROVIDERS ) ) {
			$this->redirect_back( 'error', __( 'You do not have permission to change spend cap settings.', 'aio-page-builder' ) );
			return true;
		}
		\check_admin_referer( 'aio_pb_save_spend_cap_' . $provider_id );
		if ( ! $this->container ) {
			return false;
		}
		$cap_raw   = isset( $_POST['monthly_cap_usd'] ) ? (string) \wp_unslash( $_POST['monthly_cap_usd'] ) : '0';
		$cap_usd   = max( 0.0, (float) $cap_raw );
		$override  = ! empty( $_POST['override_cap_exceeded'] );
		/** @var Provider_Spend_Cap_Settings $cap_settings */
		$cap_settings = $this->container->get( 'provider_spend_cap_settings' );
		$cap_settings->save_settings( $provider_id, $cap_usd, $override );
		$this->redirect_back( 'success', __( 'Spend cap settings saved.', 'aio-page-builder' ) );
		return true;
	}

	/**
	 * Renders per-provider spend summary notices and cap settings forms.
	 *
	 * @param array<int, array> $provider_rows
	 * @return void
	 */
	private function render_spend_cap_section( array $provider_rows ): void {
		if ( ! $this->container
			|| ! $this->container->has( 'provider_monthly_spend_service' )
			|| ! $this->container->has( 'provider_spend_cap_settings' ) ) {
			return;
		}
		/** @var Provider_Monthly_Spend_Service $spend_service */
		$spend_service = $this->container->get( 'provider_monthly_spend_service' );
		/** @var Provider_Spend_Cap_Settings $cap_settings */
		$cap_settings = $this->container->get( 'provider_spend_cap_settings' );
		?>
		<div class="wrap aio-ai-spend-cap-section">
			<h2><?php \esc_html_e( 'Monthly Spend Caps', 'aio-page-builder' ); ?></h2>
			<p><?php \esc_html_e( 'Set a per-provider monthly spend cap to prevent unexpected costs. When exceeded, new AI runs are blocked unless the override is enabled. Cost tracking uses approximate rates; verify totals in your provider dashboard.', 'aio-page-builder' ); ?></p>
			<?php foreach ( $provider_rows as $row ) :
				$pid     = $row['provider_id'] ?? '';
				if ( $pid === '' ) {
					continue;
				}
				$summary = $spend_service->get_spend_summary( $pid );
				$label   = \esc_html( $row['label'] ?? $pid );
				?>
				<div class="aio-spend-cap-provider" style="margin-bottom:2em;padding:1em;border:1px solid #ccd0d4;background:#fff;">
					<h3 style="margin-top:0;"><?php echo $label; ?></h3>
					<?php if ( $summary['month_total'] > 0.0 || $summary['has_cap'] ) : ?>
						<?php if ( $summary['exceeded'] ) : ?>
							<div class="notice notice-error inline" style="margin:0 0 1em;"><p>
								<?php
								printf(
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

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin.php?page=' . self::SLUG ) ); ?>">
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
	 * Placeholder URL for test connection; handler must verify nonce and capability (spec §49.9).
	 *
	 * @param string $provider_id
	 * @return string
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
