<?php
/**
 * Section helper documentation: tp_badge_01 (Badge / certification). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-tp_badge_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Badge or certification block: repeatable name, optional image, optional description. Use for awards or certifications on about or service pages.</p><h3>User need</h3><p>Editors need a block to display badge/certification visuals with supporting text.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional (e.g. "Certifications").</li><li><strong>Badges (repeater)</strong>: <strong>Name</strong> (required)—badge or cert name; <strong>Image</strong> (optional)—badge graphic (use FIFU/ACF; provide meaningful alt); <strong>Description</strong> (optional)—short explanation.</li></ul><h3>Credibility and proof quality</h3><p>Use only real, current badges or certifications. Images should match the named credential. Avoid generic or misleading graphics.</p><h3>Image handling and accessibility</h3><p>Alt text for badge images should describe the credential (e.g. "ISO 9001 certification badge") or be empty if adjacent text conveys the same. Do not rely on color alone.</p><h3>Mistakes to avoid</h3><p>Do not use expired or irrelevant badges. Do not imply endorsement without permission.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'tp_badge_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
