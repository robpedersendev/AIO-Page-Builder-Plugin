<?php
/**
 * Section helper documentation: cta_purchase_02 (Purchase CTA variant). Spec §15; documentation-object-schema.
 * Contact/Form/Conversion batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_purchase_02',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Purchase CTA variant with optional secondary and trust line. Use when you want supporting copy or reassurance around purchase.</p><h3>User need</h3><p>Editors need a purchase block with room for body and trust line.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong> (required): Purchase invite.</li><li><strong>Body</strong>: Optional; use for delivery, guarantee, or next step.</li><li><strong>Primary button label</strong> (required) / <strong>Primary button link</strong>: Purchase action.</li><li><strong>Trust line</strong>: Optional (e.g. "Secure checkout", "Free shipping over $50"). Must be accurate.</li><li>Omit secondary, image when empty.</li></ul><h3>Conversion and friction</h3><p>Primary action must be the main purchase path. Trust line should reduce friction without overclaiming.</p><h3>Tone and mistakes to avoid</h3><p>Clear, confident. Do not promise what you cannot deliver (e.g. shipping, guarantee).</p><h3>SEO and accessibility</h3><p>Button and link text descriptive.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_purchase_02' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
