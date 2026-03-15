<?php
/**
 * Default question pack definitions for supported industries (industry-question-pack-contract).
 * Cosmetology/nail, realtor, plumber, disaster recovery.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Built-in question pack definitions for first supported industries (industry-question-pack-contract).
 */
final class Industry_Question_Pack_Definitions {

	/**
	 * Returns built-in pack definitions for cosmetology_nail, realtor, plumber, disaster_recovery.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_packs(): array {
		return array(
			array(
				'pack_id'      => 'cosmetology_nail',
				'industry_key' => 'cosmetology_nail',
				'name'         => __( 'Cosmetology / Nail', 'aio-page-builder' ),
				'intent'       => __( 'Gather cosmetology or nail business context for services, booking, and compliance.', 'aio-page-builder' ),
				'fields'       => array(
					array( 'key' => 'service_types', 'label' => __( 'Primary service types', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'booking_style', 'label' => __( 'Booking style (walk-in, appointment, both)', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'license_notes', 'label' => __( 'License or compliance notes', 'aio-page-builder' ), 'type' => 'textarea' ),
				),
			),
			array(
				'pack_id'      => 'realtor',
				'industry_key' => 'realtor',
				'name'         => __( 'Realtor', 'aio-page-builder' ),
				'intent'       => __( 'Gather real estate agent context: market focus, listing types, and geography.', 'aio-page-builder' ),
				'fields'       => array(
					array( 'key' => 'market_focus', 'label' => __( 'Market focus (residential, commercial, both)', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'listing_types', 'label' => __( 'Listing types (buyer, seller, both)', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'service_areas', 'label' => __( 'Service areas or geography', 'aio-page-builder' ), 'type' => 'textarea' ),
				),
			),
			array(
				'pack_id'      => 'plumber',
				'industry_key' => 'plumber',
				'name'         => __( 'Plumber', 'aio-page-builder' ),
				'intent'       => __( 'Gather plumbing business context: residential vs commercial, emergency vs scheduled.', 'aio-page-builder' ),
				'fields'       => array(
					array( 'key' => 'service_scope', 'label' => __( 'Service scope (residential, commercial, both)', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'emergency_offered', 'label' => __( 'Emergency service offered', 'aio-page-builder' ), 'type' => 'boolean' ),
					array( 'key' => 'service_areas', 'label' => __( 'Service areas', 'aio-page-builder' ), 'type' => 'textarea' ),
				),
			),
			array(
				'pack_id'      => 'disaster_recovery',
				'industry_key' => 'disaster_recovery',
				'name'         => __( 'Disaster Recovery', 'aio-page-builder' ),
				'intent'       => __( 'Gather disaster recovery / restoration context: response type and scope.', 'aio-page-builder' ),
				'fields'       => array(
					array( 'key' => 'response_type', 'label' => __( 'Response type (water, fire, mold, other)', 'aio-page-builder' ), 'type' => 'text' ),
					array( 'key' => 'emergency_24_7', 'label' => __( '24/7 emergency response', 'aio-page-builder' ), 'type' => 'boolean' ),
					array( 'key' => 'coverage_areas', 'label' => __( 'Coverage areas', 'aio-page-builder' ), 'type' => 'textarea' ),
				),
			),
		);
	}
}
