<?php
/**
 * Renders industry-aware rationale and warnings for one Build Plan item (Prompt 365).
 * Expects $aio_pb_view_model from Industry_Build_Plan_Explanation_View_Model::from_item_payload().
 * Renders nothing when has_industry_data is false (generic fallback).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $aio_pb_view_model ) || ! is_array( $aio_pb_view_model ) || empty( $aio_pb_view_model['has_industry_data'] ) ) {
	return;
}

$aio_pb_summary_lines      = $aio_pb_view_model['summary_lines'] ?? array();
$aio_pb_warning_badges     = $aio_pb_view_model['warning_badges'] ?? array();
$aio_pb_fit_classification = (string) ( $aio_pb_view_model['fit_classification'] ?? 'neutral' );
$aio_pb_source_refs        = $aio_pb_view_model['source_refs'] ?? array();

$aio_pb_fit_label = $aio_pb_fit_classification;
if ( $aio_pb_fit_classification === 'recommended' ) {
	$aio_pb_fit_label = __( 'Recommended', 'aio-page-builder' );
} elseif ( $aio_pb_fit_classification === 'allowed_weak_fit' || $aio_pb_fit_classification === 'weak_fit' ) {
	$aio_pb_fit_label = __( 'Weak fit', 'aio-page-builder' );
} elseif ( $aio_pb_fit_classification === 'discouraged' ) {
	$aio_pb_fit_label = __( 'Discouraged', 'aio-page-builder' );
} else {
	$aio_pb_fit_label = __( 'Neutral', 'aio-page-builder' );
}
?>
<div class="aio-detail-section aio-detail-section-industry-explanation">
	<h4 class="aio-detail-section-heading"><?php \esc_html_e( 'Industry context', 'aio-page-builder' ); ?></h4>
	<div class="aio-detail-section-body">
		<?php if ( $aio_pb_fit_classification !== 'neutral' ) : ?>
			<p class="aio-industry-fit-badge-wrap">
				<span class="aio-industry-fit-badge aio-industry-fit-badge--<?php echo \esc_attr( \sanitize_html_class( $aio_pb_fit_classification ) ); ?>" aria-label="<?php echo \esc_attr( $aio_pb_fit_label ); ?>">
					<?php echo \esc_html( $aio_pb_fit_label ); ?>
				</span>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $aio_pb_summary_lines ) ) : ?>
			<ul class="aio-industry-summary-lines">
				<?php foreach ( $aio_pb_summary_lines as $aio_pb_line ) : ?>
					<li><?php echo \esc_html( $aio_pb_line ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( ! empty( $aio_pb_warning_badges ) ) : ?>
			<div class="aio-industry-warning-badges" role="group" aria-label="<?php \esc_attr_e( 'Industry warnings', 'aio-page-builder' ); ?>">
				<?php foreach ( $aio_pb_warning_badges as $aio_pb_badge ) : ?>
					<span class="aio-industry-warning-badge aio-industry-warning-badge--<?php echo \esc_attr( \sanitize_html_class( $aio_pb_badge['code'] ?? '' ) ); ?>" title="<?php echo \esc_attr( $aio_pb_badge['label'] ?? '' ); ?>">
						<?php echo \esc_html( $aio_pb_badge['label'] ?? '' ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $aio_pb_source_refs ) ) : ?>
			<p class="aio-industry-source-refs description">
				<?php \esc_html_e( 'Industry sources:', 'aio-page-builder' ); ?>
				<?php echo \esc_html( implode( ', ', $aio_pb_source_refs ) ); ?>
			</p>
		<?php endif; ?>
		<?php
		$aio_pb_conflict_results    = $aio_pb_view_model['conflict_results'] ?? array();
		$aio_pb_explanation_summary = (string) ( $aio_pb_view_model['explanation_summary'] ?? '' );
		if ( ! empty( $aio_pb_conflict_results ) || $aio_pb_explanation_summary !== '' ) :
			?>
			<div class="aio-industry-conflict-wrap">
				<?php require \dirname( __DIR__ ) . '/industry/industry-conflict-badges.php'; ?>
			</div>
		<?php endif; ?>
		<?php
		$aio_pb_compliance_cautions = $aio_pb_view_model['compliance_cautions'] ?? array();
		if ( ! empty( $aio_pb_compliance_cautions ) && is_array( $aio_pb_compliance_cautions ) ) :
			?>
			<div class="aio-industry-compliance-cautions" role="group" aria-label="<?php \esc_attr_e( 'Advisory compliance notes', 'aio-page-builder' ); ?>">
				<p class="aio-industry-compliance-cautions-intro description"><?php \esc_html_e( 'Advisory:', 'aio-page-builder' ); ?></p>
				<ul class="aio-industry-compliance-cautions-list">
					<?php foreach ( $aio_pb_compliance_cautions as $aio_pb_c ) : ?>
						<?php
						$aio_pb_summary  = isset( $aio_pb_c['caution_summary'] ) && is_string( $aio_pb_c['caution_summary'] ) ? $aio_pb_c['caution_summary'] : '';
						$aio_pb_severity = isset( $aio_pb_c['severity'] ) && is_string( $aio_pb_c['severity'] ) ? $aio_pb_c['severity'] : 'info';
						if ( $aio_pb_summary === '' ) {
							continue;
						}
						?>
						<li class="aio-industry-compliance-caution aio-industry-compliance-caution--<?php echo \esc_attr( \sanitize_html_class( $aio_pb_severity ) ); ?>"><?php echo \esc_html( $aio_pb_summary ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
