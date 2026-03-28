<?php
/**
 * Redacted routing health rows for admin diagnostics (no secrets, no provider payloads).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Router_Interface;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task_Schema_Map;
use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Routing_Task_Labels;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

final class AI_Provider_Routing_Diagnostics_Builder {

	private Settings_Service $settings;

	private AI_Provider_Router_Interface $router;

	private AI_Provider_Admin_Capability_Summary_Builder $summaries;

	private Provider_Capability_Resolver $resolver;

	private Service_Container $container;

	public function __construct(
		Settings_Service $settings,
		AI_Provider_Router_Interface $router,
		AI_Provider_Admin_Capability_Summary_Builder $summaries,
		Provider_Capability_Resolver $resolver,
		Service_Container $container
	) {
		$this->settings  = $settings;
		$this->router    = $router;
		$this->summaries = $summaries;
		$this->resolver  = $resolver;
		$this->container = $container;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function build_task_rows(): array {
		$config       = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		$task_routing = isset( $config['task_routing'] ) && is_array( $config['task_routing'] ) ? $config['task_routing'] : array();

		$by_id = array();
		foreach ( $this->summaries->build_rows() as $row ) {
			$pid = (string) ( $row['provider_id'] ?? '' );
			if ( $pid !== '' ) {
				$by_id[ $pid ] = $row;
			}
		}

		$out = array();
		foreach ( AI_Routing_Task::all() as $task_id ) {
			$route         = $this->router->resolve_route( $task_id, array() );
			$schema_ref    = AI_Routing_Task_Schema_Map::structured_schema_ref_for_task( $task_id );
			$primary_id    = $route->get_primary_provider_id();
			$primary_model = $route->get_primary_model_override();
			$fb_id         = $route->get_fallback_provider_id();
			$fb_model      = $route->get_fallback_model_override();

			$slice             = isset( $task_routing[ $task_id ] ) && is_array( $task_routing[ $task_id ] ) ? $task_routing[ $task_id ] : array();
			$inh_primary       = ! isset( $slice['provider_id'] ) || ! is_string( $slice['provider_id'] ) || trim( $slice['provider_id'] ) === '';
			$fallback_disabled = ! empty( $slice['fallback_disabled'] );
			$inh_fallback      = ! $fallback_disabled
				&& ( ! isset( $slice['fallback_provider_id'] ) || ! is_string( $slice['fallback_provider_id'] ) || trim( $slice['fallback_provider_id'] ) === '' );

			$driver = $this->get_driver( $primary_id );
			$struct = null;
			if ( $schema_ref !== null && $driver instanceof AI_Provider_Interface ) {
				$struct = $this->resolver->supports_schema( $driver, $schema_ref );
			}

			$sum      = $by_id[ $primary_id ] ?? null;
			$ready    = $sum['readiness'] ?? 'unknown';
			$fb_ready = $fb_id !== null && $fb_id !== '' ? ( $by_id[ $fb_id ]['readiness'] ?? 'unknown' ) : 'none';

			$status = __( 'Ready', 'aio-page-builder' );
			if ( ! $route->is_valid() ) {
				$status = __( 'Invalid route', 'aio-page-builder' );
			} elseif ( $sum !== null && empty( $sum['credential_configured'] ) ) {
				$status = __( 'Missing provider configuration', 'aio-page-builder' );
			} elseif ( $schema_ref !== null && $struct === false ) {
				$status = __( 'Structured output not supported for this task’s schema', 'aio-page-builder' );
			} elseif ( $fb_id !== null && $fb_id !== '' && isset( $by_id[ $fb_id ] ) && empty( $by_id[ $fb_id ]['credential_configured'] ) ) {
				$status = __( 'Fallback route present but fallback provider is not configured', 'aio-page-builder' );
			}

			$out[] = array(
				'task_id'                 => $task_id,
				'task_label'              => AI_Provider_Routing_Task_Labels::label_for( $task_id ),
				'primary_provider_id'     => $primary_id,
				'primary_model'           => $primary_model ?? '',
				'fallback_provider_id'    => $fb_id ?? '',
				'fallback_model'          => $fb_model ?? '',
				'inherit_global_primary'  => $inh_primary,
				'inherit_global_fallback' => $inh_fallback,
				'fallback_disabled'       => $fallback_disabled,
				'structured_schema'       => $schema_ref ?? '',
				'structured_supported'    => $struct,
				'primary_readiness'       => $ready,
				'fallback_readiness'      => $fb_ready,
				'status_summary'          => $status,
			);
		}
		return $out;
	}

	private function get_driver( string $provider_id ): ?AI_Provider_Interface {
		if ( $provider_id === 'openai' && $this->container->has( 'openai_provider_driver' ) ) {
			$d = $this->container->get( 'openai_provider_driver' );
			return $d instanceof AI_Provider_Interface ? $d : null;
		}
		if ( $provider_id === 'anthropic' && $this->container->has( 'anthropic_provider_driver' ) ) {
			$d = $this->container->get( 'anthropic_provider_driver' );
			return $d instanceof AI_Provider_Interface ? $d : null;
		}
		return null;
	}
}
