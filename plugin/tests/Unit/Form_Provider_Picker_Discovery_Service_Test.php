<?php
/**
 * Unit tests for Form_Provider_Picker_Discovery_Service and Ndr_Form_Provider_Picker_Adapter (Prompt 236).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Discovery_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Ndr_Form_Provider_Picker_Adapter;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Adapter_Interface.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Discovery_Service.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Ndr_Form_Provider_Picker_Adapter.php';

final class Form_Provider_Picker_Discovery_Service_Test extends TestCase {

	public function test_ndr_adapter_provider_key_and_label(): void {
		$registry = new Form_Provider_Registry();
		$adapter  = new Ndr_Form_Provider_Picker_Adapter( $registry );
		$this->assertSame( 'ndr_forms', $adapter->get_provider_key() );
		$this->assertNotEmpty( $adapter->get_display_label() );
	}

	public function test_ndr_adapter_available_when_registry_has_provider(): void {
		$registry = new Form_Provider_Registry();
		$adapter  = new Ndr_Form_Provider_Picker_Adapter( $registry );
		$this->assertTrue( $adapter->is_available() );
		$this->assertFalse( $adapter->supports_form_list() );
		$this->assertSame( array(), $adapter->get_form_list() );
		$this->assertFalse( $adapter->is_item_stale( 'contact' ) );
		$this->assertNotEmpty( $adapter->get_fallback_entry_label() );
	}

	public function test_discovery_returns_providers_with_picker_support(): void {
		$registry = new Form_Provider_Registry();
		$ndr      = new Ndr_Form_Provider_Picker_Adapter( $registry );
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'ndr_forms' => $ndr ) );
		$list = $discovery->get_providers_with_picker_support();
		$this->assertContains( 'ndr_forms', $list );
	}

	public function test_discovery_picker_state_for_provider(): void {
		$registry = new Form_Provider_Registry();
		$ndr      = new Ndr_Form_Provider_Picker_Adapter( $registry );
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'ndr_forms' => $ndr ) );
		$state = $discovery->get_picker_state_for_provider( 'ndr_forms' );
		$this->assertSame( 'ndr_forms', $state['provider_key'] );
		$this->assertTrue( $state['available'] );
		$this->assertFalse( $state['supports_form_list'] );
		$this->assertSame( array(), $state['picker_items'] );
		$this->assertNotEmpty( $state['fallback_entry_label'] );
	}

	public function test_discovery_has_adapter(): void {
		$registry = new Form_Provider_Registry();
		$ndr      = new Ndr_Form_Provider_Picker_Adapter( $registry );
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'ndr_forms' => $ndr ) );
		$this->assertTrue( $discovery->has_adapter( 'ndr_forms' ) );
		$this->assertFalse( $discovery->has_adapter( 'wpforms' ) );
	}

	public function test_discovery_empty_adapters(): void {
		$registry  = new Form_Provider_Registry();
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array() );
		$this->assertSame( array(), $discovery->get_providers_with_picker_support() );
		$this->assertFalse( $discovery->has_adapter( 'ndr_forms' ) );
	}
}
