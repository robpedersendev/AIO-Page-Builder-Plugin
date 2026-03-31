<?php
/**
 * Batches client-side UX trace events into the same sink as {@see Admin_Ux_Trace}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Support\Logging\Admin_Ux_Trace;

/**
 * wp_ajax handler: accepts JSON array of partial records; merges server context; fails closed without nonce/cap.
 */
final class Admin_Ux_Trace_Ajax {

	public const ACTION       = 'aio_admin_ux_trace_batch';
	public const NONCE_ACTION = 'aio_admin_ux_trace_batch';

	/**
	 * @return void
	 */
	public static function register(): void {
		\add_action( 'wp_ajax_' . self::ACTION, array( self::class, 'handle_batch' ) );
	}

	/**
	 * @return void
	 */
	public static function handle_batch(): void {
		if ( ! Admin_Ux_Trace::enabled() ) {
			\wp_send_json_error( array( 'code' => 'trace_disabled' ), 400 );
		}
		\check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! self::current_user_may_emit_trace() ) {
			\wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}
		$raw = isset( $_POST['batch'] ) ? \wp_unslash( (string) $_POST['batch'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON body; decoded and per-field sanitized below.
		if ( $raw === '' ) {
			\wp_send_json_error( array( 'code' => 'empty_batch' ), 400 );
		}
		$decoded = \json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			\wp_send_json_error( array( 'code' => 'invalid_json' ), 400 );
		}
		$max = 25;
		$n   = 0;
		foreach ( $decoded as $item ) {
			if ( $n >= $max ) {
				break;
			}
			if ( ! is_array( $item ) ) {
				continue;
			}
			$partial             = self::sanitize_client_partial( $item );
			$partial['source'] = 'browser';
			Admin_Ux_Trace::emit( $partial );
			++$n;
		}
		\wp_send_json_success( array( 'accepted' => $n ) );
	}

	private static function current_user_may_emit_trace(): bool {
		$caps = array(
			Capabilities::ACCESS_SETTINGS_HUB,
			Capabilities::ACCESS_PLANS_WORKSPACE,
			Capabilities::ACCESS_AI_WORKSPACE,
			Capabilities::ACCESS_ONBOARDING_WORKSPACE,
			Capabilities::ACCESS_TEMPLATE_LIBRARY,
			Capabilities::ACCESS_INDUSTRY_WORKSPACE,
			Capabilities::ACCESS_IMPORT_EXPORT_TAB,
		);
		foreach ( $caps as $cap ) {
			if ( Capabilities::current_user_can_for_route( $cap ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array<string, mixed>
	 */
	private static function sanitize_client_partial( array $item ): array {
		$severity = isset( $item['severity'] ) && is_string( $item['severity'] ) ? $item['severity'] : 'info';
		$facet    = isset( $item['facet'] ) && is_string( $item['facet'] ) ? $item['facet'] : 'client_interaction';
		$out      = array(
			'severity' => $severity,
			'category' => Admin_Ux_Trace::CATEGORY_ADMIN_UX,
			'facet'    => $facet,
		);
		if ( isset( $item['detail'] ) && is_string( $item['detail'] ) ) {
			$out['detail'] = \substr( \sanitize_text_field( $item['detail'] ), 0, 500 );
		}
		if ( isset( $item['tags'] ) && is_array( $item['tags'] ) ) {
			$tags = array();
			$ti   = 0;
			foreach ( $item['tags'] as $t ) {
				if ( $ti >= 24 ) {
					break;
				}
				if ( is_string( $t ) && $t !== '' ) {
					$tags[] = \substr( \sanitize_text_field( $t ), 0, 120 );
					++$ti;
				}
			}
			$out['tags'] = $tags;
		}
		if ( isset( $item['hub'] ) && is_string( $item['hub'] ) ) {
			$out['hub'] = \substr( \sanitize_key( $item['hub'] ), 0, 120 );
		}
		if ( isset( $item['tab'] ) && is_string( $item['tab'] ) ) {
			$out['tab'] = \substr( \sanitize_key( $item['tab'] ), 0, 64 );
		}
		if ( isset( $item['subtab'] ) && is_string( $item['subtab'] ) ) {
			$out['subtab'] = \substr( \sanitize_key( $item['subtab'] ), 0, 64 );
		}
		if ( isset( $item['message_id'] ) && is_string( $item['message_id'] ) ) {
			$out['message_id'] = \substr( \sanitize_key( $item['message_id'] ), 0, 120 );
		}
		if ( isset( $item['client_sequence'] ) && is_numeric( $item['client_sequence'] ) ) {
			$out['client_sequence'] = (int) $item['client_sequence'];
		}
		return $out;
	}
}
