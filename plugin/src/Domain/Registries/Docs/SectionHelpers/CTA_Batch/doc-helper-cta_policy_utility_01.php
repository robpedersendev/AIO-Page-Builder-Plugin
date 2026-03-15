<?php
/**
 * Section helper documentation: cta_policy_utility_01 (Policy/utility CTA). Spec §15; documentation-object-schema.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	'documentation_id'   => 'doc-helper-cta_policy_utility_01',
	'documentation_type' => 'section_helper',
	'content_body'       => '<h3>Purpose</h3><p>CTA that drives users to policy or utility content (e.g. "Read privacy policy", "View terms", "Download guide"). Use for compliance or utility links.</p><h3>User need</h3><p>Editors need a block that converts to policy/utility pages or downloads.</p><h3>Field-by-field guidance</h3><ul><li><strong>Heading</strong>: (e.g. "Legal &amp; policies").</li><li><strong>Body</strong>: Optional support.</li><li><strong>Primary button</strong>: (e.g. "Read privacy policy", "View terms"). Describes the destination.</li></ul><h3>CTA-specific guidance</h3><p>Action language; user should understand they are going to policy or utility content. Avoid generic "Learn more".</p><h3>Tone and mistakes to avoid</h3><p>Neutral, clear. Do not use marketing tone for policy links.</p><h3>SEO and accessibility</h3><p>Button label describes destination. Contrast and focus order.</p>',
	'status'             => 'active',
	'source_reference'    => array( 'section_template_key' => 'cta_policy_utility_01' ),
	'generated_or_human_edited' => 'human_edited',
	'version_marker'      => '1',
	'export_metadata'     => array( 'export_category' => 'documentation', 'include_in_full_export' => true ),
);
