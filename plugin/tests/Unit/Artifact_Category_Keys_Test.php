<?php
/**
 * Unit tests for Artifact_Category_Keys (spec §29.1).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';

final class Artifact_Category_Keys_Test extends TestCase {

	public function test_all_returns_non_empty_list(): void {
		$all = Artifact_Category_Keys::all();
		$this->assertIsArray( $all );
		$this->assertNotEmpty( $all );
		$this->assertContains( Artifact_Category_Keys::RAW_PROMPT, $all );
		$this->assertContains( Artifact_Category_Keys::NORMALIZED_OUTPUT, $all );
		$this->assertContains( Artifact_Category_Keys::VALIDATION_REPORT, $all );
	}

	public function test_is_valid_accepts_all_constants(): void {
		foreach ( Artifact_Category_Keys::all() as $cat ) {
			$this->assertTrue( Artifact_Category_Keys::is_valid( $cat ), "Category {$cat} should be valid" );
		}
	}

	public function test_is_valid_rejects_unknown(): void {
		$this->assertFalse( Artifact_Category_Keys::is_valid( 'unknown_cat' ) );
		$this->assertFalse( Artifact_Category_Keys::is_valid( '' ) );
	}

	public function test_redact_before_display_includes_raw_prompt_and_response(): void {
		$redact = Artifact_Category_Keys::REDACT_BEFORE_DISPLAY;
		$this->assertContains( Artifact_Category_Keys::RAW_PROMPT, $redact );
		$this->assertContains( Artifact_Category_Keys::RAW_PROVIDER_RESPONSE, $redact );
		$this->assertContains( Artifact_Category_Keys::NORMALIZED_PROMPT_PACKAGE, $redact );
	}

	public function test_category_separation_raw_vs_normalized(): void {
		$this->assertNotSame( Artifact_Category_Keys::RAW_PROMPT, Artifact_Category_Keys::NORMALIZED_OUTPUT );
		$this->assertNotSame( Artifact_Category_Keys::RAW_PROVIDER_RESPONSE, Artifact_Category_Keys::NORMALIZED_OUTPUT );
	}
}
