<?php
/**
 * Settings registration.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Settings;

use PrivatePluginBase\Admin\Settings\Page;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin settings with the Settings API.
 */
final class Registrar {

	/**
	 * Registers settings.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Registers settings, sections, and fields.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			Page::OPTION_GROUP,
			Page::OPTION_NAME,
			array(
				'type'              => 'array',
				'description'       => __( 'Private Plugin Base options.', 'private-plugin-base' ),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);

		add_settings_section(
			Page::SECTION_GENERAL,
			__( 'General', 'private-plugin-base' ),
			array( __CLASS__, 'render_section' ),
			\PrivatePluginBase\Admin\Menu::SLUG
		);

		add_settings_field(
			'enabled',
			__( 'Enabled', 'private-plugin-base' ),
			array( __CLASS__, 'render_enabled_field' ),
			\PrivatePluginBase\Admin\Menu::SLUG,
			Page::SECTION_GENERAL,
			array(
				'label_for' => 'private_plugin_base_enabled',
			)
		);
	}

	/**
	 * Sanitizes the options array.
	 *
	 * @param mixed $value Raw option value.
	 * @return array<string, mixed> Sanitized options.
	 */
	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$sanitized = array();
		if ( isset( $value['enabled'] ) ) {
			$sanitized['enabled'] = ! empty( $value['enabled'] );
		}
		return $sanitized;
	}

	/**
	 * Renders the section description.
	 *
	 * @return void
	 */
	public static function render_section(): void {
		echo '<p>' . esc_html__( 'General plugin options.', 'private-plugin-base' ) . '</p>';
	}

	/**
	 * Renders the enabled checkbox field.
	 *
	 * @param array<string, string> $args Field arguments.
	 * @return void
	 */
	public static function render_enabled_field( array $args ): void {
		$options = get_option( Page::OPTION_NAME, array() );
		$enabled = ! empty( $options['enabled'] ?? true );
		$id      = $args['label_for'] ?? 'private_plugin_base_enabled';
		$name    = Page::OPTION_NAME . '[enabled]';
		?>
		<input type="checkbox"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="1"
			<?php checked( $enabled ); ?>
		/>
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php esc_html_e( 'Enable plugin features.', 'private-plugin-base' ); ?>
		</label>
		<?php
	}
}
