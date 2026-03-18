<?php
/**
 * Section helper documentation: cta_service_detail_01 (Service detail CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-cta_service_detail_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>CTA that drives users into service detail (e.g. "View service", "Learn about this service"). Use on hub or listing pages that link to service detail pages.</p><h3>User need</h3><p>Editors need a block that converts to service detail view or inquiry.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: Service or benefit.</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: (e.g. "View service", "See details"). Describes the destination.</li></ul><h3>CTA-specific guidance</h3><p>Action language; user should understand they are going to service detail. Avoid generic "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Clear, service-focused. Do not repeat the same CTA from hero or other sections.</p><h3>SEO and accessibility</h3><p>Button label describes destination. Contrast and focus order.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'cta_service_detail_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
