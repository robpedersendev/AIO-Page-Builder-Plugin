<?php
/**
 * Unit tests for Industry_Profile_Change_Impact_Service (Prompt 375). Matching vs diverged; missing snapshot.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Rollback\Industry_Profile_Change_Impact_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Rollback/Industry_Profile_Change_Impact_Service.php';

final class Industry_Profile_Change_Impact_Service_Test extends TestCase {

	/** @var Industry_Profile_Change_Impact_Service */
	private $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Industry_Profile_Change_Impact_Service();
	}

	public function test_missing_snapshot_returns_snapshot_missing_and_info(): void {
		$live = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$result = $this->service->evaluate( $live, null, array() );
		$this->assertTrue( $result['snapshot_missing'] );
		$this->assertFalse( $result['has_divergence'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_INFO, $result['severity'] );
		$this->assertNotSame( '', $result['explanation_summary'] );
	}

	public function test_matching_profile_returns_no_divergence(): void {
		$live = array(
			'primary_industry_key'   => 'legal',
			'secondary_industry_keys' => array( 'healthcare' ),
		);
		$snapshot = array(
			'primary_industry_key'   => 'legal',
			'secondary_industry_keys' => array( 'healthcare' ),
			'style_preset_ref'       => null,
		);
		$result = $this->service->evaluate( $live, $snapshot, array( 'plan-1' ) );
		$this->assertFalse( $result['has_divergence'] );
		$this->assertFalse( $result['snapshot_missing'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_NONE, $result['severity'] );
		$this->assertSame( array( 'plan-1' ), $result['affected_artifact_refs'] );
	}

	public function test_primary_changed_returns_warning_divergence(): void {
		$live = array( 'primary_industry_key' => 'healthcare', 'secondary_industry_keys' => array() );
		$snapshot = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$result = $this->service->evaluate( $live, $snapshot, array() );
		$this->assertTrue( $result['has_divergence'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_WARNING, $result['severity'] );
		$this->assertStringContainsString( 'Primary industry changed', $result['explanation_summary'] );
	}

	public function test_secondary_changed_returns_warning_divergence(): void {
		$live = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array( 'retail' ) );
		$snapshot = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array( 'healthcare' ) );
		$result = $this->service->evaluate( $live, $snapshot, array() );
		$this->assertTrue( $result['has_divergence'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_WARNING, $result['severity'] );
		$this->assertStringContainsString( 'Secondary', $result['explanation_summary'] );
	}

	public function test_style_preset_changed_returns_info_divergence(): void {
		$live = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$snapshot = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array(), 'style_preset_ref' => 'legal_compact' );
		$result = $this->service->evaluate( $live, $snapshot, array(), '' );
		$this->assertTrue( $result['has_divergence'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_INFO, $result['severity'] );
		$this->assertStringContainsString( 'Style preset', $result['explanation_summary'] );
	}

	public function test_empty_snapshot_array_with_live_primary_yields_divergence(): void {
		$live = array( 'primary_industry_key' => 'legal', 'secondary_industry_keys' => array() );
		$result = $this->service->evaluate( $live, array(), array() );
		$this->assertFalse( $result['snapshot_missing'] );
		$this->assertTrue( $result['has_divergence'] );
		$this->assertSame( Industry_Profile_Change_Impact_Service::SEVERITY_WARNING, $result['severity'] );
	}
}

