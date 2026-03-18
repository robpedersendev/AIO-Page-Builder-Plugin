<?php
/**
 * Section helper documentation: hero_edu_01 (Hero educational). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_edu_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Educational intro hero for resource, how-to, or guide pages. Headline and explanatory subheadline set the topic; optional "Read more" CTA.</p><h3>User need</h3><p>Editors need a clear, informative opener that tells visitors what the page covers.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Topic or "How it works" style; clear and scannable.</li><li><strong>Subheadline</strong>: Short explanation of what the page covers.</li><li><strong>Eyebrow</strong>: Optional (e.g. "Guide").</li><li><strong>Primary CTA</strong>: Optional (e.g. "Read more"); use when you want to scroll or link to next section.</li></ul><h3>GeneratePress / ACF</h3><p>Map headline and subheadline; CTA optional. Keep subheadline readable length.</p><h3>AIOSEO / FIFU</h3><p>Align headline with target keywords for the resource. No hero image in this section.</p><h3>Tone and mistakes to avoid</h3><p>Clear, instructive tone. Avoid marketing hype in the subheadline; keep it educational.</p><h3>SEO and accessibility</h3><p>One primary heading; descriptive CTA if used. Ensure contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_edu_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
