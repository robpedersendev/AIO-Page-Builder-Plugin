<?php
/**
 * Normalized, secrets-free capability rows for onboarding / template-lab admin summaries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

final class AI_Provider_Admin_Capability_Summary_Builder {

	private Service_Container $container;

	private Provider_Secret_Store_Interface $secret_store;

	/** @var list<string> */
	private const DRIVER_KEYS = array(
		'openai_provider_driver',
		'anthropic_provider_driver',
	);

	public function __construct( Service_Container $container, Provider_Secret_Store_Interface $secret_store ) {
		$this->container    = $container;
		$this->secret_store = $secret_store;
	}

	/**
	 * @return list<array{provider_id: string, driver_available: bool, credential_configured: bool, structured_output_supported: bool, file_attachment_supported: bool, max_context_tokens: int|null, models_count: int, readiness: string}>
	 */
	public function build_rows(): array {
		$rows = array();
		foreach ( self::DRIVER_KEYS as $key ) {
			if ( ! $this->container->has( $key ) ) {
				continue;
			}
			$driver = $this->container->get( $key );
			if ( ! $driver instanceof AI_Provider_Interface ) {
				continue;
			}
			$pid  = $driver->get_provider_id();
			$caps = $driver->get_capabilities();
			$models = isset( $caps['models'] ) && is_array( $caps['models'] ) ? $caps['models'] : array();
			$mc     = count( $models );
			$has_cred = $this->secret_store->has_credential( $pid );
			$struct   = ! empty( $caps['structured_output_supported'] );
			$files    = ! empty( $caps['file_attachment_supported'] );
			$max_tok = null;
			if ( array_key_exists( 'max_context_tokens', $caps ) && is_int( $caps['max_context_tokens'] ) ) {
				$max_tok = $caps['max_context_tokens'];
			}
			$readiness = ! $has_cred
				? 'needs_credential'
				: ( $struct ? 'ready_structured' : 'credential_only' );
			$rows[]  = array(
				'provider_id'                 => $pid,
				'driver_available'            => true,
				'credential_configured'       => $has_cred,
				'structured_output_supported' => $struct,
				'file_attachment_supported'   => $files,
				'max_context_tokens'          => $max_tok,
				'models_count'                => $mc,
				'readiness'                   => $readiness,
			);
		}
		return $rows;
	}
}
