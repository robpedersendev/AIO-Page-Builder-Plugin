<?php
/**
 * Operator-facing model selection guidance for AI routing UI (no API behavior change).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Routing;

defined( 'ABSPATH' ) || exit;

/**
 * Curated pros/cons copy keyed by provider id and model id (must match driver capability lists).
 */
final class AI_Provider_Routing_Model_Guide {

	/**
	 * @return array<string, array{good_for: string, not_ideal_for: string}>
	 */
	public static function openai_by_model(): array {
		return array(
			'gpt-4o'      => array(
				'good_for'      => __( 'Strong default for structured planning and JSON-shaped outputs; large context; good reasoning quality for build plans and onboarding.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Higher cost per token than mini models; may be more than you need for tiny prompts or smoke tests.', 'aio-page-builder' ),
			),
			'gpt-4o-mini' => array(
				'good_for'      => __( 'Lower cost and fast responses; fine for short drafts, classification, or high-volume template-lab tries.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Weaker on long, nuanced planning and complex multi-step JSON; large plans may need a larger model.', 'aio-page-builder' ),
			),
			'gpt-4-turbo' => array(
				'good_for'      => __( 'Mature GPT-4-class model with solid tool-style and JSON workflows when your account routes traffic here reliably.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Often superseded by 4o on capability/cost; confirm availability and pricing in your OpenAI dashboard.', 'aio-page-builder' ),
			),
		);
	}

	/**
	 * @return array<string, array{good_for: string, not_ideal_for: string}>
	 */
	public static function anthropic_by_model(): array {
		return array(
			'claude-sonnet-4-20250514'   => array(
				'good_for'      => __( 'Balanced Claude Sonnet for long context planning, careful reasoning, and structured outputs.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Typically more expensive than Haiku; may be slower than smaller models for trivial tasks.', 'aio-page-builder' ),
			),
			'claude-3-5-sonnet-20241022' => array(
				'good_for'      => __( 'Stable 3.5 Sonnet snapshot; strong general assistant quality when your org standardizes on this revision.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Older snapshot than Sonnet 4; verify it still matches your org’s approved model list.', 'aio-page-builder' ),
			),
			'claude-3-haiku-20240307'    => array(
				'good_for'      => __( 'Fast and economical for lighter tasks, retries, or high-frequency template-lab iterations.', 'aio-page-builder' ),
				'not_ideal_for' => __( 'Less capable on very large structured plans or subtle reasoning than Sonnet-class models.', 'aio-page-builder' ),
			),
		);
	}

	/**
	 * @return array{good_for: string, not_ideal_for: string}
	 */
	public static function generic_unknown_model_copy(): array {
		return array(
			'good_for'      => __( 'Use when you need a specific model id from your provider account (preview, regional, or custom).', 'aio-page-builder' ),
			'not_ideal_for' => __( 'Not in the curated list—double-check pricing, context limits, and structured-output support before relying on it in production.', 'aio-page-builder' ),
		);
	}
}
