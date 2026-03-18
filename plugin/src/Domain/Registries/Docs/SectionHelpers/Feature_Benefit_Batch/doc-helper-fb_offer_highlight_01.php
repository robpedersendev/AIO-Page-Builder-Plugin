<?php
/**
 * Section helper documentation: fb_offer_highlight_01 (Offer highlight). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_offer_highlight_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Single offer highlight with name, description, and optional CTA. Use for featured plan or offer.</p><h3>User need</h3><p>Editors need to spotlight one offer clearly without competing with other CTAs on the page.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Featured offer").</li><li><strong>Offer name</strong> (required): Name of the plan or offer.</li><li><strong>Description</strong>: Short summary of what is included or why it matters.</li><li><strong>CTA link</strong>: Optional single CTA. Use one primary action; label descriptively.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure. Use GeneratePress for container and emphasis (e.g. contained block).</p><h3>AIOSEO / FIFU</h3><p>Headline and offer name can support offer intent. CTA link text should be descriptive for SEO and a11y.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, direct tone. Avoid multiple CTAs, vague offer name, or description that duplicates the headline.</p><h3>SEO and accessibility</h3><p>One section heading; CTA must have visible, descriptive text and sufficient contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_offer_highlight_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
