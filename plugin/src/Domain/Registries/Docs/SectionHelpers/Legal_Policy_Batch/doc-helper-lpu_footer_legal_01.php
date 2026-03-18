<?php
/**
 * Section helper documentation: lpu_footer_legal_01 (Footer legal strip). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-lpu_footer_legal_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Footer legal or copyright strip. Single text field; keep short. Use for copyright, short disclaimer, or legal line in footer. Not legal advice.</p><h3>User need</h3><p>Editors need a minimal block for one line or short paragraph of footer legal text.</p><h3>Field-by-field guidance</h3><ul><li><strong>Text</strong> (required): Copyright notice, short disclaimer, or similar. Keep concise. Replace sample with your actual text.</li></ul><h3>GeneratePress and accessibility</h3><p>Use small type if desired but ensure sufficient contrast. Do not embed critical legal text in images only. Logical order in footer.</p><h3>Practical notes</h3><p>This is not legal advice. Have footer text reviewed as needed. Safe failure: ensure text is never empty for published footer. For full policies use policy body and legal summary.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'lpu_footer_legal_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
