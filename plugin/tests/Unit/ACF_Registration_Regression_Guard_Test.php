<?php
/**
 * Regression guards: generic request paths must never call register_all() (Prompt 307).
 * These tests fail if unconditional full ACF registration is reintroduced on front-end or generic admin.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Registrar_Interface;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Registration_Bootstrap_Controller;
use AIOPageBuilder\Domain\ACF\Registration\Admin_Post_Edit_Context_Result;
use AIOPageBuilder\Domain\ACF\Registration\Admin_Post_Edit_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Existing_Page_ACF_Registration_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Group_Key_Section_Key_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\New_Page_ACF_Registration_Context_Resolver;
use AIOPageBuilder\Domain\ACF\Registration\Registration_Request_Context;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Registration_Request_Context.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Group_Key_Section_Key_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Admin_Post_Edit_Context_Result.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Admin_Post_Edit_Context_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Existing_Page_ACF_Registration_Context_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/New_Page_ACF_Registration_Context_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Registration_Bootstrap_Controller.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Regression guard: run_registration() must never call register_all() on any generic path.
 */
final class ACF_Registration_Regression_Guard_Test extends TestCase {

	private function create_resolver(): Group_Key_Section_Key_Resolver {
		return new Group_Key_Section_Key_Resolver();
	}

	private function create_admin_resolver( Admin_Post_Edit_Context_Result $result ): Admin_Post_Edit_Context_Resolver {
		$mock = $this->createMock( Admin_Post_Edit_Context_Resolver::class );
		$mock->method( 'resolve' )->willReturn( $result );
		return $mock;
	}

	private function create_existing_page_resolver_returning_null(): Existing_Page_ACF_Registration_Context_Resolver {
		$mock = $this->createMock( Existing_Page_ACF_Registration_Context_Resolver::class );
		$mock->method( 'get_section_keys_for_current_request' )->willReturn( null );
		return $mock;
	}

	private function create_new_page_resolver_returning_null(): New_Page_ACF_Registration_Context_Resolver {
		$mock = $this->createMock( New_Page_ACF_Registration_Context_Resolver::class );
		$mock->method( 'get_section_keys_for_current_request' )->willReturn( null );
		return $mock;
	}

	private function controller( ACF_Group_Registrar_Interface $registrar, Registration_Request_Context $request_context, ?Admin_Post_Edit_Context_Resolver $admin = null ): ACF_Registration_Bootstrap_Controller {
		$admin = $admin ?? $this->create_admin_resolver( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 ) );
		return new ACF_Registration_Bootstrap_Controller(
			$registrar,
			$request_context,
			$this->create_resolver(),
			$this->create_existing_page_resolver_returning_null(),
			$this->create_new_page_resolver_returning_null(),
			$admin,
			null
		);
	}

	/** Regression guard (Prompt 307): front-end must never trigger register_all. */
	public function test_regression_front_end_never_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( true );
		$controller = $this->controller( $registrar, $context );
		$this->assertSame( 0, $controller->run_registration(), 'Front-end must register zero groups' );
	}

	/** Regression guard (Prompt 307): generic admin (non-page) must never trigger register_all. */
	public function test_regression_non_page_admin_never_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$admin      = $this->create_admin_resolver( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 ) );
		$controller = $this->controller( $registrar, $context, $admin );
		$this->assertSame( 0, $controller->run_registration(), 'Non-page admin must register zero groups' );
	}

	/** Regression guard (Prompt 307): existing-page with null section keys must never trigger register_all. */
	public function test_regression_existing_page_null_section_keys_never_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$existing   = $this->create_existing_page_resolver_returning_null();
		$admin      = $this->create_admin_resolver( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::EXISTING_PAGE_EDIT, 1 ) );
		$controller = new ACF_Registration_Bootstrap_Controller(
			$registrar,
			$context,
			$this->create_resolver(),
			$existing,
			$this->create_new_page_resolver_returning_null(),
			$admin,
			null
		);
		$this->assertSame( 0, $controller->run_registration(), 'Existing page with null section keys must register zero groups' );
	}

	/** Regression guard (Prompt 307): new-page with null section keys must never trigger register_all. */
	public function test_regression_new_page_null_section_keys_never_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$new_page   = $this->create_new_page_resolver_returning_null();
		$admin      = $this->create_admin_resolver( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NEW_PAGE_EDIT, 0 ) );
		$controller = new ACF_Registration_Bootstrap_Controller(
			$registrar,
			$context,
			$this->create_resolver(),
			$this->create_existing_page_resolver_returning_null(),
			$new_page,
			$admin,
			null
		);
		$this->assertSame( 0, $controller->run_registration(), 'New page with null section keys must register zero groups' );
	}

	/** Regression guard (Prompt 307): scoped path must use register_sections only, never register_all. */
	public function test_regression_scoped_path_uses_register_sections_only(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$registrar->method( 'register_sections' )->with( array( 'st_hero' ) )->willReturn( 1 );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$existing = $this->createMock( Existing_Page_ACF_Registration_Context_Resolver::class );
		$existing->method( 'get_section_keys_for_current_request' )->willReturn( array( 'st_hero' ) );
		$admin      = $this->create_admin_resolver( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::EXISTING_PAGE_EDIT, 1 ) );
		$controller = new ACF_Registration_Bootstrap_Controller(
			$registrar,
			$context,
			$this->create_resolver(),
			$existing,
			$this->create_new_page_resolver_returning_null(),
			$admin,
			null
		);
		$this->assertSame( 1, $controller->run_registration(), 'Scoped path must return count from register_sections' );
	}
}
