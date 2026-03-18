<?php
/**
 * Realtor industry subtype definitions (industry-subtype-schema.md; Prompt 415).
 * Buyer-focused and seller-focused (listing) realtor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;

return array(
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'realtor_buyer_agent',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Buyer-Focused Realtor',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'Agent or team primarily serving home buyers: search support, buyer guides, pre-approval and closing guidance. Use case: buyer’s agents, buyer specialist teams.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 10 ),
	),
	array(
		Industry_Subtype_Registry::FIELD_SUBTYPE_KEY    => 'realtor_listing_agent',
		Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
		Industry_Subtype_Registry::FIELD_LABEL          => 'Seller-Focused Realtor',
		Industry_Subtype_Registry::FIELD_SUMMARY        => 'Agent or team primarily serving sellers: listing presentation, staging, marketing, and sale process. Use case: listing agents, seller specialist teams.',
		Industry_Subtype_Registry::FIELD_STATUS         => Industry_Subtype_Registry::STATUS_ACTIVE,
		Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		'metadata'                                      => array( 'sort_order' => 20 ),
	),
);
