<?php
/**
 * Unit tests for Template_Versioning_Service: version blocks, next-version suggestion, version summary (Prompt 189, spec §12.14, §13.12, §58.2).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Versioning_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Versioning/Template_Versioning_Service.php';

final class Template_Versioning_Service_Test extends TestCase {

	private Template_Versioning_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Template_Versioning_Service();
	}

	public function test_build_section_version_block_includes_version_and_stable_key(): void {
		$block = $this->service->build_section_version_block( '2', true );
		$this->assertSame( '2', $block['version'] );
		$this->assertTrue( $block['stable_key_retained'] );
	}

	public function test_build_section_version_block_with_changelog_and_breaking(): void {
		$block = $this->service->build_section_version_block( '2', true, 'changelog#v2', true );
		$this->assertSame( '2', $block['version'] );
		$this->assertSame( 'changelog#v2', $block['changelog_ref'] );
		$this->assertTrue( $block['breaking'] );
	}

	public function test_build_page_template_version_block_same_shape(): void {
		$block = $this->service->build_page_template_version_block( '1', true, '', false );
		$this->assertSame( '1', $block['version'] );
		$this->assertTrue( $block['stable_key_retained'] );
	}

	public function test_suggest_next_version_breaking_increments(): void {
		$this->assertSame( '2', $this->service->suggest_next_version( '1', true ) );
		$this->assertSame( '3', $this->service->suggest_next_version( '2', true ) );
	}

	public function test_suggest_next_version_empty_returns_one(): void {
		$this->assertSame( '1', $this->service->suggest_next_version( '', true ) );
	}

	public function test_get_version_summary_from_section_definition(): void {
		$def = array(
			Section_Schema::FIELD_VERSION => array(
				'version'             => '2',
				'stable_key_retained' => false,
				'changelog_ref'       => 'doc#v2',
				'breaking'            => true,
			),
		);
		$summary = $this->service->get_version_summary( $def, 'section' );
		$this->assertSame( '2', $summary['version'] );
		$this->assertFalse( $summary['stable_key_retained'] );
		$this->assertSame( 'doc#v2', $summary['changelog_ref'] );
		$this->assertTrue( $summary['breaking'] );
	}

	public function test_get_version_summary_missing_version_defaults_to_one(): void {
		$def = array();
		$summary = $this->service->get_version_summary( $def, 'section' );
		$this->assertSame( '1', $summary['version'] );
		$this->assertTrue( $summary['stable_key_retained'] );
	}
}
