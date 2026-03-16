<?php
/**
 * Plumber industry subtype definitions (industry-subtype-schema.md; Prompt 415).
 * Residential and commercial plumber.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

return array(
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => 'plumber_residential',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'plumber',
		Industry_Subtype_Registry::FIELD_LABEL              => 'Residential Plumber',
		Industry_Subtype_Registry::FIELD_SUMMARY             => 'Plumbing services for homes and small properties: repairs, installations, emergency calls. Use case: local residential plumbers, home-service focus.',
		Industry_Subtype_Registry::FIELD_STATUS              => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		'metadata' => array( 'sort_order' => 10 ),
	),
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => 'plumber_commercial',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'plumber',
		Industry_Subtype_Registry::FIELD_LABEL               => 'Commercial Plumber',
		Industry_Subtype_Registry::FIELD_SUMMARY             => 'Plumbing for commercial and industrial properties: maintenance contracts, large installations, compliance. Use case: commercial plumbing contractors.',
		Industry_Subtype_Registry::FIELD_STATUS              => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		'metadata' => array( 'sort_order' => 20 ),
	),
);
