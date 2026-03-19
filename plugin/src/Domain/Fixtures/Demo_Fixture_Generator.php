<?php
/**
 * Deterministic demo fixture and seed-data generator (spec §56.4, §60.7; Prompt 130).
 * Produces synthetic data for registries, profile, crawl summary, AI runs, Build Plans, logs, export.
 * Internal-only; no real customer data or secrets. Does not perform external calls or reporting.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Fixtures;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\Registries\Fixtures\Registry_Fixture_Builder;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;

/**
 * Generates sanitized deterministic demo fixtures. All output is tagged synthetic.
 */
final class Demo_Fixture_Generator {

	/** Marker key in payloads to indicate synthetic data. */
	public const SYNTHETIC_MARKER = '_synthetic';

	/** Demo run id prefix (no real provider or keys). */
	private const DEMO_RUN_ID = 'demo-run-001';

	/** Demo plan id prefix. */
	private const DEMO_PLAN_ID = 'demo-plan-001';

	/** Demo crawl run id. */
	private const DEMO_CRAWL_RUN_ID = 'demo-crawl-001';

	/** Fixed timestamp for deterministic output. */
	private const DEMO_TIMESTAMP = '2025-01-15T12:00:00Z';

	/**
	 * Runs full fixture generation and returns a result with counts and summary.
	 *
	 * @param array{include_registries?: bool, include_profile?: bool, include_crawl?: bool, include_ai_runs?: bool, include_build_plans?: bool, include_logs?: bool, include_export?: bool, include_template_showcase?: bool} $options Optional flags to include/exclude domains (default all true).
	 * @return Demo_Fixture_Result
	 */
	public function generate( array $options = array() ): Demo_Fixture_Result {
		$include_registries        = $options['include_registries'] ?? true;
		$include_profile           = $options['include_profile'] ?? true;
		$include_crawl             = $options['include_crawl'] ?? true;
		$include_ai_runs           = $options['include_ai_runs'] ?? true;
		$include_build_plans       = $options['include_build_plans'] ?? true;
		$include_logs              = $options['include_logs'] ?? true;
		$include_export            = $options['include_export'] ?? true;
		$include_template_showcase = $options['include_template_showcase'] ?? false;

		$counts  = array(
			'registries'        => 0,
			'profile'           => 0,
			'crawl_summary'     => 0,
			'ai_runs'           => 0,
			'build_plans'       => 0,
			'logs'              => 0,
			'export_example'    => 0,
			'template_showcase' => 0,
		);
		$payload = array(
			'seed_result' => array(
				'generator'            => 'Demo_Fixture_Generator',
				'purpose'              => 'demo_qa_review',
				self::SYNTHETIC_MARKER => true,
			),
		);

		if ( $include_registries ) {
			$bundle                = Registry_Fixture_Builder::full_bundle();
			$counts['registries']  = count( $bundle['sections'] ) + count( $bundle['page_templates'] ) + count( $bundle['compositions'] ) + count( $bundle['documentation'] ) + count( $bundle['snapshots'] );
			$payload['registries'] = $this->tag_synthetic( $bundle );
		}

		if ( $include_profile ) {
			$profile            = $this->get_profile_fixture();
			$counts['profile']  = 1;
			$payload['profile'] = $this->tag_synthetic( $profile );
		}

		if ( $include_crawl ) {
			$crawl                    = $this->get_crawl_summary_fixture();
			$counts['crawl_summary']  = count( $crawl );
			$payload['crawl_summary'] = $this->tag_synthetic( $crawl );
		}

		if ( $include_ai_runs ) {
			$ai_runs            = $this->get_ai_run_fixture();
			$counts['ai_runs']  = 1;
			$payload['ai_runs'] = $this->tag_synthetic( $ai_runs );
		}

		if ( $include_build_plans ) {
			$plans                  = $this->get_build_plan_fixture();
			$counts['build_plans']  = count( $plans );
			$payload['build_plans'] = $this->tag_synthetic( $plans );
		}

		if ( $include_logs ) {
			$logs            = $this->get_log_example();
			$counts['logs']  = count( $logs );
			$payload['logs'] = $this->tag_synthetic( $logs );
		}

		if ( $include_export ) {
			$export                    = $this->get_export_example();
			$counts['export_example']  = 1;
			$payload['export_example'] = $this->tag_synthetic( $export );
		}

		if ( $include_template_showcase ) {
			$showcase_gen                 = new Template_Showcase_Fixture_Generator();
			$showcase                     = $showcase_gen->generate();
			$counts['template_showcase']  = ( $showcase['manifest']['counts']['sections'] ?? 0 )
				+ ( $showcase['manifest']['counts']['page_templates'] ?? 0 )
				+ ( $showcase['manifest']['counts']['compositions'] ?? 0 )
				+ ( $showcase['manifest']['counts']['build_plan_recommendation_items'] ?? 0 );
			$payload['template_showcase'] = $this->tag_synthetic( $showcase );
		}

		return new Demo_Fixture_Result(
			true,
			'Demo fixture generation completed. All data is synthetic.',
			$counts,
			$payload,
			true
		);
	}

