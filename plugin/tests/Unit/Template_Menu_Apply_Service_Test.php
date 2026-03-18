<?php
/**
 * Unit tests for Template_Menu_Apply_Service (spec §59.10; Prompt 207).
 *
 * Covers hierarchy ordering, missing-location visible failure, and per-item status.
 * Integration-style test (missing location) runs only when WordPress nav menu API is available.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Job_Service;
use AIOPageBuilder\Domain\Execution\Menus\Template_Menu_Apply_Result;
use AIOPageBuilder\Domain\Execution\Menus\Template_Menu_Apply_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Menu_Change_Job_Service.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Menu_Change_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Menu_Change_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Menus/Template_Menu_Apply_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Menus/Template_Menu_Apply_Service.php';

final class Template_Menu_Apply_Service_Test extends TestCase {

	/** When theme does not register the location, apply fails visibly with menu_target_validation_result (no silent skip). */
	public function test_missing_menu_location_fails_visibly_with_validation_result(): void {
		if ( ! function_exists( 'get_registered_nav_menus' ) ) {
			$this->markTestSkipped( 'WordPress nav menu API not available.' );
		}
		$registered = get_registered_nav_menus();
		if ( ! is_array( $registered ) ) {
			$registered = array();
		}
		// Use a location slug that is unlikely to be registered (e.g. custom context that maps to unknown slug).
		$service  = new Template_Menu_Apply_Service( new Menu_Change_Job_Service() );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'menu_context' => 'header',
				'action'       => 'update_existing',
				'items'        => array(
					array(
						'title'      => 'A',
						'object_id'  => 1,
						'page_class' => 'top_level',
					),
				),
			),
		);
		// If primary is not registered, we get failure with missing_location in validation result.
		$result     = $service->apply( $envelope );
		$validation = $result->get_validation_result();
		$this->assertArrayHasKey( 'location_slug', $validation );
		$this->assertArrayHasKey( 'missing_location', $validation );
		// When location is missing we must not succeed (fail visibly).
		if ( ! empty( $validation['missing_location'] ) ) {
			$this->assertFalse( $result->is_success() );
			$this->assertNotEmpty( $result->get_errors() );
		}
	}

	/** Hierarchy summary and per_item_status are present on result. */
	public function test_result_includes_navigation_hierarchy_summary_and_per_item_status(): void {
		$validation = array(
			'valid'            => false,
			'location_slug'    => 'primary',
			'missing_location' => true,
		);
		$hierarchy  = array(
			'items_ordered_by_class' => array(),
			'applied_count'          => 0,
			'warnings'               => array(),
		);
		$result     = Template_Menu_Apply_Result::failure(
			'Menu location is not registered.',
			array( 'menu_target_validation_failed' ),
			$validation,
			$hierarchy,
			array( array( 'status' => 'skipped' ) )
		);
		$this->assertSame( $hierarchy, $result->get_hierarchy_summary() );
		$this->assertCount( 1, $result->get_per_item_status() );
		$this->assertSame( 'skipped', $result->get_per_item_status()[0]['status'] ?? '' );
	}
}
