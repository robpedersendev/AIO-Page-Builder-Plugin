<?php
/**
 * Renders industry recommendation badge and tooltip for one page template (industry-admin-screen-contract).
 * Expects $item_view (Industry_Page_Template_Directory_Item_View) or $badge_data (array with recommendation_status, explanation_snippet, hierarchy_fit, lpagery_fit).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $item_view ) && ! isset( $badge_data ) ) {
	return;
}

$status        = '';
$snippet       = '';
$hierarchy_fit = '';
$lpagery_fit   = '';
if ( isset( $item_view ) && $item_view instanceof \AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Directory_Item_View ) {
	$status        = $item_view->get_recommendation_status();
	$snippet       = $item_view->get_explanation_snippet();
	$hierarchy_fit = $item_view->get_hierarchy_fit();
	$lpagery_fit   = $item_view->get_lpagery_fit();
} elseif ( isset( $badge_data ) && is_array( $badge_data ) ) {
	$status        = (string) ( $badge_data['recommendation_status'] ?? $badge_data['fit_classification'] ?? '' );
	$snippet       = (string) ( $badge_data['explanation_snippet'] ?? \implode( ', ', (array) ( $badge_data['explanation_reasons'] ?? array() ) ) );
	$hierarchy_fit = (string) ( $badge_data['hierarchy_fit'] ?? '' );
	$lpagery_fit   = (string) ( $badge_data['lpagery_fit'] ?? '' );
}

if ( $status === '' && $hierarchy_fit === '' && $lpagery_fit === '' ) {
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

$parts = array();
if ( $snippet !== '' ) {
	$parts[] = $snippet;
}
if ( $hierarchy_fit !== '' ) {
	$parts[] = sprintf( /* translators: %s: hierarchy fit note */ __( 'Hierarchy: %s', 'aio-page-builder' ), $hierarchy_fit );
}
if ( $lpagery_fit !== '' ) {
	$parts[] = sprintf( /* translators: %s: LPagery fit note */ __( 'LPagery: %s', 'aio-page-builder' ), $lpagery_fit );
}
$title = \implode( ' · ', $parts );
if ( $title === '' ) {
	$title = $label;
}
$class = 'aio-industry-badge aio-industry-badge--' . \sanitize_html_class( $status !== '' ? $status : 'neutral' );
?>
<span class="<?php echo \esc_attr( $class ); ?>" title="<?php echo \esc_attr( $title ); ?>" aria-label="<?php echo \esc_attr( $label !== '' ? $label : __( 'Industry fit', 'aio-page-builder' ) ); ?>">
	<?php echo $label !== '' ? \esc_html( $label ) : '—'; ?>
	<?php if ( $hierarchy_fit !== '' || $lpagery_fit !== '' ) : ?>
		<span class="aio-industry-badge-meta">
			<?php
			if ( $hierarchy_fit !== '' ) :
				?>
				<span class="aio-hierarchy-fit" title="<?php echo \esc_attr( $hierarchy_fit ); ?>">H</span><?php endif; ?>
			<?php
			if ( $lpagery_fit !== '' ) :
				?>
				<span class="aio-lpagery-fit" title="<?php echo \esc_attr( $lpagery_fit ); ?>">L</span><?php endif; ?>
		</span>
	<?php endif; ?>
</span>
