<?php
/**
 * Section helper documentation: mlp_team_grid_01 (Team grid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_team_grid_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Team or member grid with name, role, optional image and short bio. Use for team or staff listing. Omit image/bio when empty.</p><h3>User need</h3><p>Editors need consistent team cards; empty image or bio omitted for clean layout.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Our team").</li><li><strong>Members</strong> (repeater, required): Each member — <strong>Name</strong> (required); <strong>Role</strong>; <strong>Image</strong>: optional, ACF or FIFU with alt; <strong>Short bio</strong>: optional. Keep bio length consistent; omit image when not available.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders team grid; repeater maps to cards. Use GeneratePress for columns and spacing. Image optional; omit when empty.</p><h3>AIOSEO</h3><p>Headline and names support team/person signals.</p><h3>Consistency</h3><p>Use parallel structure (e.g. all with or all without image). Avoid placeholder bios or duplicate role text.</p><h3>SEO and accessibility</h3><p>One section heading; each card has clear structure. Image requires alt when present; omit node when empty.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_team_grid_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
