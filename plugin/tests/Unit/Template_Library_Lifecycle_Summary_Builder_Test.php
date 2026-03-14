<?php
/**
 * Unit tests for Template_Library_Lifecycle_Summary_Builder (spec §4.18, §59.13, Prompt 213).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Lifecycle\Template_Library_Lifecycle_Summary_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Lifecycle/Template_Library_Lifecycle_Summary_Builder.php';

final class Template_Library_Lifecycle_Summary_Builder_Test extends TestCase {

	public function test_build_returns_stable_payload_without_repositories(): void {
		$builder = new Template_Library_Lifecycle_Summary_Builder( null, null, null );
		$summary = $builder->build();
		$this->assertIsArray( $summary );
		$this->assertTrue( $summary['built_pages_survive'] );
		$this->assertTrue( $summary['template_registry_exportable'] );
		$this->assertArrayHasKey( 'built_pages_description', $summary );
		$this->assertArrayHasKey( 'template_registry_description', $summary );
		$this->assertArrayHasKey( 'one_pagers_description', $summary );
		$this->assertArrayHasKey( 'appendices_description', $summary );
		$this->assertArrayHasKey( 'previews_description', $summary );
		$this->assertArrayHasKey( 'restore_guidance', $summary );
		$this->assertArrayHasKey( 'deactivation_message', $summary );
		$this->assertNotEmpty( $summary['built_pages_description'] );
		$this->assertNotEmpty( $summary['restore_guidance'] );
	}

	public function test_build_contains_no_secrets(): void {
		$builder = new Template_Library_Lifecycle_Summary_Builder( null, null, null );
		$summary = $builder->build();
		$json = (string) \json_encode( $summary );
		$this->assertStringNotContainsString( 'api_key', $json );
		$this->assertStringNotContainsString( 'password', $json );
		$this->assertStringNotContainsString( 'secret', $json );
	}

	public function test_example_template_library_lifecycle_summary_payload(): void {
		$builder = new Template_Library_Lifecycle_Summary_Builder( null, null, null );
		$payload = $builder->build();
		$this->assertSame( true, $payload['built_pages_survive'] );
		$this->assertSame( true, $payload['template_registry_exportable'] );
		$this->assertArrayHasKey( 'deactivation_message', $payload );
		$this->assertArrayHasKey( 'restore_guidance', $payload );
	}
}
