<?php
/**
 * Unit tests for Conversion_Goal_Conflict_Detector (Prompt 503).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Reporting\Conversion_Goal_Conflict_Detector;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once dirname( __DIR__ ) . '/bootstrap_i18n_stub.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Conversion_Goal_Conflict_Detector.php';

final class Conversion_Goal_Conflict_Detector_Test extends TestCase {

	public function test_detect_empty_goal_returns_empty(): void {
		$detector = new Conversion_Goal_Conflict_Detector();
		$profile  = array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' );
		$this->assertSame( array(), $detector->detect( $profile, array() ) );
	}

	public function test_detect_with_goal_and_bundle_not_goal_aware_returns_weak_fit(): void {
		$detector  = new Conversion_Goal_Conflict_Detector();
		$profile   = array(
			Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => 'calls',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'realtor_starter',
		);
		$conflicts = $detector->detect( $profile, array( 'bundle_goal_aware' => false ) );
		$this->assertNotEmpty( $conflicts );
		$types = array_column( $conflicts, 'conflict_type' );
		$this->assertContains( Conversion_Goal_Conflict_Detector::CONFLICT_WEAK_BUNDLE_FIT, $types );
	}
}