	/**
	 * Returns registry fixture bundle (sections, page_templates, compositions, documentation, snapshots).
	 *
	 * @return array{sections: array<int, array>, page_templates: array<int, array>, compositions: array<int, array>, documentation: array<int, array>, snapshots: array<int, array>}
	 */
	public function get_registry_fixtures(): array {
		return Registry_Fixture_Builder::full_bundle();
	}

	/**
	 * Returns synthetic brand + business profile matching Profile_Schema shape.
	 *
	 * @return array{brand_profile: array, business_profile: array}
	 */
	public function get_profile_fixture(): array {
		return array(
			Profile_Schema::ROOT_BRAND    => array(
				Profile_Schema::BRAND_VOICE_TONE       => array(
					'formality_level'           => 'neutral',
					'clarity_vs_sophistication' => 'balanced',
					'notes'                     => 'Demo brand voice for QA.',
				),
				Profile_Schema::BRAND_ASSET_REFERENCES => array(
					array(
						'role'  => 'logo',
						'ref'   => 'demo_asset_logo_001',
						'notes' => 'Synthetic reference.',
					),
				),
			),
			Profile_Schema::ROOT_BUSINESS => array(
				Profile_Schema::BUSINESS_PERSONAS        => array(
					array(
						'name'        => 'Demo Persona',
						'description' => 'Synthetic persona for fixture.',
						'priority'    => 1,
					),
				),
				Profile_Schema::BUSINESS_SERVICES_OFFERS => array(
					array(
						'name'                   => 'Demo Service',
						'description'            => 'Synthetic service.',
						'dedicated_pages_likely' => 'yes',
					),
				),
				Profile_Schema::BUSINESS_COMPETITORS     => array(),
				Profile_Schema::BUSINESS_GEOGRAPHY       => array(
					'in_person_vs_remote' => 'both',
					'notes'               => 'Demo geography.',
				),
			),
		);
	}

	/**
	 * Returns synthetic crawl session summary (page records shape for demo).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_crawl_summary_fixture(): array {
		$run_id = self::DEMO_CRAWL_RUN_ID;
		return array(
			array(
				'crawl_run_id'        => $run_id,
				'url'                 => 'https://example.demo/page-one',
				'title_snapshot'      => 'Demo Page One',
				'page_classification' => 'content',
				'crawl_status'        => 'completed',
				'crawled_at'          => '2025-01-15 12:00:00',
			),
			array(
				'crawl_run_id'        => $run_id,
				'url'                 => 'https://example.demo/page-two',
				'title_snapshot'      => 'Demo Page Two',
				'page_classification' => 'content',
				'crawl_status'        => 'completed',
				'crawled_at'          => '2025-01-15 12:01:00',
			),
		);
	}

	/**
	 * Returns synthetic AI run metadata and artifact placeholders (no real provider/keys).
	 *
	 * @return array{run_metadata: array<string, mixed>, artifacts: array<string, mixed>}
	 */
	public function get_ai_run_fixture(): array {
		$metadata  = array(
			'run_id'               => self::DEMO_RUN_ID,
			'actor'                => 'demo_fixture',
			'created_at'           => self::DEMO_TIMESTAMP,
			'completed_at'         => self::DEMO_TIMESTAMP,
			'provider_id'          => 'demo_provider',
			'model_used'           => 'demo-model',
			'prompt_pack_ref'      => 'demo_prompt_pack',
			'retry_count'          => 0,
			'build_plan_ref'       => self::DEMO_PLAN_ID,
			self::SYNTHETIC_MARKER => true,
		);
		$artifacts = array();
		foreach ( array( Artifact_Category_Keys::NORMALIZED_OUTPUT, Artifact_Category_Keys::USAGE_METADATA ) as $cat ) {
			$artifacts[ $cat ] = array(
				'placeholder'          => true,
				'category'             => $cat,
				self::SYNTHETIC_MARKER => true,
			);
		}
		return array(
			'run_metadata' => $metadata,
			'artifacts'    => $artifacts,
		);
	}

