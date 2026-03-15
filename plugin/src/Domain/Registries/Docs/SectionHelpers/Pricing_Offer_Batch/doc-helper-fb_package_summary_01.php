<?php
/**
 * Section helper documentation: fb_package_summary_01 (Package summary). Spec §15; documentation-object-schema.
 * Pricing/Offer batch.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_package_summary_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Summary of packages with name, highlights, optional price label, and CTA. Use for tier or bundle presentation. Best fit: pricing, bundles, tiers.</p><h3>User need</h3><p>Editors need to present packages clearly with consistent fields and one CTA per package; avoid hidden ambiguity.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Packages", "Plans"). Keep short.</li><li><strong>Packages</strong> (repeater, required): Each package — <strong>Package name</strong> (required); <strong>Highlights</strong>: bullet or short list of what is included; <strong>Price or label</strong>: optional price or "Contact for price"; <strong>CTA link</strong>: optional. Keep highlights parallel; be specific (e.g. "10 users" not "Scalable"). Price label must be accurate (e.g. "From $X" if minimum).</li></ul><h3>Clarity and offer framing</h3><p>Use the same highlight categories across packages where possible. Avoid "Includes everything" without listing. If using "Contact for price", clarify what the quote covers. Do not hide mandatory fees or conditions in body copy.</p><h3>GeneratePress / ACF / AIOSEO</h3><p>Section uses block structure; repeater maps to package cards. Price labels can support schema when consistent. No raw HTML.</p><h3>Tone and mistakes to avoid</h3><p>Clear, transparent. Avoid misleading price labels; avoid multiple competing CTAs per package; avoid vague highlights.</p><h3>SEO and accessibility</h3><p>One section heading; each package has clear structure. CTA links need visible, descriptive text.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_package_summary_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
