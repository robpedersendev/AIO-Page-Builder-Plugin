<?php
/**
 * Unit tests for AI_Run_Artifact_Service: redaction (spec §29.11) and category keys.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Runs/Artifact_Category_Keys.php';
require_once $plugin_root . '/src/Domain/AI/Runs/AI_Run_Artifact_Service.php';

final class AI_Run_Artifact_Service_Test extends TestCase {

	public function test_redact_sensitive_values_replaces_api_key(): void {
		$in  = array(
			'provider_id' => 'openai',
			'api_key'     => 'sk-secret',
			'model'       => 'gpt-4',
		);
		$out = AI_Run_Artifact_Service::redact_sensitive_values( $in );
		$this->assertSame( 'openai', $out['provider_id'] );
		$this->assertSame( '[redacted]', $out['api_key'] );
		$this->assertSame( 'gpt-4', $out['model'] );
	}

	public function test_redact_sensitive_values_nested(): void {
		$in  = array(
			'usage' => array(
				'token' => 'secret-tok',
				'count' => 10,
			),
		);
		$out = AI_Run_Artifact_Service::redact_sensitive_values( $in );
		$this->assertSame( '[redacted]', $out['usage']['token'] );
		$this->assertSame( 10, $out['usage']['count'] );
	}

	public function test_redact_sensitive_values_preserves_non_sensitive(): void {
		$in  = array(
			'actor'       => '1',
			'created_at'  => '2025-01-01',
			'retry_count' => 2,
		);
		$out = AI_Run_Artifact_Service::redact_sensitive_values( $in );
		$this->assertSame( $in, $out );
	}

	public function test_redact_before_display_categories_are_subset_of_all(): void {
		$all    = Artifact_Category_Keys::all();
		$redact = Artifact_Category_Keys::REDACT_BEFORE_DISPLAY;
		foreach ( $redact as $cat ) {
			$this->assertContains( $cat, $all, "Redact category {$cat} must be in all categories" );
		}
	}
}
