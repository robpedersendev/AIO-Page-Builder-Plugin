<?php
/**
 * Section helper documentation: tp_client_logo_01 (Client / partner logos). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_client_logo_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Logo band: headline and repeatable logo image with optional name for accessibility. Use for client or partner proof on homepage or service pages.</p><h3>User need</h3><p>Editors need a block to show client or partner logos with clear attribution.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Trusted by").</li><li><strong>Logos (repeater)</strong>: <strong>Logo image</strong> (required)—use ACF image or FIFU; <strong>Company name (for alt text)</strong> (optional but recommended)—use for alt so the logo is accessible (e.g. "Acme Corp").</li></ul><h3>Credibility and proof quality</h3><p>Use logos only with permission. Represent real client or partner relationships. Avoid generic or stock logos that imply false endorsement.</p><h3>Image handling and accessibility</h3><p>Always provide company name or equivalent alt text so logo meaning is available to screen readers. Do not use "logo" alone; identify the organization. FIFU/ACF: set alt per image when possible.</p><h3>Mistakes to avoid</h3><p>Do not use client logos without permission. Do not leave alt empty when the logo conveys meaning (e.g. who you work with).</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_client_logo_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
