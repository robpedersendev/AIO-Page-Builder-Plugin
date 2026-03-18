<?php
/**
 * Section helper documentation: tp_partner_01 (Partner proof). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_partner_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Partner list with name, optional logo, optional link. Use for partner or channel proof on about or ecosystem pages.</p><h3>User need</h3><p>Editors need a block to list partners with optional visuals and links.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Our partners").</li><li><strong>Partners (repeater)</strong>: <strong>Partner name</strong> (required); <strong>Logo</strong> (optional)—use alt text for accessibility; <strong>Link</strong> (optional)—to partner site or profile. Use descriptive link text.</li></ul><h3>Credibility and proof quality</h3><p>List only real, current partnerships. Use logos and links only with permission. Avoid implying endorsement beyond the actual relationship.</p><h3>Image and accessibility</h3><p>Logo alt should identify the partner (e.g. "Acme Inc. logo"). Link text must describe destination, not "here" or "link".</p><h3>Mistakes to avoid</h3><p>Do not use partner logos without permission. Do not list former or inactive partners as current.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_partner_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
