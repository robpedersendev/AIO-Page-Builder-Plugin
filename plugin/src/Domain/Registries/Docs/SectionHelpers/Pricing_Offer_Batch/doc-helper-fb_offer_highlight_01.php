<?php
/**
 * Section helper documentation: fb_offer_highlight_01 (Offer highlight). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_offer_highlight_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Single offer highlight with name, description, and optional CTA. Use for featured plan or offer. Best fit: pricing hero, featured plan, promo.</p><h3>User need</h3><p>Editors need to spotlight one offer clearly without competing with other CTAs on the page.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Featured offer", "Most popular").</li><li><strong>Offer name</strong> (required): Name of the plan or offer.</li><li><strong>Description</strong>: Short summary of what is included or why it matters. Be specific; avoid vague "Best value".</li><li><strong>CTA link</strong>: Optional single CTA. Use one primary action (e.g. "Get this plan"); label descriptively.</li></ul><h3>Clarity and offer framing</h3><p>Description should state what is included or the main benefit. If price is shown elsewhere, keep this section focused on value. Do not overclaim "best" or "most popular" without justification.</p><h3>GeneratePress / ACF / AIOSEO</h3><p>Section uses block structure. Offer name and description can support focus keyphrase. CTA link text descriptive.</p><h3>Tone and mistakes to avoid</h3><p>Clear, direct. Avoid multiple CTAs; avoid vague offer name or description that duplicates the headline.</p><h3>SEO and accessibility</h3><p>One section heading; CTA must have visible, descriptive text and sufficient contrast.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_offer_highlight_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