	/**
	 * Returns one or more Build Plan definitions conforming to Build_Plan_Schema.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_build_plan_fixture(): array {
		$plan_id = self::DEMO_PLAN_ID;
		$steps   = array(
			array(
				Build_Plan_Item_Schema::KEY_STEP_ID   => 'overview',
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
				Build_Plan_Item_Schema::KEY_TITLE     => 'Overview',
				Build_Plan_Item_Schema::KEY_ORDER     => 1,
				Build_Plan_Item_Schema::KEY_ITEMS     => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEM_ID => 'item-overview-1',
						Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE,
						Build_Plan_Item_Schema::KEY_PAYLOAD => array( 'note' => 'Demo overview item.' ),
						Build_Plan_Item_Schema::KEY_STATUS => Build_Plan_Item_Statuses::COMPLETED,
					),
				),
			),
			array(
				Build_Plan_Item_Schema::KEY_STEP_ID   => 'existing_page_changes',
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
				Build_Plan_Item_Schema::KEY_TITLE     => 'Existing Page Changes',
				Build_Plan_Item_Schema::KEY_ORDER     => 2,
				Build_Plan_Item_Schema::KEY_ITEMS     => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEM_ID => 'item-epc-1',
						Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
						Build_Plan_Item_Schema::KEY_PAYLOAD => array(
							'target' => 'demo_page',
							'action' => 'update_content',
						),
						Build_Plan_Item_Schema::KEY_STATUS => Build_Plan_Item_Statuses::APPROVED,
					),
				),
			),
		);
		$plan    = array(
			Build_Plan_Schema::KEY_PLAN_ID               => $plan_id,
			Build_Plan_Schema::KEY_STATUS                => Build_Plan_Statuses::ROOT_APPROVED,
			Build_Plan_Schema::KEY_AI_RUN_REF            => self::DEMO_RUN_ID,
			Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF => 'demo_normalized_ref',
			Build_Plan_Schema::KEY_PLAN_TITLE            => 'Demo Build Plan',
			Build_Plan_Schema::KEY_PLAN_SUMMARY          => 'Synthetic plan for demo and QA.',
			Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY  => 'Demo site purpose.',
			Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY     => 'Demo flow.',
			Build_Plan_Schema::KEY_STEPS                 => $steps,
			Build_Plan_Schema::KEY_CREATED_AT            => self::DEMO_TIMESTAMP,
			self::SYNTHETIC_MARKER                       => true,
		);
		return array( $plan );
	}

	/**
	 * Returns synthetic log entries (structure only; no real secrets).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_log_example(): array {
		return array(
			array(
				'timestamp' => self::DEMO_TIMESTAMP,
				'level'     => 'info',
				'message'   => 'Demo fixture log entry one.',
				'context'   => array( 'source' => 'demo_fixture' ),
			),
			array(
				'timestamp' => self::DEMO_TIMESTAMP,
				'level'     => 'info',
				'message'   => 'Demo fixture log entry two.',
				'context'   => array( 'source' => 'demo_fixture' ),
			),
		);
	}

	/**
	 * Returns synthetic export result payload (Export_Result shape; no real paths/secrets).
	 *
	 * @return array<string, mixed>
	 */
	public function get_export_example(): array {
		return array(
			'success'              => true,
			'message'              => 'Demo export completed (synthetic).',
			'package_path'         => '',
			'export_mode'          => Export_Mode_Keys::SUPPORT_BUNDLE,
			'included_categories'  => array( 'registries', 'profile', 'plans' ),
			'excluded_categories'  => array( 'raw_ai_artifacts', 'logs' ),
			'checksum_count'       => 3,
			'package_size_bytes'   => 0,
			'log_reference'        => 'demo_export_log_ref',
			'package_filename'     => 'demo-export-bundle.zip',
			self::SYNTHETIC_MARKER => true,
		);
	}

	/**
	 * Tags a value with the synthetic marker for audit (top-level only).
	 *
	 * @param array<string, mixed> $data Payload (associative).
	 * @return array<string, mixed>
	 */
	private function tag_synthetic( array $data ): array {
		if ( isset( $data[ self::SYNTHETIC_MARKER ] ) ) {
			return $data;
		}
		$data[ self::SYNTHETIC_MARKER ] = true;
		return $data;
	}
}
