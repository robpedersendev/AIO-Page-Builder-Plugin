<?php
/**
 * Stub AI provider driver for testing (spec §25.1). No network calls; returns configurable success or error.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;

/**
 * Test double: implements do_perform_request by returning a preconfigured raw result.
 */
final class Stub_AI_Provider_Driver extends Abstract_AI_Provider_Driver {

	/** @var array<string, mixed>|null Success raw result (success => true, structured_payload, usage); null to use error result. */
	private ?array $success_result = null;

	/** @var array<string, mixed> Error raw result (success => false, error_http_status, error_code?, error_message?). */
	private array $error_result = array();

	/**
	 * @param Provider_Secret_Store_Interface $secret_store Secret store (e.g. test double).
	 * @param array<string, mixed>            $capabilities get_capabilities() return.
	 * @param array<string, mixed>|null       $success_result Optional success payload for do_perform_request.
	 * @param array<string, mixed>            $error_result  Optional error payload when success_result is null.
	 */
	public function __construct(
		Provider_Secret_Store_Interface $secret_store,
		array $capabilities = array(),
		?array $success_result = null,
		array $error_result = array()
	) {
		$capabilities = array_merge(
			array(
				'provider_id'                 => 'stub',
				'structured_output_supported' => true,
				'file_attachment_supported'   => false,
				'max_context_tokens'          => 128000,
				'models'                      => array(
					array(
						'id'                         => 'stub-model',
						'supports_structured_output' => true,
						'default_for_planning'       => true,
					),
				),
			),
			$capabilities
		);
		parent::__construct(
			'stub',
			new Provider_Error_Normalizer( new Provider_Response_Normalizer() ),
			new Provider_Response_Normalizer(),
			$secret_store,
			$capabilities
		);
		$this->success_result = $success_result;
		$this->error_result   = $error_result;
	}

	/**
	 * Sets the raw result that do_perform_request will return (success).
	 *
	 * @param array<string, mixed> $result Keys: structured_payload, usage?, raw_provider_metadata?
	 * @return void
	 */
	public function set_success_result( array $result ): void {
		$this->success_result = array_merge(
			array(
				'success'               => true,
				'structured_payload'    => array(),
				'usage'                 => null,
				'raw_provider_metadata' => null,
			),
			$result
		);
		$this->error_result   = array();
	}

	/**
	 * Sets the raw error result that do_perform_request will return.
	 *
	 * @param int         $http_status Optional.
	 * @param string|null $code        Optional.
	 * @param string|null $message     Optional.
	 * @return void
	 */
	public function set_error_result( int $http_status = 0, ?string $code = null, ?string $message = null ): void {
		$this->success_result = null;
		$this->error_result   = array(
			'success'           => false,
			'error_http_status' => $http_status,
			'error_code'        => $code,
			'error_message'     => $message,
		);
	}

	/**
	 * @param array<string, mixed> $normalized_request
	 * @param string               $credential
	 * @return array<string, mixed>
	 */
	protected function do_perform_request( array $normalized_request, string $credential ): array {
		if ( $this->success_result !== null ) {
			return $this->success_result;
		}
		return $this->error_result;
	}
}
