<?php
/**
 * Section helper documentation: gc_offer_pricing_01 (Offer pricing summary). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-gc_offer_pricing_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Pricing or package summary for offer pages. Headline and key points. Use to state pricing clearly and responsibly.</p><h3>User need</h3><p>Editors need a block that presents pricing or key price points without hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Pricing headline (e.g. "Pricing", "Plans and pricing"). Align with page focus.</li><li><strong>Body</strong>: Key points (what is included, from-price, or "Contact for quote" with clarity). No raw HTML. Be transparent about conditions (e.g. "From $X/month").</li></ul><h3>Clarity and transparency</h3><p>State what the price includes; avoid "From" without minimum clarity. If "Contact for price", say what the quote covers. Do not hide mandatory fees.</p><h3>GeneratePress / ACF</h3><p>Block structure; headline and body map to ACF. Use GeneratePress for spacing.</p><h3>Tone and mistakes to avoid</h3><p>Clear, transparent. Avoid misleading "from" or vague conditions.</p><h3>SEO and accessibility</h3><p>One heading; body as paragraphs; contrast and order.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'gc_offer_pricing_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
