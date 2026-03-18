<?php
/**
 * Unit tests for ACF_Registration_Bootstrap_Controller (Prompt 282).
 * Proves registration is centralized: bootstrap uses controller, not direct register_all().
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

final class ACF_Registration_Bootstrap_Controller_Test extends TestCase {

	private function create_resolver(): Group_Key_Section_Key_Resolver {
		return new Group_Key_Section_Key_Resolver();
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

	private function create_admin_context_resolver_returning( Admin_Post_Edit_Context_Result $result ): Admin_Post_Edit_Context_Resolver {
		$mock = $this->createMock( Admin_Post_Edit_Context_Resolver::class );
		$mock->method( 'resolve' )->willReturn( $result );
		return $mock;
	}

	private function controller_args( $registrar, $context, $existing_page_resolver = null, $new_page_resolver = null, ?Admin_Post_Edit_Context_Resolver $admin_context_resolver = null ): array {
		$admin = $admin_context_resolver ?? $this->create_admin_context_resolver_returning(
			new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 )
		);
		return array(
			$registrar,
			$context,
			$this->create_resolver(),
			$existing_page_resolver ?? $this->create_existing_page_resolver_returning_null(),
			$new_page_resolver ?? $this->create_new_page_resolver_returning_null(),
			$admin,
			null,
		);
	}

	public function test_run_registration_returns_zero_for_non_page_admin_without_calling_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$controller = new ACF_Registration_Bootstrap_Controller( ...$this->controller_args( $registrar, $context ) );
		$this->assertSame( 0, $controller->run_registration() );
	}

	public function test_run_registration_returns_zero_on_front_end_without_calling_registrar(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( true );
		$controller = new ACF_Registration_Bootstrap_Controller( ...$this->controller_args( $registrar, $context ) );
		$this->assertSame( 0, $controller->run_registration() );
	}

	public function test_run_registration_uses_register_sections_when_existing_page_returns_section_keys(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$registrar->method( 'register_sections' )->with( array( 'st01_hero', 'st05_faq' ) )->willReturn( 2 );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$existing = $this->createMock( Existing_Page_ACF_Registration_Context_Resolver::class );
		$existing->method( 'get_section_keys_for_current_request' )->willReturn( array( 'st01_hero', 'st05_faq' ) );
		$admin_ctx  = $this->create_admin_context_resolver_returning( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::EXISTING_PAGE_EDIT, 1 ) );
		$controller = new ACF_Registration_Bootstrap_Controller( ...$this->controller_args( $registrar, $context, $existing, null, $admin_ctx ) );
		$this->assertSame( 2, $controller->run_registration() );
	}

	public function test_run_registration_uses_register_sections_when_new_page_returns_section_keys(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$registrar->method( 'register_sections' )->with( array( 'st01_hero' ) )->willReturn( 1 );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$new_page = $this->createMock( New_Page_ACF_Registration_Context_Resolver::class );
		$new_page->method( 'get_section_keys_for_current_request' )->willReturn( array( 'st01_hero' ) );
		$admin_ctx  = $this->create_admin_context_resolver_returning( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NEW_PAGE_EDIT, 0 ) );
		$controller = new ACF_Registration_Bootstrap_Controller( ...$this->controller_args( $registrar, $context, null, $new_page, $admin_ctx ) );
		$this->assertSame( 1, $controller->run_registration() );
	}

	public function test_run_full_registration_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->once() )->method( 'register_all' )->willReturn( 5 );
		$context    = $this->createMock( Registration_Request_Context::class );
		$controller = new ACF_Registration_Bootstrap_Controller( ...$this->controller_args( $registrar, $context ) );
		$this->assertSame( 5, $controller->run_full_registration() );
	}

	public function test_get_registrar_returns_injected_registrar(): void {
		$registrar  = $this->createMock( ACF_Group_Registrar_Interface::class );
		$context    = $this->createMock( Registration_Request_Context::class );
		$resolver   = $this->create_resolver();
		$admin_ctx  = $this->create_admin_context_resolver_returning( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 ) );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context, $resolver, $this->create_existing_page_resolver_returning_null(), $this->create_new_page_resolver_returning_null(), $admin_ctx, null );
		$this->assertSame( $registrar, $controller->get_registrar() );
	}

	public function test_get_group_key_section_key_resolver_returns_injected_resolver(): void {
		$registrar  = $this->createMock( ACF_Group_Registrar_Interface::class );
		$context    = $this->createMock( Registration_Request_Context::class );
		$resolver   = $this->create_resolver();
		$admin_ctx  = $this->create_admin_context_resolver_returning( new Admin_Post_Edit_Context_Result( Admin_Post_Edit_Context_Result::NON_PAGE_ADMIN, 0 ) );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context, $resolver, $this->create_existing_page_resolver_returning_null(), $this->create_new_page_resolver_returning_null(), $admin_ctx, null );
		$this->assertSame( $resolver, $controller->get_group_key_section_key_resolver() );
	}
}
