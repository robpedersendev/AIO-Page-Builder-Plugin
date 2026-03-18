<?php
/**
 * Cosmetology / Nail question pack (industry-question-pack-contract).
 * Storage: question_pack_answers[cosmetology_nail][field_key].
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	'pack_id'      => 'cosmetology_nail',
	'industry_key' => 'cosmetology_nail',
	'name'         => 'Cosmetology / Nail',
	'intent'       => 'Gather cosmetology or nail business context for services, booking, and compliance.',
	'fields'       => array(
		array(
			'key'       => 'service_types',
			'label'     => 'Primary service types',
			'type'      => 'text',
			'help_text' => 'e.g. manicure, pedicure, nail art, gel, acrylic, facials, waxing. Used for service pages and recommendations.',
		),
		array(
			'key'       => 'booking_style',
			'label'     => 'Booking style',
			'type'      => 'text',
			'help_text' => 'Walk-in, appointment-only, or both. Informs CTA and contact flow.',
		),
		array(
			'key'       => 'license_notes',
			'label'     => 'License or compliance notes',
			'type'      => 'textarea',
			'help_text' => 'Optional: state license number, certifications, or compliance details for display or trust sections.',
		),
	),
);
