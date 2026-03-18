<?php
/**
 * Section helper documentation: tp_testimonial_01 (Testimonial cards). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_testimonial_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Repeatable testimonial cards with quote, name, role, and optional avatar. Use for customer or user proof on service, product, or landing pages.</p><h3>User need</h3><p>Editors need a block that presents multiple testimonials with clear attribution.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline (e.g. "What our customers say").</li><li><strong>Testimonials (repeater)</strong>: For each card: <strong>Quote</strong> (required)—keep authentic and specific; <strong>Name</strong> (required); <strong>Role / title</strong> (optional but recommended for credibility); <strong>Avatar image</strong> (optional)—use FIFU or ACF image; provide meaningful alt text or omit.</li></ul><h3>Credibility and proof quality</h3><p>Use real, attributable quotes. Avoid generic or fabricated testimonials. Specific outcomes or details build trust more than vague praise.</p><h3>Image handling</h3><p>Avatar images should be small and consistent in aspect ratio. Use alt text that describes the person (e.g. "Photo of Jane Smith") or leave empty if decorative.</p><h3>AIOSEO and accessibility</h3><p>Testimonials support review and entity signals; keep content factual. Quote attribution must be programmatically associated (e.g. cite element or aria). Do not rely on color alone for meaning; ensure sufficient contrast.</p><h3>Mistakes to avoid</h3><p>Do not use placeholder or synthetic quotes in production. Do not omit attribution. Do not overload with too many cards; quality over quantity.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_testimonial_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
