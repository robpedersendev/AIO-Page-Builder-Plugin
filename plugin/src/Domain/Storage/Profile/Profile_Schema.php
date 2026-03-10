<?php
/**
 * Canonical profile field and structure names (spec §22). No persistence; schema contract only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Stable field names and root keys for brand/business profile. Shape and validation are in docs/schemas/profile-schema.md.
 */
final class Profile_Schema {

	/** Root key for current brand profile (single object). */
	public const ROOT_BRAND = 'brand_profile';

	/** Root key for current business profile (single object). */
	public const ROOT_BUSINESS = 'business_profile';

	/** Key for voice/tone sub-object under brand profile. */
	public const BRAND_VOICE_TONE = 'voice_tone';

	/** Key for asset references array under brand profile. */
	public const BRAND_ASSET_REFERENCES = 'asset_references';

	/** Key for personas array under business profile. */
	public const BUSINESS_PERSONAS = 'personas';

	/** Key for services/offers array under business profile. */
	public const BUSINESS_SERVICES_OFFERS = 'services_offers';

	/** Key for competitors array under business profile. */
	public const BUSINESS_COMPETITORS = 'competitors';

	/** Key for geography array under business profile. */
	public const BUSINESS_GEOGRAPHY = 'geography';

	/** Formality level enum values (voice_tone.formality_level). */
	public const FORMALITY_LEVELS = array( 'formal', 'neutral', 'informal', 'mixed', 'not_applicable' );

	/** Clarity vs sophistication enum (voice_tone.clarity_vs_sophistication). */
	public const CLARITY_VS_SOPHISTICATION = array( 'clarity', 'balanced', 'sophistication', 'not_applicable' );

	/** Asset reference role enum. */
	public const ASSET_ROLES = array( 'logo', 'visual_identity', 'color_reference', 'typography_reference', 'other' );

	/** Snapshot scope types. */
	public const SNAPSHOT_SCOPE_TYPES = array( 'ai_run', 'onboarding_session', 'plan', 'other' );
}
