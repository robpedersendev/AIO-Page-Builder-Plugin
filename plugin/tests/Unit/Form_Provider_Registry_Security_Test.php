<?php
/**
 * Unit tests for Form_Provider_Registry security (Prompt 233): malicious provider ID,
 * malformed form ID, validation helpers, no arbitrary shortcode output.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/FormProvider/Form_Provider_Registry.php';

final class Form_Provider_Registry_Security_Test extends TestCase {

	private Form_Provider_Registry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new Form_Provider_Registry();
	}

	public function test_build_shortcode_returns_null_for_unregistered_provider(): void {
		$this->assertNull( $this->registry->build_shortcode( 'evil_provider', 'contact' ) );
		$this->assertNull( $this->registry->build_shortcode( 'wpforms', '1' ) );
		$this->assertNull( $this->registry->build_shortcode( '<script>', 'x' ) );
	}

	public function test_build_shortcode_returns_null_for_empty_or_malformed_form_id(): void {
		$this->assertNull( $this->registry->build_shortcode( 'ndr_forms', '' ) );
		$this->assertNull( $this->registry->build_shortcode( 'ndr_forms', 'id=1' ) );
		$this->assertNull( $this->registry->build_shortcode( 'ndr_forms', 'form"onmouseover="alert(1)' ) );
		$this->assertNull( $this->registry->build_shortcode( 'ndr_forms', 'a b' ) );
	}

	public function test_build_shortcode_emits_only_registry_shortcode_tag_for_valid_input(): void {
		$out = $this->registry->build_shortcode( 'ndr_forms', 'contact' );
		$this->assertNotNull( $out );
		$this->assertStringContainsString( 'ndr_forms', $out );
		$this->assertStringNotContainsString( 'evil', $out );
		$this->assertStringNotContainsString( '<', $out );
		$this->assertStringNotContainsString( '>', $out );
		$this->assertSame( '[ndr_forms id="contact"]', $out );
	}

	public function test_is_valid_provider_id_accepts_only_registered(): void {
		$this->assertTrue( $this->registry->is_valid_provider_id( 'ndr_forms' ) );
		$this->assertFalse( $this->registry->is_valid_provider_id( 'evil' ) );
		$this->assertFalse( $this->registry->is_valid_provider_id( '' ) );
		$this->assertFalse( $this->registry->is_valid_provider_id( '<script>' ) );
	}

	public function test_is_valid_form_id_format_accepts_only_safe_pattern(): void {
		$this->assertTrue( $this->registry->is_valid_form_id_format( 'contact' ) );
		$this->assertTrue( $this->registry->is_valid_form_id_format( 'form_abc-123' ) );
		$this->assertFalse( $this->registry->is_valid_form_id_format( '' ) );
		$this->assertFalse( $this->registry->is_valid_form_id_format( 'id=1' ) );
		$this->assertFalse( $this->registry->is_valid_form_id_format( 'a b' ) );
		$this->assertFalse( $this->registry->is_valid_form_id_format( 'form"x' ) );
	}

	public function test_validate_provider_and_form_returns_errors_for_invalid(): void {
		$r = $this->registry->validate_provider_and_form( 'ndr_forms', 'contact' );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( array(), $r['errors'] );

		$r = $this->registry->validate_provider_and_form( 'evil', 'contact' );
		$this->assertFalse( $r['valid'] );
		$this->assertNotEmpty( $r['errors'] );

		$r = $this->registry->validate_provider_and_form( 'ndr_forms', '' );
		$this->assertFalse( $r['valid'] );
		$this->assertNotEmpty( $r['errors'] );

		$r = $this->registry->validate_provider_and_form( 'ndr_forms', 'id=1' );
		$this->assertFalse( $r['valid'] );
	}

	public function test_has_provider_sanitizes_input(): void {
		$this->assertTrue( $this->registry->has_provider( 'ndr_forms' ) );
		$this->assertFalse( $this->registry->has_provider( 'NDR_FORMS<script>' ) );
	}
}
