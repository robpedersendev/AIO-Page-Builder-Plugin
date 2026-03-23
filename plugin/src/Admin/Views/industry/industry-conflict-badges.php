<?php
/**
 * Renders multi-industry conflict/warning badges (Prompt 372). Expects $aio_pb_conflict_results (array of Industry_Conflict_Result shapes) and optionally $aio_pb_explanation_summary.
 * Safe fallback when no conflict: outputs nothing.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$aio_pb_conflict_results    = isset( $aio_pb_conflict_results ) && is_array( $aio_pb_conflict_results ) ? $aio_pb_conflict_results : array();
$aio_pb_explanation_summary = isset( $aio_pb_explanation_summary ) && is_string( $aio_pb_explanation_summary ) ? trim( $aio_pb_explanation_summary ) : '';

$aio_pb_to_show = array();
foreach ( $aio_pb_conflict_results as $aio_pb_c ) {
	if ( ! is_array( $aio_pb_c ) ) {
		continue;
	}
	$aio_pb_severity = (string) ( $aio_pb_c['severity'] ?? 'info' );
	if ( in_array( $aio_pb_severity, array( 'warning_worthy', 'blocking', 'unresolved' ), true ) ) {
		$aio_pb_to_show[] = $aio_pb_c;
	}
}

if ( count( $aio_pb_to_show ) === 0 && $aio_pb_explanation_summary === '' ) {
	return;
}

$aio_pb_title_parts = array();
foreach ( $aio_pb_to_show as $aio_pb_c ) {
	$aio_pb_expl = (string) ( $aio_pb_c['explanation'] ?? '' );
	if ( $aio_pb_expl !== '' ) {
		$aio_pb_title_parts[] = $aio_pb_expl;
	}
}
if ( $aio_pb_explanation_summary !== '' ) {
	$aio_pb_title_parts[] = $aio_pb_explanation_summary;
}
$aio_pb_badge_title = implode( ' ', $aio_pb_title_parts );
?>
<span class="aio-industry-conflict-badge aio-industry-conflict-badge--warning" title="<?php echo \esc_attr( $aio_pb_badge_title ); ?>" aria-label="<?php \esc_attr_e( 'Multi-industry conflict; primary applied.', 'aio-page-builder' ); ?>"><?php \esc_html_e( 'Conflict', 'aio-page-builder' ); ?></span>
