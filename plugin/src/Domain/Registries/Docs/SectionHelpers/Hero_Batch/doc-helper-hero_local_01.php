<?php
/**
 * Section helper documentation: hero_local_01 (Hero local / service intro). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-hero_local_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Local or service-area hero for location pages, regional hubs, or "Serving your area" openers. Headline and subheadline with optional CTA (e.g. Find a location).</p><h3>User need</h3><p>Editors need a geographically or service-area focused opener.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Local value (e.g. "Serving your area").</li><li><strong>Subheadline</strong>: Brief support (e.g. "We are here to help").</li><li><strong>Eyebrow</strong>: Optional (e.g. "Local").</li><li><strong>Primary CTA</strong>: Location-focused action (e.g. Find a location, View map).</li></ul><h3>GeneratePress / ACF</h3><p>Map fields to hero block. Use container for width and spacing.</p><h3>AIOSEO / FIFU</h3><p>Local SEO: align headline with location intent. No image field; use hero_media_01 for location image if needed.</p><h3>Tone and mistakes to avoid</h3><p>Friendly, local tone. Avoid generic "Welcome"; be specific to area or service.</p><h3>SEO and accessibility</h3><p>One primary heading; descriptive CTA (e.g. "Find a location" not "Click here").</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'hero_local_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
