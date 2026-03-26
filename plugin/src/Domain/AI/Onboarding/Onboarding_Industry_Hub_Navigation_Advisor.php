<?php
/**
 * Uses OpenAI to suggest Industry hub primary tab and sub-tab after onboarding planning completes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Single-purpose classifier: maps onboarding + planning output text to Industry hub tab keys (Admin_Menu_Hub_Renderer).
 */
final class Onboarding_Industry_Hub_Navigation_Advisor {

	private const PRIMARY_KEYS = array(
		'profile',
		'overrides',
		'author',
		'import',
		'repair',
		'style',
		'reports',
		'comparisons',
	);

	private const REPORT_SUB_KEYS = array(
		'health',
		'stale',
		'drift',
		'maturity',
		'future_industry',
		'future_subtype',
		'scaffold',
		'pack_family',
	);

	private const COMPARE_SUB_KEYS = array(
		'subtype',
		'bundle',
		'goal',
		'style_layer',
	);

	private Onboarding_Draft_Service $draft_service;

	private AI_Run_Artifact_Service $artifact_service;

	private AI_Provider_Interface $openai;

	public function __construct(
		Onboarding_Draft_Service $draft_service,
		AI_Run_Artifact_Service $artifact_service,
		AI_Provider_Interface $openai
	) {
		$this->draft_service    = $draft_service;
		$this->artifact_service = $artifact_service;
		$this->openai           = $openai;
	}

	/**
	 * Returns suggested tab and optional sub-tab. Defaults to profile on any failure.
	 * Tab keys match Industry hub (render_industry_hub); sub-tab only for reports or comparisons.
	 *
	 * @param int $run_post_id AI run post ID for normalized_output excerpt.
	 * @return array{tab: string, subtab: string|null}
	 */
	public function suggest_navigation( int $run_post_id ): array {
		$default = array(
			'tab'    => 'profile',
			'subtab' => null,
		);
		if ( $run_post_id <= 0 ) {
			return $default;
		}

		$context = $this->build_context_string( $run_post_id );
		if ( $context === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'empty_context default=profile' );
			return $default;
		}

		$request_id = 'onboarding-industry-nav-' . ( function_exists( 'wp_generate_uuid4' ) ? \wp_generate_uuid4() : (string) \wp_rand( 100000, 999999 ) );
		$response   = $this->openai->request(
			array(
				'request_id'      => $request_id,
				'model'           => 'gpt-4o-mini',
				'system_prompt'   => $this->system_prompt(),
				'user_message'    => "Context:\n" . $context,
				'max_tokens'      => 220,
				'temperature'     => 0.1,
				'timeout_seconds' => 35,
			)
		);

		if ( empty( $response['success'] ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'openai_failed default=profile' );
			return $default;
		}

