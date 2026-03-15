<?php
/**
 * Unit tests for Industry_Profile_Form_Builder (industry-admin-screen-contract; Prompt 342).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Forms\Industry_Profile_Form_Builder;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Admin/Forms/Industry_Profile_Form_Builder.php';

final class Industry_Profile_Form_Builder_Test extends TestCase {

	public function test_nonce_action_and_name(): void {
		$builder = new Industry_Profile_Form_Builder( null );
		$this->assertSame( 'aio_save_industry_profile', $builder->get_nonce_action() );
		$this->assertSame( 'aio_industry_profile_nonce', $builder->get_nonce_name() );
	}

	public function test_primary_industry_options_include_empty_without_registry(): void {
		$builder = new Industry_Profile_Form_Builder( null );
		$options = $builder->get_primary_industry_options();
		$this->assertArrayHasKey( '', $options );
		$this->assertIsString( $options[''] );
		$this->assertCount( 1, $options );
	}

	public function test_secondary_industry_options_empty_without_registry(): void {
		$builder = new Industry_Profile_Form_Builder( null );
		$options = $builder->get_secondary_industry_options();
		$this->assertSame( array(), $options );
	}

	public function test_primary_industry_options_include_active_packs(): void {
		$registry = new Industry_Pack_Registry();
		$registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY  => 'legal',
				Industry_Pack_Schema::FIELD_NAME         => 'Legal',
				Industry_Pack_Schema::FIELD_SUMMARY      => 'Legal industry',
				Industry_Pack_Schema::FIELD_STATUS       => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
			),
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY  => 'healthcare',
				Industry_Pack_Schema::FIELD_NAME         => 'Healthcare',
				Industry_Pack_Schema::FIELD_SUMMARY      => 'Healthcare industry',
				Industry_Pack_Schema::FIELD_STATUS       => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
			),
		) );
		$builder = new Industry_Profile_Form_Builder( $registry );
		$options = $builder->get_primary_industry_options();
		$this->assertArrayHasKey( '', $options );
		$this->assertArrayHasKey( 'legal', $options );
		$this->assertSame( 'Legal', $options['legal'] );
		$this->assertArrayHasKey( 'healthcare', $options );
		$this->assertSame( 'Healthcare', $options['healthcare'] );
	}

	public function test_field_config_has_primary_and_secondary(): void {
		$builder = new Industry_Profile_Form_Builder( null );
		$config = $builder->get_field_config();
		$this->assertArrayHasKey( 'primary_industry_key', $config );
		$this->assertSame( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY, $config['primary_industry_key']['name'] );
		$this->assertSame( 'select', $config['primary_industry_key']['type'] );
		$this->assertArrayHasKey( 'secondary_industry_keys', $config );
		$this->assertSame( 'multiselect', $config['secondary_industry_keys']['type'] );
	}
}
