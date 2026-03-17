<?php
/**
 * Unit tests for Industry_Subtype_Form_Field (Prompt 432; industry-subtype-extension-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Industry_Subtype_Form_Field;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Industry_Subtype_Form_Field.php';

final class Industry_Subtype_Form_Field_Test extends TestCase {

	private function subtype_def( string $subtype_key, string $parent_industry_key, string $label = '' ): array {
		return array(
			Industry_Subtype_Registry::FIELD_SUBTYPE_KEY         => $subtype_key,
			Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => $parent_industry_key,
			Industry_Subtype_Registry::FIELD_LABEL              => $label !== '' ? $label : $subtype_key,
			Industry_Subtype_Registry::FIELD_SUMMARY            => 'Summary',
			Industry_Subtype_Registry::FIELD_STATUS             => 'active',
			Industry_Subtype_Registry::FIELD_VERSION_MARKER      => '1',
		);
	}

	public function test_get_options_for_industry_returns_none_only_when_registry_null(): void {
		$field = new Industry_Subtype_Form_Field( null );
		$options = $field->get_options_for_industry( 'realtor' );
		$this->assertSame( array( '' => '— None —' ), $options );
	}

	public function test_get_options_for_industry_returns_none_only_when_parent_empty(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$field = new Industry_Subtype_Form_Field( $registry );
		$options = $field->get_options_for_industry( '' );
		$this->assertSame( array( '' => '— None —' ), $options );
	}

	public function test_get_options_for_industry_returns_subtypes_for_parent(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array(
			$this->subtype_def( 'realtor_buyer_agent', 'realtor', 'Buyer Agent' ),
			$this->subtype_def( 'realtor_listing', 'realtor', 'Listing Agent' ),
		) );
		$field = new Industry_Subtype_Form_Field( $registry );
		$options = $field->get_options_for_industry( 'realtor' );
		$this->assertArrayHasKey( '', $options );
		$this->assertSame( '— None —', $options[''] );
		$this->assertArrayHasKey( 'realtor_buyer_agent', $options );
		$this->assertSame( 'Buyer Agent', $options['realtor_buyer_agent'] );
		$this->assertArrayHasKey( 'realtor_listing', $options );
		$this->assertSame( 'Listing Agent', $options['realtor_listing'] );
	}

	public function test_industry_has_subtypes_returns_false_when_registry_null(): void {
		$field = new Industry_Subtype_Form_Field( null );
		$this->assertFalse( $field->industry_has_subtypes( 'realtor' ) );
	}

	public function test_industry_has_subtypes_returns_true_when_subtypes_exist(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$field = new Industry_Subtype_Form_Field( $registry );
		$this->assertTrue( $field->industry_has_subtypes( 'realtor' ) );
	}

	public function test_industry_has_subtypes_returns_false_when_no_subtypes_for_parent(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( array( $this->subtype_def( 'realtor_buyer_agent', 'realtor' ) ) );
		$field = new Industry_Subtype_Form_Field( $registry );
		$this->assertFalse( $field->industry_has_subtypes( 'plumber' ) );
	}

	public function test_get_field_config_returns_expected_keys(): void {
		$field = new Industry_Subtype_Form_Field( null );
		$config = $field->get_field_config();
		$this->assertSame( Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY, $config['name'] );
		$this->assertSame( 'select', $config['type'] );
		$this->assertSame( 'Subtype (optional)', $config['label'] );
		$this->assertNotEmpty( $config['description'] );
	}
}
