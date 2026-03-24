<?php
/**
 * Ensures admin_post_* handlers register without relying on admin_menu (admin-post.php contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Admin_Post_Handler_Registrar;
use AIOPageBuilder\Admin\Screens\AI\Profile_Snapshot_History_Panel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Admin\Admin_Post_Handler_Registrar
 */
final class Admin_Post_Handler_Registrar_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_actions'] = array();
	}

	public function test_register_all_registers_core_admin_post_hooks(): void {
		Admin_Post_Handler_Registrar::register_all( null );
		$this->assertNotFalse( \has_action( 'admin_post_aio_seed_form_templates' ) );
		$this->assertNotFalse( \has_action( 'admin_post_aio_save_industry_profile' ) );
		$this->assertNotFalse( \has_action( 'admin_post_aio_export_logs' ) );
		$this->assertNotFalse( \has_action( 'admin_post_aio_import_export_validate' ) );
		$this->assertNotFalse( \has_action( 'admin_post_' . Profile_Snapshot_History_Panel::ACTION_RESTORE ) );
	}
}
