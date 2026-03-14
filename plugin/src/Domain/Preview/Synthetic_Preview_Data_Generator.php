<?php
/**
 * Deterministic synthetic preview-data generator for section and page templates (spec §17.1, template-preview-and-dummy-data-contract, large-scale-acf-lpagery-binding-contract §3).
 * Produces family-aware headings, copy, lists, CTAs, proof items, FAQs, legal stubs, media placeholders; supports omission-case and animation-tier metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Generates preview-safe, deterministic ACF-style field values by purpose family.
 * No real data; no secrets; no misleading legal text. All content is clearly synthetic.
 * Output is deterministic for a given context so preview snapshot cache keys remain stable (Prompt 184).
 */
final class Synthetic_Preview_Data_Generator {

	/** Preview-safe URL placeholder (large-scale-acf §3.3). */
	private const PLACEHOLDER_URL = '#';

	/** Generic fallback headline (smart-omission §8). */
	private const FALLBACK_HEADLINE = 'Heading';

	/** Generic fallback body. */
	private const FALLBACK_BODY = 'Body copy for this section.';

	/**
	 * Generates synthetic field values for a section template from its preview context.
	 *
	 * @param Synthetic_Preview_Context $context
	 * @return array<string, mixed> field_name => value (flat and repeater shapes; ACF-compatible).
	 */
	public function generate_for_section( Synthetic_Preview_Context $context ): array {
		if ( ! $context->is_section() ) {
			return $this->generate_field_values_for_family( 'other', $context->get_variant(), $context->get_omission_case() );
		}
		return $this->generate_field_values_for_family(
			$context->get_purpose_family(),
			$context->get_variant(),
			$context->get_omission_case()
		);
	}

	/**
	 * Generates per-section field values for a page template preview.
	 *
	 * @param Synthetic_Preview_Context $context         Page context (template_key, category, family).
	 * @param list<array{section_key: string, position: int, purpose_family?: string}> $ordered_sections Each with section_key, position; purpose_family optional (default 'other').
	 * @return list<array{section_key: string, position: int, field_values: array<string, mixed>}>
	 */
	public function generate_for_page( Synthetic_Preview_Context $context, array $ordered_sections ): array {
		$omission_case = $context->get_omission_case();
		$variant       = $context->get_variant();
		$out           = array();
		foreach ( $ordered_sections as $item ) {
			$section_key    = (string) ( $item['section_key'] ?? '' );
			$position       = (int) ( $item['position'] ?? count( $out ) );
			$purpose_family = (string) ( $item['purpose_family'] ?? 'other' );
			if ( $purpose_family === '' ) {
				$purpose_family = 'other';
			}
			$out[] = array(
				'section_key'   => $section_key,
				'position'      => $position,
				'field_values'  => $this->generate_field_values_for_family( $purpose_family, $variant, $omission_case ),
			);
		}
		return $out;
	}

	/**
	 * Generates deterministic field values for a given purpose family (and optional omission case).
	 * Used by generate_for_section and generate_for_page; also for tests and single-section preview.
	 *
	 * @param string $purpose_family hero, proof, cta, faq, offer, legal, etc.
	 * @param string $variant        Variant key (e.g. default, compact).
	 * @param string $omission_case  '' or Synthetic_Preview_Context::OMISSION_CASE_OPTIONAL_EMPTY.
	 * @return array<string, mixed>
	 */
	public function generate_field_values_for_family( string $purpose_family, string $variant = 'default', string $omission_case = '' ): array {
		$empty_optional = ( $omission_case === Synthetic_Preview_Context::OMISSION_CASE_OPTIONAL_EMPTY );
		$family         = $purpose_family !== '' ? $purpose_family : 'other';

		switch ( $family ) {
			case 'hero':
				return $this->hero_fields( $empty_optional );
			case 'proof':
				return $this->proof_fields( $empty_optional );
			case 'cta':
			case 'contact':
				return $this->cta_fields( $empty_optional );
			case 'faq':
				return $this->faq_fields( $empty_optional );
			case 'offer':
				return $this->offer_fields( $empty_optional );
			case 'explainer':
				return $this->explainer_fields( $empty_optional );
			case 'legal':
				return $this->legal_fields( $empty_optional );
			case 'listing':
				return $this->listing_fields( $empty_optional );
			case 'comparison':
				return $this->comparison_fields( $empty_optional );
			case 'profile':
				return $this->profile_fields( $empty_optional );
			case 'stats':
				return $this->stats_fields( $empty_optional );
			case 'timeline':
				return $this->timeline_fields( $empty_optional );
			case 'locations':
			case 'location':
				return $this->locations_fields( $empty_optional );
			default:
				return $this->other_fields( $empty_optional );
		}
	}

	/** @return array<string, mixed> */
	private function hero_fields( bool $empty_optional ): array {
		$out = array(
			'headline'    => 'Welcome to Our Service',
			'subheadline' => 'Supporting copy that explains the value in one or two sentences.',
			'cta_text'    => 'Get started',
			'cta_url'     => self::PLACEHOLDER_URL,
		);
		if ( $empty_optional ) {
			$out['eyebrow'] = '';
		} else {
			$out['eyebrow'] = 'Welcome';
		}
		return $out;
	}

