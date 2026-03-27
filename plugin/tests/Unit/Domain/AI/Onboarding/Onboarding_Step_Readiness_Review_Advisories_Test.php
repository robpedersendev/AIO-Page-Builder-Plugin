<?php
/**
 * Review-step advisories: geography short labels, explicit N/A vs placeholder heuristics.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Onboarding;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Readiness;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Normalizer;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Step_Readiness::get_review_advisories
 */
final class Onboarding_Step_Readiness_Review_Advisories_Test extends TestCase {

	private function make_prefill(): Onboarding_Prefill_Service {
		$settings      = new Settings_Service();
		$normalizer    = new Profile_Normalizer();
		$profile_store = new Profile_Store( $settings, $normalizer );
		$secret        = $this->createMock( Provider_Secret_Store_Interface::class );
		$secret->method( 'get_credential_state' )->willReturn( Provider_Secret_Store_Interface::STATE_ABSENT );
		$secret->method( 'has_credential' )->willReturn( false );
		return new Onboarding_Prefill_Service( $profile_store, $settings, null, $secret );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function base_profile( string $geo ): array {
		return array(
			Profile_Schema::ROOT_BUSINESS => array(
				'business_name'             => 'Acme Corporation',
				'business_type'             => 'B2B SaaS',
				'target_audience_summary'   => 'Mid-market ops teams needing workflow automation and audit trails.',
				'primary_offers_summary'    => 'Annual subscriptions, onboarding packages, and priority support tiers.',
				'core_geographic_market'    => $geo,
			),
			Profile_Schema::ROOT_BRAND    => array(
				'brand_positioning_summary' => 'Trusted operator-first platform with clear ROI narratives.',
				'brand_voice_summary'       => 'Direct, confident, and implementation-focused.',
			),
		);
	}

	public function test_geography_compact_label_skips_placeholder_advisory(): void {
		$prefill = $this->make_prefill();
		$adv     = Onboarding_Step_Readiness::get_review_advisories( $this->base_profile( 'UK' ), $prefill );
		$this->assertIsArray( $adv );
		$joined = implode( ' ', $adv );
		$this->assertStringNotContainsString( 'Geograph', $joined );
	}

	public function test_geography_explicit_not_applicable_uses_targeted_message(): void {
		$prefill = $this->make_prefill();
		$adv     = Onboarding_Step_Readiness::get_review_advisories( $this->base_profile( 'n/a' ), $prefill );
		$joined  = implode( ' ', $adv );
		$this->assertStringContainsString( 'not applicable', strtolower( $joined ) );
		$this->assertStringNotContainsString( 'underspecified', strtolower( $joined ) );
	}

	public function test_geography_too_short_raises_underspecified(): void {
		$prefill = $this->make_prefill();
		$adv     = Onboarding_Step_Readiness::get_review_advisories( $this->base_profile( 'ab' ), $prefill );
		$joined  = implode( ' ', $adv );
		$this->assertStringContainsString( 'underspecified', strtolower( $joined ) );
	}

	public function test_business_name_n_a_still_flagged_as_placeholder(): void {
		$prefill = $this->make_prefill();
		$p       = $this->base_profile( 'United Kingdom' );
		$p[ Profile_Schema::ROOT_BUSINESS ]['business_name'] = 'N/A';
		$adv = Onboarding_Step_Readiness::get_review_advisories( $p, $prefill );
		$joined = implode( ' ', $adv );
		$this->assertStringContainsString( 'business name', strtolower( $joined ) );
	}
}
