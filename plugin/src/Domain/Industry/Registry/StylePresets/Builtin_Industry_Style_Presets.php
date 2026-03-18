<?php
/**
 * Built-in industry style preset definitions (industry-style-preset-schema.md).
 * Cosmetology/nail, realtor, plumber, disaster recovery. Token values use sanctioned --aio-* names only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry\StylePresets;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in style preset definitions for the first four launch industries.
 * All token keys and values comply with industry-style-preset-schema and styling subsystem.
 */
final class Builtin_Industry_Style_Presets {

	/**
	 * Returns preset definitions for cosmetology_elegant, realtor_warm, plumber_trust, disaster_recovery_urgency.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		return array(
			self::cosmetology_elegant(),
			self::realtor_warm(),
			self::plumber_trust(),
			self::disaster_recovery_urgency(),
		);
	}

	/**
	 * Cosmetology / Nail: soft, elegant palette; refined radius and spacing.
	 *
	 * @return array<string, mixed>
	 */
	private static function cosmetology_elegant(): array {
		return array(
			'style_preset_key'        => 'cosmetology_elegant',
			'label'                   => 'Elegant Salon',
			'version_marker'          => '1',
			'status'                  => 'active',
			'industry_key'            => 'cosmetology_nail',
			'description'             => 'Soft, elegant palette suited to salons and nail studios.',
			'token_values'            => array(
				'--aio-color-primary'    => '#7c5c8f',
				'--aio-color-accent'     => '#c9a0b8',
				'--aio-color-surface'    => '#faf8fb',
				'--aio-color-background' => '#ffffff',
				'--aio-color-text'       => '#2d2a2e',
				'--aio-color-text-muted' => '#6b656e',
				'--aio-radius-card'      => '0.75rem',
				'--aio-radius-button'    => '2rem',
				'--aio-radius-badge'     => '0.5rem',
				'--aio-space-md'         => '1rem',
				'--aio-space-section'    => '3rem',
				'--aio-shadow-card'      => '0 2px 12px rgba(124, 92, 143, 0.08)',
			),
			'component_override_refs' => array( 'card', 'cta', 'badge', 'headline', 'intro' ),
		);
	}

	/**
	 * Realtor: warm, trustworthy palette; approachable spacing.
	 *
	 * @return array<string, mixed>
	 */
	private static function realtor_warm(): array {
		return array(
			'style_preset_key'        => 'realtor_warm',
			'label'                   => 'Warm & Trusted',
			'version_marker'          => '1',
			'status'                  => 'active',
			'industry_key'            => 'realtor',
			'description'             => 'Warm, trustworthy palette for real estate and listing-focused sites.',
			'token_values'            => array(
				'--aio-color-primary'    => '#b85c38',
				'--aio-color-accent'     => '#d4a574',
				'--aio-color-surface'    => '#fdf8f5',
				'--aio-color-background' => '#ffffff',
				'--aio-color-text'       => '#2c2419',
				'--aio-color-text-muted' => '#6b5b4d',
				'--aio-radius-card'      => '0.5rem',
				'--aio-radius-button'    => '0.375rem',
				'--aio-radius-badge'     => '0.25rem',
				'--aio-space-md'         => '1rem',
				'--aio-space-section'    => '2.5rem',
				'--aio-shadow-card'      => '0 2px 8px rgba(184, 92, 56, 0.1)',
			),
			'component_override_refs' => array( 'card', 'cta', 'badge', 'headline', 'intro' ),
		);
	}

	/**
	 * Plumber: solid, dependable palette; clear CTAs.
	 *
	 * @return array<string, mixed>
	 */
	private static function plumber_trust(): array {
		return array(
			'style_preset_key'        => 'plumber_trust',
			'label'                   => 'Trust & Reliability',
			'version_marker'          => '1',
			'status'                  => 'active',
			'industry_key'            => 'plumber',
			'description'             => 'Solid, dependable palette for plumbing and trade services.',
			'token_values'            => array(
				'--aio-color-primary'    => '#0d47a1',
				'--aio-color-accent'     => '#1976d2',
				'--aio-color-surface'    => '#e3f2fd',
				'--aio-color-background' => '#ffffff',
				'--aio-color-text'       => '#0d2137',
				'--aio-color-text-muted' => '#546e7a',
				'--aio-radius-card'      => '0.375rem',
				'--aio-radius-button'    => '0.375rem',
				'--aio-radius-badge'     => '0.25rem',
				'--aio-space-md'         => '1rem',
				'--aio-space-section'    => '2.5rem',
				'--aio-shadow-card'      => '0 2px 6px rgba(13, 71, 161, 0.12)',
			),
			'component_override_refs' => array( 'card', 'cta', 'badge', 'headline', 'intro' ),
		);
	}

	/**
	 * Disaster recovery: urgent, responsive feel; high-contrast CTAs.
	 *
	 * @return array<string, mixed>
	 */
	private static function disaster_recovery_urgency(): array {
		return array(
			'style_preset_key'        => 'disaster_recovery_urgency',
			'label'                   => 'Urgency & Response',
			'version_marker'          => '1',
			'status'                  => 'active',
			'industry_key'            => 'disaster_recovery',
			'description'             => 'Clear, urgent palette for disaster recovery and 24/7 response messaging.',
			'token_values'            => array(
				'--aio-color-primary'    => '#b71c1c',
				'--aio-color-accent'     => '#e53935',
				'--aio-color-surface'    => '#ffebee',
				'--aio-color-background' => '#ffffff',
				'--aio-color-text'       => '#1a0a0a',
				'--aio-color-text-muted' => '#5d4e4e',
				'--aio-radius-card'      => '0.25rem',
				'--aio-radius-button'    => '0.25rem',
				'--aio-radius-badge'     => '0.25rem',
				'--aio-space-md'         => '1rem',
				'--aio-space-section'    => '2rem',
				'--aio-shadow-card'      => '0 2px 8px rgba(183, 28, 28, 0.15)',
			),
			'component_override_refs' => array( 'card', 'cta', 'badge', 'headline', 'intro' ),
		);
	}
}
