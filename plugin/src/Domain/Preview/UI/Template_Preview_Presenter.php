<?php
/**
 * Presents truthful preview labels for template previews.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview\UI;

defined( 'ABSPATH' ) || exit;

final class Template_Preview_Presenter {

	/**
	 * @param bool $has_rendered_preview True when a real rendered HTML preview exists.
	 * @return string
	 */
	public function get_preview_title( bool $has_rendered_preview ): string {
		return $has_rendered_preview
			? __( 'Preview', 'aio-page-builder' )
			: __( 'Structural preview', 'aio-page-builder' );
	}

	/**
	 * @param bool $has_rendered_preview
	 * @return string
	 */
	public function get_preview_aria_label( bool $has_rendered_preview ): string {
		return $has_rendered_preview
			? __( 'Rendered preview', 'aio-page-builder' )
			: __( 'Structural preview', 'aio-page-builder' );
	}
}
