<?php
/**
 * Unit tests for Environment_Validator and Validation_Result.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Bootstrap\Environment_Validator;
use AIOPageBuilder\Bootstrap\Lifecycle_Result;
use AIOPageBuilder\Bootstrap\Validation_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
define( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES', dirname( __DIR__ ) . '/fixtures/wp-plugin-api-stub.php' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Dependency_Requirements.php';
require_once $plugin_root . '/src/Bootstrap/Lifecycle_Manager.php';
require_once $plugin_root . '/src/Bootstrap/Environment_Validator.php';

/**
 * Tests environment validation: WP/PHP blocking, required/optional plugin results.
 */
final class Environment_Validator_Test extends TestCase {

	private function run_validator_with_wp_version( string $wp_version ): Environment_Validator {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = $wp_version;
		$v                     = new Environment_Validator();
		$v->validate();
		return $v;
	}

	public function test_old_wp_fails_blocking(): void {
		$v = $this->run_validator_with_wp_version( '6.5' );
		$this->assertFalse( $v->passes() );
		$found = false;
		foreach ( $v->get_results() as $r ) {
			if ( $r->code === 'wp_version_blocking' && $r->is_blocking ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected one result with code wp_version_blocking and is_blocking' );
	}

	public function test_supported_wp_version_has_no_wp_blocking(): void {
		$v = $this->run_validator_with_wp_version( '6.6' );
		foreach ( $v->get_results() as $r ) {
			if ( $r->code === 'wp_version_blocking' ) {
				$this->fail( 'Should not have wp_version_blocking when WP is 6.6' );
			}
		}
		$this->assertTrue( true, 'No wp_version_blocking when WP is 6.6' );
	}

	public function test_missing_acf_fails_blocking(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = '6.6';
		$v                     = new Environment_Validator();
		$v->validate();
		$found = false;
		foreach ( $v->get_results() as $r ) {
			if ( ( $r->code === 'acf_pro_missing_blocking' || $r->code === 'acf_pro_version_blocking' ) && $r->is_blocking ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected ACF required dependency to produce blocking result when missing' );
	}

	public function test_missing_generateblocks_fails_blocking(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = '6.6';
		$v                     = new Environment_Validator();
		$v->validate();
		$found = false;
		foreach ( $v->get_results() as $r ) {
			if ( ( $r->code === 'generateblocks_missing_blocking' || $r->code === 'generateblocks_version_blocking' ) && $r->is_blocking ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected GenerateBlocks required dependency to produce blocking result when missing' );
	}

	public function test_missing_lpagery_warns_only(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = '6.6';
		$v                     = new Environment_Validator();
		$v->validate();
		$found = false;
		foreach ( $v->get_results() as $r ) {
			if ( $r->code === 'lpagery_missing_warning' ) {
				$this->assertFalse( $r->is_blocking, 'LPagery missing must be warning only, not blocking' );
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected lpagery_missing_warning when LPagery not active' );
	}

	public function test_to_lifecycle_result_blocking_when_validation_fails(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = '6.5';
		$v                     = new Environment_Validator();
		$v->validate();
		$lifecycle = $v->to_lifecycle_result( 'validate_environment' );
		$this->assertTrue( $lifecycle->is_blocking() );
		$this->assertSame( Lifecycle_Result::STATUS_BLOCKING_FAILURE, $lifecycle->status );
		$this->assertNotSame( '', $lifecycle->message );
	}

	public function test_validation_result_structure(): void {
		$r = new Validation_Result( 'platform', 'blocking_failure', 'test_code', 'Test message', true );
		$this->assertSame( 'platform', $r->category );
		$this->assertSame( 'blocking_failure', $r->severity );
		$this->assertSame( 'test_code', $r->code );
		$this->assertSame( 'Test message', $r->message );
		$this->assertTrue( $r->is_blocking );
		$arr = $r->to_array();
		$this->assertArrayHasKey( 'category', $arr );
		$this->assertArrayHasKey( 'severity', $arr );
		$this->assertArrayHasKey( 'code', $arr );
		$this->assertArrayHasKey( 'message', $arr );
		$this->assertArrayHasKey( 'is_blocking', $arr );
	}

	public function test_extension_pack_results_are_never_blocking(): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Intentional override to test version checks.
		$GLOBALS['wp_version'] = '6.6';
		$v                     = new Environment_Validator();
		$v->validate();
		$all_extension_pack_non_blocking = true;
		foreach ( $v->get_results() as $r ) {
			if ( $r->category === Environment_Validator::CATEGORY_EXTENSION_PACK ) {
				$all_extension_pack_non_blocking = $all_extension_pack_non_blocking && ! $r->is_blocking;
			}
		}
		$this->assertTrue( $all_extension_pack_non_blocking, 'Extension-pack results must be informational only, never blocking' );
	}
}
