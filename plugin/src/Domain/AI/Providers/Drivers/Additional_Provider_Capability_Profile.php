<?php
/**
 * Capability profile for the additional AI provider (Anthropic) (spec §25.6, §49.9).
 * Provides capability metadata for model/default selection and connection testing.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Drivers;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the capability array for the additional provider. Used by Additional_AI_Provider_Driver.
 */
final class Additional_Provider_Capability_Profile {

	/** Stable provider identifier. */
	public const PROVIDER_ID = 'anthropic';

	/**
	 * Returns capability metadata for the additional provider (Anthropic Claude).
	 *
	 * @return array{provider_id: string, structured_output_supported: bool, file_attachment_supported: bool, max_context_tokens: int|null, models: array, error_format_notes?: string, retry_notes?: string}
	 */
	public static function get_capabilities(): array {
		return array(
			'provider_id'                 => self::PROVIDER_ID,
			'structured_output_supported' => true,
			'file_attachment_supported'   => false,
			'max_context_tokens'          => 200000,
			'models'                      => array(
				array(
					'id'                         => 'claude-sonnet-4-20250514',
					'supports_structured_output' => true,
					'default_for_planning'       => true,
				),
				array(
					'id'                         => 'claude-3-5-sonnet-20241022',
					'supports_structured_output' => true,
					'default_for_planning'       => false,
				),
				array(
					'id'                         => 'claude-3-haiku-20240307',
					'supports_structured_output' => true,
					'default_for_planning'       => false,
				),
			),
			'error_format_notes'          => 'Anthropic returns error type and message in JSON body.',
			'retry_notes'                 => 'Rate limits and server errors support retry with backoff.',
		);
	}
}
