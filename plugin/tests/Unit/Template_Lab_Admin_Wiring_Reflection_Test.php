<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Templates\Template_Lab_Chat_Screen;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Rest\AI_Chat_REST_Controller;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Ensures template-lab admin/REST entrypoints require a real {@see Service_Container} (non-null wiring).
 */
final class Template_Lab_Admin_Wiring_Reflection_Test extends TestCase {

	public function test_template_lab_chat_screen_constructor_requires_service_container(): void {
		$m      = new ReflectionMethod( Template_Lab_Chat_Screen::class, '__construct' );
		$params = $m->getParameters();
		$this->assertCount( 1, $params );
		$t = $params[0]->getType();
		$this->assertNotNull( $t );
		$this->assertSame( Service_Container::class, $t->getName() );
		$this->assertFalse( $params[0]->allowsNull() );
	}

	public function test_ai_chat_rest_controller_constructor_requires_service_container(): void {
		$m      = new ReflectionMethod( AI_Chat_REST_Controller::class, '__construct' );
		$params = $m->getParameters();
		$this->assertCount( 1, $params );
		$t = $params[0]->getType();
		$this->assertNotNull( $t );
		$this->assertSame( Service_Container::class, $t->getName() );
		$this->assertFalse( $params[0]->allowsNull() );
	}
}
