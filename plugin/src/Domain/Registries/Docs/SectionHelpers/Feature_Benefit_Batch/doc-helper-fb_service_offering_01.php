<?php
/**
 * Section helper documentation: fb_service_offering_01 (Service offering layout). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-fb_service_offering_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Service offerings with name, description, and optional link. Use for service hub or location pages.</p><h3>User need</h3><p>Editors need a consistent way to list services with one optional link per service for navigation.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Our services").</li><li><strong>Services</strong> (repeater, required): Each row — <strong>Service name</strong> (required); <strong>Description</strong>: brief summary; <strong>Link</strong>: optional link to detail page. Keep descriptions parallel; use link only when a dedicated page exists.</li></ul><h3>GeneratePress / ACF</h3><p>Section uses block structure; repeater maps to list or cards. Use GeneratePress for grid and spacing.</p><h3>AIOSEO / FIFU</h3><p>Headline and service names support service entity signals. Links should have descriptive text.</p><h3>Tone and mistakes to avoid</h3><p>Use clear, service-focused tone. Avoid generic "Learn more" links, duplicate service names, or long descriptions that belong on detail pages.</p><h3>SEO and accessibility</h3><p>One section heading; list or grid semantics. Links must have visible, descriptive text and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'fb_service_offering_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
