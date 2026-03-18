<?php
/**
 * Renders multi-industry conflict/warning badges (Prompt 372). Expects $conflict_results (array of Industry_Conflict_Result shapes) and optionally $explanation_summary.
 * Safe fallback when no conflict: outputs nothing.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

$conflict_results    = isset( $conflict_results ) && is_array( $conflict_results ) ? $conflict_results : array();
$explanation_summary = isset( $explanation_summary ) && is_string( $explanation_summary ) ? trim( $explanation_summary ) : '';

$to_show = array();
foreach ( $conflict_results as $c ) {
	if ( ! is_array( $c ) ) {
		continue;
	}
	$severity = (string) ( $c['severity'] ?? 'info' );
	if ( in_array( $severity, array( 'warning_worthy', 'blocking', 'unresolved' ), true ) ) {
		$to_show[] = $c;
	}
}

if ( count( $to_show ) === 0 && $explanation_summary === '' ) {
	return;
}

$title_parts = array();
foreach ( $to_show as $c ) {
	$expl = (string) ( $c['explanation'] ?? '' );
	if ( $expl !== '' ) {
		$title_parts[] = $expl;
	}
}
if ( $explanation_summary !== '' ) {
	$title_parts[] = $explanation_summary;
}
$title = implode( ' ', $title_parts );
?>
<span class="aio-industry-conflict-badge aio-industry-conflict-badge--warning" title="<?php echo \esc_attr( $title ); ?>" aria-label="<?php \esc_attr_e( 'Multi-industry conflict; primary applied.', 'aio-page-builder' ); ?>"><?php \esc_html_e( 'Conflict', 'aio-page-builder' ); ?></span>
