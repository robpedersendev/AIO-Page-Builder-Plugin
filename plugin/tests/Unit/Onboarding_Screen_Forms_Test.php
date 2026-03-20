<?php
/**
 * Tests that Onboarding_Screen contains all v1 profile step form methods, field names, and persist methods.
 * Guards against regression where profile steps were missing from the form flow.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Onboarding_Screen_Forms_Test extends TestCase {

	private function screen_source(): string {
		$path = dirname( __DIR__, 2 ) . '/src/Admin/Screens/AI/Onboarding_Screen.php';
		$src  = file_get_contents( $path );
		$this->assertNotFalse( $src, 'Onboarding_Screen.php must be readable.' );
		return (string) $src;
	}

	/** Each of the 7 profile step render methods must exist. */
	public function test_all_step_render_methods_declared(): void {
		$src      = $this->screen_source();
		$required = array(
			'render_business_profile_step',
			'render_brand_profile_step',
			'render_audience_offers_step',
			'render_geography_competitors_step',
			'render_asset_intake_step',
			'render_existing_site_step',
			'render_crawl_preferences_step',
			'render_review_step',
		);
		foreach ( $required as $method ) {
			$this->assertStringContainsString(
				"function {$method}",
				$src,
				"Onboarding_Screen must declare {$method}()."
			);
		}
	}

	/** Both profile persist methods must be declared. */
	public function test_persist_methods_declared(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'function persist_brand_profile_from_post',
			$src,
			'Onboarding_Screen must declare persist_brand_profile_from_post().'
		);
		$this->assertStringContainsString(
			'function persist_business_profile_from_post',
			$src,
			'Onboarding_Screen must declare persist_business_profile_from_post().'
		);
	}

	/** Business profile step must include key HTML field names. */
	public function test_business_profile_step_fields(): void {
		$src = $this->screen_source();
		foreach ( array( 'aio_bp_biz_name', 'aio_bp_biz_type', 'aio_bp_biz_contact_goals', 'aio_bp_biz_value_prop' ) as $field ) {
			$this->assertStringContainsString( $field, $src, "Business profile step must include field '{$field}'." );
		}
	}

	/** Brand profile step must include key HTML field names. */
	public function test_brand_profile_step_fields(): void {
		$src = $this->screen_source();
		foreach ( array( 'aio_bp_brand_positioning', 'aio_bp_brand_voice', 'aio_bp_brand_formality', 'aio_bp_brand_cta_style' ) as $field ) {
			$this->assertStringContainsString( $field, $src, "Brand profile step must include field '{$field}'." );
		}
	}

	/** Audience/offers step must include key HTML field names. */
	public function test_audience_offers_step_fields(): void {
		$src = $this->screen_source();
		foreach ( array( 'aio_bp_biz_target_audience', 'aio_bp_biz_primary_offers', 'aio_bp_biz_priorities' ) as $field ) {
			$this->assertStringContainsString( $field, $src, "Audience/offers step must include field '{$field}'." );
		}
	}

	/** Geography/competitors step must include key HTML field names. */
	public function test_geography_competitors_step_fields(): void {
		$src = $this->screen_source();
		foreach ( array( 'aio_bp_biz_geo_market', 'aio_bp_biz_marketing_lang', 'aio_bp_biz_seasonality' ) as $field ) {
			$this->assertStringContainsString( $field, $src, "Geography step must include field '{$field}'." );
		}
	}

	/** Asset intake step must include key HTML field names. */
	public function test_asset_intake_step_fields(): void {
		$src = $this->screen_source();
		foreach ( array( 'aio_bp_biz_visual_ref', 'aio_bp_biz_sales_process' ) as $field ) {
			$this->assertStringContainsString( $field, $src, "Asset intake step must include field '{$field}'." );
		}
	}

	/** Existing site step must include site URL field. */
	public function test_existing_site_step_fields(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'aio_bp_biz_url',
			$src,
			'Existing site step must include field aio_bp_biz_url.'
		);
	}

	/** Crawl preferences step must reference the Crawler Sessions screen. */
	public function test_crawl_preferences_references_crawler(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'Crawler Sessions',
			$src,
			'Crawl preferences step must reference the Crawler Sessions screen.'
		);
	}

	/** Persist brand method must call merge_brand_profile. */
	public function test_persist_brand_calls_merge(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'merge_brand_profile',
			$src,
			'persist_brand_profile_from_post must call merge_brand_profile.'
		);
	}

	/** Persist business method must call merge_business_profile. */
	public function test_persist_business_calls_merge(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'merge_business_profile',
			$src,
			'persist_business_profile_from_post must call merge_business_profile.'
		);
	}

	/** Review step must show profile summary labels. */
	public function test_review_step_shows_profile_summary(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'Profile summary',
			$src,
			'Review step must contain a profile summary section.'
		);
		$this->assertStringContainsString(
			'Provider readiness',
			$src,
			'Review step must contain a provider readiness section.'
		);
	}

	/** The generic else fallback for un-rendered steps must be removed now all steps have explicit handlers. */
	public function test_no_generic_step_fallback(): void {
		$src = $this->screen_source();
		$this->assertStringNotContainsString(
			'use Next to advance through the onboarding flow',
			$src,
			'Generic step fallback copy must be removed since all steps have explicit render methods.'
		);
	}

	/** persist_brand_profile_from_post must be called in handle_post. */
	public function test_persist_brand_called_in_handle_post(): void {
		$src = $this->screen_source();
		$this->assertGreaterThan(
			1,
			substr_count( $src, 'persist_brand_profile_from_post' ),
			'persist_brand_profile_from_post must be called from handle_post (at least one call site besides the declaration).'
		);
	}

	/** persist_business_profile_from_post must be called in handle_post. */
	public function test_persist_business_called_in_handle_post(): void {
		$src = $this->screen_source();
		$this->assertGreaterThan(
			1,
			substr_count( $src, 'persist_business_profile_from_post' ),
			'persist_business_profile_from_post must be called from handle_post (at least one call site besides the declaration).'
		);
	}
}
