<?php
/**
 * Unit tests for diff summarizers (spec §41.4–41.7, §59.11; Prompt 088).
 *
 * Covers Page_Diff_Summarizer (content), Navigation_Diff_Summarizer, Token_Diff_Summarizer,
 * no-meaningful-diff outcomes, and failure when snapshots are missing.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summary_Result;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Type_Keys;
use AIOPageBuilder\Domain\Rollback\Diffs\Navigation_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Diffs\Page_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Diffs\Token_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Summary_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Page_Diff_Summarizer.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Navigation_Diff_Summarizer.php';
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Token_Diff_Summarizer.php';

final class Diff_Summarizer_Test extends TestCase {

	/** Example page pre-change snapshot (object_family page). */
	private static function page_pre_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'post_id'      => 42,
				'post_title'   => 'About Us',
				'post_name'    => 'about-us',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'content_hash' => 'sha256:abc',
				'excerpt'      => 'Short excerpt before.',
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-pre-page-42',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '42',
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_replace_plan_xyz_0_20250312T100000Z',
			Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => 'plan-xyz',
			Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF => 'item-0',
			Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS => Operational_Snapshot_Schema::ROLLBACK_STATUS_AVAILABLE,
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'captured_at'    => '2025-03-12T10:00:00+00:00',
				'state_snapshot' => $state,
			),
		);
	}

	/** Example page post-change snapshot. */
	private static function page_post_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'post_id'     => 42,
				'post_title'  => 'About Our Company',
				'post_name'   => 'about-our-company',
				'post_status' => 'publish',
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-post-page-42',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '42',
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_replace_plan_xyz_0_20250312T100000Z',
			Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => 'plan-xyz',
			Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF => 'item-0',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => array(
				'captured_at'     => '2025-03-12T10:01:00+00:00',
				'result_snapshot' => $state,
				'outcome'         => 'success',
			),
		);
	}

	/** Example menu pre-change snapshot. */
	private static function menu_pre_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'menu_id'  => 5,
				'name'     => 'Primary Menu',
				'location' => 'primary',
				'items'    => array(
					array(
						'id'     => 101,
						'title'  => 'Home',
						'url'    => '/',
						'parent' => 0,
						'order'  => 1,
					),
					array(
						'id'     => 102,
						'title'  => 'About',
						'url'    => '/about',
						'parent' => 0,
						'order'  => 2,
					),
				),
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-pre-menu-5',
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '5',
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_MENU,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_menu_plan_xyz_1_20250312T100500Z',
			Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => 'plan-xyz',
			Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF => 'item-1',
			Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'captured_at'    => '2025-03-12T10:05:00+00:00',
				'state_snapshot' => $state,
			),
		);
	}

	/** Example menu post-change snapshot. */
	private static function menu_post_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'menu_id'  => 5,
				'name'     => 'Main Navigation',
				'location' => 'primary',
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-post-menu-5',
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '5',
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_MENU,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_menu_plan_xyz_1_20250312T100500Z',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => array(
				'captured_at'     => '2025-03-12T10:06:00+00:00',
				'result_snapshot' => $state,
				'outcome'         => 'success',
			),
		);
	}

	/** Example token_set pre-change snapshot. */
	private static function token_pre_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'token_set_id' => 'colors:primary',
				'tokens'       => array(
					'primary'   => array(
						'value' => '#1e40af',
						'role'  => 'brand',
						'group' => 'colors',
					),
					'secondary' => array(
						'value' => '#475569',
						'role'  => 'neutral',
						'group' => 'colors',
					),
				),
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-pre-token-1',
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => 'colors:primary',
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_apply_tokens_plan_xyz_2_20250312T100500Z',
			Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'captured_at'    => '2025-03-12T10:05:00+00:00',
				'state_snapshot' => $state,
			),
		);
	}

	/** Example token_set post-change snapshot. */
	private static function token_post_snapshot( array $state_overrides = array() ): array {
		$state = array_merge(
			array(
				'token_set_id' => 'colors:primary',
				'tokens'       => array(
					'primary'   => array(
						'value'      => '#2563eb',
						'role'       => 'brand',
						'group'      => 'colors',
						'provenance' => 'ai_proposed',
					),
					'secondary' => array(
						'value'      => '#64748b',
						'role'       => 'neutral',
						'group'      => 'colors',
						'provenance' => 'user_overridden',
					),
				),
			),
			$state_overrides
		);
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-post-token-1',
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => 'colors:primary',
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_apply_tokens_plan_xyz_2_20250312T100500Z',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => array(
				'captured_at'     => '2025-03-12T10:06:00+00:00',
				'result_snapshot' => $state,
				'outcome'         => 'success',
			),
		);
	}

	// --- Page diff ---

	public function test_page_diff_summarizer_produces_meaningful_content_diff(): void {
		$summarizer = new Page_Diff_Summarizer();
		$pre        = self::page_pre_snapshot();
		$post       = self::page_post_snapshot();
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->has_meaningful_diff() );
		$diff = $result->get_diff();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_CONTENT, $diff['diff_type'] );
		$this->assertSame( 'summary', $diff['level'] );
		$this->assertSame( '42', $diff['target_ref'] );
		$this->assertSame( 'post', $diff['target_type_hint'] );
		$this->assertStringContainsString( 'About Us', $diff['before_summary'] );
		$this->assertStringContainsString( 'about-us', $diff['before_summary'] );
		$this->assertStringContainsString( 'About Our Company', $diff['after_summary'] );
		$this->assertStringContainsString( 'about-our-company', $diff['after_summary'] );
		$this->assertGreaterThanOrEqual( 2, $diff['change_count'] );
		$this->assertArrayHasKey( 'rollback', $diff );
		$this->assertSame( 'op-snap-pre-page-42', $diff['rollback']['pre_snapshot_id'] );
		$this->assertSame( 'op-snap-post-page-42', $diff['rollback']['post_snapshot_id'] );
		$this->assertArrayHasKey( 'diff_id', $diff );
		$this->assertNotEmpty( $diff['diff_id'] );
	}

	public function test_page_diff_summarizer_detail_includes_family_payload(): void {
		$summarizer = new Page_Diff_Summarizer();
		$pre        = self::page_pre_snapshot();
		$post       = self::page_post_snapshot();
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_DETAIL );
		$this->assertTrue( $result->is_success() );
		$diff = $result->get_diff();
		$this->assertArrayHasKey( 'family_payload', $diff );
		$fp = $diff['family_payload'];
		$this->assertSame( 'About Us', $fp['title_before'] );
		$this->assertSame( 'About Our Company', $fp['title_after'] );
		$this->assertSame( 'about-us', $fp['slug_before'] );
		$this->assertSame( 'about-our-company', $fp['slug_after'] );
		$this->assertSame( 'publish', $fp['status_before'] );
		$this->assertSame( 'publish', $fp['status_after'] );
		$this->assertArrayHasKey( 'content_replacement_indicator', $fp );
	}

	public function test_page_diff_no_meaningful_diff_when_unchanged(): void {
		$summarizer = new Page_Diff_Summarizer();
		$pre        = self::page_pre_snapshot(
			array(
				'post_title'  => 'Same',
				'post_name'   => 'same',
				'post_status' => 'publish',
			)
		);
		$post       = self::page_post_snapshot(
			array(
				'post_title'  => 'Same',
				'post_name'   => 'same',
				'post_status' => 'publish',
			)
		);
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertFalse( $result->has_meaningful_diff() );
		$diff = $result->get_diff();
		$this->assertSame( 0, $diff['change_count'] );
	}

	public function test_page_diff_failure_when_pre_state_missing(): void {
		$summarizer = new Page_Diff_Summarizer();
		$pre        = self::page_pre_snapshot();
		unset( $pre[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] );
		$post   = self::page_post_snapshot();
		$result = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'snapshot_missing', $result->get_fallback_reason() );
	}

	// --- Navigation diff ---

	public function test_navigation_diff_summarizer_produces_meaningful_diff(): void {
		$summarizer = new Navigation_Diff_Summarizer();
		$pre        = self::menu_pre_snapshot();
		$post       = self::menu_post_snapshot(
			array(
				'name'     => 'Main Navigation',
				'location' => 'primary',
			)
		);
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->has_meaningful_diff() );
		$diff = $result->get_diff();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_NAVIGATION, $diff['diff_type'] );
		$this->assertSame( '5', $diff['target_ref'] );
		$this->assertStringContainsString( 'Primary Menu', $diff['before_summary'] );
		$this->assertStringContainsString( 'Main Navigation', $diff['after_summary'] );
		$this->assertGreaterThanOrEqual( 1, $diff['change_count'] );
		$this->assertArrayHasKey( 'rollback', $diff );
	}

	public function test_navigation_diff_detail_includes_family_payload(): void {
		$summarizer = new Navigation_Diff_Summarizer();
		$pre        = self::menu_pre_snapshot();
		$post       = self::menu_post_snapshot(
			array(
				'name'     => 'Footer Menu',
				'location' => 'footer',
			)
		);
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_DETAIL );
		$this->assertTrue( $result->is_success() );
		$diff = $result->get_diff();
		$this->assertArrayHasKey( 'family_payload', $diff );
		$fp = $diff['family_payload'];
		$this->assertSame( 'Primary Menu', $fp['menu_name_before'] );
		$this->assertSame( 'Footer Menu', $fp['menu_name_after'] );
		$this->assertSame( 'primary', $fp['location_before'] );
		$this->assertSame( 'footer', $fp['location_after'] );
		$this->assertArrayHasKey( 'items_added', $fp );
		$this->assertArrayHasKey( 'items_removed', $fp );
		$this->assertArrayHasKey( 'labels_changed', $fp );
	}

	public function test_navigation_diff_no_meaningful_diff_when_unchanged(): void {
		$summarizer = new Navigation_Diff_Summarizer();
		$pre        = self::menu_pre_snapshot(
			array(
				'name'     => 'Same',
				'location' => 'primary',
			)
		);
		$post       = self::menu_post_snapshot(
			array(
				'name'     => 'Same',
				'location' => 'primary',
			)
		);
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertFalse( $result->has_meaningful_diff() );
		$this->assertSame( 0, $result->get_diff()['change_count'] );
	}

	public function test_navigation_diff_failure_when_post_state_missing(): void {
		$summarizer = new Navigation_Diff_Summarizer();
		$pre        = self::menu_pre_snapshot();
		$post       = self::menu_post_snapshot();
		unset( $post[ Operational_Snapshot_Schema::FIELD_POST_CHANGE ] );
		$result = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'snapshot_missing', $result->get_fallback_reason() );
	}

	// --- Token diff ---

	public function test_token_diff_summarizer_produces_meaningful_diff(): void {
		$summarizer = new Token_Diff_Summarizer();
		$pre        = self::token_pre_snapshot();
		$post       = self::token_post_snapshot();
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->has_meaningful_diff() );
		$diff = $result->get_diff();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_TOKEN, $diff['diff_type'] );
		$this->assertSame( 'colors:primary', $diff['target_ref'] );
		$this->assertSame( 2, $diff['change_count'] );
		$this->assertArrayHasKey( 'rollback', $diff );
	}

	public function test_token_diff_detail_includes_changes_array(): void {
		$summarizer = new Token_Diff_Summarizer();
		$pre        = self::token_pre_snapshot();
		$post       = self::token_post_snapshot();
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_DETAIL );
		$this->assertTrue( $result->is_success() );
		$diff = $result->get_diff();
		$this->assertArrayHasKey( 'family_payload', $diff );
		$fp = $diff['family_payload'];
		$this->assertSame( 'colors:primary', $fp['token_set_ref'] );
		$this->assertCount( 2, $fp['changes'] );
		$primary_change = null;
		foreach ( $fp['changes'] as $c ) {
			if ( ( $c['token_key'] ?? '' ) === 'primary' ) {
				$primary_change = $c;
				break;
			}
		}
		$this->assertNotNull( $primary_change );
		$this->assertSame( '#1e40af', $primary_change['value_before'] );
		$this->assertSame( '#2563eb', $primary_change['value_after'] );
		$this->assertSame( 'brand', $primary_change['role'] ?? '' );
		$this->assertSame( 'colors', $primary_change['group'] ?? '' );
	}

	public function test_token_diff_no_meaningful_diff_when_unchanged(): void {
		$summarizer = new Token_Diff_Summarizer();
		$state      = array(
			'token_set_id' => 'colors:primary',
			'tokens'       => array( 'primary' => array( 'value' => '#1e40af' ) ),
		);
		$pre        = self::token_pre_snapshot( $state );
		$post       = self::token_post_snapshot( $state );
		$result     = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertTrue( $result->is_success() );
		$this->assertFalse( $result->has_meaningful_diff() );
		$this->assertSame( 0, $result->get_diff()['change_count'] );
	}

	public function test_token_diff_failure_when_pre_state_missing(): void {
		$summarizer = new Token_Diff_Summarizer();
		$pre        = self::token_pre_snapshot();
		$pre[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] = array( 'captured_at' => 'now' ); // no state_snapshot
		$post   = self::token_post_snapshot();
		$result = $summarizer->summarize( $pre, $post, Diff_Type_Keys::LEVEL_SUMMARY );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'snapshot_missing', $result->get_fallback_reason() );
	}

	// --- Diff_Summary_Result ---

	public function test_diff_summary_result_with_diff_has_meaningful_diff(): void {
		$diff   = array(
			'diff_id'      => 'd1',
			'diff_type'    => 'content',
			'level'        => 'summary',
			'target_ref'   => '42',
			'change_count' => 1,
		);
		$result = Diff_Summary_Result::with_diff( $diff );
		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $result->has_meaningful_diff() );
		$this->assertSame( $diff, $result->get_diff() );
		$this->assertArrayNotHasKey( 'fallback_reason', $result->to_array() );
	}

	public function test_diff_summary_result_no_meaningful_diff(): void {
		$diff   = array(
			'diff_id'      => 'd2',
			'diff_type'    => 'content',
			'level'        => 'summary',
			'target_ref'   => '42',
			'change_count' => 0,
		);
		$result = Diff_Summary_Result::no_meaningful_diff( $diff, 'No changes.' );
		$this->assertTrue( $result->is_success() );
		$this->assertFalse( $result->has_meaningful_diff() );
		$this->assertSame( 'No changes.', $result->get_message() );
	}

	public function test_diff_summary_result_failure_includes_fallback_reason_in_to_array(): void {
		$diff   = array(
			'diff_id'    => 'd3',
			'diff_type'  => 'content',
			'target_ref' => '',
		);
		$result = Diff_Summary_Result::failure( $diff, 'Missing snapshot.', 'snapshot_missing' );
		$this->assertFalse( $result->is_success() );
		$arr = $result->to_array();
		$this->assertArrayHasKey( 'fallback_reason', $arr );
		$this->assertSame( 'snapshot_missing', $arr['fallback_reason'] );
	}

	// --- Example payloads (contract-shaped; diff-service-contract.md §9, §10) ---

	/** Example page (content) diff summary (contract §9). */
	public static function example_page_diff_summary(): array {
		return array(
			'diff_id'          => 'diff-content-abc123',
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_CONTENT,
			'level'            => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => '42',
			'target_type_hint' => 'post',
			'before_summary'   => 'About Us (about-us), publish',
			'after_summary'    => 'About Our Company (about-our-company), publish',
			'change_count'     => 2,
			'execution_ref'    => 'exec_replace_plan_xyz_0_20250312T100000Z',
			'build_plan_ref'   => 'plan-xyz',
			'plan_item_ref'    => 'item-0',
			'rollback'         => array(
				'rollback_eligible' => true,
				'pre_snapshot_id'   => 'op-snap-pre-abc123',
				'post_snapshot_id'  => 'op-snap-post-abc124',
				'rollback_status'   => 'available',
			),
		);
	}

	/** Example navigation diff summary (contract §5.3). */
	public static function example_navigation_diff_summary(): array {
		return array(
			'diff_id'          => 'diff-navigation-def456',
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_NAVIGATION,
			'level'            => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => '5',
			'target_type_hint' => 'term',
			'before_summary'   => 'Primary Menu @ primary, 3 items',
			'after_summary'    => 'Main Navigation @ primary, 4 items',
			'change_count'     => 2,
			'execution_ref'    => 'exec_menu_plan_xyz_1_20250312T100500Z',
			'build_plan_ref'   => 'plan-xyz',
			'plan_item_ref'    => 'item-1',
			'rollback'         => array(
				'rollback_eligible' => true,
				'pre_snapshot_id'   => 'op-snap-pre-menu-5',
				'post_snapshot_id'  => 'op-snap-post-menu-5',
				'rollback_status'   => 'available',
			),
		);
	}

	/** Example token diff (detail level) (contract §10). */
	public static function example_token_diff_summary(): array {
		return array(
			'diff_id'          => 'diff-token-ghi789',
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_TOKEN,
			'level'            => Diff_Type_Keys::LEVEL_DETAIL,
			'target_ref'       => 'design-tokens-primary',
			'target_type_hint' => 'token_set',
			'change_count'     => 2,
			'execution_ref'    => 'exec_apply_tokens_plan_xyz_2_20250312T100500Z',
			'build_plan_ref'   => 'plan-xyz',
			'plan_item_ref'    => 'item-2',
			'rollback'         => array(
				'rollback_eligible' => true,
				'pre_snapshot_id'   => 'op-snap-pre-tok1',
				'post_snapshot_id'  => 'op-snap-post-tok2',
				'rollback_status'   => 'available',
			),
			'family_payload'   => array(
				'token_set_ref' => 'design-tokens-primary',
				'changes'       => array(
					array(
						'token_key'    => 'color.primary',
						'value_before' => '#1e40af',
						'value_after'  => '#2563eb',
						'role'         => 'brand',
						'group'        => 'colors',
						'provenance'   => 'ai_proposed',
					),
					array(
						'token_key'    => 'color.secondary',
						'value_before' => '#475569',
						'value_after'  => '#64748b',
						'role'         => 'neutral',
						'group'        => 'colors',
						'provenance'   => 'user_overridden',
					),
				),
			),
		);
	}

	public function test_example_page_diff_summary_has_contract_shape(): void {
		$ex = self::example_page_diff_summary();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_CONTENT, $ex['diff_type'] );
		$this->assertSame( Diff_Type_Keys::LEVEL_SUMMARY, $ex['level'] );
		$this->assertArrayHasKey( 'before_summary', $ex );
		$this->assertArrayHasKey( 'after_summary', $ex );
		$this->assertArrayHasKey( 'rollback', $ex );
		$this->assertArrayHasKey( 'pre_snapshot_id', $ex['rollback'] );
		$this->assertArrayHasKey( 'post_snapshot_id', $ex['rollback'] );
	}

	public function test_example_navigation_diff_summary_has_contract_shape(): void {
		$ex = self::example_navigation_diff_summary();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_NAVIGATION, $ex['diff_type'] );
		$this->assertArrayHasKey( 'target_ref', $ex );
		$this->assertArrayHasKey( 'rollback', $ex );
	}

	public function test_example_token_diff_summary_has_contract_shape(): void {
		$ex = self::example_token_diff_summary();
		$this->assertSame( Diff_Type_Keys::DIFF_TYPE_TOKEN, $ex['diff_type'] );
		$this->assertSame( Diff_Type_Keys::LEVEL_DETAIL, $ex['level'] );
		$this->assertArrayHasKey( 'family_payload', $ex );
		$this->assertArrayHasKey( 'token_set_ref', $ex['family_payload'] );
		$this->assertArrayHasKey( 'changes', $ex['family_payload'] );
		$this->assertCount( 2, $ex['family_payload']['changes'] );
		$this->assertArrayHasKey( 'token_key', $ex['family_payload']['changes'][0] );
		$this->assertArrayHasKey( 'value_before', $ex['family_payload']['changes'][0] );
		$this->assertArrayHasKey( 'value_after', $ex['family_payload']['changes'][0] );
	}
}
