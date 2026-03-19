<?php
/**
 * Minimal service container. Registers factories and resolves singletons by stable ID.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Supports registering factories and retrieving singletons by service ID.
 * No autowiring, no reflection. Lazy: instances are created on first get().
 */
final class Service_Container {

	/**
	 * Factory callables keyed by service ID.
	 *
	 * @var array<string, callable>
	 */
	private array $factories = array();

	/**
	 * Resolved instances keyed by service ID.
	 *
	 * @var array<string, object|mixed>
	 */
	private array $instances = array();

	/**
	 * Registers a factory for a service ID. Factory is invoked at most once per ID.
	 *
	 * @param string   $id      Stable service ID (see service-registration-contract.md).
	 * @param callable $factory Callable that returns the service. Signature: (): mixed.
	 * @return void
	 */
	public function register( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	/**
	 * Returns the service for the given ID. Resolves once and caches (singleton).
	 *
	 * @param string $id Service ID.
	 * @return object|mixed
	 * @throws \RuntimeException When the service ID is not registered.
	 */
	public function get( string $id ): mixed {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}
		if ( ! array_key_exists( $id, $this->factories ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message; ID is internal, not HTML.
			throw new \RuntimeException( 'Unknown service ID: ' . $id );
		}
		$this->instances[ $id ] = ( $this->factories[ $id ] )();
		return $this->instances[ $id ];
	}

	/**
	 * Checks whether a service ID is registered.
	 *
	 * @param string $id Service ID.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->factories );
	}
}
