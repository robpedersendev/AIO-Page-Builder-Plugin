<?php
/**
 * Unit tests for Admin_Post_Edit_Context_Resolver and Admin_Post_Edit_Context_Result (Prompt 293).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Registration\Admin_Post_Edit_Context_Result;
use AIOPageBuilder\Domain\ACF\Registration\Admin_Post_Edit_Context_Resolver;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/Admin_Post_Edit_Context_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Admin_Post_Edit_Context_Resolver.php';

final class Admin_Post_Edit_Context_Resolver_Test extends TestCase {

	public function test_result_existing_page_edit_has_page_id(): void {
		$result = new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::EXISTING_PAGE_EDIT, 42 );
		$this->assertTrue( $result->is_existing_page_edit() );
		$this->assertSame( 42, $result->get_page_id() );
		$this->assertTrue( $result->is_scoped_registration_context() );
	}

	public function test_result_new_page_edit_is_scoped(): void {
		$result = new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NEW_PAGE_EDIT, 0 );
		$this->assertTrue( $result->is_new_page_edit() );
		$this->assertSame( 0, $result->get_page_id() );
		$this->assertTrue( $result->is_scoped_registration_context() );
	}

	public function test_result_non_page_admin_not_scoped(): void {
		$result = new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 );
		$this->assertTrue( $result->is_non_page_admin() );
		$this->assertFalse( $result->is_scoped_registration_context() );
	}

	public function test_result_unsupported_admin_not_scoped(): void {
		$result = new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::UNSUPPORTED_ADMIN, 0 );
		$this->assertTrue( $result->is_unsupported_admin() );
		$this->assertFalse( $result->is_scoped_registration_context() );
	}
}
