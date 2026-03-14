<?php
/**
 * Bounded template-style preference signals for planning and recommendation (spec §1.9.5, §59.6, Prompt 212).
 * Advisory only; does not override CTA-law or planner judgment. Stable payload: template_preference_profile.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Value object for template-relevant user preferences: page emphasis, conversion posture, proof style, content density, animation.
 * All fields optional; used in planning context and recommendation explanations.
 *
 * Example template_preference_profile payload (to_array()):
 * [
 *   'page_emphasis'             => 'conversion',
 *   'conversion_posture'        => 'moderate',
 *   'proof_style'               => 'social_proof',
 *   'content_density'           => 'moderate',
 *   'animation_preference'      => 'reduced',
 *   'cta_intensity_preference'  => 'medium',
 *   'reduced_motion_preference' => true,
 * ]
 */
final class Template_Preference_Profile {

	/** Page emphasis: where the user wants focus (informational, conversion, balanced). */
	public const PAGE_EMPHASIS_INFORMATIONAL = 'informational';
	public const PAGE_EMPHASIS_CONVERSION    = 'conversion';
	public const PAGE_EMPHASIS_BALANCED      = 'balanced';
	public const PAGE_EMPHASIS_NOT_SPECIFIED = '';

	/** Conversion posture: how strongly to lean into CTAs (soft, moderate, strong). */
	public const CONVERSION_POSTURE_SOFT     = 'soft';
	public const CONVERSION_POSTURE_MODERATE = 'moderate';
	public const CONVERSION_POSTURE_STRONG   = 'strong';
	public const CONVERSION_POSTURE_NOT_SPECIFIED = '';

	/** Proof style: social proof, credentials, testimonials, minimal. */
	public const PROOF_STYLE_SOCIAL     = 'social_proof';
	public const PROOF_STYLE_CREDENTIALS = 'credentials';
	public const PROOF_STYLE_TESTIMONIALS = 'testimonials';
	public const PROOF_STYLE_MINIMAL    = 'minimal';
	public const PROOF_STYLE_NOT_SPECIFIED = '';

	/** Content density: compact, moderate, spacious. */
	public const CONTENT_DENSITY_COMPACT  = 'compact';
	public const CONTENT_DENSITY_MODERATE = 'moderate';
	public const CONTENT_DENSITY_SPACIOUS = 'spacious';
	public const CONTENT_DENSITY_NOT_SPECIFIED = '';

	/** Animation preference: full, reduced, minimal, none. Respects reduced-motion. */
	public const ANIMATION_FULL    = 'full';
	public const ANIMATION_REDUCED = 'reduced';
	public const ANIMATION_MINIMAL = 'minimal';
	public const ANIMATION_NONE    = 'none';
	public const ANIMATION_NOT_SPECIFIED = '';

	/** CTA intensity preference: advisory only; does not override CTA-law. */
	public const CTA_INTENSITY_LOW    = 'low';
	public const CTA_INTENSITY_MEDIUM = 'medium';
	public const CTA_INTENSITY_HIGH   = 'high';
	public const CTA_INTENSITY_NOT_SPECIFIED = '';

	/** @var string */
	private string $page_emphasis;

	/** @var string */
	private string $conversion_posture;

	/** @var string */
	private string $proof_style;

	/** @var string */
	private string $content_density;

	/** @var string */
	private string $animation_preference;

	/** @var string */
	private string $cta_intensity_preference;

	/** @var bool Whether user prefers reduced motion (advisory for template selection). */
	private bool $reduced_motion_preference;

	/**
	 * @param string $page_emphasis
	 * @param string $conversion_posture
	 * @param string $proof_style
	 * @param string $content_density
	 * @param string $animation_preference
	 * @param string $cta_intensity_preference
	 * @param bool   $reduced_motion_preference
	 */
	public function __construct(
		string $page_emphasis = self::PAGE_EMPHASIS_NOT_SPECIFIED,
		string $conversion_posture = self::CONVERSION_POSTURE_NOT_SPECIFIED,
		string $proof_style = self::PROOF_STYLE_NOT_SPECIFIED,
		string $content_density = self::CONTENT_DENSITY_NOT_SPECIFIED,
		string $animation_preference = self::ANIMATION_NOT_SPECIFIED,
		string $cta_intensity_preference = self::CTA_INTENSITY_NOT_SPECIFIED,
		bool $reduced_motion_preference = false
	) {
		$this->page_emphasis            = $page_emphasis;
		$this->conversion_posture      = $conversion_posture;
		$this->proof_style              = $proof_style;
		$this->content_density          = $content_density;
		$this->animation_preference     = $animation_preference;
		$this->cta_intensity_preference = $cta_intensity_preference;
		$this->reduced_motion_preference = $reduced_motion_preference;
	}

	public function get_page_emphasis(): string {
		return $this->page_emphasis;
	}

	public function get_conversion_posture(): string {
		return $this->conversion_posture;
	}

	public function get_proof_style(): string {
		return $this->proof_style;
	}

	public function get_content_density(): string {
		return $this->content_density;
	}

	public function get_animation_preference(): string {
		return $this->animation_preference;
	}

	public function get_cta_intensity_preference(): string {
		return $this->cta_intensity_preference;
	}

	public function get_reduced_motion_preference(): bool {
		return $this->reduced_motion_preference;
	}

