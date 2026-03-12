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

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders provider management UI. No raw keys; credential status only. Mutating actions are nonce-protected placeholders.
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
		$handled = $this->maybe_handle_test_connection() || $this->maybe_handle_update_credential();
		if ( $handled ) {
			return;
		}
		$this->render_connection_test_notice();
		$state = $this->get_state();
		$this->render_disclosure( $state['disclosure_blocks'] );
		$this->render_provider_list( $state['provider_rows'], $state['ai_runs_url'] );
	}

	private function render_connection_test_notice(): void {
		$message = isset( $_GET['aio_provider_message'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_provider_message'] ) ) : '';
		$status = isset( $_GET['aio_provider_status'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['aio_provider_status'] ) ) : '';
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
		$action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$provider_id = isset( $_GET['provider_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['provider_id'] ) ) : '';
		if ( $action !== 'test_connection' || $provider_id === '' ) {
			return false;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		if ( ! \wp_verify_nonce( $nonce, 'aio_test_connection_' . $provider_id ) ) {
			return false;
		}
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
			$result = $test_service->run_test( $driver );
			$message = $result->is_success()
				? __( 'Connection test succeeded.', 'aio-page-builder' )
				: $result->get_user_message();
			$this->redirect_back( $result->is_success() ? 'success' : 'error', $message );
		} catch ( \Throwable $e ) {
			$this->redirect_back( 'error', __( 'Connection test failed.', 'aio-page-builder' ) );
		}
		return true;
	}

	private function get_driver_for_provider_id( string $provider_id ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $this->container->has( 'openai_provider_driver' ) ) {
			return $this->container->get( 'openai_provider_driver' );
		}
		return null;
	}

	/**
	 * Handles action=update_credential: nonce and capability already checked in render(); redirects to Onboarding.
	 *
	 * @return bool True if request was handled (redirect sent).
	 */
	private function maybe_handle_update_credential(): bool {
		$action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$provider_id = isset( $_GET['provider_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['provider_id'] ) ) : '';
		if ( $action !== 'update_credential' || $provider_id === '' ) {
			return false;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		if ( ! \wp_verify_nonce( $nonce, 'aio_update_credential_' . $provider_id ) ) {
			return false;
		}
		$url = \add_query_arg( array( 'page' => 'aio-page-builder-onboarding' ), \admin_url( 'admin.php' ) );
		\wp_safe_redirect( $url );
		exit;
	}

	private function redirect_back( string $status, string $message ): void {
		$url = \add_query_arg(
			array(
				'page' => self::SLUG,
				'aio_provider_message' => $message,
				'aio_provider_status'  => $status,
			),
			\admin_url( 'admin.php' )
		);
		\wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @return array{provider_rows: list<array>, disclosure_blocks: list<array>, ai_runs_url: string}
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
	 * @param list<array{heading: string, content: string}> $blocks
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
	 * @param list<array> $provider_rows
	 * @param string      $ai_runs_url
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
							$credential = $row['credential_status'] ?? array( 'label' => '—' );
							$model_state = $row['model_default_state'] ?? array( 'label' => '—' );
							$test = $row['connection_test_summary'];
							$last_use = $row['last_successful_use'] ?? null;
							$test_btn_url = $this->test_connection_url( $row['provider_id'] );
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
									<a href="<?php echo \esc_url( $test_btn_url ); ?>" class="button button-small"><?php \esc_html_e( 'Test connection', 'aio-page-builder' ); ?></a>
									<a href="<?php echo \esc_url( $update_cred_url ); ?>" class="button button-small"><?php \esc_html_e( 'Update credential', 'aio-page-builder' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
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
	private function test_connection_url( string $provider_id ): string {
		return \add_query_arg(
			array(
				'page'   => self::SLUG,
				'action' => 'test_connection',
				'provider_id' => $provider_id,
				'_wpnonce' => \wp_create_nonce( 'aio_test_connection_' . $provider_id ),
			),
			\admin_url( 'admin.php' )
		);
	}

	/**
	 * Placeholder URL for update credential; handler must verify nonce and capability (spec §49.9).
	 *
	 * @param string $provider_id
	 * @return string
	 */
	private function update_credential_url( string $provider_id ): string {
		return \add_query_arg(
			array(
				'page'   => self::SLUG,
				'action' => 'update_credential',
				'provider_id' => $provider_id,
				'_wpnonce' => \wp_create_nonce( 'aio_update_credential_' . $provider_id ),
			),
			\admin_url( 'admin.php' )
		);
	}
}
