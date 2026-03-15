<?php
/**
 * Unit tests for Group_Key_Section_Key_Resolver (Prompt 285).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Registration\Group_Key_Section_Key_Resolver;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Group_Key_Section_Key_Resolver.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Group_Key_Section_Key_Resolver_Test extends TestCase {

	private function resolver(): Group_Key_Section_Key_Resolver {
		return new Group_Key_Section_Key_Resolver();
	}

	public function test_valid_plugin_group_key_resolves_to_section_key(): void {
		$r = $this->resolver();
		$this->assertSame( 'st01_hero', $r->group_key_to_section_key( 'group_aio_st01_hero' ) );
		$this->assertSame( 'st05_faq', $r->group_key_to_section_key( 'group_aio_st05_faq' ) );
	}

	public function test_resolution_consistent_with_forward_generator(): void {
		$r = $this->resolver();
		$section_keys = array( 'st01_hero', 'st05_faq', 'child_detail_location_local_01' );
		foreach ( $section_keys as $sk ) {
			$group_key = Field_Key_Generator::group_key( $sk );
			$this->assertSame( $sk, $r->group_key_to_section_key( $group_key ), "group_key($sk) round-trips" );
		}
	}

	public function test_malformed_group_keys_rejected(): void {
		$r = $this->resolver();
		$this->assertSame( '', $r->group_key_to_section_key( '' ) );
		$this->assertSame( '', $r->group_key_to_section_key( 'group_aio_' ) );
		$this->assertSame( '', $r->group_key_to_section_key( 'group_aio_UpperCase' ) );
		$this->assertSame( '', $r->group_key_to_section_key( 'group_aio_st01-hero' ) );
	}

	public function test_non_plugin_foreign_keys_rejected(): void {
		$r = $this->resolver();
		$this->assertSame( '', $r->group_key_to_section_key( 'group_other_st01_hero' ) );
		$this->assertSame( '', $r->group_key_to_section_key( 'group_st01_hero' ) );
		$this->assertSame( '', $r->group_key_to_section_key( 'acf_group_st01_hero' ) );
	}

	public function test_is_plugin_group_key(): void {
		$r = $this->resolver();
		$this->assertTrue( $r->is_plugin_group_key( 'group_aio_st01_hero' ) );
		$this->assertFalse( $r->is_plugin_group_key( 'group_other_xyz' ) );
		$this->assertFalse( $r->is_plugin_group_key( '' ) );
	}

	public function test_group_keys_to_section_keys_deduplicates_and_skips_invalid(): void {
		$r = $this->resolver();
		$group_keys = array( 'group_aio_st01_hero', 'group_aio_st05_faq', 'group_aio_st01_hero', 'group_foreign_xyz', 'invalid' );
		$section_keys = $r->group_keys_to_section_keys( $group_keys );
		$this->assertSame( array( 'st01_hero', 'st05_faq' ), $section_keys );
	}
}
