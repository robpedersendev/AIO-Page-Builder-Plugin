<?php
/**
 * Default question pack definitions for supported industries (industry-question-pack-contract).
 * Loads from Profile/QuestionPacks/ for cosmetology_nail, realtor, plumber, disaster_recovery.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Built-in question pack definitions for first supported industries (industry-question-pack-contract).
 * Packs are defined under Domain/Industry/Profile/QuestionPacks/ and merged here for the registry.
 */
final class Industry_Question_Pack_Definitions {

	/** Relative path from plugin root to QuestionPacks directory. */
	private const QUESTION_PACKS_DIR = 'src/Domain/Industry/Profile/QuestionPacks';

	/** Built-in pack file names (one per industry). */
	private const PACK_FILES = array(
		'cosmetology-nail-pack.php',
		'realtor-pack.php',
		'plumber-pack.php',
		'disaster-recovery-pack.php',
	);

	/**
	 * Returns built-in pack definitions for cosmetology_nail, realtor, plumber, disaster_recovery.
	 * Loads from QuestionPacks/*.php; invalid or missing files are skipped.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_packs(): array {
		$root = defined( 'AIO_PAGE_BUILDER_DIR' ) ? rtrim( AIO_PAGE_BUILDER_DIR, '/\\' ) : dirname( __DIR__, 4 );
		$dir  = $root . '/' . self::QUESTION_PACKS_DIR;
		if ( ! is_dir( $dir ) ) {
			return self::fallback_packs();
		}
		$packs = array();
		foreach ( self::PACK_FILES as $file ) {
			$path = $dir . '/' . $file;
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$pack = include $path;
			if ( is_array( $pack ) && isset( $pack['industry_key'], $pack['fields'] ) && is_array( $pack['fields'] ) ) {
				$packs[] = $pack;
			}
		}
		return $packs !== array() ? $packs : self::fallback_packs();
	}

	/**
	 * Fallback pack definitions when QuestionPacks files are not available (e.g. tests with different root).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fallback_packs(): array {
		return array(
			array(
				'pack_id'      => 'cosmetology_nail',
				'industry_key' => 'cosmetology_nail',
				'name'         => 'Cosmetology / Nail',
				'intent'       => 'Gather cosmetology or nail business context for services, booking, and compliance.',
				'fields'       => array(
					array(
						'key'   => 'service_types',
						'label' => 'Primary service types',
						'type'  => 'text',
					),
					array(
						'key'   => 'booking_style',
						'label' => 'Booking style (walk-in, appointment, both)',
						'type'  => 'text',
					),
					array(
						'key'   => 'license_notes',
						'label' => 'License or compliance notes',
						'type'  => 'textarea',
					),
				),
			),
			array(
				'pack_id'      => 'realtor',
				'industry_key' => 'realtor',
				'name'         => 'Realtor',
				'intent'       => 'Gather real estate agent context: market focus, listing types, and geography.',
				'fields'       => array(
					array(
						'key'   => 'market_focus',
						'label' => 'Market focus (residential, commercial, both)',
						'type'  => 'text',
					),
					array(
						'key'   => 'listing_types',
						'label' => 'Listing types (buyer, seller, both)',
						'type'  => 'text',
					),
					array(
						'key'   => 'service_areas',
						'label' => 'Service areas or geography',
						'type'  => 'textarea',
					),
				),
			),
			array(
				'pack_id'      => 'plumber',
				'industry_key' => 'plumber',
				'name'         => 'Plumber',
				'intent'       => 'Gather plumbing business context: residential vs commercial, emergency vs scheduled.',
				'fields'       => array(
					array(
						'key'   => 'service_scope',
						'label' => 'Service scope (residential, commercial, both)',
						'type'  => 'text',
					),
					array(
						'key'   => 'emergency_offered',
						'label' => 'Emergency service offered',
						'type'  => 'boolean',
					),
					array(
						'key'   => 'service_areas',
						'label' => 'Service areas',
						'type'  => 'textarea',
					),
				),
			),
			array(
				'pack_id'      => 'disaster_recovery',
				'industry_key' => 'disaster_recovery',
				'name'         => 'Disaster Recovery',
				'intent'       => 'Gather disaster recovery / restoration context: response type and scope.',
				'fields'       => array(
					array(
						'key'   => 'response_type',
						'label' => 'Response type (water, fire, mold, other)',
						'type'  => 'text',
					),
					array(
						'key'   => 'emergency_24_7',
						'label' => '24/7 emergency response',
						'type'  => 'boolean',
					),
					array(
						'key'   => 'coverage_areas',
						'label' => 'Coverage areas',
						'type'  => 'textarea',
					),
				),
			),
		);
	}
}
