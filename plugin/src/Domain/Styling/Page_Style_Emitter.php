<?php
/**
 * Emits per-page style payload as CSS for .aio-page (Prompt 254). Sanitized payload only; no new selectors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Reads per-page entity style payload and emits .aio-page { --aio-* } and scoped component override rules.
 */
final class Page_Style_Emitter {

	/** Page wrapper selector per render-surfaces spec. */
	private const PAGE_SELECTOR = '.aio-page';

	/** @var Entity_Style_Payload_Repository */
	private Entity_Style_Payload_Repository $payload_repository;

	/** @var Style_Token_Registry|null */
	private ?Style_Token_Registry $token_registry;

	/** @var Component_Override_Registry|null */
	private ?Component_Override_Registry $component_registry;

	public function __construct(
		Entity_Style_Payload_Repository $payload_repository,
		?Style_Token_Registry $token_registry = null,
		?Component_Override_Registry $component_registry = null
	) {
		$this->payload_repository = $payload_repository;
		$this->token_registry     = $token_registry;
		$this->component_registry = $component_registry;
	}

	/**
	 * Emits CSS for the given page template: .aio-page token vars and scoped component overrides. Empty when invalid or no payload.
	 *
	 * @param string $template_key Page template internal_key.
	 * @return string CSS block(s) or empty string.
	 */
	public function emit_for_page( string $template_key ): string {
		if ( $template_key === '' ) {
			return '';
		}
		$payload             = $this->payload_repository->get_payload( 'page_template', $template_key );
		$token_overrides     = $payload[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] ?? array();
		$component_overrides = $payload[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] ?? array();
		$parts               = array();
		$declarations        = $this->emit_token_declarations( $token_overrides );
		if ( $declarations !== '' ) {
			$parts[] = self::PAGE_SELECTOR . ' { ' . $declarations . ' }';
		}
		$component_css = $this->emit_component_override_blocks( $component_overrides, self::PAGE_SELECTOR . ' ' );
		if ( $component_css !== '' ) {
			$parts[] = $component_css;
		}
		return implode( ' ', $parts );
	}

	/**
	 * Builds declaration string for token overrides (--aio-* only). Uses registry when available.
	 *
	 * @param array<string, array<string, string>> $token_overrides
	 * @return string
	 */
	private function emit_token_declarations( array $token_overrides ): string {
		$out = array();
		if ( $this->token_registry !== null && $this->token_registry->is_loaded() ) {
			$group_names = $this->token_registry->get_token_group_names();
			foreach ( $token_overrides as $group => $names ) {
				if ( ! is_string( $group ) || ! in_array( $group, $group_names, true ) || $group === 'component' || ! is_array( $names ) ) {
					continue;
				}
				$allowed = $this->token_registry->get_allowed_names_for_group( $group );
				foreach ( $names as $name => $value ) {
					if ( ! is_string( $name ) || ! is_string( $value ) || ! in_array( $name, $allowed, true ) ) {
						continue;
					}
					$var_name = $this->token_registry->get_token_variable_name( $group, $name );
					if ( $var_name === '' ) {
						continue;
					}
					$safe = $this->safe_css_value( $value );
					if ( $safe !== '' ) {
						$out[] = $var_name . ': ' . $safe . ';';
					}
				}
			}
		}
		return implode( ' ', $out );
	}

	/**
	 * Emits component override rule blocks with optional prefix selector (e.g. ".aio-page ").
	 *
	 * @param array<string, array<string, string>> $component_overrides
	 * @param string                               $selector_prefix
	 * @return string
	 */
	private function emit_component_override_blocks( array $component_overrides, string $selector_prefix ): string {
		if ( $this->component_registry === null || ! $this->component_registry->is_loaded() ) {
			return '';
		}
		$blocks = array();
		foreach ( $component_overrides as $component_id => $pairs ) {
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
				if ( ! is_string( $var_name ) || ! is_string( $value ) || ! in_array( $var_name, $allowed, true ) ) {
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
			$role = preg_replace( '/[^a-z0-9_-]/', '', $element_role );
			if ( $role !== '' && strlen( $role ) <= 64 ) {
				$selector = $selector_prefix . '[class*="aio-s-"][class*="__' . $role . '"]';
				$blocks[] = $selector . ' { ' . implode( ' ', $declarations ) . ' }';
			}
		}
		return implode( ' ', $blocks );
	}

	private function safe_css_value( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}
		$value = str_replace( array( '<', '>', '\\', "\0", "\n", "\r" ), '', $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		return $value !== null ? trim( $value ) : '';
	}
}
