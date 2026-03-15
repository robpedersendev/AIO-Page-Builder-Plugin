<?php
/**
 * Section helper documentation: tp_outcome_01 (Outcome stats). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_outcome_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Outcome-focused stat block: headline and repeatable label/value/suffix. Use for results or proof metrics on landing or service pages.</p><h3>User need</h3><p>Editors need a block to present key metrics (e.g. "99% satisfaction", "500+ projects") in a scannable format.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline.</li><li><strong>Stat items (repeater)</strong>: <strong>Label</strong> (required)—what the stat measures; <strong>Value</strong> (required)—number or short text; <strong>Suffix</strong> (optional)—e.g. "%", "+", "k".</li></ul><h3>Credibility and proof quality</h3><p>Use accurate, verifiable metrics. Define time period or scope where relevant (e.g. "in 2024"). Avoid misleading or cherry-picked stats.</p><h3>Accessibility</h3><p>Ensure numbers and labels are in text, not only in images. Screen readers should get full context (e.g. "99 percent satisfaction").</p><h3>Mistakes to avoid</h3><p>Do not inflate or round in a misleading way. Do not use stats you cannot substantiate.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_outcome_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
