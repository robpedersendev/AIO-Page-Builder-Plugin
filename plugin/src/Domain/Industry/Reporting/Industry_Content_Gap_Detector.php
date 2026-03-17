<?php
/**
 * Compares Industry Profile and selected bundle/recommended structure against available content
 * to surface missing or weak supporting content (Prompt 408). Advisory only; no auto-generation or blocking.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Detects content gaps by industry and optional bundle; returns structured gap results for diagnostics/onboarding.
 */
final class Industry_Content_Gap_Detector {

	/** Gap type: missing staff bios. */
	public const GAP_STAFF_BIOS = 'missing_staff_bios';

	/** Gap type: missing gallery assets. */
	public const GAP_GALLERY = 'missing_gallery_assets';

	/** Gap type: missing trust proof. */
	public const GAP_TRUST_PROOF = 'missing_trust_proof';

	/** Gap type: missing service area detail. */
	public const GAP_SERVICE_AREA = 'missing_service_area_detail';

	/** Gap type: missing emergency response details. */
	public const GAP_EMERGENCY_RESPONSE = 'missing_emergency_response_details';

	/** Gap type: missing valuation/conversion assets. */
	public const GAP_VALUATION_CONVERSION = 'missing_valuation_conversion_assets';

	/** Severity: info. */
	public const SEVERITY_INFO = 'info';

	/** Severity: caution. */
	public const SEVERITY_CAUTION = 'caution';

	/** Severity: warning. */
	public const SEVERITY_WARNING = 'warning';

	/** Option key: content hints (has_staff_bios, has_gallery, has_trust_proof, has_service_area_detail, has_emergency_details, has_valuation_assets). */
	public const OPT_CONTENT_HINTS = 'content_hints';

	/** Option key: available page template internal_keys (for optional family derivation). */
	public const OPT_AVAILABLE_PAGE_TEMPLATE_KEYS = 'available_page_template_keys';

	/** Option key: available section internal_keys. */
	public const OPT_AVAILABLE_SECTION_KEYS = 'available_section_keys';

	/** Result key: optional subtype influence (refined_action_summary, additive_note) when subtype context refines this gap. */
	public const RESULT_SUBTYPE_INFLUENCE = 'subtype_influence';

	private const ACTION_SUMMARY_MAX = 256;

	/** Per-industry expected content types (gap_type => severity). Empty = no industry-specific expectations. */
	private const INDUSTRY_EXPECTATIONS = array(
		'cosmetology_nail' => array(
			self::GAP_STAFF_BIOS  => self::SEVERITY_CAUTION,
			self::GAP_GALLERY     => self::SEVERITY_CAUTION,
			self::GAP_TRUST_PROOF => self::SEVERITY_INFO,
		),
		'realtor' => array(
			self::GAP_GALLERY              => self::SEVERITY_CAUTION,
			self::GAP_TRUST_PROOF         => self::SEVERITY_INFO,
			self::GAP_VALUATION_CONVERSION => self::SEVERITY_CAUTION,
		),
		'plumber' => array(
			self::GAP_SERVICE_AREA      => self::SEVERITY_CAUTION,
			self::GAP_EMERGENCY_RESPONSE => self::SEVERITY_CAUTION,
			self::GAP_TRUST_PROOF      => self::SEVERITY_INFO,
		),
		'disaster_recovery' => array(
			self::GAP_EMERGENCY_RESPONSE => self::SEVERITY_CAUTION,
			self::GAP_TRUST_PROOF        => self::SEVERITY_INFO,
			self::GAP_SERVICE_AREA       => self::SEVERITY_INFO,
		),
	);

	/** Recommended action summary per gap type. */
	private const GAP_ACTIONS = array(
		self::GAP_STAFF_BIOS           => 'Add staff or team bios to support trust and personal connection.',
		self::GAP_GALLERY              => 'Add a gallery or portfolio of work to showcase results.',
		self::GAP_TRUST_PROOF          => 'Add testimonials, certifications, or trust indicators where permitted.',
		self::GAP_SERVICE_AREA         => 'Define service area or coverage so visitors know where you operate.',
		self::GAP_EMERGENCY_RESPONSE   => 'Clarify emergency or urgent response details if you offer them.',
		self::GAP_VALUATION_CONVERSION => 'Add valuation or conversion-oriented content (e.g. CMA, lead capture) where appropriate.',
	);

	/** Related page/section families per gap type (generic). */
	private const GAP_PAGE_FAMILIES = array(
		self::GAP_STAFF_BIOS           => array( 'about', 'team' ),
		self::GAP_GALLERY              => array( 'gallery', 'portfolio', 'services', 'home' ),
		self::GAP_TRUST_PROOF          => array( 'about', 'home', 'services' ),
		self::GAP_SERVICE_AREA         => array( 'contact', 'services', 'home' ),
		self::GAP_EMERGENCY_RESPONSE   => array( 'contact', 'services', 'home' ),
		self::GAP_VALUATION_CONVERSION => array( 'services', 'home', 'landing' ),
	);