	/** @return array<string, mixed> */
	private function proof_fields( bool $empty_optional ): array {
		$items = array(
			array( 'name' => 'Client A', 'quote' => 'This service made a real difference.', 'role' => 'Customer' ),
			array( 'name' => 'Jane D.', 'quote' => 'Professional and responsive.', 'role' => 'Client' ),
		);
		if ( $empty_optional ) {
			$items[] = array( 'name' => '', 'quote' => '', 'role' => '' );
		}
		return array(
			'headline' => 'What Our Clients Say',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function cta_fields( bool $empty_optional ): array {
		$out = array(
			'headline'  => 'Ready to get started?',
			'cta_text'  => 'Sign up now',
			'cta_url'   => self::PLACEHOLDER_URL,
		);
		if ( ! $empty_optional ) {
			$out['secondary_cta_text'] = 'Contact us';
			$out['secondary_cta_url']  = self::PLACEHOLDER_URL;
		}
		return $out;
	}

	/** @return array<string, mixed> */
	private function faq_fields( bool $empty_optional ): array {
		$items = array(
			array( 'question' => 'What is this service?', 'answer' => 'A short explanation in one or two sentences for preview.' ),
			array( 'question' => 'How do I get started?', 'answer' => 'Steps are outlined in the documentation.' ),
		);
		if ( $empty_optional ) {
			$items[] = array( 'question' => '', 'answer' => '' );
		}
		return array(
			'headline' => 'Frequently Asked Questions',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function offer_fields( bool $empty_optional ): array {
		$plans = array(
			array( 'title' => 'Starter', 'price' => '$XX', 'features' => 'Feature one, feature two.' ),
			array( 'title' => 'Pro', 'price' => '$YY', 'features' => 'All Starter plus more.' ),
		);
		return array(
			'headline' => 'Plans and Pricing',
			'intro'    => $empty_optional ? '' : 'Choose the option that fits your needs.',
			'plans'    => $plans,
		);
	}

	/** @return array<string, mixed> */
	private function explainer_fields( bool $empty_optional ): array {
		$steps = array(
			array( 'title' => 'Step one', 'description' => 'Short description for this step.' ),
			array( 'title' => 'Step two', 'description' => 'Next step description.' ),
		);
		return array(
			'headline' => 'How It Works',
			'steps'    => $steps,
		);
	}

	/** @return array<string, mixed> */
	private function legal_fields( bool $empty_optional ): array {
		// * No fake effective dates or realistic policy text (template-preview §2.4, §8).
		return array(
			'headline' => 'Legal',
			'content'  => 'Legal disclaimer placeholder. Terms and conditions text.',
		);
	}

	/** @return array<string, mixed> */
	private function listing_fields( bool $empty_optional ): array {
		$items = array(
			array( 'title' => 'Item one', 'description' => 'Short description.', 'link' => self::PLACEHOLDER_URL ),
			array( 'title' => 'Item two', 'description' => 'Another item.', 'link' => self::PLACEHOLDER_URL ),
		);
		return array(
			'headline' => 'Directory or Listing',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function comparison_fields( bool $empty_optional ): array {
		$rows = array(
			array( 'option' => 'Option A', 'pros' => 'Pro one.', 'cons' => 'Con one.' ),
			array( 'option' => 'Option B', 'pros' => 'Pro two.', 'cons' => 'Con two.' ),
		);
		return array(
			'headline' => 'Comparison',
			'rows'     => $rows,
		);
	}

	/** @return array<string, mixed> */
	private function profile_fields( bool $empty_optional ): array {
		return array(
			'headline'   => 'Team',
			'name'      => 'Team Member',
			'role'      => 'Role title',
			'bio'       => 'Short bio in one or two sentences for preview.',
			'image_ref' => $empty_optional ? '' : 'placeholder',
		);
	}

	/** @return array<string, mixed> */
	private function stats_fields( bool $empty_optional ): array {
		$items = array(
			array( 'label' => 'Projects', 'number' => '100+' ),
			array( 'label' => 'Clients', 'number' => '50+' ),
		);
		return array(
			'headline' => 'By the Numbers',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function timeline_fields( bool $empty_optional ): array {
		$items = array(
			array( 'title' => 'Phase one', 'description' => 'Description for this phase.' ),
			array( 'title' => 'Phase two', 'description' => 'Next phase.' ),
		);
		return array(
			'headline' => 'Timeline',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function locations_fields( bool $empty_optional ): array {
		// * No real addresses (template-preview §3.2, §8).
		$items = array(
			array( 'name' => 'Main Office', 'address' => '123 Example St', 'link' => self::PLACEHOLDER_URL ),
		);
		return array(
			'headline' => 'Locations',
			'items'    => $items,
		);
	}

	/** @return array<string, mixed> */
	private function other_fields( bool $empty_optional ): array {
		$out = array(
			'headline' => self::FALLBACK_HEADLINE,
			'content'  => self::FALLBACK_BODY,
		);
		if ( $empty_optional ) {
			$out['subheadline'] = '';
		}
		return $out;
	}

	/**
	 * Returns preview-safe fallback for a single field type when no family-specific value exists (large-scale-acf §3.3).
	 *
	 * @param string $field_type text, textarea, url, image, repeater.
	 * @return string|array<int, array<string, string>>
	 */
	public static function fallback_for_field_type( string $field_type ) {
		switch ( $field_type ) {
			case 'url':
			case 'link':
				return self::PLACEHOLDER_URL;
			case 'textarea':
			case 'wysiwyg':
				return self::FALLBACK_BODY;
			case 'image':
				return '';
			case 'repeater':
				return array( array( 'title' => self::FALLBACK_HEADLINE, 'content' => self::FALLBACK_BODY ) );
			default:
				return self::FALLBACK_HEADLINE;
		}
	}
}
