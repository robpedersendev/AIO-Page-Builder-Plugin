<?php
/**
 * Unit tests for Form_Provider_Availability_Service (Prompt 237): available, no_forms, provider_error, cached_fallback, stale_binding.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Availability_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Adapter_Interface;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Cache_Service;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Picker_Discovery_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once __DIR__ . '/bootstrap-i18n.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Adapter_Interface.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Discovery_Service.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Availability_Service.php';
require_once $plugin_root . '/src/Domain/Integrations/FormProviders/Form_Provider_Picker_Cache_Service.php';

/**
 * Test double: adapter that can return items, empty list, or throw.
 */
final class Form_Provider_Availability_Test_Adapter implements Form_Provider_Picker_Adapter_Interface {
	public string $provider_key = 'test_provider';
	public bool $available = true;
	public bool $supports_form_list = true;
	/** @var list<array{provider_key: string, item_id: string, item_label: string}> */
	public array $form_list = array();
	public bool $throw_on_get_form_list = false;
	public bool $stale_for_any = false;

	public function get_provider_key(): string { return $this->provider_key; }
	public function get_display_label(): string { return 'Test Provider'; }
	public function is_available(): bool { return $this->available; }
	public function supports_form_list(): bool { return $this->supports_form_list; }
	public function get_form_list(): array {
		if ( $this->throw_on_get_form_list ) {
			throw new \RuntimeException( 'Provider unreachable' );
		}
		return $this->form_list;
	}
	public function is_item_stale( string $form_id ): bool { return $this->stale_for_any; }
	public function get_fallback_entry_label(): string { return 'Form ID'; }
}

final class Form_Provider_Availability_Service_Test extends TestCase {

	public function test_no_adapter_returns_available_status(): void {
		$registry  = new Form_Provider_Registry();
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array() );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$state = $availability->get_availability_state( 'ndr_forms', '' );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_AVAILABLE, $state['status'] );
		$this->assertFalse( $state['from_cache'] );
		$this->assertFalse( $state['stale_binding'] );
	}

	public function test_available_with_items(): void {
		$registry  = new Form_Provider_Registry();
		$registry->register( 'test_provider', 'testform', 'id' );
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$adapter->form_list = array( array( 'provider_key' => 'test_provider', 'item_id' => 'f1', 'item_label' => 'Form 1' ) );
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$state = $availability->get_availability_state( 'test_provider', '' );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_AVAILABLE, $state['status'] );
		$this->assertCount( 1, $state['picker_items'] );
		$this->assertSame( 'f1', $state['picker_items'][0]['item_id'] );
		$this->assertFalse( $state['stale_binding'] );
	}

	public function test_no_forms_status(): void {
		$registry  = new Form_Provider_Registry();
		$registry->register( 'test_provider', 'testform', 'id' );
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$adapter->form_list = array();
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$state = $availability->get_availability_state( 'test_provider', '' );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_NO_FORMS, $state['status'] );
		$this->assertSame( array(), $state['picker_items'] );
		$this->assertNotNull( $state['message'] );
	}

	public function test_provider_error_when_adapter_throws_and_no_fallback(): void {
		$registry  = new Form_Provider_Registry();
		$registry->register( 'test_provider', 'testform', 'id' );
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$adapter->throw_on_get_form_list = true;
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$state = $availability->get_availability_state( 'test_provider', '' );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_PROVIDER_ERROR, $state['status'] );
		$this->assertSame( array(), $state['picker_items'] );
		$this->assertNotNull( $state['message'] );
		$this->assertFalse( $state['from_cache'] );
	}

	public function test_cached_fallback_when_adapter_throws_and_cache_has_entry(): void {
		$registry  = new Form_Provider_Registry();
		$registry->register( 'test_provider', 'testform', 'id' );
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$adapter->throw_on_get_form_list = true;
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$cache = new Form_Provider_Picker_Cache_Service( 1 );
		$cached_items = array( array( 'provider_key' => 'test_provider', 'item_id' => 'old1', 'item_label' => 'Old' ) );
		$cache->set( 'test_provider', $cached_items, 'success' );
		sleep( 2 );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, $cache );
		$state = $availability->get_availability_state( 'test_provider', '' );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_CACHED_FALLBACK, $state['status'] );
		$this->assertSame( $cached_items, $state['picker_items'] );
		$this->assertTrue( $state['from_cache'] );
		$this->assertNotNull( $state['message'] );
	}

	public function test_stale_binding_when_adapter_reports_stale(): void {
		$registry  = new Form_Provider_Registry();
		$registry->register( 'test_provider', 'testform', 'id' );
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$adapter->form_list = array( array( 'provider_key' => 'test_provider', 'item_id' => 'f1', 'item_label' => 'Form 1' ) );
		$adapter->stale_for_any = true;
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$state = $availability->get_availability_state( 'test_provider', 'deleted_form_id' );
		$this->assertTrue( $state['stale_binding'] );
		$this->assertSame( Form_Provider_Availability_Service::STATUS_AVAILABLE, $state['status'] );
	}

	public function test_get_summary_for_admin_returns_bounded_list(): void {
		$registry  = new Form_Provider_Registry();
		$adapter   = new Form_Provider_Availability_Test_Adapter();
		$discovery = new Form_Provider_Picker_Discovery_Service( $registry, array( 'test_provider' => $adapter ) );
		$availability = new Form_Provider_Availability_Service( $registry, $discovery, null );
		$summary = $availability->get_summary_for_admin();
		$this->assertIsArray( $summary );
		foreach ( $summary as $row ) {
			$this->assertArrayHasKey( 'provider_key', $row );
			$this->assertArrayHasKey( 'status', $row );
			$this->assertArrayHasKey( 'message', $row );
		}
	}
}
