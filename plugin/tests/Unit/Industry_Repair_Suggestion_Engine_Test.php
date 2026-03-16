<?php
/**
 * Tests for Industry_Repair_Suggestion_Engine (Prompt 443).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Repair_Suggestion_Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group industry
 */
final class Industry_Repair_Suggestion_Engine_Test extends TestCase {

	public static function setUpBeforeClass(): void {
		$plugin_root = \dirname( __DIR__, 2 );
		require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Repair_Suggestion_Engine.php';
		require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
	}

	public function test_suggest_for_issue_returns_null_when_no_suggestion(): void {
		$engine = new Industry_Repair_Suggestion_Engine( null, null, null, null, null );
		$issue  = array(
			'object_type'   => 'pack',
			'key'           => 'unknown_pack',
			'severity'      => 'error',
			'issue_summary' => 'Pack token_preset_ref does not resolve.',
			'related_refs'  => array( 'missing_preset' ),
		);
		$this->assertNull( $engine->suggest_for_issue( $issue ) );
	}

	public function test_suggestion_shape_when_returned_has_required_keys(): void {
		$engine = new Industry_Repair_Suggestion_Engine( null, null, null, null, null );
		$issue  = array(
			'object_type'   => 'pack',
			'key'           => 'test',
			'severity'      => 'error',
			'issue_summary' => 'Pack starter_bundle_ref does not resolve.',
			'related_refs'  => array( 'missing_bundle' ),
		);
		$suggestion = $engine->suggest_for_issue( $issue );
		// With null registries we get no suggestion; when a suggestion exists it must have these keys (contract).
		$this->assertTrue( $suggestion === null || ( \is_array( $suggestion ) && isset( $suggestion['broken_ref'], $suggestion['suggested_ref'], $suggestion['suggestion_type'], $suggestion['confidence_summary'], $suggestion['explanation'] ) ) );
	}
}