	private const GAP_SECTION_FAMILIES = array(
		self::GAP_STAFF_BIOS           => array( 'proof', 'listing' ),
		self::GAP_GALLERY              => array( 'listing', 'proof' ),
		self::GAP_TRUST_PROOF          => array( 'proof', 'testimonial' ),
		self::GAP_SERVICE_AREA         => array( 'contact', 'listing' ),
		self::GAP_EMERGENCY_RESPONSE   => array( 'contact', 'cta' ),
		self::GAP_VALUATION_CONVERSION => array( 'cta', 'listing' ),
	);

	/** @var Industry_Starter_Bundle_Registry|null */
	private ?Industry_Starter_Bundle_Registry $bundle_registry;

	/** @var Industry_Subtype_Content_Gap_Extender|null When set, subtype context can refine expectations and gap explanations (Prompt 448). */
	private ?Industry_Subtype_Content_Gap_Extender $subtype_extender;

	/** @var Conversion_Goal_Content_Gap_Extender|null When set, conversion goal can refine gap severity/explanation (Prompt 504). */
	private ?Conversion_Goal_Content_Gap_Extender $goal_extender;

	public function __construct(
		?Industry_Starter_Bundle_Registry $bundle_registry = null,
		?Industry_Subtype_Content_Gap_Extender $subtype_extender = null,
		?Conversion_Goal_Content_Gap_Extender $goal_extender = null
	) {
		$this->bundle_registry  = $bundle_registry;
		$this->subtype_extender = $subtype_extender;
		$this->goal_extender    = $goal_extender;
	}

	/**
	 * Detects content gaps for the given profile and optional bundle. Advisory only; safe when profile empty.
	 *
	 * @param array<string, mixed> $profile Normalized Industry Profile (primary_industry_key, selected_starter_bundle_key, etc.).
	 * @param string|null          $bundle_key Override bundle key; when null, profile selected_starter_bundle_key is used.
	 * @param array<string, mixed> $options Optional. content_hints (map of hint => bool), available_page_template_keys, available_section_keys.
	 * @return list<array{gap_type: string, severity: string, related_page_families: list<string>, related_section_families: list<string>, recommended_action_summary: string, subtype_influence?: array}>
	 */
	public function detect( array $profile, ?string $bundle_key = null, array $options = array() ): array {
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		if ( $primary === '' ) {
			return array();
		}
		$expectations = self::INDUSTRY_EXPECTATIONS[ $primary ] ?? array();
		$subtype_key  = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';
		if ( $subtype_key !== '' && $this->subtype_extender !== null ) {
			$subtype_expectations = $this->subtype_extender->get_expectations( $primary, $subtype_key );
			foreach ( $subtype_expectations as $gap_type => $severity ) {
				$expectations[ $gap_type ] = $severity;
			}
		}
		if ( $expectations === array() ) {
			return array();
		}
		$hints = isset( $options[ self::OPT_CONTENT_HINTS ] ) && is_array( $options[ self::OPT_CONTENT_HINTS ] )
			? $options[ self::OPT_CONTENT_HINTS ]
			: array();
		$hint_map = array(
			self::GAP_STAFF_BIOS           => $hints['has_staff_bios'] ?? null,
			self::GAP_GALLERY              => $hints['has_gallery'] ?? null,
			self::GAP_TRUST_PROOF          => $hints['has_trust_proof'] ?? null,
			self::GAP_SERVICE_AREA          => $hints['has_service_area_detail'] ?? null,
			self::GAP_EMERGENCY_RESPONSE   => $hints['has_emergency_details'] ?? null,
			self::GAP_VALUATION_CONVERSION => $hints['has_valuation_assets'] ?? null,
		);
		$gaps = array();
		foreach ( $expectations as $gap_type => $severity ) {
			$present = $hint_map[ $gap_type ] ?? null;
			if ( $present === true ) {
				continue;
			}
			$action = self::GAP_ACTIONS[ $gap_type ] ?? '';
			if ( $this->subtype_extender !== null && $subtype_key !== '' ) {
				$refinement = $this->subtype_extender->get_refinement( $primary, $subtype_key, $gap_type );
				if ( $refinement !== null && isset( $refinement['refined_action_summary'] ) && $refinement['refined_action_summary'] !== '' ) {
					$action = $refinement['refined_action_summary'];
				}
			}
			if ( strlen( $action ) > self::ACTION_SUMMARY_MAX ) {
				$action = substr( $action, 0, self::ACTION_SUMMARY_MAX - 3 ) . '...';
			}
			$item = array(
				'gap_type'                   => $gap_type,
				'severity'                   => $severity,
				'related_page_families'      => self::GAP_PAGE_FAMILIES[ $gap_type ] ?? array(),
				'related_section_families'   => self::GAP_SECTION_FAMILIES[ $gap_type ] ?? array(),
				'recommended_action_summary' => $action,
			);
			if ( $this->subtype_extender !== null && $subtype_key !== '' ) {
				$refinement = $this->subtype_extender->get_refinement( $primary, $subtype_key, $gap_type );
				if ( $refinement !== null ) {
					$item[ self::RESULT_SUBTYPE_INFLUENCE ] = $refinement;
				}
			}
			$goal_key = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				: '';
			if ( $goal_key !== '' && $this->goal_extender !== null ) {
				$item = $this->goal_extender->apply_to_gap_item( $item, $goal_key );
			}
			$gaps[] = $item;
		}
		return $gaps;
	}
}
