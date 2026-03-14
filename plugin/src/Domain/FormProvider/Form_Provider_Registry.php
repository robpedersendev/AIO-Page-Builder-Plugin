<?php
/**
 * Registry of form providers compatible with AIO Page Builder form sections (form-provider-integration-contract.md).
 * Maps provider identifier to shortcode tag and id attribute for embed string construction.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\FormProvider;

defined( 'ABSPATH' ) || exit;

/**
 * Holds provider configs and builds shortcode embed strings from (provider_id, form_id).
 */
final class Form_Provider_Registry {

	/** ACF field name for provider identifier in form sections. */
	public const FIELD_FORM_PROVIDER = 'form_provider';

	/** ACF field name for form identifier in form sections. */
	public const FIELD_FORM_ID = 'form_id';

	/** Pattern for allowed form_id in shortcode (storage key or slug: alphanumeric, underscore, hyphen). */
	private const FORM_ID_PATTERN = '/^[a-zA-Z0-9_\-]+$/';

	/**
	 * Known providers: provider_id => [ shortcode_tag, id_attribute_name ].
	 *
	 * @var array<string, array{shortcode_tag: string, id_attr: string}>
	 */
	private array $providers = array();

	public function __construct() {
		$this->register_ndr_forms();
	}

	/**
	 * Registers NDR Form Manager per integration doc (provider_id: ndr_forms).
	 *
	 * @return void
	 */
	private function register_ndr_forms(): void {
		$this->providers['ndr_forms'] = array(
			'shortcode_tag' => 'ndr_forms',
			'id_attr'      => 'id',
		);
	}

	/**
	 * Registers a form provider.
	 *
	 * @param string $provider_id   Stable slug (e.g. ndr_forms, wpforms).
	 * @param string $shortcode_tag WordPress shortcode tag.
	 * @param string $id_attr       Attribute name for form identifier (e.g. id, form_id).
	 * @return void
	 */
	public function register( string $provider_id, string $shortcode_tag, string $id_attr = 'id' ): void {
		$provider_id   = $this->sanitize_provider_id( $provider_id );
		$shortcode_tag = sanitize_key( $shortcode_tag );
		$id_attr       = sanitize_key( $id_attr );
		if ( $provider_id !== '' && $shortcode_tag !== '' && $id_attr !== '' ) {
			$this->providers[ $provider_id ] = array(
				'shortcode_tag' => $shortcode_tag,
				'id_attr'       => $id_attr,
			);
		}
	}

	/**
	 * Returns whether a provider is registered.
	 *
	 * @param string $provider_id
	 * @return bool
	 */
	public function has_provider( string $provider_id ): bool {
		return isset( $this->providers[ $this->sanitize_provider_id( $provider_id ) ] );
	}

	/**
	 * Builds the shortcode embed string for the given provider and form id, or null if invalid.
	 *
	 * @param string $provider_id Registered provider identifier.
	 * @param string $form_id     Form identifier (storage key or slug); will be sanitized.
	 * @return string|null Shortcode string e.g. [ndr_forms id="contact"], or null if provider unknown or form_id invalid.
	 */
	public function build_shortcode( string $provider_id, string $form_id ): ?string {
		$provider_id = $this->sanitize_provider_id( $provider_id );
		$form_id     = $this->sanitize_form_id( $form_id );
		if ( $form_id === '' ) {
			return null;
		}
		$config = $this->providers[ $provider_id ] ?? null;
		if ( $config === null ) {
			return null;
		}
		$tag = $config['shortcode_tag'];
		$attr = $config['id_attr'];
		return '[' . $tag . ' ' . $attr . '="' . esc_attr( $form_id ) . '"]';
	}

	/**
	 * Returns list of registered provider identifiers (for admin/ACF choices).
	 *
	 * @return list<string>
	 */
	public function get_registered_provider_ids(): array {
		return array_keys( $this->providers );
	}

	/**
	 * Sanitizes provider_id for lookup (lowercase, alphanumeric + underscore).
	 *
	 * @param string $provider_id
	 * @return string
	 */
	private function sanitize_provider_id( string $provider_id ): string {
		$id = preg_replace( '/[^a-z0-9_]/', '', strtolower( $provider_id ) );
		return is_string( $id ) ? $id : '';
	}

	/**
	 * Sanitizes form_id for use in shortcode attribute (allow alphanumeric, underscore, hyphen).
	 *
	 * @param string $form_id
	 * @return string
	 */
	private function sanitize_form_id( string $form_id ): string {
		$form_id = trim( (string) $form_id );
		if ( $form_id === '' ) {
			return '';
		}
		if ( ! preg_match( self::FORM_ID_PATTERN, $form_id ) ) {
			return '';
		}
		return $form_id;
	}

	/**
	 * Validates provider_id against the registry (for save/input validation; spec §0.10.9, Prompt 233).
	 *
	 * @param string $provider_id Raw input.
	 * @return bool True if non-empty and registered.
	 */
	public function is_valid_provider_id( string $provider_id ): bool {
		$id = $this->sanitize_provider_id( $provider_id );
		return $id !== '' && $this->has_provider( $id );
	}

	/**
	 * Validates form_id format only (alphanumeric, underscore, hyphen). Does not check provider existence.
	 *
	 * @param string $form_id Raw input.
	 * @return bool True if matches allowed pattern.
	 */
	public function is_valid_form_id_format( string $form_id ): bool {
		return $this->sanitize_form_id( $form_id ) !== '';
	}

	/**
	 * Validates both provider and form_id for persistence/display (registry + format).
	 *
	 * @param string $provider_id
	 * @param string $form_id
	 * @return array{ valid: bool, errors: list<string> }
	 */
	public function validate_provider_and_form( string $provider_id, string $form_id ): array {
		$errors = array();
		$p      = $this->sanitize_provider_id( $provider_id );
		if ( $p === '' ) {
			$errors[] = __( 'Form provider is required and must be alphanumeric.', 'aio-page-builder' );
		} elseif ( ! $this->has_provider( $p ) ) {
			$errors[] = sprintf(
				/* translators: 1: provider id */
				__( 'Form provider "%1$s" is not registered.', 'aio-page-builder' ),
				$p
			);
		}
		if ( $this->sanitize_form_id( $form_id ) === '' ) {
			$errors[] = __( 'Form ID is required and may only contain letters, numbers, hyphens, and underscores.', 'aio-page-builder' );
		}
		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}
}
