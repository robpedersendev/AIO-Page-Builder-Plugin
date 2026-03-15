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
use AIOPageBuilder\Domain\ACF\Registration\Registration_Request_Context;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Registrar_Interface.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/Registration_Request_Context.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Registration_Bootstrap_Controller.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class ACF_Registration_Bootstrap_Controller_Test extends TestCase {

	public function test_run_registration_delegates_to_registrar_when_not_skipped(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->method( 'register_all' )->willReturn( 3 );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( false );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context );
		$this->assertSame( 3, $controller->run_registration() );
	}

	public function test_run_registration_returns_zero_on_front_end_without_calling_registrar(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->never() )->method( 'register_all' );
		$context = $this->createMock( Registration_Request_Context::class );
		$context->method( 'should_skip_registration' )->willReturn( true );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context );
		$this->assertSame( 0, $controller->run_registration() );
	}

	public function test_run_full_registration_calls_register_all(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$registrar->expects( $this->once() )->method( 'register_all' )->willReturn( 5 );
		$context = $this->createMock( Registration_Request_Context::class );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context );
		$this->assertSame( 5, $controller->run_full_registration() );
	}

	public function test_get_registrar_returns_injected_registrar(): void {
		$registrar = $this->createMock( ACF_Group_Registrar_Interface::class );
		$context = $this->createMock( Registration_Request_Context::class );
		$controller = new ACF_Registration_Bootstrap_Controller( $registrar, $context );
		$this->assertSame( $registrar, $controller->get_registrar() );
	}
}
