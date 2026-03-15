<?php
/**
 * Section helper documentation: fb_package_summary_01 (Package summary). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'             => 'doc-helper-fb_package_summary_01',
	'documentation_type'           => 'section_helper',
	'content_body'                => '<h3>Purpose</h3><p>Summary of packages with name, highlights, optional price label, and CTA. Use for tier or bundle presentation.</p><h3>User need</h3><p>Editors need a clear way to present packages or tiers with consistent fields and one CTA per package.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Packages"). Keep short.</li><li><strong>Packages</strong> (repeater, required): Each package — <strong>Package name</strong> (required); <strong>Highlights</strong>: bullet or short list; <strong>Price or label</strong>: optional price or "Contact for price"; <strong>CTA link</strong>: optional. Keep highlights parallel across packages; avoid vague "Includes everything."</li></ul><h3>GeneratePress / ACF</h3><p>Section renders in block structure; repeater maps to package cards/columns. Use GeneratePress for grid and spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline may support pricing/package intent. CTAs need descriptive text.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, transparent tone. Avoid hidden conditions in highlights, misleading price labels, or multiple competing CTAs in one package.</p><h3>SEO and accessibility</h3><p>One section heading; structure each package with clear labels. CTA links must have visible, descriptive text and contrast.</p>',
	'status'                      => 'active',
	'source_reference'             => array( 'section_template_key' => 'fb_package_summary_01' ),
	'generated_or_human_edited'   => 'human_edited',
	'version_marker'              => '1',
	'export_metadata'             => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
