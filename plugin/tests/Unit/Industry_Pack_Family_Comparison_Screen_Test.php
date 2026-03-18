<?php
/**
 * Unit tests for Industry_Pack_Family_Comparison_Screen (Prompt 558). State structure and missing-input fallback.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Pack_Family_Comparison_Screen;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Industry_Pack_Family_Comparison_Screen_Test extends TestCase {

	public function test_screen_constants_and_titles(): void {
		$screen = new Industry_Pack_Family_Comparison_Screen( null );
		$this->assertSame( 'aio-page-builder-industry-pack-family-comparison', Industry_Pack_Family_Comparison_Screen::SLUG );
		$this->assertNotSame( '', $screen->get_title() );
		$this->assertNotSame( '', $screen->get_capability() );
	}

	public function test_get_state_with_null_container_returns_bounded_structure(): void {
		$screen = new Industry_Pack_Family_Comparison_Screen( null );
		$method = new ReflectionMethod( Industry_Pack_Family_Comparison_Screen::class, 'get_state' );
		$method->setAccessible( true );
		$state = $method->invoke( $screen );
		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'rows', $state );
		$this->assertArrayHasKey( 'generated_at', $state );
		$this->assertArrayHasKey( 'dashboard_url', $state );
		$this->assertIsArray( $state['rows'] );
		$this->assertSame( array(), $state['rows'] );
		$this->assertNotEmpty( $state['generated_at'] );
		$this->assertNotEmpty( $state['dashboard_url'] );
	}

	public function test_get_state_row_structure_when_rows_present(): void {
		$screen = new Industry_Pack_Family_Comparison_Screen( null );
		$method = new ReflectionMethod( Industry_Pack_Family_Comparison_Screen::class, 'get_state' );
		$method->setAccessible( true );
		$state         = $method->invoke( $screen );
		$required_keys = array( 'pack_key', 'subtype_key', 'scope_label', 'band', 'total', 'gap_count', 'blocker_count' );
		foreach ( $state['rows'] as $row ) {
			foreach ( $required_keys as $key ) {
				$this->assertArrayHasKey( $key, $row );
			}
			$this->assertIsInt( $row['total'] );
			$this->assertIsInt( $row['gap_count'] );
			$this->assertIsInt( $row['blocker_count'] );
		}
	}
}
