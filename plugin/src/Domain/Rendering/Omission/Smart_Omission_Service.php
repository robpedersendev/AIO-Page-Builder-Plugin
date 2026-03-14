<?php
/**
 * Smart omission: suppresses empty optional nodes per smart-omission-rendering-contract.
 * Field-driven, schema-aware; refuses to omit required structural nodes (headline, primary CTA).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Omission;

defined( 'ABSPATH' ) || exit;

/**
 * Applies omission rules to field values; returns filtered values and Omission_Result.
 * When in doubt, does not omit.
 */
final class Smart_Omission_Service {

	/** Node roles that must never be omitted when they supply required structure (contract §3.1, §3.3). */
	private const STRUCTURAL_HEADING_ROLES = array( 'headline', 'title' );

	/** Node roles omission-eligible when optional and empty (contract §2.2). */
	private const OMISSION_ELIGIBLE_ROLES = array(
		'eyebrow', 'subheadline', 'intro', 'media', 'media-caption', 'content',
		'badge', 'note', 'footer', 'caption', 'list', 'cards', 'cta_group',
		'faq_items', 'faq_item', 'list_item', 'card',
	);

	/** Fallback when required headline is empty (contract §8). */
	private const FALLBACK_HEADLINE = 'Untitled';

	/** Fallback when required CTA label/link is empty (contract §8). */
	private const FALLBACK_CTA_LABEL = 'Learn more';

	/** Fallback for other required text. */
	private const FALLBACK_OTHER = '—';

	/**
	 * Applies omission: removes optional empty keys, applies fallbacks for required-but-empty, records result.
	 *
	 * @param array<string, mixed> $field_values   Raw field name => value.
	 * @param array<string, array{optional: bool, role: string}> $field_eligibility From blueprint: name => optional, role (inferred or from name).
	 * @param array{section_key: string, position?: int, is_cta_classified?: bool, supplies_h1?: bool, primary_cta_key?: string} $context Section context.
	 * @return array{field_values: array<string, mixed>, omission_result: Omission_Result}
	 */
	public function apply(
		array $field_values,
		array $field_eligibility,
		array $context
	): array {
		$omitted_keys      = array();
		$refused           = array();
		$fallbacks_applied = array();
		$filtered          = $field_values;
		$section_key       = (string) ( $context['section_key'] ?? '' );
		$position          = (int) ( $context['position'] ?? 0 );
		$is_cta            = ! empty( $context['is_cta_classified'] );
		$supplies_h1       = ! empty( $context['supplies_h1'] );
		$primary_cta_key   = isset( $context['primary_cta_key'] ) && is_string( $context['primary_cta_key'] ) ? $context['primary_cta_key'] : '';

		foreach ( $field_eligibility as $field_key => $eligibility ) {
			if ( ! is_array( $eligibility ) ) {
				continue;
			}
			$optional = ! empty( $eligibility['optional'] );
			$role     = isset( $eligibility['role'] ) && is_string( $eligibility['role'] ) ? $eligibility['role'] : $field_key;
			$value    = $field_values[ $field_key ] ?? null;
			$empty    = $this->is_value_empty( $value );

			// * Refuse omission for required structural nodes (contract §3.1, §3.3, §6).
			if ( ! $optional ) {
				$refused[ $field_key ] = 'required';
				if ( $empty ) {
					$fallback = $this->fallback_for( $role, $field_key, $primary_cta_key, $is_cta, $supplies_h1 );
					$filtered[ $field_key ] = $fallback;
					$fallbacks_applied[ $field_key ] = $fallback;
				}
				continue;
			}

			// * Refuse omission for headline when it supplies h1 or section title (contract §3.3).
			if ( in_array( $role, self::STRUCTURAL_HEADING_ROLES, true ) && ( $supplies_h1 || $position === 0 ) ) {
				$refused[ $field_key ] = 'structural_heading';
				if ( $empty ) {
					$filtered[ $field_key ] = self::FALLBACK_HEADLINE;
					$fallbacks_applied[ $field_key ] = self::FALLBACK_HEADLINE;
				}
				continue;
			}

			// * Refuse omission for primary CTA in CTA-classified section (contract §3.1, §6).
			if ( $is_cta && $primary_cta_key !== '' && $field_key === $primary_cta_key ) {
				$refused[ $field_key ] = 'primary_cta';
				if ( $empty ) {
					$filtered[ $field_key ] = self::FALLBACK_CTA_LABEL;
					$fallbacks_applied[ $field_key ] = self::FALLBACK_CTA_LABEL;
				}
				continue;
			}

			// * Omit only when optional, eligible role, and empty (contract §2.2, §2.3).
			if ( ! $empty ) {
				continue;
			}
			$role_eligible = in_array( $role, self::OMISSION_ELIGIBLE_ROLES, true )
				|| $this->role_like_eligible( $role );
			if ( ! $role_eligible ) {
				// * Uncertainty: do not omit (contract §6).
				$refused[ $field_key ] = 'role_not_eligible';
				continue;
			}
			$omitted_keys[] = $field_key;
			unset( $filtered[ $field_key ] );
		}

		// * Repeater/group: omit when 0 rows and key still in filtered (eligibility may not list every key). Link-shaped arrays are not repeaters.
		foreach ( array_keys( $filtered ) as $key ) {
			$v = $filtered[ $key ];
			if ( is_array( $v ) && ! $this->is_link_shaped( $v ) && $this->is_repeater_empty( $v ) ) {
				$elig = $field_eligibility[ $key ] ?? null;
				if ( is_array( $elig ) && ! empty( $elig['optional'] ) ) {
					$omitted_keys[] = $key;
					unset( $filtered[ $key ] );
				}
			}
		}

		return array(
			'field_values'   => $filtered,
			'omission_result' => new Omission_Result( $omitted_keys, $refused, $fallbacks_applied ),
		);
	}

