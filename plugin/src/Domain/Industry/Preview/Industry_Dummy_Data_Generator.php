<?php
/**
 * Industry-specific dummy data overrides for preview contexts only (industry-preview-dummy-data-contract).
 * Returns partial field overrides by purpose_family and industry_key. No persistence; no execution use.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Generates industry-appropriate placeholder overrides for synthetic preview data.
 * Output is merged over Synthetic_Preview_Data_Generator output; same ACF field shapes.
 */
final class Industry_Dummy_Data_Generator {

	private const PLACEHOLDER_URL = '#';

	/** Supported industry keys (first four seeded). */
	private const SUPPORTED_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns field overrides for the given purpose family and industry. Empty when industry unsupported.
	 *
	 * @param string $purpose_family hero, proof, cta, offer, faq, profile, listing, etc.
	 * @param string $industry_key    Industry pack key (e.g. cosmetology_nail, realtor).
	 * @return array<string, mixed> Partial map of field_name => value; merge over base synthetic data.
	 */
	public function get_overrides_for_family( string $purpose_family, string $industry_key ): array {
		$industry_key   = \trim( $industry_key );
		$purpose_family = $purpose_family !== '' ? $purpose_family : 'other';
		if ( $industry_key === '' || ! \in_array( $industry_key, self::SUPPORTED_INDUSTRIES, true ) ) {
			return array();
		}

		$method = 'overrides_' . $industry_key;
		if ( ! \method_exists( $this, $method ) ) {
			return array();
		}
		$by_family = $this->{$method}();
		return isset( $by_family[ $purpose_family ] ) && \is_array( $by_family[ $purpose_family ] )
			? $by_family[ $purpose_family ]
			: array();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function overrides_cosmetology_nail(): array {
		return array(
			'hero'    => array(
				'headline'    => 'Your Best Look Starts Here',
				'subheadline' => 'Book your appointment with our experienced stylists. Services for nails, hair, and skin.',
				'cta_text'    => 'Book now',
				'eyebrow'     => 'Salon & Spa',
			),
			'proof'   => array(
				'headline' => 'What Our Clients Say',
				'items'    => array(
					array(
						'name'  => 'Sarah M.',
						'quote' => 'Always leave feeling refreshed and looking great.',
						'role'  => 'Client',
					),
					array(
						'name'  => 'Jessica L.',
						'quote' => 'Professional service and a relaxing atmosphere.',
						'role'  => 'Regular',
					),
				),
			),
			'cta'     => array(
				'headline'           => 'Ready to book your visit?',
				'cta_text'           => 'Book appointment',
				'secondary_cta_text' => 'View services',
			),
			'offer'   => array(
				'headline' => 'Services & Packages',
				'intro'    => 'Choose a single service or a package that fits your schedule.',
				'plans'    => array(
					array(
						'title'    => 'Manicure',
						'price'    => '$XX',
						'features' => 'Classic or gel; nail art optional.',
					),
					array(
						'title'    => 'Pedicure',
						'price'    => '$YY',
						'features' => 'Relaxing soak and finish.',
					),
				),
			),
			'faq'     => array(
				'headline' => 'Frequently Asked Questions',
				'items'    => array(
					array(
						'question' => 'How do I book an appointment?',
						'answer'   => 'Use our booking link or call the salon during business hours.',
					),
					array(
						'question' => 'Do you offer walk-ins?',
						'answer'   => 'Walk-ins are welcome when we have availability.',
					),
				),
			),
			'profile' => array(
				'headline' => 'Our Team',
				'name'     => 'Stylist Name',
				'role'     => 'Senior Stylist',
				'bio'      => 'Experienced in nails and skincare. Licensed and certified.',
			),
			'listing' => array(
				'headline' => 'Our Services',
				'items'    => array(
					array(
						'title'       => 'Manicure & Nail Care',
						'description' => 'Classic and gel options.',
						'link'        => self::PLACEHOLDER_URL,
					),
					array(
						'title'       => 'Pedicure',
						'description' => 'Spa-style foot care.',
						'link'        => self::PLACEHOLDER_URL,
					),
				),
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function overrides_realtor(): array {
		return array(
			'hero'      => array(
				'headline'    => 'Find Your Next Home',
				'subheadline' => 'Local expertise for buyers and sellers. Get a free home valuation.',
				'cta_text'    => 'Get home value',
				'eyebrow'     => 'Local Real Estate',
			),
			'proof'     => array(
				'headline' => 'What Clients Say',
				'items'    => array(
					array(
						'name'  => 'Buyer Client',
						'quote' => 'Made the process smooth from search to closing.',
						'role'  => 'Buyer',
					),
					array(
						'name'  => 'Seller Client',
						'quote' => 'Sold above asking. Highly recommend.',
						'role'  => 'Seller',
					),
				),
			),
			'cta'       => array(
				'headline'           => 'Ready to buy or sell?',
				'cta_text'           => 'Request valuation',
				'secondary_cta_text' => 'View listings',
			),
			'offer'     => array(
				'headline' => 'Buyer & Seller Services',
				'intro'    => 'Full-service representation for your real estate goals.',
				'plans'    => array(
					array(
						'title'    => 'Buyer representation',
						'price'    => 'Contact',
						'features' => 'Search, tours, negotiation, closing.',
					),
					array(
						'title'    => 'Seller representation',
						'price'    => 'Contact',
						'features' => 'Pricing, marketing, offers, closing.',
					),
				),
			),
			'faq'       => array(
				'headline' => 'Frequently Asked Questions',
				'items'    => array(
					array(
						'question' => 'How is my home value estimated?',
						'answer'   => 'We use local comparables and market data for a free estimate.',
					),
					array(
						'question' => 'What areas do you serve?',
						'answer'   => 'We focus on the local market and surrounding neighborhoods.',
					),
				),
			),
			'listing'   => array(
				'headline' => 'Featured Listings',
				'items'    => array(
					array(
						'title'       => 'Neighborhood A Home',
						'description' => '3 bed, 2 bath. Great location.',
						'link'        => self::PLACEHOLDER_URL,
					),
					array(
						'title'       => 'Neighborhood B Home',
						'description' => 'Family-friendly area.',
						'link'        => self::PLACEHOLDER_URL,
					),
				),
			),
			'locations' => array(
				'headline' => 'Service Area',
				'items'    => array(
					array(
						'name'    => 'Main Office',
						'address' => '123 Example St',
						'link'    => self::PLACEHOLDER_URL,
					),
				),
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function overrides_plumber(): array {
		return array(
			'hero'      => array(
				'headline'    => 'Licensed Plumbing When You Need It',
				'subheadline' => 'Repairs, installations, and maintenance. Emergency and scheduled service.',
				'cta_text'    => 'Call now',
				'eyebrow'     => 'Plumbing Services',
			),
			'proof'     => array(
				'headline' => 'What Customers Say',
				'items'    => array(
					array(
						'name'  => 'Homeowner',
						'quote' => 'Fast, professional, and fairly priced.',
						'role'  => 'Customer',
					),
					array(
						'name'  => 'Property Manager',
						'quote' => 'Reliable for our emergency calls.',
						'role'  => 'Client',
					),
				),
			),
			'cta'       => array(
				'headline'           => 'Need a plumber?',
				'cta_text'           => 'Call for service',
				'secondary_cta_text' => 'Book online',
			),
			'offer'     => array(
				'headline' => 'Plumbing Services',
				'intro'    => 'Repairs, installations, and maintenance for residential and commercial.',
				'plans'    => array(
					array(
						'title'    => 'Repairs',
						'price'    => 'Quote',
						'features' => 'Leaks, clogs, fixtures, water heaters.',
					),
					array(
						'title'    => 'Installations',
						'price'    => 'Quote',
						'features' => 'New fixtures, water heaters, repipes.',
					),
				),
			),
			'faq'       => array(
				'headline' => 'Frequently Asked Questions',
				'items'    => array(
					array(
						'question' => 'Do you offer emergency service?',
						'answer'   => 'Yes. Call for same-day or after-hours emergency response.',
					),
					array(
						'question' => 'Are you licensed?',
						'answer'   => 'Yes. We are fully licensed and insured.',
					),
				),
			),
			'explainer' => array(
				'headline' => 'How We Work',
				'steps'    => array(
					array(
						'title'       => 'Call or book',
						'description' => 'Describe the issue and schedule a visit.',
					),
					array(
						'title'       => 'Diagnosis',
						'description' => 'We assess the problem and provide a clear quote.',
					),
					array(
						'title'       => 'Repair or install',
						'description' => 'We complete the work and clean up.',
					),
				),
			),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function overrides_disaster_recovery(): array {
		return array(
			'hero'      => array(
				'headline'    => '24/7 Emergency Response',
				'subheadline' => 'Water, fire, and mold damage. We respond fast and work with your insurance.',
				'cta_text'    => 'Call for help',
				'eyebrow'     => 'Restoration Services',
			),
			'proof'     => array(
				'headline' => 'Trusted by Property Owners',
				'items'    => array(
					array(
						'name'  => 'Property Owner',
						'quote' => 'They were here within the hour. Professional and thorough.',
						'role'  => 'Client',
					),
					array(
						'name'  => 'Insurance Partner',
						'quote' => 'Certified and reliable for mitigation and restoration.',
						'role'  => 'Partner',
					),
				),
			),
			'cta'       => array(
				'headline'           => 'Need immediate help?',
				'cta_text'           => 'Call 24/7',
				'secondary_cta_text' => 'Insurance claim help',
			),
			'offer'     => array(
				'headline' => 'Restoration Services',
				'intro'    => 'Water damage, fire and smoke, mold remediation. IICRC-trained technicians.',
				'plans'    => array(
					array(
						'title'    => 'Water damage',
						'price'    => 'Assessment',
						'features' => 'Extraction, drying, sanitization.',
					),
					array(
						'title'    => 'Fire & smoke',
						'price'    => 'Assessment',
						'features' => 'Board-up, cleaning, odor removal.',
					),
				),
			),
			'faq'       => array(
				'headline' => 'Frequently Asked Questions',
				'items'    => array(
					array(
						'question' => 'Do you work with insurance?',
						'answer'   => 'Yes. We work with most carriers and can help with the claims process.',
					),
					array(
						'question' => 'How fast can you respond?',
						'answer'   => 'We offer 24/7 emergency dispatch. Response time depends on location.',
					),
				),
			),
			'explainer' => array(
				'headline' => 'What Happens Next',
				'steps'    => array(
					array(
						'title'       => 'Call',
						'description' => 'Contact us 24/7 to report the damage.',
					),
					array(
						'title'       => 'Assessment',
						'description' => 'We assess the damage and document for insurance.',
					),
					array(
						'title'       => 'Mitigation & restore',
						'description' => 'We stabilize the property and restore it.',
					),
				),
			),
		);
	}
}
