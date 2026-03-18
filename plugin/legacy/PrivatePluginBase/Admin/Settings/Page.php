<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old settings page config. Active plugin uses AIOPageBuilder admin/settings.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Settings page configuration.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the main settings page sections and fields.
 */
final class Page {

	/**
	 * Option group for settings.
	 *
	 * @var string
	 */
	public const OPTION_GROUP = 'private_plugin_base_settings';

	/**
	 * Option name for settings.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'private_plugin_base_options';

	/**
	 * Section ID.
	 *
	 * @var string
	 */
	public const SECTION_GENERAL = 'private_plugin_base_general';
}
