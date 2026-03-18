<?php
/**
 * Emits global component override settings as scoped CSS variables for approved components (Prompt 250).
 * Selectors derived from component spec only; invalid overrides omitted.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Reads validated global component overrides and emits scoped --aio-* declarations per component.
 */
final class Global_Component_Override_Emitter {

	/** @var Global_Style_Settings_Repository */
	private Global_Style_Settings_Repository $repository;

	/** @var Component_Override_Registry|null When null, no emission (fail closed). */
	private ?Component_Override_Registry $component_registry;

	public function __construct(
		Global_Style_Settings_Repository $repository,
		?Component_Override_Registry $component_registry = null
	) {
		$this->repository         = $repository;
		$this->component_registry = $component_registry;
	}

	/**
	 * Emits CSS rules for all approved global component overrides. One rule block per component; selectors use spec-derived pattern only.
	 *
	 * @return string CSS block (e.g. "[class*=\"aio-s-\"][class*=\"__card\"] { ... } ...") or empty string.
	 */
	public function emit(): string {
		if ( $this->component_registry === null || ! $this->component_registry->is_loaded() ) {
			return '';
		}
		$overrides = $this->repository->get_global_component_overrides();
		if ( empty( $overrides ) ) {
			return '';
		}
		$blocks = array();
		foreach ( $overrides as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
				continue;
			}
			$allowed      = $this->component_registry->get_allowed_token_overrides( $component_id );
			$element_role = $this->component_registry->get_element_role( $component_id );
			if ( $element_role === '' || empty( $allowed ) ) {
				continue;
			}
			$declarations = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( ! is_string( $var_name ) || ! is_string( $value ) ) {
					continue;
				}
				if ( ! in_array( $var_name, $allowed, true ) ) {
					continue;
				}
				$safe = $this->safe_css_value( $value );
				if ( $safe !== '' ) {
					$declarations[] = $var_name . ': ' . $safe . ';';
				}
			}
			if ( empty( $declarations ) ) {
				continue;
			}
			$selector = $this->selector_for_element_role( $element_role );
			if ( $selector !== '' ) {
				$blocks[] = $selector . ' { ' . implode( ' ', $declarations ) . ' }';
			}
		}
		return implode( ' ', $blocks );
	}

	/**
	 * Returns a spec-compliant selector that targets all instances of a component by element role.
	 * Uses existing BEM pattern aio-s-*__{element_role}; no new structural selectors.
	 *
	 * @param string $element_role From component spec (e.g. card, cta).
	 * @return string Selector or empty if role invalid.
	 */
	private function selector_for_element_role( string $element_role ): string {
		$role = preg_replace( '/[^a-z0-9_-]/', '', $element_role );
		if ( $role === '' || strlen( $role ) > 64 ) {
			return '';
		}
		return '[class*="aio-s-"][class*="__' . $role . '"]';
	}

	/**
	 * Sanitizes a value for safe emission inside a CSS declaration.
	 *
	 * @param string $value
	 * @return string
	 */
	private function safe_css_value( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}
		$value = str_replace( array( '<', '>', '\\', "\0", "\n", "\r" ), '', $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		if ( $value === null ) {
			return '';
		}
		return trim( $value );
	}
}
