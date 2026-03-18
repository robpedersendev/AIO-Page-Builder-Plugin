<?php
/**
 * Built-in SEO and entity-guidance rules for the first four industries (Prompt 359, industry-seo-guidance-schema.md).
 * Advisory only; no mutation of third-party SEO plugins. Keys match pack seo_guidance_ref.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;

$v      = Industry_SEO_Guidance_Registry::SUPPORTED_SCHEMA_VERSION;
$active = Industry_SEO_Guidance_Registry::STATUS_ACTIVE;

return array(
	array(
		Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY => 'cosmetology_nail',
		Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER => $v,
		Industry_SEO_Guidance_Registry::FIELD_STATUS       => $active,
		Industry_SEO_Guidance_Registry::FIELD_TITLE_PATTERNS => 'Service Name | Business Name; Location Name | Salon Name. Keep under 60 chars; include primary keyword.',
		Industry_SEO_Guidance_Registry::FIELD_H1_PATTERNS  => 'One primary H1 per page; service or location name; avoid duplicate H1s.',
		Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE => 'Moderate: service-area or neighborhood pages optional; NAP consistency and reviews matter more than many thin local pages.',
		Industry_SEO_Guidance_Registry::FIELD_INTERNAL_LINK_GUIDANCE => 'Link from home and services hub to key service and gallery pages; booking/consultation CTAs as link targets.',
		Industry_SEO_Guidance_Registry::FIELD_FAQ_EMPHASIS => 'FAQ sections support service and booking intent; use for common questions about treatments, pricing, and booking.',
		Industry_SEO_Guidance_Registry::FIELD_REVIEW_EMPHASIS => 'Reviews and testimonials strongly support trust and conversion; surface on home and service pages.',
		Industry_SEO_Guidance_Registry::FIELD_ENTITY_CAUTIONS => 'Avoid thin location pages with little unique content; prefer one strong service-area page if local SEO is needed.',
	),
	array(
		Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY => 'realtor',
		Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'realtor',
		Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER => $v,
		Industry_SEO_Guidance_Registry::FIELD_STATUS       => $active,
		Industry_SEO_Guidance_Registry::FIELD_TITLE_PATTERNS => 'Area or Service | Agent/Team Name; keep listing and market pages distinct; under 60 chars.',
		Industry_SEO_Guidance_Registry::FIELD_H1_PATTERNS  => 'One primary H1; area name, service type, or property type; avoid generic "Welcome".',
		Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE => 'Strong: neighborhood, market, and service-area pages are central; NAP, schema, and unique local content matter.',
		Industry_SEO_Guidance_Registry::FIELD_INTERNAL_LINK_GUIDANCE => 'Hub-and-spoke: link from home and area hubs to listings, services, and valuation/contact; avoid orphan pages.',
		Industry_SEO_Guidance_Registry::FIELD_FAQ_EMPHASIS => 'FAQ for buying/selling process, market questions, and valuation; supports featured snippets.',
		Industry_SEO_Guidance_Registry::FIELD_REVIEW_EMPHASIS => 'Testimonials and reviews build trust; surface near valuation and contact CTAs.',
		Industry_SEO_Guidance_Registry::FIELD_ENTITY_CAUTIONS => 'Comply with MLS/board rules; avoid duplicate or thin area pages; ensure listing data is permitted for indexing.',
	),
	array(
		Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY => 'plumber',
		Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'plumber',
		Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER => $v,
		Industry_SEO_Guidance_Registry::FIELD_STATUS       => $active,
		Industry_SEO_Guidance_Registry::FIELD_TITLE_PATTERNS => 'Service in Area | Business Name; Emergency Service | Business Name. Under 60 chars; include service and area.',
		Industry_SEO_Guidance_Registry::FIELD_H1_PATTERNS  => 'One primary H1; service name or "Service in [Area]"; clear and actionable.',
		Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE => 'Strong: service-area and locality pages central; NAP, service area, and emergency visibility matter.',
		Industry_SEO_Guidance_Registry::FIELD_INTERNAL_LINK_GUIDANCE => 'Link from home and services hub to service-area and emergency/contact; call-now and booking as link targets.',
		Industry_SEO_Guidance_Registry::FIELD_FAQ_EMPHASIS => 'FAQ for common repairs, emergency process, and pricing; supports local and service intent.',
		Industry_SEO_Guidance_Registry::FIELD_REVIEW_EMPHASIS => 'Reviews and credentials (license, guarantees) support trust; surface near CTAs.',
		Industry_SEO_Guidance_Registry::FIELD_ENTITY_CAUTIONS => 'Jurisdiction and licensing compliance; avoid thin or duplicate city pages; distinguish emergency vs scheduled.',
	),
	array(
		Industry_SEO_Guidance_Registry::FIELD_GUIDANCE_RULE_KEY => 'disaster_recovery',
		Industry_SEO_Guidance_Registry::FIELD_INDUSTRY_KEY => 'disaster_recovery',
		Industry_SEO_Guidance_Registry::FIELD_VERSION_MARKER => $v,
		Industry_SEO_Guidance_Registry::FIELD_STATUS       => $active,
		Industry_SEO_Guidance_Registry::FIELD_TITLE_PATTERNS => 'Service Type | Business Name; 24/7 Emergency Restoration | Business. Under 60 chars; urgency and service clear.',
		Industry_SEO_Guidance_Registry::FIELD_H1_PATTERNS  => 'One primary H1; service type or "24/7 [Service]"; emergency and availability clear.',
		Industry_SEO_Guidance_Registry::FIELD_LOCAL_SEO_POSTURE => 'Strong: service-area and disaster-type pages matter; NAP, 24/7, and claims assistance visible.',
		Industry_SEO_Guidance_Registry::FIELD_INTERNAL_LINK_GUIDANCE => 'Link from home to emergency, claims, and service-area pages; clear path to call and claims CTA.',
		Industry_SEO_Guidance_Registry::FIELD_FAQ_EMPHASIS => 'FAQ for insurance, process, and response time; supports trust and featured snippets.',
		Industry_SEO_Guidance_Registry::FIELD_REVIEW_EMPHASIS => 'Certifications (e.g. IICRC) and reviews support credibility; surface near emergency and claims CTAs.',
		Industry_SEO_Guidance_Registry::FIELD_ENTITY_CAUTIONS => 'Avoid exaggerated urgency; comply with insurance/advertising norms; thin or duplicate area pages hurt more than help.',
	),
);
