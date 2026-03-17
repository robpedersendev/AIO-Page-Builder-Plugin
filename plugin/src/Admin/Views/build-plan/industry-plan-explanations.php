<?php
/**
 * Renders industry-aware rationale and warnings for one Build Plan item (Prompt 365).
 * Expects $view_model from Industry_Build_Plan_Explanation_View_Model::from_item_payload().
 * Renders nothing when has_industry_data is false (generic fallback).
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $view_model ) || ! is_array( $view_model ) || empty( $view_model['has_industry_data'] ) ) {
	return;
}

$summary_lines      = $view_model['summary_lines'] ?? array();
$warning_badges     = $view_model['warning_badges'] ?? array();
$fit_classification = (string) ( $view_model['fit_classification'] ?? 'neutral' );
$source_refs        = $view_model['source_refs'] ?? array();

$fit_label = $fit_classification;
if ( $fit_classification === 'recommended' ) {
	$fit_label = __( 'Recommended', 'aio-page-builder' );
} elseif ( $fit_classification === 'allowed_weak_fit' || $fit_classification === 'weak_fit' ) {
	$fit_label = __( 'Weak fit', 'aio-page-builder' );
} elseif ( $fit_classification === 'discouraged' ) {
	$fit_label = __( 'Discouraged', 'aio-page-builder' );
} else {
	$fit_label = __( 'Neutral', 'aio-page-builder' );
}
?>
<div class="aio-detail-section aio-detail-section-industry-explanation">
	<h4 class="aio-detail-section-heading"><?php \esc_html_e( 'Industry context', 'aio-page-builder' ); ?></h4>
	<div class="aio-detail-section-body">
		<?php if ( $fit_classification !== 'neutral' ) : ?>
			<p class="aio-industry-fit-badge-wrap">
				<span class="aio-industry-fit-badge aio-industry-fit-badge--<?php echo \esc_attr( \sanitize_html_class( $fit_classification ) ); ?>" aria-label="<?php echo \esc_attr( $fit_label ); ?>">
					<?php echo \esc_html( $fit_label ); ?>
				</span>
			</p>
		<?php endif; ?>
		<?php if ( ! empty( $summary_lines ) ) : ?>
			<ul class="aio-industry-summary-lines">
				<?php foreach ( $summary_lines as $line ) : ?>
					<li><?php echo \esc_html( $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( ! empty( $warning_badges ) ) : ?>
			<div class="aio-industry-warning-badges" role="group" aria-label="<?php \esc_attr_e( 'Industry warnings', 'aio-page-builder' ); ?>">
				<?php foreach ( $warning_badges as $badge ) : ?>
					<span class="aio-industry-warning-badge aio-industry-warning-badge--<?php echo \esc_attr( \sanitize_html_class( $badge['code'] ?? '' ) ); ?>" title="<?php echo \esc_attr( $badge['label'] ?? '' ); ?>">
						<?php echo \esc_html( $badge['label'] ?? '' ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $source_refs ) ) : ?>
			<p class="aio-industry-source-refs description">
				<?php \esc_html_e( 'Industry sources:', 'aio-page-builder' ); ?>
				<?php echo \esc_html( implode( ', ', $source_refs ) ); ?>
			</p>
		<?php endif; ?>
		<?php
		$conflict_results = $view_model['conflict_results'] ?? array();
		$explanation_summary = (string) ( $view_model['explanation_summary'] ?? '' );
		if ( ! empty( $conflict_results ) || $explanation_summary !== '' ) :
			?>
			<div class="aio-industry-conflict-wrap">
				<?php require \dirname( __DIR__ ) . '/industry/industry-conflict-badges.php'; ?>
			</div>
		<?php endif; ?>
		<?php
		$compliance_cautions = $view_model['compliance_cautions'] ?? array();
		if ( ! empty( $compliance_cautions ) && is_array( $compliance_cautions ) ) :
			?>
			<div class="aio-industry-compliance-cautions" role="group" aria-label="<?php \esc_attr_e( 'Advisory compliance notes', 'aio-page-builder' ); ?>">
				<p class="aio-industry-compliance-cautions-intro description"><?php \esc_html_e( 'Advisory:', 'aio-page-builder' ); ?></p>
				<ul class="aio-industry-compliance-cautions-list">
					<?php foreach ( $compliance_cautions as $c ) : ?>
						<?php
						$summary = isset( $c['caution_summary'] ) && is_string( $c['caution_summary'] ) ? $c['caution_summary'] : '';
						$severity = isset( $c['severity'] ) && is_string( $c['severity'] ) ? $c['severity'] : 'info';
						if ( $summary === '' ) {
							continue;
						}
						?>
						<li class="aio-industry-compliance-caution aio-industry-compliance-caution--<?php echo \esc_attr( \sanitize_html_class( $severity ) ); ?>"><?php echo \esc_html( $summary ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</div>
