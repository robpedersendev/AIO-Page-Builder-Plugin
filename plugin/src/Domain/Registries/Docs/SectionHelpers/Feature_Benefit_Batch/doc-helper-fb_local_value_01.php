<?php
/**
 * Section helper documentation: fb_local_value_01 (Local / service value). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_local_value_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Value points for local or service area pages. Use for location-specific value messaging.</p><h3>User need</h3><p>Editors need to state local or service-area value without generic or duplicate content.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Value in your area").</li><li><strong>Value points</strong> (repeater, required): Each row — <strong>Point text</strong> (required): one value point per item. Keep location- or area-specific; avoid generic "We serve you" phrases.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list or band. Use GeneratePress for spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline and points can support local SEO intent. No image field.</p><h3>Tone and mistakes to avoid</h3><p>Use local, specific tone. Avoid vague local claims, duplicate value from other sections, or points that are not area-specific.</p><h3>SEO and accessibility</h3><p>One section heading; semantic list for value points. Ensure contrast and clarity.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_local_value_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
