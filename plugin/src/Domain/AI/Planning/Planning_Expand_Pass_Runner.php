<?php
/**
 * Second LLM call: append additional new_pages_to_create rows when deterministic merge is still short.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;

/**
 * Emits JSON-only expand payloads; merges into normalized output and re-validates.
 */
final class Planning_Expand_Pass_Runner {

	private AI_Output_Validator $validator;

	private Provider_Request_Context_Builder $request_builder;

	public function __construct(
		AI_Output_Validator $validator,
		Provider_Request_Context_Builder $request_builder
	) {
		$this->validator       = $validator;
		$this->request_builder = $request_builder;
	}

	/**
	 * Attempts to grow new_pages_to_create toward the target using governed template keys only.
	 *
	 * @param array<string, mixed> $normalized Current normalized draft.
	 * @param list<string>         $allowed_template_keys Distinct template_key values the model may use.
	 * @param string               $goal_text             Operator goal for tone.
	 * @return array{normalized: array<string, mixed>, usage: array<string, mixed>|null}
	 */
	public function maybe_expand(
		AI_Provider_Interface $driver,
		string $provider_id,
		string $model_id,
		array $normalized,
		array $allowed_template_keys,
		string $goal_text
	): array {
		$pages = isset( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] ) && is_array( $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ] )
			? $normalized[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ]
			: array();
		$have  = count( $pages );
		if ( $have >= Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET ) {
			return array(
				'normalized' => $normalized,
				'usage'      => null,
			);
		}
		$keys = array_values(
			array_filter(
				array_unique(
					array_map(
						static function ( $k ) {
							return is_string( $k ) ? trim( $k ) : '';
						},
						$allowed_template_keys
					)
				)
			)
		);
		if ( $keys === array() ) {
			return array(
				'normalized' => $normalized,
				'usage'      => null,
			);
		}
		$need = Planning_Breadth_Constants::MIN_NEW_PAGES_TARGET - $have;

		$system = <<<'TEXT'
You are the AIO Page Builder expand pass. Output ONLY a single JSON object with one key: "new_pages_to_create" (array).
Each array element must be a full object with: proposed_page_title, proposed_slug, purpose, template_key, menu_eligible (boolean), section_guidance (array of objects with section_key, intent, content_direction, must_include, must_avoid), confidence (high|medium|low), page_type (hub|detail|faq|pricing|request|location|service|other).
Reuse template_key values when appropriate for similar page roles. Slugs must be unique and URL-safe. Do not repeat titles already present in the "existing_titles" list.
No markdown, no commentary — JSON only.
TEXT;

		$user_payload = array(
			'needed_additional_pages' => $need,
			'allowed_template_keys'   => array_slice( $keys, 0, 200 ),
			'goal'                    => $goal_text,
			'existing_page_count'     => $have,
			'existing_titles'         => $this->collect_titles( $pages ),
			'existing_slugs'          => $this->collect_slugs( $pages ),
		);
		$user         = "Expand data (JSON):\n" . ( function_exists( 'wp_json_encode' ) ? \wp_json_encode( $user_payload ) : \json_encode( $user_payload ) );

		$request_id = 'aio-expand-' . uniqid( '', true );
		$req        = $this->request_builder->build(
			$request_id,
			$model_id,
			$system,
			$user,
			array(
				'max_tokens'      => Planning_Breadth_Constants::EXPAND_PASS_MAX_OUTPUT_TOKENS,
				'timeout_seconds' => 180,
			)
		);

		$response = $driver->request( $req );
		$usage    = isset( $response['usage'] ) && is_array( $response['usage'] ) ? $response['usage'] : null;
		if ( empty( $response['success'] ) ) {
			return array(
				'normalized' => $normalized,
				'usage'      => $usage,
			);
		}
		$content = isset( $response['structured_payload']['content'] ) && is_string( $response['structured_payload']['content'] )
			? $response['structured_payload']['content']
			: ( is_string( $response['structured_payload'] ?? null ) ? $response['structured_payload'] : '' );
		if ( $content === '' ) {
			return array(
				'normalized' => $normalized,
				'usage'      => $usage,
			);
		}
		$parsed = $this->parse_expand_json( $content );
		if ( $parsed === null || ! isset( $parsed['new_pages_to_create'] ) || ! is_array( $parsed['new_pages_to_create'] ) ) {
			return array(
				'normalized' => $normalized,
				'usage'      => $usage,
			);
		}

		$merged                        = $normalized;
		$merged['new_pages_to_create'] = array_merge( $pages, $parsed['new_pages_to_create'] );
		$report                        = $this->validator->validate(
			function_exists( 'wp_json_encode' ) ? \wp_json_encode( $merged ) : \json_encode( $merged ),
			Build_Plan_Draft_Schema::SCHEMA_REF
		);
		if ( ! $report->allows_build_plan_handoff() ) {
			return array(
				'normalized' => $normalized,
				'usage'      => $usage,
			);
		}
		$out        = $report->get_normalized_output();
		$warnings   = isset( $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] ) && is_array( $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] )
			? $out[ Build_Plan_Draft_Schema::KEY_WARNINGS ]
			: array();
		$warnings[] = array(
			'message'  => __( 'An expand pass added additional new-page rows to reach the minimum breadth target.', 'aio-page-builder' ),
			'severity' => 'low',
		);
		$out[ Build_Plan_Draft_Schema::KEY_WARNINGS ] = $warnings;
		return array(
			'normalized' => $out,
			'usage'      => $usage,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $pages
	 * @return list<string>
	 */
	private function collect_titles( array $pages ): array {
		$out = array();
		foreach ( $pages as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$t = isset( $p['proposed_page_title'] ) && is_string( $p['proposed_page_title'] ) ? trim( $p['proposed_page_title'] ) : '';
			if ( $t !== '' ) {
				$out[] = $t;
			}
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, mixed>> $pages
	 * @return list<string>
	 */
	private function collect_slugs( array $pages ): array {
		$out = array();
		foreach ( $pages as $p ) {
			if ( ! is_array( $p ) ) {
				continue;
			}
			$s = isset( $p['proposed_slug'] ) && is_string( $p['proposed_slug'] ) ? trim( $p['proposed_slug'] ) : '';
			if ( $s !== '' ) {
				$out[] = $s;
			}
		}
		return $out;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function parse_expand_json( string $raw ): ?array {
		$raw = trim( $raw );
		if ( preg_match( '/\{[\s\S]*\}/', $raw, $m ) ) {
			$raw = $m[0];
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
