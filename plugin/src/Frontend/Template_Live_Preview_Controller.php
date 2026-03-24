<?php
/**
 * Front-end opaque-ticket URL for full-theme template live preview; aggressive no-cache + security headers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Frontend;

use AIOPageBuilder\Diagnostics\Preview_Audit_Log_Service;
use AIOPageBuilder\Domain\Preview\Template_Live_Preview_State_Builder_Factory;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

defined( 'ABSPATH' ) || exit;

/**
 * Handles ?aio_pb_tpl_live=1&ticket=… on the site front; outputs theme wp_head/wp_footer around rendered template HTML.
 */
final class Template_Live_Preview_Controller {

	/** @var Service_Container|null */
	private ?Service_Container $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Hooks template_redirect.
	 *
	 * @return void
	 */
	public function register(): void {
		\add_action( 'template_redirect', array( $this, 'maybe_serve' ), 1 );
	}

	/**
	 * @return void
	 */
	public function maybe_serve(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public GET; ticket validated server-side.
		if ( ! isset( $_GET[ Template_Live_Preview_Ticket_Service::QUERY_FLAG ] ) || (string) \wp_unslash( $_GET[ Template_Live_Preview_Ticket_Service::QUERY_FLAG ] ) !== '1' ) {
			return;
		}

		$ticket_raw = isset( $_GET[ Template_Live_Preview_Ticket_Service::QUERY_TICKET ] ) ? (string) \wp_unslash( $_GET[ Template_Live_Preview_Ticket_Service::QUERY_TICKET ] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $ticket_raw === '' ) {
			$this->finish_with_ticket_failure( 'missing_ticket', 403 );
		}

		if ( ! \is_user_logged_in() ) {
			$this->finish_with_ticket_failure( 'not_logged_in', 403, Template_Live_Preview_Ticket_Service::hash_ticket_for_log( $ticket_raw ) );
		}

		$result = Template_Live_Preview_Ticket_Service::validate_and_consume( $ticket_raw );
		if ( ! $result['ok'] ) {
			$this->finish_with_ticket_failure( (string) $result['code'], $this->http_status_for_ticket_code( (string) $result['code'] ), (string) $result['ticket_hash'] );
		}

		$record = $result['record'];
		if ( ! \is_array( $record ) ) {
			$this->finish_with_ticket_failure( 'invalid_record', 403, (string) $result['ticket_hash'] );
		}

		$typ = (string) ( $record['typ'] ?? '' );
		$key = (string) ( $record['key'] ?? '' );
		if ( $key === '' ) {
			$this->finish_with_ticket_failure( 'invalid_record', 400, (string) $result['ticket_hash'] );
		}

		$cap_ok = false;
		if ( $typ === Template_Live_Preview_Ticket_Service::TYPE_PAGE ) {
			$cap_ok = Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_PAGE_TEMPLATES );
		} elseif ( $typ === Template_Live_Preview_Ticket_Service::TYPE_SECTION ) {
			$cap_ok = Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_SECTION_TEMPLATES );
		} else {
			$this->finish_with_ticket_failure( 'invalid_type', 400, (string) $result['ticket_hash'] );
		}

		if ( ! $cap_ok ) {
			$this->finish_with_ticket_failure( 'capability_denied', 403, (string) $result['ticket_hash'], $typ, $key );
		}

		$ctx_builder = new Preview_Context_Builder();
		$ctx         = $ctx_builder->build( $record );
		if ( $ctx['shell'] === 'shell_failed' ) {
			$this->log_audit(
				array(
					'outcome'       => 'shell_failed',
					'ticket_hash'   => (string) $result['ticket_hash'],
					'user_id'       => (int) \get_current_user_id(),
					'blog_id'       => (int) \get_current_blog_id(),
					'template_type' => $typ,
					'template_key'  => $key,
				)
			);
			$this->send_preview_headers_and_output_error( 200, 'shell_unavailable', $typ, $key );
			$ctx_builder->teardown();
			exit;
		}

		$request = array(
			'live_preview'   => true,
			'reduced_motion' => ! empty( $record['rm'] ),
			'category_class' => isset( $record['cc'] ) && \is_string( $record['cc'] ) ? $record['cc'] : '',
			'family'         => isset( $record['fam'] ) && \is_string( $record['fam'] ) ? $record['fam'] : '',
			'purpose_family' => isset( $record['pf'] ) && \is_string( $record['pf'] ) ? $record['pf'] : '',
		);

		$switched = false;
		$blog_id  = (int) ( $record['blog_id'] ?? 0 );
		if ( $blog_id > 0 && \function_exists( 'is_multisite' ) && \is_multisite() && \function_exists( 'switch_to_blog' ) ) {
			\switch_to_blog( $blog_id );
			$switched = true;
		}

		try {
			$factory = new Template_Live_Preview_State_Builder_Factory( $this->container );
			if ( $typ === Template_Live_Preview_Ticket_Service::TYPE_PAGE ) {
				$state = $factory->create_page_builder()->build_state( $key, $request );
			} else {
				$state = $factory->create_section_builder()->build_state( $key, $request );
			}

			if ( ! empty( $state['not_found'] ) ) {
				$this->log_audit(
					array(
						'outcome'       => 'template_not_found',
						'ticket_hash'   => (string) $result['ticket_hash'],
						'user_id'       => (int) \get_current_user_id(),
						'blog_id'       => (int) \get_current_blog_id(),
						'template_type' => $typ,
						'template_key'  => $key,
					)
				);
				$this->send_preview_headers_and_output_error( 404, 'template_not_found', $typ, $key );
				return;
			}

			$html = (string) ( $state['rendered_preview_html'] ?? '' );

			\add_filter( 'show_admin_bar', '__return_false', 100 );
			\add_filter( 'aio_page_builder_should_enqueue_base_styles', '__return_true', 1 );

			\status_header( 200 );
			Template_Live_Preview_Response_Service::send_preview_response_headers();
			\header( 'Content-Type: text/html; charset=' . \get_option( 'blog_charset' ), true );

			$this->log_audit(
				array(
					'outcome'       => 'success',
					'ticket_hash'   => (string) $result['ticket_hash'],
					'user_id'       => (int) \get_current_user_id(),
					'blog_id'       => (int) \get_current_blog_id(),
					'template_type' => $typ,
					'template_key'  => $key,
					'shell'         => (string) $ctx['shell'],
				)
			);

			$body_classes = isset( $ctx['body_classes'] ) && \is_array( $ctx['body_classes'] ) ? $ctx['body_classes'] : array( 'aio-template-live-preview' );
			$wp_body_open = ( $ctx['shell'] ?? '' ) === Template_Live_Preview_Ticket_Service::SHELL_COMPAT;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full HTML document; inner preview from trusted renderer + wp_kses_post below.
			echo $this->wrap_document( $html, $body_classes, $wp_body_open );
		} finally {
			if ( $switched && \function_exists( 'restore_current_blog' ) ) {
				\restore_current_blog();
			}
			$ctx_builder->teardown();
		}
		exit;
	}

	/**
	 * @param string $code Ticket validation code.
	 * @return int HTTP status.
	 */
	private function http_status_for_ticket_code( string $code ): int {
		switch ( $code ) {
			case 'expired':
			case 'unknown_or_expired':
			case 'exhausted':
				return 410;
			case 'rate_limited_document':
				return 429;
			case 'wrong_user':
			case 'wrong_session':
			case 'wrong_blog':
			case 'invalid_ticket':
			case 'not_logged_in':
				return 403;
			default:
				return 403;
		}
	}

	/**
	 * @param string $reason Internal reason code.
	 * @param int    $http_status HTTP status.
	 * @param string $ticket_hash Hashed ticket id.
	 * @param string $template_type Optional.
	 * @param string $template_key Optional.
	 * @return void
	 */
	private function finish_with_ticket_failure( string $reason, int $http_status, string $ticket_hash = '', string $template_type = '', string $template_key = '' ): void {
		$this->log_audit(
			array(
				'outcome'       => 'failure',
				'failure_code'  => $reason,
				'ticket_hash'   => $ticket_hash,
				'user_id'       => (int) \get_current_user_id(),
				'blog_id'       => (int) \get_current_blog_id(),
				'template_type' => $template_type,
				'template_key'  => $template_key,
			)
		);
		$this->send_preview_headers_and_output_error( $http_status, $reason, $template_type, $template_key );
		exit;
	}

	/**
	 * @param array<string, mixed> $payload Safe, redacted fields.
	 * @return void
	 */
	private function log_audit( array $payload ): void {
		$audit = $this->get_audit_service();
		if ( $audit === null ) {
			return;
		}
		$audit->log_preview_event( $payload );
	}

	/**
	 * @return Preview_Audit_Log_Service|null
	 */
	private function get_audit_service(): ?Preview_Audit_Log_Service {
		if ( $this->container === null || ! $this->container->has( 'preview_audit_log' ) ) {
			return null;
		}
		$svc = $this->container->get( 'preview_audit_log' );
		return $svc instanceof Preview_Audit_Log_Service ? $svc : null;
	}

	/**
	 * @param int    $http_status HTTP status.
	 * @param string $reason_code Machine-readable reason for the iframe.
	 * @param string $template_type Optional.
	 * @param string $template_key Optional.
	 * @return void
	 */
	private function send_preview_headers_and_output_error( int $http_status, string $reason_code, string $template_type = '', string $template_key = '' ): void {
		Template_Live_Preview_Response_Service::send_preview_response_headers();
		\status_header( $http_status );
		\header( 'Content-Type: text/html; charset=' . \get_option( 'blog_charset' ), true );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Branded error shell; escaped fields below.
		echo $this->build_preview_error_document( $http_status, $reason_code );
	}

	/**
	 * @param int    $http_status HTTP status.
	 * @param string $reason_code Machine-readable reason.
	 * @return string
	 */
	private function build_preview_error_document( int $http_status, string $reason_code ): string {
		$title = \__( 'Template preview', 'aio-page-builder' );
		$msg   = \__( 'Preview could not be loaded.', 'aio-page-builder' );
		if ( $http_status === 410 || $reason_code === 'expired' || $reason_code === 'unknown_or_expired' || $reason_code === 'exhausted' ) {
			$msg = \__( 'This preview link has expired. Regenerate preview from the template screen.', 'aio-page-builder' );
		} elseif ( $http_status === 429 || $reason_code === 'rate_limited_document' ) {
			$msg = \__( 'Too many preview requests. Wait a moment and try again.', 'aio-page-builder' );
		} elseif ( $http_status === 403 || $reason_code === 'capability_denied' || $reason_code === 'wrong_session' || $reason_code === 'wrong_user' || $reason_code === 'wrong_blog' ) {
			$msg = \__( 'You do not have access to this preview.', 'aio-page-builder' );
		} elseif ( $reason_code === 'shell_unavailable' ) {
			$msg = \__( 'This theme needs a fuller page shell for preview. Try again or contact support.', 'aio-page-builder' );
		} elseif ( $http_status === 404 || $reason_code === 'template_not_found' ) {
			$msg = \__( 'This template could not be found for preview.', 'aio-page-builder' );
		}

		$code_esc  = \esc_attr( $reason_code );
		$msg_esc   = \esc_html( $msg );
		$title_esc = \esc_html( $title );

		return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $title_esc . '</title><style>body{font-family:system-ui,sans-serif;margin:2rem;background:#f6f7f7;color:#1d2327}.aio-preview-error-card{max-width:28rem;padding:1.25rem;border:1px solid #c3c4c7;background:#fff;border-radius:4px;box-shadow:0 1px 2px rgba(0,0,0,.04)}.aio-preview-error-code{font-size:0.85rem;color:#646970;margin-top:0.75rem}</style></head><body><div class="aio-preview-error-card" data-aio-preview-reason="' . $code_esc . '"><h1 style="font-size:1.1rem;margin:0 0 0.5rem">' . $title_esc . '</h1><p style="margin:0">' . $msg_esc . '</p><p class="aio-preview-error-code">' . \esc_html__( 'Code:', 'aio-page-builder' ) . ' <code>' . $code_esc . '</code></p></div></body></html>';
	}

	/**
	 * @return string Origin (scheme + host + port) for postMessage targetOrigin.
	 */
	private static function origin_for_post_message(): string {
		$home = \home_url( '/' );
		$p    = \wp_parse_url( $home );
		if ( ! \is_array( $p ) ) {
			return '*';
		}
		$origin = ( isset( $p['scheme'] ) ? $p['scheme'] . '://' : '' ) . ( $p['host'] ?? '' );
		if ( $origin !== '' && isset( $p['port'] ) ) {
			$origin .= ':' . (string) $p['port'];
		}
		return $origin !== '' ? $origin : '*';
	}

	/**
	 * @param string       $inner_html Rendered block HTML.
	 * @param list<string> $body_classes Body classes.
	 * @param bool         $call_wp_body_open Whether to call wp_body_open() after body tag.
	 * @return string
	 */
	private function wrap_document( string $inner_html, array $body_classes, bool $call_wp_body_open ): string {
		\ob_start();
		?><!DOCTYPE html>
<html <?php \language_attributes(); ?>>
<head>
	<meta charset="<?php \bloginfo( 'charset' ); ?>">
	<meta name="robots" content="noindex, nofollow, noarchive">
		<?php
		\wp_head();
		?>
</head>
<body <?php \body_class( $body_classes ); ?>>
		<?php
		if ( $call_wp_body_open && \function_exists( 'wp_body_open' ) ) {
			\wp_body_open();
		}
		?>
<div id="aio-template-live-preview-root" class="aio-template-live-preview-root">
		<?php echo \wp_kses_post( $inner_html ); ?>
</div>
		<?php
		// * Same-origin iframe height handshake for wp-admin preview panels.
		?>
<script>
(function(){
	function aioTplLiveSendHeight(){
		var root=document.getElementById('aio-template-live-preview-root');
		if(!root||!window.parent){return;}
		var h=Math.max(document.body.scrollHeight, root.scrollHeight||0);
		window.parent.postMessage({source:'aio_tpl_live_preview',type:'height',height:h},'<?php echo \esc_js( self::origin_for_post_message() ); ?>');
	}
	if(document.readyState==='complete'){aioTplLiveSendHeight();}
	else{window.addEventListener('load', aioTplLiveSendHeight);}
})();
</script>
		<?php
		\wp_footer();
		?>
</body>
</html>
		<?php
		return (string) \ob_get_clean();
	}
}
