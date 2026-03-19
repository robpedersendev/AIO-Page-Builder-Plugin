<?php
/**
 * Builds UI state for the Privacy, Reporting & Settings screen (spec §49.12, §46.11, §47).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Constants;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Result;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Assembles disclosure, retention, uninstall/export, environment, version, report destination, privacy helper.
 * No secrets or raw credentials in any payload.
 * When container provides template_library_lifecycle_summary_builder, uninstall_export_state includes template_library_lifecycle_summary (Prompt 213).
 */
final class Privacy_Settings_State_Builder {

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Service_Container|null */
	private ?Service_Container $container;

	public function __construct( Settings_Service $settings, ?Service_Container $container = null ) {
		$this->settings  = $settings;
		$this->container = $container;
	}

	/**
	 * Builds full screen state. All values safe for display; no secrets.
	 *
	 * @return array{
	 *   reporting_disclosure: list<array{heading: string, content: string}>,
	 *   retention_state: array{reporting_log_summary: string, retention_note: string},
	 *   uninstall_export_state: array{choices: list<array>, prefs_summary: string, built_pages_message: string},
	 *   environment_summary: array{php_version: string, wp_version: string},
	 *   version_summary: array{plugin_version: string},
	 *   report_destination_summary: array{transport_type: string, description: string},
	 *   privacy_helper_text: string,
	 *   diagnostics_verbosity_allowed: bool
	 * }
	 */
	public function build(): array {
		return array(
			'reporting_disclosure'          => $this->build_reporting_disclosure(),
			'retention_state'               => $this->build_retention_state(),
			'uninstall_export_state'        => $this->build_uninstall_export_state(),
			'environment_summary'           => $this->build_environment_summary(),
			'version_summary'               => $this->build_version_summary(),
			'report_destination_summary'    => $this->build_report_destination_summary(),
			'privacy_helper_text'           => $this->build_privacy_helper_text(),
			'diagnostics_verbosity_allowed' => false,
		);
	}

	/**
	 * Mandatory reporting disclosure (spec §46.1, §46.11). Must remain clearly visible.
	 *
	 * @return list<array{heading: string, content: string}>
	 */
	private function build_reporting_disclosure(): array {
		return array(
			array(
				'heading' => __( 'Private distribution reporting', 'aio-page-builder' ),
				'content' => __( 'This plugin is privately distributed. As part of that model, it may send operational reports (installation notification, periodic heartbeat, and error reports) to an approved destination. This reporting is mandatory and cannot be disabled. It helps ensure support and compatibility. No secrets or credentials are ever included in reports.', 'aio-page-builder' ),
			),
			array(
				'heading' => __( 'What may be sent', 'aio-page-builder' ),
				'content' => __( 'Included: site identifier, plugin version, WordPress and PHP versions, dependency state, and sanitized error summaries when failures occur. Excluded: API keys, passwords, personal data, or raw logs. Delivery status is recorded locally for diagnostics.', 'aio-page-builder' ),
			),
		);
	}

	/**
	 * @return array{reporting_log_summary: string, retention_note: string}
	 */
	private function build_retention_state(): array {
		$log                   = \get_option( Option_Names::REPORTING_LOG, array() );
		$count                 = is_array( $log ) ? count( $log ) : 0;
		$reporting_log_summary = sprintf(
			/* translators: %d: number of reporting log entries */
			__( 'Reporting log: %d entry(ies) (delivery attempts and status).', 'aio-page-builder' ),
			$count
		);
		$retention_note = __( 'Log entries are retained for diagnostics. Retention is governed by product policy; local logs do not contain secrets.', 'aio-page-builder' );
		return array(
			'reporting_log_summary' => $reporting_log_summary,
			'retention_note'        => $retention_note,
		);
	}

