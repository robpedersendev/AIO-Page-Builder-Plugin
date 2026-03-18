<?php
/**
 * Unit tests for Industry_Build_Plan_Scoring_Service (industry-build-plan-scoring-contract, Prompt 345).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Industry\AI\Industry_Build_Plan_Scoring_Service;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Resolver;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository_Interface;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';

final class Industry_Build_Plan_Scoring_Service_Test extends TestCase {

	private function valid_normalized_output(): array {
		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => '1',
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY      => array(),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array(),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array(),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE => array(
				array(
					'proposed_page_title' => 'Legal landing',
					'proposed_slug'       => 'legal',
					'purpose'             => 'Landing',
					'template_key'        => 'landing_legal',
					'menu_eligible'       => true,
					'section_guidance'    => '',
					'confidence'          => 'high',
				),
				array(
					'proposed_page_title' => 'Generic hub',
					'proposed_slug'       => 'hub',
					'purpose'             => 'Hub',
					'template_key'        => 'hub_generic',
					'menu_eligible'       => true,
					'section_guidance'    => '',
					'confidence'          => 'medium',
				),
			),
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => array(),
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array(),
		);
	}

	private function page_templates_for_resolver(): array {
		return array(
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY => 'landing_legal',
				Page_Template_Schema::FIELD_NAME         => 'Landing Legal',
				'template_family'                        => 'landing_legal',
				Page_Template_Schema::FIELD_INDUSTRY_AFFINITY => array( 'legal' ),
			),
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY => 'hub_generic',
				Page_Template_Schema::FIELD_NAME         => 'Hub Generic',
			),
		);
	}

	private function page_repo_stub( array $templates ): Page_Template_Repository_Interface {
		return new class( $templates ) implements Page_Template_Repository_Interface {
			private array $templates;
			public function __construct( array $templates ) {
				$this->templates = $templates;
			}
			public function list_all_definitions( int $limit = 0, int $offset = 0 ): array {
				return array_slice( $this->templates, $offset, $limit ? $limit : count( $this->templates ) );
			}
		};
	}

	public function test_enrich_output_returns_unchanged_when_primary_industry_key_empty(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		$page_repo = $this->page_repo_stub( $this->page_templates_for_resolver() );
		$service   = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			null
		);
		$input     = $this->valid_normalized_output();
		$output    = $service->enrich_output( $input, array() );
		$this->assertSame( $input, $output );
	}

	public function test_enrich_output_returns_unchanged_when_context_has_empty_profile(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$page_repo    = $this->page_repo_stub( array() );
		$service      = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			null
		);
		$input        = $this->valid_normalized_output();
		$output       = $service->enrich_output( $input, array( Industry_Build_Plan_Scoring_Service::CONTEXT_INDUSTRY_PROFILE => array( 'primary_industry_key' => '' ) ) );
		$this->assertSame( $input, $output );
	}

	public function test_enrich_output_adds_industry_metadata_when_profile_and_templates_present(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
				Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			)
		);
		$page_repo = $this->page_repo_stub( $this->page_templates_for_resolver() );
		$service   = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			null
		);
		$input     = $this->valid_normalized_output();
		$output    = $service->enrich_output( $input, array() );
		$this->assertNotSame( $input, $output );
		$new_pages = $output[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ];
		$this->assertCount( 2, $new_pages );
		$landing = $new_pages[0];
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_SOURCE_REFS, $landing );
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS, $landing );
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE, $landing );
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_WARNING_FLAGS, $landing );
		$this->assertContains( 'template_affinity_primary', $landing[ Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS ] );
		$this->assertGreaterThanOrEqual( 20, $landing[ Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE ] );
	}

	public function test_enrich_output_existing_page_changes_receive_metadata_via_target_template_key(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
				Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			)
		);
		$page_repo = $this->page_repo_stub( $this->page_templates_for_resolver() );
		$service   = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			null
		);
		$input     = $this->valid_normalized_output();
		$input[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ] = array(
			array(
				'current_page_url'    => '/old',
				'current_page_title'  => 'Old',
				'action'              => 'rebuild_from_template',
				'reason'              => 'Rebuild',
				'risk_level'          => 'low',
				'confidence'          => 'high',
				'target_template_key' => 'landing_legal',
			),
		);
		$output = $service->enrich_output( $input, array() );
		$epc    = $output[ Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES ];
		$this->assertCount( 1, $epc );
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_INDUSTRY_FIT_SCORE, $epc[0] );
		$this->assertArrayHasKey( Industry_Build_Plan_Scoring_Service::RECORD_RECOMMENDATION_REASONS, $epc[0] );
	}

	public function test_enrich_output_sorts_new_pages_by_fit_recommended_first(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
				Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			)
		);
		$page_repo = $this->page_repo_stub( $this->page_templates_for_resolver() );
		$service   = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			null
		);
		$input     = $this->valid_normalized_output();
		$output    = $service->enrich_output( $input, array() );
		$new_pages = $output[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ];
		$this->assertCount( 2, $new_pages );
		$first_key  = $new_pages[0]['template_key'] ?? '';
		$second_key = $new_pages[1]['template_key'] ?? '';
		$this->assertSame( 'landing_legal', $first_key );
		$this->assertSame( 'hub_generic', $second_key );
	}

	/**
	 * When is_pack_active returns false for the primary industry key, scoring treats as no pack (generic fallback).
	 */
	public function test_enrich_output_uses_generic_fallback_when_is_pack_active_returns_false(): void {
		$settings     = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile(
			array(
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
				Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			)
		);
		$page_repo     = $this->page_repo_stub( $this->page_templates_for_resolver() );
		$pack_registry = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry();
		$pack_registry->load(
			array(
				array(
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'legal',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema::FIELD_NAME           => 'Legal',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema::FIELD_STATUS         => \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema::STATUS_ACTIVE,
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$is_pack_active = static function ( string $key ): bool {
			return $key !== 'legal';
		};
		$service        = new Industry_Build_Plan_Scoring_Service(
			new Industry_Page_Template_Recommendation_Resolver(),
			$page_repo,
			$profile_repo,
			$pack_registry,
			null,
			$is_pack_active
		);
		$input          = $this->valid_normalized_output();
		$output         = $service->enrich_output( $input, array() );
		$this->assertIsArray( $output );
		$this->assertArrayHasKey( Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE, $output );
		$new_pages = $output[ Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE ];
		$this->assertCount( 2, $new_pages );
		// With pack disabled, primary_pack is null so resolver gets (profile, null, templates). Behavior is generic; no exception.
		$this->assertArrayHasKey( 'template_key', $new_pages[0] );
	}
}
