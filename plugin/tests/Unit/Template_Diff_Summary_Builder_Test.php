<?php
/**
 * Unit tests for Template_Diff_Summary_Builder (spec §59.11; Prompt 197).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Summary_Builder;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Diff/Template_Diff_Context.php';
require_once $plugin_root . '/src/Domain/Rollback/Diff/Template_Diff_Summary_Builder.php';

final class Template_Diff_Summary_Builder_Test extends TestCase {

	private static function page_pre_snapshot( array $state_overrides = array() ): array {
		$state = array_merge( array(
			'post_id'      => 42,
			'post_title'   => 'About',
			'post_name'    => 'about',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'content_hash' => 'sha256:abc',
			'excerpt'      => '',
		), $state_overrides );
		return array(
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'captured_at'    => '2025-03-12T10:00:00+00:00',
				'state_snapshot' => $state,
			),
		);
	}

	private static function page_post_snapshot( array $result_overrides = array() ): array {
		$result = array_merge( array(
			'post_id'      => 42,
			'post_title'   => 'About Our Company',
			'post_name'    => 'about-our-company',
			'post_status'  => 'publish',
		), $result_overrides );
		return array(
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_POST_CHANGE   => array(
				'captured_at'     => '2025-03-12T10:01:00+00:00',
				'result_snapshot' => $result,
				'outcome'         => 'success',
			),
		);
	}

	public function test_build_includes_template_key_after_and_family_when_post_has_template_context(): void {
		$builder = new Template_Diff_Summary_Builder();
		$pre  = self::page_pre_snapshot();
		$post = self::page_post_snapshot( array(
			'template_context' => array(
				'template_key'    => 'tpl_services_hub',
				'template_family' => 'services',
				'section_count'   => 5,
			),
		) );
		$summary = $builder->build( $pre, $post );
		$this->assertSame( '', $summary['template_key_before'] );
		$this->assertSame( 'tpl_services_hub', $summary['template_key_after'] );
		$this->assertSame( 'services', $summary['template_family_after'] );
		$this->assertSame( 5, $summary['section_count_after'] );
		$this->assertTrue( $summary['template_structural_change'] ); // empty before -> template after
		$this->assertArrayHasKey( 'rollback_template_context', $summary );
		$this->assertSame( 'tpl_services_hub', $summary['rollback_template_context']['template_key'] );
		$this->assertSame( 'services', $summary['rollback_template_context']['template_family'] );
	}

	public function test_build_includes_template_key_before_when_pre_has_intended_template_key(): void {
		$builder = new Template_Diff_Summary_Builder();
		$pre  = self::page_pre_snapshot( array( 'intended_template_key' => 'tpl_landing_old' ) );
		$post = self::page_post_snapshot( array(
			'template_context' => array(
				'template_key'    => 'tpl_services_hub',
				'template_family' => 'services',
				'section_count'   => 6,
			),
		) );
		$summary = $builder->build( $pre, $post );
		$this->assertSame( 'tpl_landing_old', $summary['template_key_before'] );
		$this->assertSame( 'tpl_services_hub', $summary['template_key_after'] );
		$this->assertTrue( $summary['template_structural_change'] );
	}

	public function test_build_returns_empty_keys_when_no_template_context(): void {
		$builder = new Template_Diff_Summary_Builder();
		$pre  = self::page_pre_snapshot();
		$post = self::page_post_snapshot();
		$summary = $builder->build( $pre, $post );
		$this->assertSame( '', $summary['template_key_before'] );
		$this->assertSame( '', $summary['template_key_after'] );
		$this->assertSame( '', $summary['template_family_after'] );
		$this->assertSame( 0, $summary['section_count_after'] );
		$this->assertFalse( $summary['template_structural_change'] );
		$this->assertArrayHasKey( 'rollback_template_context', $summary );
	}

	public function test_example_template_diff_summary_payload_has_contract_shape(): void {
		$ex = Template_Diff_Summary_Builder::example_template_diff_summary_payload();
		$this->assertArrayHasKey( 'template_key_before', $ex );
		$this->assertArrayHasKey( 'template_key_after', $ex );
		$this->assertArrayHasKey( 'template_family_after', $ex );
		$this->assertArrayHasKey( 'section_count_after', $ex );
		$this->assertArrayHasKey( 'template_structural_change', $ex );
		$this->assertArrayHasKey( 'rollback_template_context', $ex );
		$this->assertSame( 'tpl_services_hub', $ex['template_key_after'] );
		$this->assertTrue( $ex['template_structural_change'] );
		$this->assertIsArray( $ex['rollback_template_context'] );
		$this->assertArrayHasKey( 'template_key', $ex['rollback_template_context'] );
	}
}
