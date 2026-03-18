<?php
/**
 * Segregated provider credential storage using a dedicated option (spec §43.13, provider-secret-storage-contract.md).
 * This option is never exported or included in settings blobs; access is capability-gated at call sites.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Secrets;

defined( 'ABSPATH' ) || exit;

/**
 * Persists provider credentials in a single dedicated option. Not in Option_Names::all(); never exported.
 */
final class Option_Based_Provider_Secret_Store implements Provider_Secret_Store_Interface {

	/** Option key for secret material. Intentionally not in Option_Names so it is never exported. */
	private const OPTION_KEY = 'aio_page_builder_provider_secrets';

	/** @var array<string, array{value: string, state: string}> */
	private array $cache = array();

	/** @var bool */
	private bool $loaded = false;

	private function load(): void {
		if ( $this->loaded ) {
			return;
		}
		$raw          = \get_option( self::OPTION_KEY, array() );
		$this->cache  = is_array( $raw ) ? $raw : array();
		$this->loaded = true;
	}

	/** @inheritdoc */
	public function get_credential_for_provider( string $provider_id ): ?string {
		$this->load();
		return $this->cache[ $provider_id ]['value'] ?? null;
	}

	/** @inheritdoc */
	public function get_credential_state( string $provider_id ): string {
		$this->load();
		return $this->cache[ $provider_id ]['state'] ?? self::STATE_ABSENT;
	}

	/** @inheritdoc */
	public function has_credential( string $provider_id ): bool {
		$this->load();
		return isset( $this->cache[ $provider_id ]['value'] );
	}

	/** @inheritdoc */
	public function set_credential( string $provider_id, string $value ): bool {
		$this->load();
		$this->cache[ $provider_id ] = array(
			'value' => $value,
			'state' => self::STATE_PENDING_VALIDATION,
		);
		$this->persist();
		return true;
	}

	/** @inheritdoc */
	public function delete_credential( string $provider_id ): bool {
		$this->load();
		$had = isset( $this->cache[ $provider_id ] );
		unset( $this->cache[ $provider_id ] );
		if ( $had ) {
			$this->persist();
		}
		return $had;
	}

	private function persist(): void {
		\update_option( self::OPTION_KEY, $this->cache, false );
	}
}
