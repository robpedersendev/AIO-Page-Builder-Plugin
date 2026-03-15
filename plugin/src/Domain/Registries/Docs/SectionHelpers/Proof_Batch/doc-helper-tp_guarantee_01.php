<?php
/**
 * Section helper documentation: tp_guarantee_01 (Guarantee band). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-tp_guarantee_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>Guarantee or promise block with headline and supporting text. Use for satisfaction or service guarantees on product or service pages.</p><h3>User need</h3><p>Editors need a clear block to state guarantees that reduce perceived risk.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong> (required): Short guarantee title (e.g. "Our guarantee").</li><li><strong>Guarantee text</strong> (required): Clear description of what is guaranteed and any conditions. Use plain language.</li><li><strong>Optional badge text</strong>: Short badge label if the layout supports it (e.g. "100%"). Omit when empty.</li></ul><h3>Credibility and proof quality</h3><p>State only guarantees you can honor. Avoid vague or legally unenforceable language. This is not legal advice—ensure your actual terms are reviewed by legal counsel.</p><h3>AIOSEO and accessibility</h3><p>Guarantee content can support trust signals. Ensure text is readable and not embedded in images. Sufficient contrast.</p><h3>Mistakes to avoid</h3><p>Do not overpromise. Do not use this section for legal terms; use dedicated legal sections for full policy language.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'tp_guarantee_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
