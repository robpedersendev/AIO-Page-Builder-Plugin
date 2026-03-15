<?php
/**
 * Plumber question pack (industry-question-pack-contract).
 * Storage: question_pack_answers[plumber][field_key].
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	'pack_id'      => 'plumber',
	'industry_key' => 'plumber',
	'name'         => 'Plumber',
	'intent'       => 'Gather plumbing business context: residential vs commercial, emergency vs scheduled.',
	'fields'       => array(
		array(
			'key'          => 'service_scope',
			'label'        => 'Service scope',
			'type'         => 'text',
			'help_text'    => 'Residential, commercial, or both. Informs service pages and content.',
		),
		array(
			'key'          => 'emergency_offered',
			'label'        => 'Emergency service offered',
			'type'         => 'boolean',
			'help_text'    => 'Whether you offer 24/7 or after-hours emergency calls. Drives CTA and contact prominence.',
		),
		array(
			'key'          => 'service_areas',
			'label'        => 'Service areas',
			'type'         => 'textarea',
			'help_text'    => 'Cities, counties, or regions you serve. Used for local and contact content.',
		),
	),
);
