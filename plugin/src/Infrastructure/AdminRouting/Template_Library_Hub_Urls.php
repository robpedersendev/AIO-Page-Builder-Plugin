<?php
/**
 * Canonical admin URLs for the Template library hub (single menu `page` + aio_tab).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\AdminRouting;

defined( 'ABSPATH' ) || exit;

/**
 * * QUERY_TAB must match Admin_Screen_Hub::QUERY_TAB.
 */
final class Template_Library_Hub_Urls {

	public const HUB_PAGE_SLUG = 'aio-page-builder-page-templates';

	public const QUERY_TAB = 'aio_tab';

	public const TAB_SECTION      = 'section_templates';
	public const TAB_PAGE         = 'page_templates';
	public const TAB_COMPOSITIONS = 'compositions';
	public const TAB_COMPARE      = 'compare';

	/**
	 * @param array<string, mixed> $extra Query args merged after hub args.
	 */
	public static function tab_url( string $tab, array $extra = array() ): string {
		$tab = \sanitize_key( $tab );
		if ( $tab === '' ) {
			$tab = self::TAB_SECTION;
		}
		$args = \array_merge(
			array(
				'page'          => self::HUB_PAGE_SLUG,
				self::QUERY_TAB => $tab,
			),
			$extra
		);
		return \add_query_arg( $args, \admin_url( 'admin.php' ) );
	}

	/**
	 * @return array<string, string>
	 */
	public static function query_args_for_tab( string $tab ): array {
		$tab = \sanitize_key( $tab );
		if ( $tab === '' ) {
			$tab = self::TAB_SECTION;
		}
		return array(
			'page'          => self::HUB_PAGE_SLUG,
			self::QUERY_TAB => $tab,
		);
	}
}
