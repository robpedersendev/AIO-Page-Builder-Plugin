<?php
/**
 * Provider availability state and form-list fetch with cache fallback (Prompt 237).
 * Normalized states: available, unavailable, no_forms, provider_error, cached_fallback.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Integrations\FormProviders;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;

/**
 * Bounded availability checks and optional form-list fetch with cache; no secrets in state.
 */
final class Form_Provider_Availability_Service {

	public const STATUS_AVAILABLE       = 'available';
	public const STATUS_UNAVAILABLE     = 'unavailable';
	public const STATUS_NO_FORMS        = 'no_forms';
	public const STATUS_PROVIDER_ERROR  = 'provider_error';
	public const STATUS_CACHED_FALLBACK = 'cached_fallback';

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $registry;

	/** @var Form_Provider_Picker_Discovery_Service */
	private Form_Provider_Picker_Discovery_Service $discovery;

	/** @var Form_Provider_Picker_Cache_Service|null */
	private ?Form_Provider_Picker_Cache_Service $cache;

	public function __construct(
		Form_Provider_Registry $registry,
		Form_Provider_Picker_Discovery_Service $discovery,
		?Form_Provider_Picker_Cache_Service $cache = null
	) {
		$this->registry  = $registry;
		$this->discovery = $discovery;
		$this->cache     = $cache;
	}

	/**
	 * Returns availability state for a provider (and optionally form list when supports_form_list).
	 *
	 * @param string $provider_key
	 * @param string $current_form_id Optional; used for stale_binding hint when adapter supports it.
	 * @return array{
	 *   provider_key: string,
	 *   status: string,
	 *   message: string|null,
	 *   from_cache: bool,
	 *   checked_at: int|null,
	 *   picker_items: array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string|null}>,
	 *   stale_binding: bool
	 * }
	 */
	public function get_availability_state( string $provider_key, string $current_form_id = '' ): array {
		$provider_key = $this->sanitize_key( $provider_key );
		$empty        = array(
			'provider_key'  => $provider_key,
			'status'        => self::STATUS_UNAVAILABLE,
			'message'       => null,
			'from_cache'    => false,
			'checked_at'    => null,
			'picker_items'  => array(),
			'stale_binding' => false,
		);
		if ( $provider_key === '' ) {
			return $empty;
		}
		if ( ! $this->registry->has_provider( $provider_key ) ) {
			$empty['message'] = \__( 'Form provider is not registered.', 'aio-page-builder' );
			return $empty;
		}
		if ( ! $this->discovery->has_adapter( $provider_key ) ) {
			$empty['status']  = self::STATUS_AVAILABLE;
			$empty['message'] = null;
			return $empty;
		}
		$meta = $this->discovery->get_picker_metadata_for_provider( $provider_key );
		if ( ! $meta['available'] ) {
			$empty['message'] = \__( 'Form provider is not available (e.g. plugin inactive).', 'aio-page-builder' );
			return $empty;
		}
		if ( ! $meta['supports_form_list'] ) {
			return array(
				'provider_key'  => $provider_key,
				'status'        => self::STATUS_AVAILABLE,
				'message'       => null,
				'from_cache'    => false,
				'checked_at'    => time(),
				'picker_items'  => array(),
				'stale_binding' => false,
			);
		}
		$adapter = $this->discovery->get_adapter( $provider_key );
		if ( $adapter === null ) {
			return array_merge( $empty, array( 'status' => self::STATUS_AVAILABLE ) );
		}
		$cached = $this->cache !== null ? $this->cache->get( $provider_key ) : null;
		if ( $cached !== null ) {
			$stale = $current_form_id !== '' && $adapter->is_item_stale( $current_form_id );
			return array(
				'provider_key'  => $provider_key,
				'status'        => $cached['outcome'] === 'success' ? self::STATUS_AVAILABLE : ( $cached['outcome'] === 'empty' ? self::STATUS_NO_FORMS : self::STATUS_PROVIDER_ERROR ),
				'message'       => null,
				'from_cache'    => true,
				'checked_at'    => $cached['fetched_at'],
				'picker_items'  => $cached['items'],
				'stale_binding' => $stale,
			);
		}
		try {
			$raw   = $adapter->get_form_list();
			$items = $this->discovery->normalize_picker_items_for_provider( $provider_key, $raw );
			if ( $this->cache !== null ) {
				$this->cache->set( $provider_key, $items, empty( $items ) ? 'empty' : 'success' );
			}
			$stale = $current_form_id !== '' && $adapter->is_item_stale( $current_form_id );
			return array(
				'provider_key'  => $provider_key,
				'status'        => empty( $items ) ? self::STATUS_NO_FORMS : self::STATUS_AVAILABLE,
				'message'       => empty( $items ) ? \__( 'No forms available from this provider.', 'aio-page-builder' ) : null,
				'from_cache'    => false,
				'checked_at'    => time(),
				'picker_items'  => $items,
				'stale_binding' => $stale,
			);
		} catch ( \Throwable $e ) {
			$fallback = $this->cache !== null ? $this->cache->get_fallback( $provider_key ) : null;
			if ( $fallback !== null ) {
				return array(
					'provider_key'  => $provider_key,
					'status'        => self::STATUS_CACHED_FALLBACK,
					'message'       => \__( 'Provider temporarily unavailable; showing cached form list.', 'aio-page-builder' ),
					'from_cache'    => true,
					'checked_at'    => $fallback['fetched_at'],
					'picker_items'  => $fallback['items'],
					'stale_binding' => false,
				);
			}
			return array(
				'provider_key'  => $provider_key,
				'status'        => self::STATUS_PROVIDER_ERROR,
				'message'       => \__( 'Form provider could not be reached.', 'aio-page-builder' ),
				'from_cache'    => false,
				'checked_at'    => null,
				'picker_items'  => array(),
				'stale_binding' => false,
			);
		}
	}

	/**
	 * Summary of all registered providers' availability (for diagnostics/support).
	 *
	 * @return array<int, array{provider_key: string, status: string, message: string|null}>
	 */
	public function get_summary_for_admin(): array {
		$ids = $this->registry->get_registered_provider_ids();
		$out = array();
		foreach ( $ids as $key ) {
			$state = $this->get_availability_state( $key );
			$out[] = array(
				'provider_key' => $state['provider_key'],
				'status'       => $state['status'],
				'message'      => $state['message'],
			);
		}
		return $out;
	}

	private function sanitize_key( string $key ): string {
		$key = preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
		return is_string( $key ) ? $key : '';
	}
}
