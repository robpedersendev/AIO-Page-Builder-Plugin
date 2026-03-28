<?php
/**
 * Privacy-safe aggregate counters for template-lab milestones (local option; no content payloads).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

final class Template_Lab_Telemetry {

	public const EVENT_SESSION_CREATED = 'session_created';

	public const EVENT_PROMPT_SUBMITTED = 'prompt_submitted';

	public const EVENT_SNAPSHOT_APPROVED = 'snapshot_approved';

	public const EVENT_CANONICAL_APPLY_OK = 'canonical_apply_ok';

	public const EVENT_CANONICAL_APPLY_FAIL = 'canonical_apply_fail';

	private Settings_Service $settings;

	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	public function bump( string $event_key ): void {
		$key = \sanitize_key( $event_key );
		if ( $key === '' || strlen( $key ) > 64 ) {
			return;
		}
		$raw = $this->settings->get( Option_Names::TEMPLATE_LAB_TELEMETRY_AGGREGATE );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$counters = isset( $raw['c'] ) && is_array( $raw['c'] ) ? $raw['c'] : array();
		if ( count( $counters ) > 80 ) {
			$counters = array_slice( $counters, -60, null, true );
		}
		$counters[ $key ]  = (int) ( $counters[ $key ] ?? 0 ) + 1;
		$raw['v']          = 1;
		$raw['c']          = $counters;
		$raw['updated_at'] = \gmdate( 'c' );
		$this->settings->set( Option_Names::TEMPLATE_LAB_TELEMETRY_AGGREGATE, $raw );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_aggregate_for_diagnostics(): array {
		$raw = $this->settings->get( Option_Names::TEMPLATE_LAB_TELEMETRY_AGGREGATE );
		return is_array( $raw ) ? $raw : array();
	}
}
