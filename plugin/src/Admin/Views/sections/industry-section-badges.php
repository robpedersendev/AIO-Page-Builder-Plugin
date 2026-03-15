<?php
/**
 * Renders industry recommendation badge and tooltip for one section (industry-admin-screen-contract).
 * Expects $item_view (Industry_Section_Library_Item_View) or $badge_data (array with recommendation_status, explanation_snippet).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $item_view ) && ! isset( $badge_data ) ) {
	return;
}

$status = '';
$snippet = '';
if ( isset( $item_view ) && $item_view instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Library_Item_View ) {
	$status  = $item_view->get_recommendation_status();
	$snippet = $item_view->get_explanation_snippet();
} elseif ( isset( $badge_data ) && is_array( $badge_data ) ) {
	$status  = (string) ( $badge_data['recommendation_status'] ?? $badge_data['fit_classification'] ?? '' );
	$snippet = (string) ( $badge_data['explanation_snippet'] ?? \implode( ', ', (array) ( $badge_data['explanation_reasons'] ?? array() ) ) );
}

if ( $status === '' ) {
	return;
}

$label = $status;
if ( $status === 'recommended' ) {
	$label = __( 'Recommended', 'aio-page-builder' );
} elseif ( $status === 'allowed_weak_fit' ) {
	$label = __( 'Weak fit', 'aio-page-builder' );
} elseif ( $status === 'discouraged' ) {
	$label = __( 'Discouraged', 'aio-page-builder' );
} elseif ( $status === 'neutral' ) {
	$label = __( 'Neutral', 'aio-page-builder' );
}

$class = 'aio-industry-badge aio-industry-badge--' . \esc_attr( \sanitize_html_class( $status ) );
$title = $snippet !== '' ? \esc_attr( $snippet ) : \esc_attr( $label );
?>
<span class="<?php echo $class; ?>" title="<?php echo $title; ?>" aria-label="<?php echo \esc_attr( $label ); ?>"><?php echo \esc_html( $label ); ?></span>
