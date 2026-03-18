<?php
/**
 * Section helper documentation: mlp_profile_summary_01 (Profile summary). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_profile_summary_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Single profile block with name, role, bio, optional image and link. Use for team or author detail. Omit image/link when empty.</p><h3>User need</h3><p>Editors need one focused profile with consistent fields; empty image/link are omitted.</p><h3>Field-by-field guidance</h3><ul><li><strong>Name</strong> (required): Full name or display name.</li><li><strong>Role / title</strong>: Job title or role. Keep short.</li><li><strong>Bio</strong>: Short biography. No long paragraphs; suitable for profile card.</li><li><strong>Image</strong>: ACF image or FIFU. Use when available; provide descriptive alt (e.g. name + role). Omit when empty.</li><li><strong>Link</strong>: Optional link to profile page or external. Omit when empty.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders single profile block. Image supports ACF/FIFU; ensure alt. Empty image/link not rendered.</p><h3>AIOSEO</h3><p>Name and role support person/author signals. Link text descriptive if used.</p><h3>Consistency and tone</h3><p>Keep bio length and tone consistent with other profiles on site. Avoid placeholder or duplicate bios.</p><h3>SEO and accessibility</h3><p>Profile should have one logical heading (e.g. name as heading). Image must have alt; omit when empty. Link needs visible text.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_profile_summary_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
