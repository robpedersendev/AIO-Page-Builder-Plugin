<?php
/**
 * Unit tests for Scoped_Registration_Inspection_Service (Prompt 308).
 * Verifies inspection output shape, resolution logic, and no sensitive data.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\ACF\Registration\Group_Key_Section_Key_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Page_Section_Key_Cache_Service;
use AIOPageBuilder\Domain\ACF\Registration\Scoped_Registration_Inspection_Service;
use AIOPageBuilder\Domain\Templates\Template_Section_Key_Derivation_Result;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Group_Key_Section_Key_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Page_Section_Key_Cache_Service.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Scoped_Registration_Inspection_Service.php';
require_once $plugin_root . '/src/Domain/Templates/Template_Section_Key_Derivation_Result.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Scoped_Registration_Inspection_Service_Test extends TestCase {

	private function create_resolver(): Group_Key_Section_Key_Resolver {
		return new Group_Key_Section_Key_Resolver();
	}

	/** Prompt 308: inspect_for_page returns structured result; no sensitive data (keys only). */
	public function test_inspect_for_page_returns_keys_only(): void {
		$assignment = $this->createMock( Page_Field_Group_Assignment_Service::class );
		$assignment->method( 'get_visible_groups_for_page' )->with( 42 )->willReturn( array( 'group_aio_st_hero', 'group_aio_st_cta' ) );
		$derivation = $this->createMock( Field_Group_Derivation_Service::class );
		$service = new Scoped_Registration_Inspection_Service( $assignment, $this->create_resolver(), $derivation, null );
		$result = $service->inspect_for_page( 42 );
		$this->assertSame( 'existing_page', $result['mode'] );
		$this->assertIsArray( $result['section_keys'] );
		$this->assertIsArray( $result['group_keys'] );
		$this->assertContains( 'st_hero', $result['section_keys'] );
		$this->assertContains( 'st_cta', $result['section_keys'] );
		$this->assertContains( 'group_aio_st_hero', $result['group_keys'] );
		$this->assertArrayHasKey( 'cache_used', $result );
		$this->assertArrayHasKey( 'resolved', $result );
		$this->assertTrue( $result['resolved'] );
		$this->assertFalse( $result['cache_used'] );
	}

	/** Prompt 308: inspect_for_page with invalid page_id returns resolved false. */
	public function test_inspect_for_page_zero_returns_unresolved(): void {
		$assignment = $this->createMock( Page_Field_Group_Assignment_Service::class );
		$derivation = $this->createMock( Field_Group_Derivation_Service::class );
		$service = new Scoped_Registration_Inspection_Service( $assignment, $this->create_resolver(), $derivation, null );
		$result = $service->inspect_for_page( 0 );
		$this->assertSame( 'existing_page', $result['mode'] );
		$this->assertSame( array(), $result['section_keys'] );
		$this->assertSame( array(), $result['group_keys'] );
		$this->assertFalse( $result['resolved'] );
	}

	/** Prompt 308: inspect_for_new_page_template returns structured result. */
	public function test_inspect_for_new_page_template_returns_keys_only(): void {
		$assignment = $this->createMock( Page_Field_Group_Assignment_Service::class );
		$derivation = $this->createMock( Field_Group_Derivation_Service::class );
		$derivation->method( 'derive_section_keys_from_template_for_registration' )->with( 'pt_landing' )->willReturn( new Template_Section_Key_Derivation_Result( array( 'st_hero', 'st_faq' ), true ) );
		$service = new Scoped_Registration_Inspection_Service( $assignment, $this->create_resolver(), $derivation, null );
		$result = $service->inspect_for_new_page_template( 'pt_landing' );
		$this->assertSame( 'new_page_template', $result['mode'] );
		$this->assertSame( array( 'st_hero', 'st_faq' ), $result['section_keys'] );
		$this->assertContains( 'group_aio_st_hero', $result['group_keys'] );
		$this->assertContains( 'group_aio_st_faq', $result['group_keys'] );
		$this->assertTrue( $result['resolved'] );
	}

	/** Prompt 308: inspect_for_new_page_composition returns structured result. */
	public function test_inspect_for_new_page_composition_returns_keys_only(): void {
		$assignment = $this->createMock( Page_Field_Group_Assignment_Service::class );
		$derivation = $this->createMock( Field_Group_Derivation_Service::class );
		$derivation->method( 'derive_section_keys_from_composition_for_registration' )->with( 'comp_1' )->willReturn( new Template_Section_Key_Derivation_Result( array( 'st_cta' ), true ) );
		$service = new Scoped_Registration_Inspection_Service( $assignment, $this->create_resolver(), $derivation, null );
		$result = $service->inspect_for_new_page_composition( 'comp_1' );
		$this->assertSame( 'new_page_composition', $result['mode'] );
		$this->assertSame( array( 'st_cta' ), $result['section_keys'] );
		$this->assertContains( 'group_aio_st_cta', $result['group_keys'] );
		$this->assertTrue( $result['resolved'] );
	}

	/** Prompt 308: cache_used true when cache returns data. */
	public function test_inspect_for_page_reports_cache_used_when_cache_hit(): void {
		$assignment = $this->createMock( Page_Field_Group_Assignment_Service::class );
		$cache = new Page_Section_Key_Cache_Service( 60 );
		$cache->set_for_page( 10, array( 'st_hero' ) );
		$derivation = $this->createMock( Field_Group_Derivation_Service::class );
		$service = new Scoped_Registration_Inspection_Service( $assignment, $this->create_resolver(), $derivation, $cache );
		$result = $service->inspect_for_page( 10 );
		$this->assertTrue( $result['cache_used'] );
		$this->assertSame( array( 'st_hero' ), $result['section_keys'] );
		$this->assertContains( 'group_aio_st_hero', $result['group_keys'] );
	}
}