	/**
	 * Builds field eligibility from normalized blueprint (field name => optional, role).
	 *
	 * @param array<string, mixed> $normalized_blueprint Blueprint with 'fields' array.
	 * @return array<string, array{optional: bool, role: string}>
	 */
	public function eligibility_from_blueprint( array $normalized_blueprint ): array {
		$fields = $normalized_blueprint['fields'] ?? array();
		$out    = array();
		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}
			$name = (string) ( $f['name'] ?? '' );
			if ( $name === '' ) {
				continue;
			}
			$required = ! empty( $f['required'] );
			$out[ $name ] = array(
				'optional' => ! $required,
				'role'     => $this->infer_role( $name ),
			);
		}
		return $out;
	}

	private function is_value_empty( $value ): bool {
		if ( $value === null ) {
			return true;
		}
		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}
		if ( is_array( $value ) ) {
			if ( $this->is_link_shaped( $value ) ) {
				$url = isset( $value['url'] ) ? trim( (string) $value['url'] ) : '';
				return $url === '';
			}
			return $this->is_repeater_empty( $value );
		}
		if ( is_numeric( $value ) ) {
			return false;
		}
		return true;
	}

	private function is_link_shaped( array $value ): bool {
		return array_key_exists( 'url', $value ) || array_key_exists( 'title', $value );
	}

	private function is_repeater_empty( array $value ): bool {
		if ( count( $value ) === 0 ) {
			return true;
		}
		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			foreach ( $row as $v ) {
				if ( ! $this->is_value_empty( $v ) ) {
					return false;
				}
			}
		}
		return true;
	}

	private function role_like_eligible( string $role ): bool {
		$r = strtolower( $role );
		return strpos( $r, 'caption' ) !== false
			|| strpos( $r, 'intro' ) !== false
			|| strpos( $r, 'note' ) !== false
			|| strpos( $r, 'badge' ) !== false
			|| strpos( $r, 'footer' ) !== false
			|| strpos( $r, 'media' ) !== false
			|| strpos( $r, 'subhead' ) !== false
			|| strpos( $r, 'eyebrow' ) !== false;
	}

	private function infer_role( string $field_name ): string {
		$n = strtolower( $field_name );
		if ( $n === 'headline' || $n === 'title' ) {
			return $n;
		}
		if ( strpos( $n, 'cta' ) !== false || $n === 'link' || $n === 'button' ) {
			return 'cta';
		}
		if ( strpos( $n, 'faq' ) !== false ) {
			return 'faq_items';
		}
		if ( strpos( $n, 'card' ) !== false ) {
			return 'cards';
		}
		return $field_name;
	}

	private function fallback_for( string $role, string $field_key, string $primary_cta_key, bool $is_cta, bool $supplies_h1 ): string {
		if ( in_array( $role, self::STRUCTURAL_HEADING_ROLES, true ) ) {
			return self::FALLBACK_HEADLINE;
		}
		if ( $role === 'cta' && $is_cta && $field_key === $primary_cta_key ) {
			return self::FALLBACK_CTA_LABEL;
		}
		return self::FALLBACK_OTHER;
	}
}
