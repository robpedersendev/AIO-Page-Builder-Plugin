<?php
/**
 * Unit tests for LPagery token compatibility: supported mappings, unsupported warnings, canonical identity (spec §7.4, §20.7, §35, Prompt 126).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Token_Compatibility_Service;
use AIOPageBuilder\Domain\Rendering\LPagery\LPagery_Token_Mapping_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/LPagery/LPagery_Token_Mapping_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/LPagery/LPagery_Token_Compatibility_Service.php';

final class LPagery_Token_Compatibility_Service_Test extends TestCase {

	public function test_map_core_to_lpagery_supported_returns_reversible_result(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'color', 'primary' );
		$this->assertInstanceOf( LPagery_Token_Mapping_Result::class, $result );
		$this->assertTrue( $result->is_supported() );
		$this->assertTrue( $result->is_reversible() );
		$this->assertSame( 'color.primary', $result->get_lpagery_key() );
		$this->assertSame( 'color', $result->get_canonical_token_group() );
		$this->assertSame( 'primary', $result->get_canonical_token_name() );
		$this->assertNull( $result->get_warning() );
	}

	public function test_map_lpagery_to_core_supported_preserves_canonical_identity(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_lpagery_to_core( 'color.primary' );
		$this->assertTrue( $result->is_supported() );
		$this->assertTrue( $result->is_reversible() );
		$this->assertSame( 'color', $result->get_canonical_token_group() );
		$this->assertSame( 'primary', $result->get_canonical_token_name() );
		$this->assertSame( 'color.primary', $result->get_lpagery_key() );
	}

	public function test_roundtrip_core_to_lpagery_to_core_preserves_identity(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$r1      = $service->map_core_to_lpagery( 'typography', 'heading' );
		$this->assertTrue( $r1->is_supported() );
		$r2 = $service->map_lpagery_to_core( $r1->get_lpagery_key() );
		$this->assertTrue( $r2->is_supported() );
		$this->assertSame( 'typography', $r2->get_canonical_token_group() );
		$this->assertSame( 'heading', $r2->get_canonical_token_name() );
	}

	public function test_unsupported_invalid_group_returns_warning(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'unsupported_group', 'primary' );
		$this->assertFalse( $result->is_supported() );
		$this->assertFalse( $result->is_reversible() );
		$this->assertNotEmpty( $result->get_warning() );
		$this->assertSame( '', $result->get_lpagery_key() );
		$this->assertSame( 'unsupported_group', $result->get_canonical_token_group() );
		$this->assertSame( 'primary', $result->get_canonical_token_name() );
	}

	public function test_unsupported_empty_name_returns_warning(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'color', '' );
		$this->assertFalse( $result->is_supported() );
		$this->assertNotEmpty( $result->get_warning() );
	}

	public function test_unsupported_malformed_lpagery_key_returns_warning(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_lpagery_to_core( 'no-dot' );
		$this->assertFalse( $result->is_supported() );
		$this->assertNotEmpty( $result->get_warning() );
	}

	public function test_unsupported_empty_lpagery_key_returns_warning(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_lpagery_to_core( '' );
		$this->assertFalse( $result->is_supported() );
		$this->assertNotEmpty( $result->get_warning() );
	}

	public function test_canonical_identity_never_altered_mapping_only_exposes_lpagery_key(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'spacing', 'medium' );
		$this->assertSame( 'spacing', $result->get_canonical_token_group() );
		$this->assertSame( 'medium', $result->get_canonical_token_name() );
		$this->assertSame( 'spacing.medium', $result->get_lpagery_key() );
		// Canonical (group, name) are the source of truth; lpagery_key is derived, not a replacement.
		$this->assertNotSame( $result->get_lpagery_key(), $result->get_canonical_token_group() );
	}

	public function test_get_allowed_groups_returns_readonly_list(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$groups  = $service->get_allowed_groups();
		$this->assertIsArray( $groups );
		$this->assertContains( 'color', $groups );
		$this->assertContains( 'typography', $groups );
		$this->assertContains( 'spacing', $groups );
		$this->assertContains( 'radius', $groups );
		$this->assertContains( 'shadow', $groups );
		$this->assertContains( 'component', $groups );
	}

	/**
	 * Example LPagery compatibility summary payload (spec §7.4, §20.7). Stable schema for diagnostics/API.
	 */
	public function test_compatibility_summary_payload_structure_and_example(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$summary = $service->get_compatibility_summary();

		$this->assertArrayHasKey( 'allowed_groups', $summary );
		$this->assertArrayHasKey( 'mapping_convention', $summary );
		$this->assertArrayHasKey( 'canonical_identity_preserved', $summary );
		$this->assertArrayHasKey( 'sample_mappings', $summary );
		$this->assertArrayHasKey( 'unsupported_warnings', $summary );

		$this->assertSame( array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' ), $summary['allowed_groups'] );
		$this->assertSame( 'group.name', $summary['mapping_convention'] );
		$this->assertTrue( $summary['canonical_identity_preserved'] );
		$this->assertIsArray( $summary['sample_mappings'] );
		$this->assertSame( array(), $summary['unsupported_warnings'] );

		// One full example lpagery_compatibility_summary payload (no pseudocode).
		$example_lpagery_compatibility_summary = array(
			'allowed_groups'               => array( 'color', 'typography', 'spacing', 'radius', 'shadow', 'component' ),
			'mapping_convention'           => 'group.name',
			'canonical_identity_preserved' => true,
			'sample_mappings'              => array(
				array(
					'canonical_group' => 'color',
					'canonical_name'  => 'primary',
					'lpagery_key'     => 'color.primary',
				),
				array(
					'canonical_group' => 'typography',
					'canonical_name'  => 'heading',
					'lpagery_key'     => 'typography.heading',
				),
				array(
					'canonical_group' => 'spacing',
					'canonical_name'  => 'medium',
					'lpagery_key'     => 'spacing.medium',
				),
				array(
					'canonical_group' => 'radius',
					'canonical_name'  => 'default',
					'lpagery_key'     => 'radius.default',
				),
				array(
					'canonical_group' => 'shadow',
					'canonical_name'  => 'card',
					'lpagery_key'     => 'shadow.card',
				),
				array(
					'canonical_group' => 'component',
					'canonical_name'  => 'button',
					'lpagery_key'     => 'component.button',
				),
			),
			'unsupported_warnings'         => array(),
		);
		$this->assertEquals( $example_lpagery_compatibility_summary, $summary );
	}

	/**
	 * Example lpagery_token_mapping result payload (single mapping to_array()).
	 */
	public function test_lpagery_token_mapping_result_payload_example(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'color', 'primary' );
		$payload = $result->to_array();

		$example_lpagery_token_mapping = array(
			'supported'             => true,
			'canonical_token_group' => 'color',
			'canonical_token_name'  => 'primary',
			'lpagery_key'           => 'color.primary',
			'warning'               => null,
			'reversible'            => true,
		);
		$this->assertEquals( $example_lpagery_token_mapping, $payload );
	}

	public function test_sanitized_token_name_in_lpagery_key(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$result  = $service->map_core_to_lpagery( 'color', 'primary-accent_2' );
		$this->assertTrue( $result->is_supported() );
		$this->assertSame( 'color.primary-accent_2', $result->get_lpagery_key() );
	}

	/** Prompt 179: validate_token_key supported and unsupported. */
	public function test_validate_token_key_supported(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$out     = $service->validate_token_key( 'color.primary' );
		$this->assertTrue( $out['supported'] );
		$this->assertSame( '', $out['reason'] );
	}

	public function test_validate_token_key_unsupported(): void {
		$service = new LPagery_Token_Compatibility_Service();
		$out     = $service->validate_token_key( 'no-dot' );
		$this->assertFalse( $out['supported'] );
		$this->assertNotEmpty( $out['reason'] );
	}
}
