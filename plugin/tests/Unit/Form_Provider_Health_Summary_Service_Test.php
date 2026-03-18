<?php
/**
 * Unit tests for Form_Provider_Health_Summary_Service (Prompt 239): summary shape, bounded counts.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Reporting\FormProvider\Form_Provider_Health_Summary_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Reporting/FormProvider/Form_Provider_Health_Summary_Service.php';

final class Form_Provider_Health_Summary_Service_Test extends TestCase {

	public function test_build_summary_returns_expected_keys(): void {
		$registry = new Form_Provider_Registry();
		$service  = new Form_Provider_Health_Summary_Service( $registry, null, null, null, null );
		$summary  = $service->build_summary();
		$this->assertArrayHasKey( 'provider_availability', $summary );
		$this->assertArrayHasKey( 'registered_provider_ids', $summary );
		$this->assertArrayHasKey( 'section_templates_with_forms_count', $summary );
		$this->assertArrayHasKey( 'page_templates_using_forms_count', $summary );
		$this->assertArrayHasKey( 'recent_failures_summary', $summary );
		$this->assertArrayHasKey( 'built_at', $summary );
		$this->assertIsArray( $summary['provider_availability'] );
		$this->assertIsArray( $summary['registered_provider_ids'] );
		$this->assertIsInt( $summary['section_templates_with_forms_count'] );
		$this->assertIsInt( $summary['page_templates_using_forms_count'] );
		$this->assertIsArray( $summary['recent_failures_summary'] );
		$this->assertNotEmpty( $summary['built_at'] );
	}

	public function test_build_summary_with_null_repos_returns_zero_counts(): void {
		$registry = new Form_Provider_Registry();
		$service  = new Form_Provider_Health_Summary_Service( $registry, null, null, null, null );
		$summary  = $service->build_summary();
		$this->assertSame( 0, $summary['section_templates_with_forms_count'] );
		$this->assertSame( 0, $summary['page_templates_using_forms_count'] );
	}

	public function test_build_summary_registered_provider_ids_from_registry(): void {
		$registry = new Form_Provider_Registry();
		$service  = new Form_Provider_Health_Summary_Service( $registry, null, null, null, null );
		$summary  = $service->build_summary();
		$this->assertContains( 'ndr_forms', $summary['registered_provider_ids'] );
	}
}
