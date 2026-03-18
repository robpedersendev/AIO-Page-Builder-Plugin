<?php
/**
 * Section helper documentation: mlp_card_grid_01 (Card grid). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-mlp_card_grid_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Grid of cards with title, optional description, image, and link. Use for directory, product, or service cards. Omit image/link when empty.</p><h3>User need</h3><p>Editors need consistent card structure with optional media and links; rendering omits empty fields.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Optional section headline.</li><li><strong>Cards</strong> (repeater, required): Each card — <strong>Title</strong> (required); <strong>Description</strong>: optional; <strong>Image</strong>: ACF image or FIFU; <strong>Link</strong>: optional. Use image when it adds value; provide descriptive alt. Omit image/link when not used. Keep card content parallel across items.</li></ul><h3>GeneratePress / ACF / FIFU</h3><p>Section renders in block grid; repeater maps to cards. Use GeneratePress for column count and spacing. Image supports ACF image or FIFU; ensure alt text. Empty image/link are omitted in output.</p><h3>AIOSEO</h3><p>Headline and card titles support entity signals. Link text should be descriptive.</p><h3>Tone and consistency</h3><p>Keep card titles and description length consistent where possible. Avoid mixed empty/full image usage without reason; use same structure across cards.</p><h3>SEO and accessibility</h3><p>One section heading; grid/list semantics. Images must have alt; omit image node when empty. Links need visible, descriptive text and contrast.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'mlp_card_grid_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
