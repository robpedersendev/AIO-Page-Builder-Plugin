<?php
/**
 * Renders industry recommendation badge and tooltip for one section (industry-admin-screen-contract).
 * Expects $aio_pb_item_view (Industry_Section_Library_Item_View) or $aio_pb_badge_data (array with recommendation_status, explanation_snippet).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $aio_pb_item_view ) && ! isset( $aio_pb_badge_data ) ) {
	return;
}

$aio_pb_badge_status = '';
$aio_pb_snippet      = '';
if ( isset( $aio_pb_item_view ) && $aio_pb_item_view instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Library_Item_View ) {
	$aio_pb_badge_status = $aio_pb_item_view->get_recommendation_status();
	$aio_pb_snippet      = $aio_pb_item_view->get_explanation_snippet();
} elseif ( isset( $aio_pb_badge_data ) && is_array( $aio_pb_badge_data ) ) {
	$aio_pb_badge_status = (string) ( $aio_pb_badge_data['recommendation_status'] ?? $aio_pb_badge_data['fit_classification'] ?? '' );
	$aio_pb_snippet      = (string) ( $aio_pb_badge_data['explanation_snippet'] ?? \implode( ', ', (array) ( $aio_pb_badge_data['explanation_reasons'] ?? array() ) ) );
}

if ( $aio_pb_badge_status === '' ) {
	return;
}

$aio_pb_label = $aio_pb_badge_status;
if ( $aio_pb_badge_status === 'recommended' ) {
	$aio_pb_label = __( 'Recommended', 'aio-page-builder' );
} elseif ( $aio_pb_badge_status === 'allowed_weak_fit' ) {
	$aio_pb_label = __( 'Weak fit', 'aio-page-builder' );
} elseif ( $aio_pb_badge_status === 'discouraged' ) {
	$aio_pb_label = __( 'Discouraged', 'aio-page-builder' );
} elseif ( $aio_pb_badge_status === 'neutral' ) {
	$aio_pb_label = __( 'Neutral', 'aio-page-builder' );
}

$aio_pb_badge_class = 'aio-industry-badge aio-industry-badge--' . \sanitize_html_class( $aio_pb_badge_status );
$aio_pb_badge_title = $aio_pb_snippet !== '' ? $aio_pb_snippet : $aio_pb_label;
?>
<span class="<?php echo \esc_attr( $aio_pb_badge_class ); ?>" title="<?php echo \esc_attr( $aio_pb_badge_title ); ?>" aria-label="<?php echo \esc_attr( $aio_pb_label ); ?>"><?php echo \esc_html( $aio_pb_label ); ?></span>
