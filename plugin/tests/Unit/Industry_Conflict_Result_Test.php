<?php
/**
 * Unit tests for Industry_Conflict_Result (Prompt 370). create, from_array, should_surface_warning.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Conflict_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Conflict_Result.php';

final class Industry_Conflict_Result_Test extends TestCase {

	public function test_create_returns_shape_with_all_keys(): void {
		$result = Industry_Conflict_Result::create(
			Industry_Conflict_Result::CONFLICT_TYPE_SECTION_FIT,
			array( 'legal', 'healthcare' ),
			Industry_Conflict_Result::RESOLUTION_PRIMARY_WINS,
			'Primary applied.',
			Industry_Conflict_Result::SEVERITY_WARNING
		);
		$this->assertSame( Industry_Conflict_Result::CONFLICT_TYPE_SECTION_FIT, $result[ Industry_Conflict_Result::KEY_CONFLICT_TYPE ] );
		$this->assertSame( array( 'legal', 'healthcare' ), $result[ Industry_Conflict_Result::KEY_SOURCE_INDUSTRIES ] );
		$this->assertSame( Industry_Conflict_Result::RESOLUTION_PRIMARY_WINS, $result[ Industry_Conflict_Result::KEY_RESOLUTION_MODE ] );
		$this->assertSame( 'Primary applied.', $result[ Industry_Conflict_Result::KEY_EXPLANATION ] );
		$this->assertSame( Industry_Conflict_Result::SEVERITY_WARNING, $result[ Industry_Conflict_Result::KEY_SEVERITY ] );
	}

	public function test_from_array_normalizes_and_defaults(): void {
		$raw    = array(
			Industry_Conflict_Result::KEY_CONFLICT_TYPE => 'template_fit',
			Industry_Conflict_Result::KEY_SOURCE_INDUSTRIES => array( 'legal' ),
		);
		$result = Industry_Conflict_Result::from_array( $raw );
		$this->assertSame( 'template_fit', $result[ Industry_Conflict_Result::KEY_CONFLICT_TYPE ] );
		$this->assertSame( array( 'legal' ), $result[ Industry_Conflict_Result::KEY_SOURCE_INDUSTRIES ] );
		$this->assertSame( Industry_Conflict_Result::RESOLUTION_NONE, $result[ Industry_Conflict_Result::KEY_RESOLUTION_MODE ] );
		$this->assertSame( Industry_Conflict_Result::SEVERITY_INFO, $result[ Industry_Conflict_Result::KEY_SEVERITY ] );
	}

	public function test_should_surface_warning_true_for_warning_blocking_unresolved(): void {
		$this->assertTrue( Industry_Conflict_Result::should_surface_warning( array( Industry_Conflict_Result::KEY_SEVERITY => Industry_Conflict_Result::SEVERITY_WARNING ) ) );
		$this->assertTrue( Industry_Conflict_Result::should_surface_warning( array( Industry_Conflict_Result::KEY_SEVERITY => Industry_Conflict_Result::SEVERITY_BLOCKING ) ) );
		$this->assertTrue( Industry_Conflict_Result::should_surface_warning( array( Industry_Conflict_Result::KEY_SEVERITY => Industry_Conflict_Result::SEVERITY_UNRESOLVED ) ) );
	}

	public function test_should_surface_warning_false_for_info(): void {
		$this->assertFalse( Industry_Conflict_Result::should_surface_warning( array( Industry_Conflict_Result::KEY_SEVERITY => Industry_Conflict_Result::SEVERITY_INFO ) ) );
	}
}
