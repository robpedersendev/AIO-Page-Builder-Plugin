<?php
/**
 * Realtor question pack (industry-question-pack-contract).
 * Storage: question_pack_answers[realtor][field_key].
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	'pack_id'      => 'realtor',
	'industry_key' => 'realtor',
	'name'         => 'Realtor',
	'intent'       => 'Gather real estate agent context: market focus, listing types, and geography.',
	'fields'       => array(
		array(
			'key'          => 'market_focus',
			'label'        => 'Market focus',
			'type'         => 'text',
			'help_text'    => 'Residential, commercial, or both. Used for page and section recommendations.',
		),
		array(
			'key'          => 'listing_types',
			'label'        => 'Listing types',
			'type'         => 'text',
			'help_text'    => 'Buyer agent, seller agent, or both. Informs valuation and contact CTAs.',
		),
		array(
			'key'          => 'service_areas',
			'label'        => 'Service areas or geography',
			'type'         => 'textarea',
			'help_text'    => 'Cities, regions, or ZIPs you serve. Used for local pages and SEO.',
		),
	),
);
