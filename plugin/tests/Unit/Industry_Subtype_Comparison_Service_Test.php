<?php
/**
 * Unit tests for Industry_Subtype_Comparison_Service: fallback when subtype absent, invalid, or deprecated (Prompt 456).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Comparison_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Subtype_Comparison_Service.php';

final class Industry_Subtype_Comparison_Service_Test extends TestCase {

	private function subtype_def( string $subtype_key, string $parent, string $status = 'active' ): array {
		return array(
			Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => $subtype_key,
			Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => $parent,
			Industry_Subtype_Registry::FIELD_LABEL       => $subtype_key,
			Industry_Subtype_Registry::FIELD_STATUS      => $status,
			Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
		);
	}

	private function bundle_def( string $bundle_key, string $industry_key, string $subtype_key = '' ): array {
		$b = array(
			Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => $industry_key,
			Industry_Starter_Bundle_Registry::FIELD_LABEL => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => '1',
		);
		if ( $subtype_key !== '' ) {
			$b[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] = $subtype_key;
		}
		return $b;
	}

	public function test_get_comparison_with_empty_subtype_returns_parent_only(): void {
		$subtype_reg = new Industry_Subtype_Registry();
		$subtype_reg->load( array( $this->subtype_def( 'realtor_buyer', 'realtor' ) ) );
		$bundle_reg = new Industry_Starter_Bundle_Registry();
		$bundle_reg->load( array( $this->bundle_def( 'realtor_starter', 'realtor' ) ) );
		$service = new Industry_Subtype_Comparison_Service( null, null, $bundle_reg, $subtype_reg, null, null, null, null );
		$out     = $service->get_comparison( 'realtor', '' );
		$this->assertFalse( $out['has_subtype'] );
		$this->assertSame( '', $out['subtype_key'] );
		$this->assertSame( array(), $out['subtype_bundles'] );
		$this->assertGreaterThanOrEqual( 0, count( $out['parent_bundles'] ) );
	}

	public function test_get_comparison_with_unknown_subtype_key_fallback_to_parent_only(): void {
		$subtype_reg = new Industry_Subtype_Registry();
		$subtype_reg->load( array( $this->subtype_def( 'realtor_buyer', 'realtor' ) ) );
		$bundle_reg = new Industry_Starter_Bundle_Registry();
		$bundle_reg->load( array( $this->bundle_def( 'realtor_starter', 'realtor' ) ) );
		$service = new Industry_Subtype_Comparison_Service( null, null, $bundle_reg, $subtype_reg, null, null, null, null );
		$out     = $service->get_comparison( 'realtor', 'unknown_subtype' );
		$this->assertFalse( $out['has_subtype'] );
		$this->assertSame( array(), $out['subtype_bundles'] );
	}

	public function test_get_comparison_with_deprecated_subtype_fallback_to_parent_only(): void {
		$subtype_reg = new Industry_Subtype_Registry();
		$subtype_reg->load( array( $this->subtype_def( 'realtor_deprecated', 'realtor', Industry_Subtype_Registry::STATUS_DEPRECATED ) ) );
		$bundle_reg = new Industry_Starter_Bundle_Registry();
		$bundle_reg->load(
			array(
				$this->bundle_def( 'realtor_starter', 'realtor' ),
				$this->bundle_def( 'realtor_deprecated_bundle', 'realtor', 'realtor_deprecated' ),
			)
		);
		$service = new Industry_Subtype_Comparison_Service( null, null, $bundle_reg, $subtype_reg, null, null, null, null );
		$out     = $service->get_comparison( 'realtor', 'realtor_deprecated' );
		$this->assertFalse( $out['has_subtype'], 'Deprecated subtype must yield has_subtype false (parent-only fallback)' );
		$this->assertSame( array(), $out['subtype_bundles'] );
	}
}
