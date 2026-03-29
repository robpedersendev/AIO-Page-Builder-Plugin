<?php
/**
 * Unit tests for prompt experiment service (spec §26, §58.3, Prompt 121).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\PromptPacks\Experiments\Experiment_Result;
use AIOPageBuilder\Domain\AI\PromptPacks\Experiments\Prompt_Experiment_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Experiments/Experiment_Result.php';
require_once $plugin_root . '/src/Domain/AI/PromptPacks/Experiments/Prompt_Experiment_Service.php';
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/AI_Run_Repository.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Artifact_Service.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Service.php';

final class Prompt_Experiment_Service_Test extends TestCase {

	private function get_settings(): Settings_Service {
		$s = new Settings_Service();
		$s->set( Option_Names::PROMPT_EXPERIMENTS, array( 'definitions' => array() ) );
		return $s;
	}

	private function get_service(): Prompt_Experiment_Service {
		$settings    = $this->get_settings();
		$repo        = new AI_Run_Repository();
		$artifact    = new AI_Run_Artifact_Service( $repo );
		$run_service = new AI_Run_Service( $repo, $artifact );
		return new Prompt_Experiment_Service( $settings, $run_service, $repo );
	}

	public function test_validate_definition_requires_name(): void {
		$service = $this->get_service();
		$def     = array(
			'variants' => array(
				array(
					'variant_id'      => 'v1',
					'label'           => 'V1',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/pack',
						'version'      => '1.0',
					),
					'provider_id'     => 'openai',
				),
			),
		);
		$this->assertSame( 'Please enter a name for this comparison.', $service->validate_definition( $def ) );
	}

	public function test_validate_definition_requires_variants(): void {
		$service = $this->get_service();
		$def     = array(
			'name'     => 'Test',
			'variants' => array(),
		);
		$this->assertNotSame( '', $service->validate_definition( $def ) );
	}

	public function test_validate_definition_valid(): void {
		$service = $this->get_service();
		$def     = array(
			'name'     => 'Test',
			'variants' => array(
				array(
					'variant_id'      => 'v1',
					'label'           => 'Baseline',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/build-plan-draft',
						'version'      => '1.0.0',
					),
					'provider_id'     => 'openai',
				),
			),
		);
		$this->assertSame( '', $service->validate_definition( $def ) );
	}

	public function test_save_and_list_definitions(): void {
		$service = $this->get_service();
		$def     = array(
			'name'     => 'Compare packs',
			'variants' => array(
				array(
					'variant_id'      => 'v1',
					'label'           => 'Pack A',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/pack',
						'version'      => '1.0',
					),
					'provider_id'     => 'openai',
				),
			),
		);
		$result  = $service->save_definition( $def );
		$this->assertTrue( $result['ok'] );
		$list = $service->list_definitions();
		$this->assertCount( 1, $list );
		$this->assertSame( 'Compare packs', $list[0]['name'] ?? '' );
		$this->assertArrayHasKey( 'id', $list[0] );
	}

	public function test_record_experiment_run_persists_and_labels(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$service                               = $this->get_service();
		$def                                   = array(
			'name'     => 'Exp',
			'variants' => array(
				array(
					'variant_id'      => 'v1',
					'label'           => 'V1',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/pack',
						'version'      => '1.0',
					),
					'provider_id'     => 'openai',
				),
			),
		);
		$service->save_definition( $def );
		$exp_id = $service->list_definitions()[0]['id'] ?? '';
		$this->assertNotSame( '', $exp_id );

		$run_id   = 'aio-run-exp-' . uniqid( '', true );
		$metadata = array(
			'actor'           => '1',
			'created_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'completed_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'provider_id'     => 'openai',
			'model_used'      => 'gpt-4o',
			'prompt_pack_ref' => array(
				'internal_key' => 'aio/pack',
				'version'      => '1.0',
			),
			'request_id'      => 'req-1',
		);
		$result   = $service->record_experiment_run( $exp_id, 'v1', 'V1', $run_id, $metadata, 'completed', array() );
		$this->assertInstanceOf( Experiment_Result::class, $result );
		$this->assertSame( $run_id, $result->get_run_id() );
		$this->assertSame( $exp_id, $result->get_experiment_id() );
		$this->assertSame( 'v1', $result->get_experiment_variant_id() );
		$this->assertTrue( $result->get_post_id() > 0 );

		$GLOBALS['_aio_wp_query_posts'] = array(
			(object) array(
				'ID'          => 1,
				'post_type'   => 'aio_ai_run',
				'post_title'  => $run_id,
				'post_status' => 'publish',
				'post_name'   => '',
			),
		);
		$runs                           = $service->get_experiment_runs( $exp_id );
		$this->assertCount( 1, $runs );
		$this->assertSame( $run_id, $runs[0]['internal_key'] ?? '' );
		$this->assertTrue( ( $runs[0]['run_metadata']['is_experiment'] ?? false ) === true );
		unset( $GLOBALS['_aio_wp_insert_post_return'], $GLOBALS['_aio_wp_query_posts'] );
	}

	public function test_comparison_summary_groups_by_variant(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$service                               = $this->get_service();
		$def                                   = array(
			'name'     => 'Exp2',
			'variants' => array(
				array(
					'variant_id'      => 'va',
					'label'           => 'Variant A',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/pack',
						'version'      => '1.0',
					),
					'provider_id'     => 'openai',
				),
				array(
					'variant_id'      => 'vb',
					'label'           => 'Variant B',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/pack',
						'version'      => '2.0',
					),
					'provider_id'     => 'anthropic',
				),
			),
		);
		$service->save_definition( $def );
		$exp_id = $service->list_definitions()[0]['id'] ?? '';

		$meta = array(
			'actor'           => '1',
			'created_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'completed_at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'provider_id'     => 'openai',
			'model_used'      => 'gpt-4o',
			'prompt_pack_ref' => array(),
			'request_id'      => 'r1',
		);
		$service->record_experiment_run( $exp_id, 'va', 'Variant A', 'run-va-1', $meta, 'completed', array() );
		$GLOBALS['_aio_wp_insert_post_return'] = 2;
		$meta['provider_id']                   = 'anthropic';
		$meta['model_used']                    = 'claude-3';
		$service->record_experiment_run( $exp_id, 'vb', 'Variant B', 'run-vb-1', $meta, 'failed_validation', array() );

		$GLOBALS['_aio_wp_query_posts'] = array(
			(object) array(
				'ID'          => 1,
				'post_type'   => 'aio_ai_run',
				'post_title'  => 'run-va-1',
				'post_status' => 'publish',
				'post_name'   => '',
			),
			(object) array(
				'ID'          => 2,
				'post_type'   => 'aio_ai_run',
				'post_title'  => 'run-vb-1',
				'post_status' => 'publish',
				'post_name'   => '',
			),
		);
		$summary                        = $service->get_comparison_summary( $exp_id );
		$this->assertSame( $exp_id, $summary['experiment_id'] );
		$this->assertArrayHasKey( 'va', $summary['variants'] );
		$this->assertArrayHasKey( 'vb', $summary['variants'] );
		$this->assertSame( 1, $summary['variants']['va']['total'] );
		$this->assertSame( 1, $summary['variants']['vb']['total'] );
		$this->assertSame( 1, $summary['variants']['va']['runs']['completed'] ?? 0 );
		$this->assertSame( 1, $summary['variants']['vb']['runs']['failed_validation'] ?? 0 );
		unset( $GLOBALS['_aio_wp_insert_post_return'], $GLOBALS['_aio_wp_query_posts'] );
	}

	public function test_production_run_has_no_experiment_label(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 42;
		$settings                              = $this->get_settings();
		$repo                                  = new AI_Run_Repository();
		$artifact                              = new AI_Run_Artifact_Service( $repo );
		$run_service                           = new AI_Run_Service( $repo, $artifact );
		$run_id                                = 'aio-run-prod-' . uniqid( '', true );
		$metadata                              = array(
			'actor'           => '1',
			'created_at'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'provider_id'     => 'openai',
			'model_used'      => 'gpt-4o',
			'prompt_pack_ref' => array(),
			'request_id'      => 'r1',
		);
		$post_id                               = $run_service->create_run( $run_id, $metadata, 'completed', array() );
		$this->assertGreaterThan( 0, $post_id );
		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'          => 42,
				'post_type'   => 'aio_ai_run',
				'post_title'  => $run_id,
				'post_status' => 'publish',
				'post_name'   => '',
			)
		);
		$record                          = $run_service->get_run_by_post_id( $post_id );
		$this->assertNotNull( $record );
		$meta = $record['run_metadata'] ?? array();
		$this->assertEmpty( $meta['is_experiment'] ?? null );
		$this->assertArrayNotHasKey( 'experiment_id', $meta );
		unset( $GLOBALS['_aio_wp_insert_post_return'], $GLOBALS['_aio_get_post_return'] );
	}

	/**
	 * Example experiment-run summary payload (machine-readable shape for UI/API).
	 *
	 * Single run result (Experiment_Result::to_array()):
	 *   { "run_id": "aio-run-example-1", "post_id": 1, "status": "completed", "experiment_id": "<uuid>", "experiment_variant_id": "v1", "variant_label": "Baseline", "message": "" }
	 *
	 * Comparison summary (get_comparison_summary()):
	 *   { "experiment_id": "<uuid>", "variants": { "v1": { "variant_id": "v1", "variant_label": "Baseline", "runs": { "completed": 1 }, "total": 1 }, "v2": { ... } } }
	 */
	public function test_example_experiment_run_summary_payload(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$service                               = $this->get_service();
		$def                                   = array(
			'name'     => 'Example',
			'variants' => array(
				array(
					'variant_id'      => 'v1',
					'label'           => 'Baseline',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/build-plan-draft',
						'version'      => '1.0.0',
					),
					'provider_id'     => 'openai',
				),
				array(
					'variant_id'      => 'v2',
					'label'           => 'Alternate',
					'prompt_pack_ref' => array(
						'internal_key' => 'aio/build-plan-draft',
						'version'      => '1.1.0',
					),
					'provider_id'     => 'anthropic',
				),
			),
		);
		$service->save_definition( $def );
		$exp_id  = $service->list_definitions()[0]['id'] ?? '';
		$meta    = array(
			'actor'           => '1',
			'created_at'      => '2025-03-12T12:00:00Z',
			'completed_at'    => '2025-03-12T12:00:05Z',
			'provider_id'     => 'openai',
			'model_used'      => 'gpt-4o',
			'prompt_pack_ref' => array(),
			'request_id'      => 'req-1',
		);
		$result  = $service->record_experiment_run( $exp_id, 'v1', 'Baseline', 'aio-run-example-1', $meta, 'completed', array() );
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'run_id', $payload );
		$this->assertArrayHasKey( 'post_id', $payload );
		$this->assertArrayHasKey( 'status', $payload );
		$this->assertArrayHasKey( 'experiment_id', $payload );
		$this->assertArrayHasKey( 'experiment_variant_id', $payload );
		$this->assertArrayHasKey( 'variant_label', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$summary = $service->get_comparison_summary( $exp_id );
		$this->assertArrayHasKey( 'experiment_id', $summary );
		$this->assertArrayHasKey( 'variants', $summary );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}
}
