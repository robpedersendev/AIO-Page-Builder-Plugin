<?php
/**
 * Renders industry recommendation badge and tooltip for one page template (industry-admin-screen-contract).
 * Expects $aio_pb_item_view (Industry_Page_Template_Directory_Item_View) or $aio_pb_badge_data (array with recommendation_status, explanation_snippet, hierarchy_fit, lpagery_fit).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $aio_pb_item_view ) && ! isset( $aio_pb_badge_data ) ) {
	return;
}

$aio_pb_badge_status  = '';
$aio_pb_snippet       = '';
$aio_pb_hierarchy_fit = '';
$aio_pb_lpagery_fit   = '';
if ( isset( $aio_pb_item_view ) && $aio_pb_item_view instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Item_View ) {
	$aio_pb_badge_status  = $aio_pb_item_view->get_recommendation_status();
	$aio_pb_snippet       = $aio_pb_item_view->get_explanation_snippet();
	$aio_pb_hierarchy_fit = $aio_pb_item_view->get_hierarchy_fit();
	$aio_pb_lpagery_fit   = $aio_pb_item_view->get_lpagery_fit();
} elseif ( isset( $aio_pb_badge_data ) && is_array( $aio_pb_badge_data ) ) {
	$aio_pb_badge_status  = (string) ( $aio_pb_badge_data['recommendation_status'] ?? $aio_pb_badge_data['fit_classification'] ?? '' );
	$aio_pb_snippet       = (string) ( $aio_pb_badge_data['explanation_snippet'] ?? \implode( ', ', (array) ( $aio_pb_badge_data['explanation_reasons'] ?? array() ) ) );
	$aio_pb_hierarchy_fit = (string) ( $aio_pb_badge_data['hierarchy_fit'] ?? '' );
	$aio_pb_lpagery_fit   = (string) ( $aio_pb_badge_data['lpagery_fit'] ?? '' );
}

if ( $aio_pb_badge_status === '' && $aio_pb_hierarchy_fit === '' && $aio_pb_lpagery_fit === '' ) {
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

$aio_pb_parts = array();
if ( $aio_pb_snippet !== '' ) {
	$aio_pb_parts[] = $aio_pb_snippet;
}
if ( $aio_pb_hierarchy_fit !== '' ) {
	$aio_pb_parts[] = sprintf( /* translators: %s: hierarchy fit note */ __( 'Hierarchy: %s', 'aio-page-builder' ), $aio_pb_hierarchy_fit );
}
if ( $aio_pb_lpagery_fit !== '' ) {
	$aio_pb_parts[] = sprintf( /* translators: %s: LPagery fit note */ __( 'LPagery: %s', 'aio-page-builder' ), $aio_pb_lpagery_fit );
}
$aio_pb_badge_title = \implode( ' · ', $aio_pb_parts );
if ( $aio_pb_badge_title === '' ) {
	$aio_pb_badge_title = $aio_pb_label;
}
$aio_pb_badge_class = 'aio-industry-badge aio-industry-badge--' . \sanitize_html_class( $aio_pb_badge_status !== '' ? $aio_pb_badge_status : 'neutral' );
?>
<span class="<?php echo \esc_attr( $aio_pb_badge_class ); ?>" title="<?php echo \esc_attr( $aio_pb_badge_title ); ?>" aria-label="<?php echo \esc_attr( $aio_pb_label !== '' ? $aio_pb_label : __( 'Industry fit', 'aio-page-builder' ) ); ?>">
	<?php echo $aio_pb_label !== '' ? \esc_html( $aio_pb_label ) : '—'; ?>
	<?php if ( $aio_pb_hierarchy_fit !== '' || $aio_pb_lpagery_fit !== '' ) : ?>
		<span class="aio-industry-badge-meta">
			<?php
			if ( $aio_pb_hierarchy_fit !== '' ) :
				?>
				<span class="aio-hierarchy-fit" title="<?php echo \esc_attr( $aio_pb_hierarchy_fit ); ?>">H</span><?php endif; ?>
			<?php
			if ( $aio_pb_lpagery_fit !== '' ) :
				?>
				<span class="aio-lpagery-fit" title="<?php echo \esc_attr( $aio_pb_lpagery_fit ); ?>">L</span><?php endif; ?>
		</span>
	<?php endif; ?>
</span>