		$payload = $response['structured_payload'] ?? null;
		$content = '';
		if ( is_array( $payload ) && isset( $payload['content'] ) && is_string( $payload['content'] ) ) {
			$content = trim( $payload['content'] );
		}
		if ( $content === '' ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'empty_content default=profile' );
			return $default;
		}

		$parsed = self::parse_json_object( $content );
		if ( ! is_array( $parsed ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'parse_failed default=profile' );
			return $default;
		}

		$tab = isset( $parsed['tab'] ) && is_string( $parsed['tab'] ) ? \sanitize_key( $parsed['tab'] ) : '';
		if ( ! in_array( $tab, self::PRIMARY_KEYS, true ) ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'invalid_tab=' . $tab . ' default=profile' );
			return $default;
		}

		$sub = null;
		if ( isset( $parsed['subtab'] ) && is_string( $parsed['subtab'] ) && $parsed['subtab'] !== '' ) {
			$sub = \sanitize_key( $parsed['subtab'] );
		}

		if ( $tab === 'reports' ) {
			if ( $sub === null || ! in_array( $sub, self::REPORT_SUB_KEYS, true ) ) {
				$sub = 'health';
			}
		} elseif ( $tab === 'comparisons' ) {
			if ( $sub === null || ! in_array( $sub, self::COMPARE_SUB_KEYS, true ) ) {
				$sub = 'subtype';
			}
		} else {
			$sub = null;
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::ONBOARDING_INDUSTRY_HUB_NAV, 'ok tab=' . $tab . ' sub=' . ( $sub ?? 'null' ) );

		return array(
			'tab'    => $tab,
			'subtab' => $sub,
		);
	}

	/**
	 * @param int $run_post_id Run post ID.
	 * @return string
	 */
	private function build_context_string( int $run_post_id ): string {
		$draft = $this->draft_service->get_draft();
		$parts = array();

		$goal = isset( $draft['goal_or_intent_text'] ) && is_string( $draft['goal_or_intent_text'] ) ? trim( $draft['goal_or_intent_text'] ) : '';
		if ( $goal !== '' ) {
			$parts[] = 'User goal / intent (from onboarding): ' . self::truncate( $goal, 2000 );
		}

		$norm = $this->artifact_service->get( $run_post_id, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		if ( is_array( $norm ) ) {
			$summary = '';
			$rs      = $norm[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] ?? null;
			if ( is_array( $rs ) && isset( $rs[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ] ) && is_string( $rs[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ] ) ) {
				$summary .= (string) $rs[ Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT ];
			}
			$sp = $norm[ Build_Plan_Draft_Schema::KEY_SITE_PURPOSE ] ?? null;
			if ( is_array( $sp ) && isset( $sp['summary'] ) && is_string( $sp['summary'] ) ) {
				$summary .= "\n" . (string) $sp['summary'];
			}
			$summary = trim( $summary );
			if ( $summary !== '' ) {
				$parts[] = 'AI planning summary (normalized output): ' . self::truncate( $summary, 3500 );
			}
		}

		return implode( "\n\n", $parts );
	}

	private function system_prompt(): string {
		$primary = implode( ', ', self::PRIMARY_KEYS );
		$report  = implode( ', ', self::REPORT_SUB_KEYS );
		$compare = implode( ', ', self::COMPARE_SUB_KEYS );

		return <<<PROMPT
You choose which Industry admin screen tab is most helpful immediately after onboarding AI planning completes.

Primary tab keys (pick exactly one): {$primary}.

If primary is "reports", subtab must be one of: {$report}.
If primary is "comparisons", subtab must be one of: {$compare}.
For any other primary tab, subtab must be null.

Guidance:
- profile: user should review or set industry profile, packs, readiness.
- style: user cares about style presets / tokens for the industry.
- import: user likely needs bundle import or migration.
- repair: content drift, fixes, guided repair.
- overrides: template overrides for the industry.
- author: author / governance workflows.
- reports: readiness, drift, stale content, maturity, future readiness, scaffold, pack family — pick the best subtab.
- comparisons: compare subtypes, bundles, goals, style layers — pick the best subtab.

Reply with ONLY a JSON object, no markdown, no prose. Shape: {"tab":"profile","subtab":null}
PROMPT;
	}

	private static function truncate( string $text, int $max ): string {
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return substr( $text, 0, $max - 1 ) . '…';
	}

	/**
	 * @param string $content Model output.
	 * @return array<string, mixed>|null
	 */
	public static function parse_json_object( string $content ): ?array {
		$t = trim( $content );
		if ( $t === '' ) {
			return null;
		}
		if ( str_starts_with( $t, '```' ) ) {
			$t = preg_replace( '/^```[a-zA-Z0-9]*\s*/', '', $t ) ?? $t;
			$t = preg_replace( '/```\s*$/', '', $t ) ?? $t;
			$t = trim( $t );
		}
		$decoded = json_decode( $t, true );
		return is_array( $decoded ) ? $decoded : null;
	}
}
