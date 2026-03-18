<?php
/**
 * Disaster Recovery industry subtype definitions (industry-subtype-schema.md; Prompt 415).
 * Residential restoration and commercial restoration.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

return array(
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'disaster_recovery_residential',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'disaster_recovery',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Residential Restoration',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'Restoration and mitigation for homes: water, fire, storm damage; insurance and homeowner focus. Use case: residential restoration companies.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 10 ),
	),
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'disaster_recovery_commercial',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'disaster_recovery',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Commercial Restoration',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'Restoration for commercial and industrial properties: business continuity, larger-scale mitigation, and commercial insurance. Use case: commercial restoration contractors.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 20 ),
	),
);
