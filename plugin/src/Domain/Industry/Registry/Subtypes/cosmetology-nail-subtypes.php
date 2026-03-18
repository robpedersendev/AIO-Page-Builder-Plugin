<?php
/**
 * Cosmetology / Nail industry subtype definitions (industry-subtype-schema.md; Prompt 415).
 * Luxury nail salon and mobile nail technician.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

return array(
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'cosmetology_nail_luxury_salon',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Luxury Nail Salon',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'High-end salon or spa nail services with emphasis on experience, premium products, and appointment-based booking. Use case: full-service nail salons, day spas with nail departments.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 10 ),
	),
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'cosmetology_nail_mobile_tech',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Mobile Nail Technician',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'Nail tech who travels to client locations (home, office, events). Emphasis on booking, service area, and convenience. Use case: mobile nail artists, event stylists.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 20 ),
	),
);
