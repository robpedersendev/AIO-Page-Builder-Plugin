<?php
/**
 * Runs ordered template library seeds for Settings "seed all" actions (idempotent batches).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Services;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailBatch\Child_Detail_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProductBatch\Child_Detail_Product_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProfileEntityBatch\Child_Detail_Profile_Entity_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailVariantExpansionBatch\Child_Detail_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack\Page_Template_And_Composition_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\GapClosingSuperBatch\Page_Template_Gap_Closing_Super_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\GeographicHubBatch\Geographic_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubBatch\Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubNestedHubVariantExpansionBatch\Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\NestedHubBatch\Nested_Hub_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelBatch\Top_Level_Marketing_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelEducationalResourceAuthorityBatch\Top_Level_Educational_Resource_Authority_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelLegalUtilityBatch\Top_Level_Legal_Utility_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelVariantExpansionBatch\Top_Level_Variant_Expansion_Page_Template_Seeder;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Seeder;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch\Section_Gap_Closing_Super_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\LegalPolicyUtilityBatch\Legal_Policy_Utility_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Orchestrates full section or page template seeding in dependency order.
 */
final class Settings_Template_Bulk_Seed_Service {

	/**
	 * @return array{ success: bool, failed_step: string, errors: list<string> }
	 */
	public static function seed_all_sections( Section_Template_Repository $section_repo ): array {
		$steps = array(
			'section_expansion_pack'    => static fn() => Section_Expansion_Pack_Seeder::run( $section_repo ),
			'hero_intro_library_batch'  => static fn() => Hero_Intro_Library_Batch_Seeder::run( $section_repo ),
			'trust_proof_library_batch' => static fn() => Trust_Proof_Library_Batch_Seeder::run( $section_repo ),
			'feature_benefit_value'     => static fn() => Feature_Benefit_Value_Library_Batch_Seeder::run( $section_repo ),
			'process_timeline_faq'      => static fn() => Process_Timeline_FAQ_Library_Batch_Seeder::run( $section_repo ),
			'media_listing_profile'     => static fn() => Media_Listing_Profile_Detail_Library_Batch_Seeder::run( $section_repo ),
			'legal_policy_utility'      => static fn() => Legal_Policy_Utility_Library_Batch_Seeder::run( $section_repo ),
			'cta_super_library'         => static fn() => CTA_Super_Library_Batch_Seeder::run( $section_repo ),
			'section_gap_closing'       => static fn() => Section_Gap_Closing_Super_Batch_Seeder::run( $section_repo ),
		);
		return self::run_stepped_results( $steps );
	}

	/**
	 * @return array{ success: bool, failed_step: string, errors: list<string> }
	 */
	public static function seed_all_pages( Page_Template_Repository $page_repo, Composition_Repository $composition_repo ): array {
		$steps = array(
			'page_composition_expansion'  => static function () use ( $page_repo, $composition_repo ) {
				return Page_Template_And_Composition_Expansion_Pack_Seeder::run( $page_repo, $composition_repo );
			},
			'top_level_marketing'         => static fn() => Top_Level_Marketing_Page_Template_Seeder::run( $page_repo ),
			'top_level_legal_utility'     => static fn() => Top_Level_Legal_Utility_Page_Template_Seeder::run( $page_repo ),
			'top_level_edu_resource'      => static fn() => Top_Level_Educational_Resource_Authority_Page_Template_Seeder::run( $page_repo ),
			'top_level_variant_expansion' => static fn() => Top_Level_Variant_Expansion_Page_Template_Seeder::run( $page_repo ),
			'hub_page'                    => static fn() => Hub_Page_Template_Seeder::run( $page_repo ),
			'geographic_hub'              => static fn() => Geographic_Hub_Page_Template_Seeder::run( $page_repo ),
			'nested_hub'                  => static fn() => Nested_Hub_Page_Template_Seeder::run( $page_repo ),
			'hub_nested_variant'          => static fn() => Hub_Nested_Hub_Variant_Expansion_Page_Template_Seeder::run( $page_repo ),
			'child_detail'                => static fn() => Child_Detail_Page_Template_Seeder::run( $page_repo ),
			'child_detail_product'        => static fn() => Child_Detail_Product_Page_Template_Seeder::run( $page_repo ),
			'child_detail_profile_entity' => static fn() => Child_Detail_Profile_Entity_Page_Template_Seeder::run( $page_repo ),
			'child_detail_variant'        => static fn() => Child_Detail_Variant_Expansion_Page_Template_Seeder::run( $page_repo ),
			'page_gap_closing'            => static fn() => Page_Template_Gap_Closing_Super_Batch_Seeder::run( $page_repo ),
		);
		return self::run_stepped_results( $steps );
	}

	/**
	 * @param array<string, callable(): array{ success: bool, errors?: array<int, string> }> $steps
	 * @return array{ success: bool, failed_step: string, errors: list<string> }
	 */
	private static function run_stepped_results( array $steps ): array {
		foreach ( $steps as $step_key => $runner ) {
			$result = $runner();
			if ( empty( $result['success'] ) ) {
				$errs = isset( $result['errors'] ) && is_array( $result['errors'] ) ? array_values( array_filter( array_map( 'strval', $result['errors'] ) ) ) : array();
				return array(
					'success'     => false,
					'failed_step' => (string) $step_key,
					'errors'      => $errs,
				);
			}
		}
		return array(
			'success'     => true,
			'failed_step' => '',
			'errors'      => array(),
		);
	}
}
