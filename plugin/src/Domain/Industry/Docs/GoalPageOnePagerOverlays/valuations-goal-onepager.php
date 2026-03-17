<?php
/**
 * Conversion-goal page one-pager overlays for valuations goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'valuations',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'      => 'Lead with get a valuation, home value, or CMA; position valuation as the key conversion step.',
		'funnel_notes'         => 'Valuation-first funnel: valuation tool or request as primary conversion; lead magnet or tool entry.',
		'cta_placement_notes'  => 'Primary CTA: get a valuation, home value, or CMA. One clear entry to valuation flow.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'valuations',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_offerings_compare_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'         => 'Use for valuation comparison or tier; link to valuation request or CMA. Avoid diluting with other CTAs.',
		'cta_placement_notes'  => 'Single CTA to valuation tool or request; align with valuation goal.',
	),
);
