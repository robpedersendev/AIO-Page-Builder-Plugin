<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Telemetry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

require_once dirname( __DIR__, 2 ) . '/src/Infrastructure/Config/Option_Names.php';
require_once dirname( __DIR__, 2 ) . '/src/Domain/AI/TemplateLab/Template_Lab_Telemetry.php';
require_once dirname( __DIR__, 2 ) . '/src/Infrastructure/Settings/Settings_Service.php';

final class Template_Lab_Telemetry_Test extends TestCase {

	public function tearDown(): void {
		\delete_option( Option_Names::TEMPLATE_LAB_TELEMETRY_AGGREGATE );
		parent::tearDown();
	}

	public function test_bump_increments_counters_without_storing_freeform_payload(): void {
		$settings = new Settings_Service();
		$tel      = new Template_Lab_Telemetry( $settings );
		$tel->bump( Template_Lab_Telemetry::EVENT_SESSION_CREATED );
		$tel->bump( Template_Lab_Telemetry::EVENT_SESSION_CREATED );
		$agg = $tel->get_aggregate_for_diagnostics();
		$this->assertSame( 1, (int) ( $agg['v'] ?? 0 ) );
		$this->assertSame( 2, (int) ( $agg['c'][ Template_Lab_Telemetry::EVENT_SESSION_CREATED ] ?? 0 ) );
		$this->assertArrayNotHasKey( 'prompt', $agg );
	}
}
