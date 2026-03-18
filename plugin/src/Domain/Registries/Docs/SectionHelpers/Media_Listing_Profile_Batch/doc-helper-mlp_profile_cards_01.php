<?php
/**
 * Section helper documentation: mlp_profile_cards_01 (Profile cards). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_profile_cards_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Grid of profile cards with name, role, optional image and short bio. Use for team or member listing. Omit image when empty.</p><h3>User need</h3><p>Editors need consistent profile cards; empty image is omitted for clean layout.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Our team").</li><li><strong>Profiles</strong> (repeater, required): Each profile — <strong>Name</strong> (required); <strong>Role</strong>; <strong>Image</strong>: optional, ACF or FIFU with alt; <strong>Short bio</strong>: one line or short paragraph. Keep bio length consistent across cards.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders card grid; repeater maps to cards. Use GeneratePress for columns and spacing. Image optional; omit when empty.</p><h3>AIOSEO</h3><p>Headline and names support person/team signals.</p><h3>Consistency</h3><p>Use same fields (e.g. all with or all without image) where possible; parallel structure improves scannability.</p><h3>SEO and accessibility</h3><p>One section heading; each card should have clear structure. Image requires alt when present; omit node when empty.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_profile_cards_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
