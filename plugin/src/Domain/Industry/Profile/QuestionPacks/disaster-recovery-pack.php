<?php
/**
 * Disaster Recovery question pack (industry-question-pack-contract).
 * Storage: question_pack_answers[disaster_recovery][field_key].
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	'pack_id'      => 'disaster_recovery',
	'industry_key' => 'disaster_recovery',
	'name'         => 'Disaster Recovery',
	'intent'       => 'Gather disaster recovery / restoration context: response type and scope.',
	'fields'       => array(
		array(
			'key'          => 'response_type',
			'label'        => 'Response type',
			'type'         => 'text',
			'help_text'    => 'Water, fire, mold, storm, or other. Used for service and emergency messaging.',
		),
		array(
			'key'          => 'emergency_24_7',
			'label'        => '24/7 emergency response',
			'type'         => 'boolean',
			'help_text'    => 'Whether you offer round-the-clock emergency dispatch. Informs CTAs and contact placement.',
		),
		array(
			'key'          => 'coverage_areas',
			'label'        => 'Coverage areas',
			'type'         => 'textarea',
			'help_text'    => 'Regions or territories you serve. Used for local and contact content.',
		),
	),
);