	/**
	 * @return array{choices: list<array>, prefs_summary: string, built_pages_message: string, acf_preservation_message: string, template_library_lifecycle_summary?: array<string, mixed>}
	 */
	private function build_uninstall_export_state(): array {
		$choices                  = array(
			array(
				'value'       => Uninstall_Result::CHOICE_FULL_BACKUP,
				'label'       => __( 'Export full backup', 'aio-page-builder' ),
				'description' => __( 'Download a full backup ZIP, then remove plugin data. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_SETTINGS_PROFILE_ONLY,
				'label'       => __( 'Export settings and profile only', 'aio-page-builder' ),
				'description' => __( 'Download a reduced bundle (settings, profiles, restore metadata), then remove plugin data. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_SKIP_EXPORT,
				'label'       => __( 'Skip export and continue', 'aio-page-builder' ),
				'description' => __( 'Remove plugin data without exporting. Built pages will remain.', 'aio-page-builder' ),
			),
			array(
				'value'       => Uninstall_Result::CHOICE_CANCEL,
				'label'       => __( 'Cancel uninstall', 'aio-page-builder' ),
				'description' => __( 'Do nothing and keep the plugin installed.', 'aio-page-builder' ),
			),
		);
		$prefs                    = $this->settings->get( Option_Names::UNINSTALL_PREFS );
		$prefs_summary            = empty( $prefs ) || ! is_array( $prefs )
			? __( 'No uninstall preference saved. On uninstall you will be offered export choices.', 'aio-page-builder' )
			: __( 'Uninstall/export preferences are stored. Use the Uninstall flow to export or remove plugin data.', 'aio-page-builder' );
		$built_pages_message      = __( 'Built pages (content created with the builder) will remain on your site. Only plugin-owned data (settings, templates, plans, logs) will be removed when you continue.', 'aio-page-builder' );
		$acf_preservation_message = __( 'ACF field values (section content) are retained. To keep section field groups editable in the editor after uninstall, run the handoff before uninstall. See the ACF Uninstall Preservation operator guide.', 'aio-page-builder' );
		$out                      = array(
			'choices'                  => $choices,
			'prefs_summary'            => $prefs_summary,
			'built_pages_message'      => $built_pages_message,
			'acf_preservation_message' => $acf_preservation_message,
		);
		if ( $this->container !== null && $this->container->has( 'template_library_lifecycle_summary_builder' ) ) {
			$builder                                   = $this->container->get( 'template_library_lifecycle_summary_builder' );
			$out['template_library_lifecycle_summary'] = $builder->build();
		}
		return $out;
	}

	/**
	 * @return array{php_version: string, wp_version: string}
	 */
	private function build_environment_summary(): array {
		$php = PHP_VERSION;
		$wp  = $GLOBALS['wp_version'] ?? '';
		return array(
			'php_version' => $php,
			'wp_version'  => $wp !== '' ? $wp : __( 'Unknown', 'aio-page-builder' ),
		);
	}

	/**
	 * @return array{plugin_version: string}
	 */
	private function build_version_summary(): array {
		return array(
			'plugin_version' => Constants::plugin_version(),
		);
	}

	/**
	 * Report destination display only; no raw email or credentials (spec §46.11).
	 *
	 * @return array{transport_type: string, description: string}
	 */
	private function build_report_destination_summary(): array {
		return array(
			'transport_type' => __( 'Email', 'aio-page-builder' ),
			'description'    => __( 'Install notification, heartbeat, and error reports are sent to the approved destination via email. The destination is configured for private distribution.', 'aio-page-builder' ),
		);
	}

	/**
	 * Suggested privacy-policy helper text (spec §47.11, SPR-004).
	 * Aligns with WordPress Tools → Export Personal Data / Erase Personal Data for actor-linked data.
	 *
	 * @return string
	 */
	private function build_privacy_helper_text(): string {
		return __(
			'The AIO Page Builder plugin stores configuration, profile data, crawl summaries, Build Plan records, and operational logs. It may send operational reports (installation, heartbeat, error summaries) to an approved destination. AI planning requests and related data may be sent to configured AI providers. Stored data and reporting are admin-facing; retention follows the product’s policy. The plugin registers with WordPress Tools → Export Personal Data and Erase Personal Data for actor-linked data: AI run metadata, job queue records, template compare lists, and bundle preview cache. Export produces a copy; erase redacts the actor link while keeping records for audit. Administrators can also export settings and use the uninstall flow to remove plugin data; built page content may be preserved.',
			'aio-page-builder'
		);
	}
}
