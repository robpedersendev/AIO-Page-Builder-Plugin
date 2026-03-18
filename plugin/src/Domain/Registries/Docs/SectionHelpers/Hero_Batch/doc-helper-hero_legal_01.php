<?php
/**
 * Section helper documentation: hero_legal_01 (Hero legal / trust intro). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'          => 'doc-helper-hero_legal_01',
	'documentation_type'        => 'section_helper',
	'content_body'              => '<h3>Purpose</h3><p>Minimal hero for legal, privacy, terms, or policy pages. Headline and short subheadline only; no strong CTA. Keeps the opener clear and formal.</p><h3>User need</h3><p>Editors need a simple, non-promotional opener for compliance and policy content.</p><h3>Field-by-field guidance</h3><ul><li><strong>Headline</strong>: Document type (e.g. "Legal information", "Privacy Policy").</li><li><strong>Subheadline</strong>: Brief instruction (e.g. "Please read the following").</li><li><strong>Eyebrow</strong>: Usually empty for legal.</li><li><strong>Primary CTA / Secondary link</strong>: Typically left empty; avoid marketing CTAs on legal pages.</li></ul><h3>GeneratePress / ACF</h3><p>Map headline and subheadline only. Use restrained layout and typography.</p><h3>AIOSEO / FIFU</h3><p>Headline can match page title for legal documents. No image needed.</p><h3>Tone and mistakes to avoid</h3><p>Neutral, formal tone. Do not add promotional copy or CTAs that distract from the legal content.</p><h3>SEO and accessibility</h3><p>One primary heading; ensure contrast and readability for long-form content below.</p>',
	'status'                    => 'active',
	'source_reference'          => array( 'section_template_key' => 'hero_legal_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'            => '1',
	'export_metadata'           => array(
		'export_category'        => 'documentation',
		'include_in_full_export' => true,
	),
);
