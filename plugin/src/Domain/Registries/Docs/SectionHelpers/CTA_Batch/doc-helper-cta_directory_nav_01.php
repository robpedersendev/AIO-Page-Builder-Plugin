<?php
/**
 * Section helper documentation: cta_directory_nav_01 (Directory nav CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-cta_directory_nav_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>CTA that drives users into the directory: browse, search, or navigate to a listing. Use on hub or landing pages that lead to directory content.</p><h3>User need</h3><p>Editors need a conversion block that moves users into the directory (e.g. "Browse directory", "Find a provider").</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Find what you need").</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: Directory action (e.g. "Browse directory", "View listings"). Must describe the destination.</li></ul><h3>CTA-specific guidance</h3><p>Use clear navigation language; user should understand they are going to the directory. Avoid generic "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Clear, navigational. Do not use the same CTA as a hero or other section.</p><h3>SEO and accessibility</h3><p>Button label describes destination. Contrast and focus order.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'cta_directory_nav_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