	/**
	 * Stable payload for planning context and artifact (template_preference_profile).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$out = array(
			'page_emphasis'             => $this->page_emphasis,
			'conversion_posture'       => $this->conversion_posture,
			'proof_style'              => $this->proof_style,
			'content_density'          => $this->content_density,
			'animation_preference'     => $this->animation_preference,
			'cta_intensity_preference' => $this->cta_intensity_preference,
			'reduced_motion_preference' => $this->reduced_motion_preference,
		);
		return $out;
	}

	/**
	 * Build from raw array (e.g. form POST or stored profile). Invalid values become not_specified/empty.
	 *
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$allowed_emphasis = array( self::PAGE_EMPHASIS_INFORMATIONAL, self::PAGE_EMPHASIS_CONVERSION, self::PAGE_EMPHASIS_BALANCED, self::PAGE_EMPHASIS_NOT_SPECIFIED );
		$allowed_posture = array( self::CONVERSION_POSTURE_SOFT, self::CONVERSION_POSTURE_MODERATE, self::CONVERSION_POSTURE_STRONG, self::CONVERSION_POSTURE_NOT_SPECIFIED );
		$allowed_proof = array( self::PROOF_STYLE_SOCIAL, self::PROOF_STYLE_CREDENTIALS, self::PROOF_STYLE_TESTIMONIALS, self::PROOF_STYLE_MINIMAL, self::PROOF_STYLE_NOT_SPECIFIED );
		$allowed_density = array( self::CONTENT_DENSITY_COMPACT, self::CONTENT_DENSITY_MODERATE, self::CONTENT_DENSITY_SPACIOUS, self::CONTENT_DENSITY_NOT_SPECIFIED );
		$allowed_animation = array( self::ANIMATION_FULL, self::ANIMATION_REDUCED, self::ANIMATION_MINIMAL, self::ANIMATION_NONE, self::ANIMATION_NOT_SPECIFIED );
		$allowed_cta = array( self::CTA_INTENSITY_LOW, self::CTA_INTENSITY_MEDIUM, self::CTA_INTENSITY_HIGH, self::CTA_INTENSITY_NOT_SPECIFIED );

		$page_emphasis = isset( $data['page_emphasis'] ) && is_string( $data['page_emphasis'] ) ? trim( $data['page_emphasis'] ) : '';
		$conversion_posture = isset( $data['conversion_posture'] ) && is_string( $data['conversion_posture'] ) ? trim( $data['conversion_posture'] ) : '';
		$proof_style = isset( $data['proof_style'] ) && is_string( $data['proof_style'] ) ? trim( $data['proof_style'] ) : '';
		$content_density = isset( $data['content_density'] ) && is_string( $data['content_density'] ) ? trim( $data['content_density'] ) : '';
		$animation_preference = isset( $data['animation_preference'] ) && is_string( $data['animation_preference'] ) ? trim( $data['animation_preference'] ) : '';
		$cta_intensity = isset( $data['cta_intensity_preference'] ) && is_string( $data['cta_intensity_preference'] ) ? trim( $data['cta_intensity_preference'] ) : '';
		$reduced_motion = isset( $data['reduced_motion_preference'] ) && ( $data['reduced_motion_preference'] === true || $data['reduced_motion_preference'] === '1' || ( is_string( $data['reduced_motion_preference'] ) && strtolower( $data['reduced_motion_preference'] ) === 'true' ) );

		$page_emphasis = in_array( $page_emphasis, $allowed_emphasis, true ) ? $page_emphasis : self::PAGE_EMPHASIS_NOT_SPECIFIED;
		$conversion_posture = in_array( $conversion_posture, $allowed_posture, true ) ? $conversion_posture : self::CONVERSION_POSTURE_NOT_SPECIFIED;
		$proof_style = in_array( $proof_style, $allowed_proof, true ) ? $proof_style : self::PROOF_STYLE_NOT_SPECIFIED;
		$content_density = in_array( $content_density, $allowed_density, true ) ? $content_density : self::CONTENT_DENSITY_NOT_SPECIFIED;
		$animation_preference = in_array( $animation_preference, $allowed_animation, true ) ? $animation_preference : self::ANIMATION_NOT_SPECIFIED;
		$cta_intensity = in_array( $cta_intensity, $allowed_cta, true ) ? $cta_intensity : self::CTA_INTENSITY_NOT_SPECIFIED;

		return new self(
			$page_emphasis,
			$conversion_posture,
			$proof_style,
			$content_density,
			$animation_preference,
			$cta_intensity,
			$reduced_motion
		);
	}

	/** Allowed values for page_emphasis (for validation/UI). */
	public static function allowed_page_emphasis(): array {
		return array( self::PAGE_EMPHASIS_INFORMATIONAL, self::PAGE_EMPHASIS_CONVERSION, self::PAGE_EMPHASIS_BALANCED );
	}

	public static function allowed_conversion_posture(): array {
		return array( self::CONVERSION_POSTURE_SOFT, self::CONVERSION_POSTURE_MODERATE, self::CONVERSION_POSTURE_STRONG );
	}

	public static function allowed_proof_style(): array {
		return array( self::PROOF_STYLE_SOCIAL, self::PROOF_STYLE_CREDENTIALS, self::PROOF_STYLE_TESTIMONIALS, self::PROOF_STYLE_MINIMAL );
	}

	public static function allowed_content_density(): array {
		return array( self::CONTENT_DENSITY_COMPACT, self::CONTENT_DENSITY_MODERATE, self::CONTENT_DENSITY_SPACIOUS );
	}

	public static function allowed_animation_preference(): array {
		return array( self::ANIMATION_FULL, self::ANIMATION_REDUCED, self::ANIMATION_MINIMAL, self::ANIMATION_NONE );
	}

	public static function allowed_cta_intensity_preference(): array {
		return array( self::CTA_INTENSITY_LOW, self::CTA_INTENSITY_MEDIUM, self::CTA_INTENSITY_HIGH );
	}
}
