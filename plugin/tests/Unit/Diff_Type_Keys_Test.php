<?php
/**
 * Unit tests for Diff_Type_Keys: diff type and level validation (spec §41.4–41.7; Prompt 086).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rollback/Diffs/Diff_Type_Keys.php';

final class Diff_Type_Keys_Test extends TestCase {

	public function test_diff_types_cover_four_families(): void {
		$types = Diff_Type_Keys::get_diff_types();
		$this->assertContains( Diff_Type_Keys::DIFF_TYPE_CONTENT, $types );
		$this->assertContains( Diff_Type_Keys::DIFF_TYPE_STRUCTURE, $types );
		$this->assertContains( Diff_Type_Keys::DIFF_TYPE_NAVIGATION, $types );
		$this->assertContains( Diff_Type_Keys::DIFF_TYPE_TOKEN, $types );
		$this->assertCount( 4, $types );
	}

	public function test_is_valid_diff_type(): void {
		$this->assertTrue( Diff_Type_Keys::is_valid_diff_type( Diff_Type_Keys::DIFF_TYPE_CONTENT ) );
		$this->assertTrue( Diff_Type_Keys::is_valid_diff_type( Diff_Type_Keys::DIFF_TYPE_TOKEN ) );
		$this->assertFalse( Diff_Type_Keys::is_valid_diff_type( 'metadata' ) );
		$this->assertFalse( Diff_Type_Keys::is_valid_diff_type( '' ) );
	}

	public function test_levels_are_summary_and_detail(): void {
		$levels = Diff_Type_Keys::get_levels();
		$this->assertContains( Diff_Type_Keys::LEVEL_SUMMARY, $levels );
		$this->assertContains( Diff_Type_Keys::LEVEL_DETAIL, $levels );
		$this->assertCount( 2, $levels );
	}

	public function test_is_valid_level(): void {
		$this->assertTrue( Diff_Type_Keys::is_valid_level( Diff_Type_Keys::LEVEL_SUMMARY ) );
		$this->assertTrue( Diff_Type_Keys::is_valid_level( Diff_Type_Keys::LEVEL_DETAIL ) );
		$this->assertFalse( Diff_Type_Keys::is_valid_level( 'full' ) );
	}

	/** Example summary diff shape (diff-service-contract.md §9) has required root keys. */
	public function test_example_summary_diff_has_contract_root_keys(): void {
		$example = array(
			'diff_id'        => 'diff-content-abc123',
			'diff_type'      => Diff_Type_Keys::DIFF_TYPE_CONTENT,
			'level'          => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'     => '42',
			'before_summary' => 'About Us (about-us), published',
			'after_summary'  => 'About Our Company (about-our-company), published',
		);
		$this->assertTrue( Diff_Type_Keys::is_valid_diff_type( $example['diff_type'] ) );
		$this->assertTrue( Diff_Type_Keys::is_valid_level( $example['level'] ) );
		$this->assertArrayHasKey( 'diff_id', $example );
		$this->assertArrayHasKey( 'target_ref', $example );
		$this->assertArrayHasKey( 'before_summary', $example );
		$this->assertArrayHasKey( 'after_summary', $example );
	}
}
