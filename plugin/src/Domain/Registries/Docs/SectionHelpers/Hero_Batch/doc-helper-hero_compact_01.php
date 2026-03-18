<?php
/**
 * Section helper documentation: hero_compact_01 (Hero compact). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_compact_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Compact hero with reduced height for dense layouts or secondary pages. One short headline and optional primary CTA; variants include Tighter for minimal padding.</p><h3>User need</h3><p>Editors need a smaller hero when the page has many sections or a sub-page feel.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Short headline only; avoid long subheadlines.</li><li><strong>Subheadline</strong>: Often empty for compact; use sparingly.</li><li><strong>Eyebrow</strong>: Optional; leave empty to save space.</li><li><strong>Primary CTA</strong>: One clear action (e.g. "Go", "Next").</li></ul><h3>GeneratePress / ACF</h3><p>Map fields; use variant "Tighter" in section settings if you need minimal padding. GeneratePress container controls overall density.</p><h3>AIOSEO / FIFU</h3><p>Headline can support page focus. No image field in this section.</p><h3>Tone and mistakes to avoid</h3><p>Concise tone. Do not overload with copy or multiple CTAs; keep it compact.</p><h3>SEO and accessibility</h3><p>One primary heading; CTA with clear label. Sufficient contrast in compact layout.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_compact_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
