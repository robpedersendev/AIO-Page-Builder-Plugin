<?php
/**
 * Deterministic test double for AI_Provider_Interface (no network).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Support;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../Unit/wordpress/' );

/**
 * Set next_response before request(); defaults to generic error.
 */
final class Fake_AI_Provider_Driver implements AI_Provider_Interface {

	/** @var array<string, mixed>|null */
	private ?array $next_response = null;

	private string $id;

	public function __construct( string $provider_id = 'fake' ) {
		$this->id = $provider_id;
	}

	/**
	 * @param array<string, mixed>|null $response Full normalized response shape.
	 */
	public function set_next_response( ?array $response ): void {
		$this->next_response = $response;
	}

	public function get_provider_id(): string {
		return $this->id;
	}

	public function get_capabilities(): array {
		return array(
			'provider_id'                 => $this->id,
			'structured_output_supported' => true,
			'file_attachment_supported'   => false,
			'max_context_tokens'          => 8192,
			'models'                      => array(
				array(
					'id'                         => 'fake-model',
					'supports_structured_output' => true,
					'default_for_planning'       => true,
				),
			),
		);
	}

	public function request( array $request ): array {
		if ( $this->next_response !== null ) {
			return $this->next_response;
		}
		$n = new Provider_Response_Normalizer();
		return $n->build_error_response(
			(string) ( $request['request_id'] ?? 'x' ),
			$this->id,
			(string) ( $request['model'] ?? 'fake-model' ),
			Provider_Response_Normalizer::ERROR_PROVIDER_ERROR,
			'fake_default'
		);
	}

	public function supports_structured_output( string $schema_ref ): bool {
		return $schema_ref !== '';
	}
}
