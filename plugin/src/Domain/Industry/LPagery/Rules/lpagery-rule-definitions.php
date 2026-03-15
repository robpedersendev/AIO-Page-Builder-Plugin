<?php
/**
 * Built-in LPagery rule definitions for the first four industries (Prompt 360, industry-lpagery-rule-schema.md).
 * Advisory only; no mutation of LPagery binding or token naming. Keys match pack lpagery_rule_ref.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;

$v = Industry_LPagery_Rule_Registry::SUPPORTED_SCHEMA_VERSION;
$active = Industry_LPagery_Rule_Registry::STATUS_ACTIVE;

return array(
	array(
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY   => 'cosmetology_nail_01',
		Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY        => 'cosmetology_nail',
		Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER     => $v,
		Industry_LPagery_Rule_Registry::FIELD_STATUS             => $active,
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE    => Industry_LPagery_Rule_Registry::POSTURE_OPTIONAL,
		Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS => array( '{{service_name}}', '{{location_name}}', '{{booking_url}}' ),
		Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE => 'Hub (services, gallery) then child-detail (service, staff). Local/location pages optional; one strong service-area page preferred over many thin pages.',
		Industry_LPagery_Rule_Registry::FIELD_WEAK_PAGE_WARNINGS => 'Thin location pages with little unique content; duplicate service pages by micro-area.',
		Industry_LPagery_Rule_Registry::FIELD_NOTES              => 'LPagery supports personalization and booking context; not required for core salon/shop presence.',
	),
	array(
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY    => 'realtor_01',
		Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY         => 'realtor',
		Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER      => $v,
		Industry_LPagery_Rule_Registry::FIELD_STATUS              => $active,
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE      => Industry_LPagery_Rule_Registry::POSTURE_CENTRAL,
		Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS => array( '{{location_name}}', '{{service_title}}' ),
		Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS => array( '{{valuation_url}}', '{{contact_phone}}' ),
		Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE  => 'Hub (areas, services) then child-detail (area, listing, valuation). Local/area pages central; avoid orphan listings.',
		Industry_LPagery_Rule_Registry::FIELD_WEAK_PAGE_WARNINGS  => 'Thin area pages with no unique content; duplicate listing content across many URLs; pages without clear area or service scope.',
		Industry_LPagery_Rule_Registry::FIELD_NOTES               => 'LPagery location and service tokens align with listing and market pages; comply with MLS/board rules.',
	),
	array(
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY    => 'plumber_01',
		Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY        => 'plumber',
		Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER      => $v,
		Industry_LPagery_Rule_Registry::FIELD_STATUS             => $active,
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE     => Industry_LPagery_Rule_Registry::POSTURE_CENTRAL,
		Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS => array( '{{location_name}}', '{{service_title}}' ),
		Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS => array( '{{contact_phone}}', '{{emergency_phone}}', '{{service_area}}' ),
		Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE  => 'Hub (services, area) then child-detail (service in area, emergency). Service-area and locality pages central; distinguish emergency vs scheduled.',
		Industry_LPagery_Rule_Registry::FIELD_WEAK_PAGE_WARNINGS  => 'Thin city pages with no unique value; duplicate service pages per micro-locality; weak differentiation between emergency and scheduled.',
		Industry_LPagery_Rule_Registry::FIELD_NOTES               => 'LPagery supports service-area and call/emergency context; NAP and area consistency matter.',
	),
	array(
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_RULE_KEY    => 'disaster_recovery_01',
		Industry_LPagery_Rule_Registry::FIELD_INDUSTRY_KEY        => 'disaster_recovery',
		Industry_LPagery_Rule_Registry::FIELD_VERSION_MARKER      => $v,
		Industry_LPagery_Rule_Registry::FIELD_STATUS             => $active,
		Industry_LPagery_Rule_Registry::FIELD_LPAGERY_POSTURE    => Industry_LPagery_Rule_Registry::POSTURE_CENTRAL,
		Industry_LPagery_Rule_Registry::FIELD_REQUIRED_TOKEN_REFS => array( '{{location_name}}', '{{service_title}}' ),
		Industry_LPagery_Rule_Registry::FIELD_OPTIONAL_TOKEN_REFS => array( '{{contact_phone}}', '{{emergency_phone}}', '{{claims_assistance}}' ),
		Industry_LPagery_Rule_Registry::FIELD_HIERARCHY_GUIDANCE  => 'Hub (services, area) then child-detail (disaster type, area, claims). Service-area and 24/7 visibility central; clear path to emergency and claims CTA.',
		Industry_LPagery_Rule_Registry::FIELD_WEAK_PAGE_WARNINGS  => 'Thin area or disaster-type pages with no unique content; exaggerated urgency; duplicate thin pages per locality.',
		Industry_LPagery_Rule_Registry::FIELD_NOTES               => 'LPagery supports emergency and claims context; IICRC and insurance alignment matter.',
	),
);
