<?php
/**
 * Unit tests for Industry bundle import/apply preview screen contract.
 *
 * Prevents contract drift where the screen still claims preview-only or
 * apply-not-implemented behavior after the apply flow is supported.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );

final class IndustryBundleImportPreviewScreenTest extends TestCase {

	private function plugin_root(): string {
		return \dirname( __DIR__, 3 );
	}

	/**
	 * Ensures the screen code no longer contains stale preview-only/apply-not-implemented contract language.
	 */
	public function test_screen_code_has_no_preview_only_or_apply_not_implemented_language(): void {
		$path    = $this->plugin_root() . '/src/Admin/Screens/Industry/Industry_Bundle_Import_Preview_Screen.php';
		$content = \file_get_contents( $path );

		$this->assertNotFalse( $content );

		$lower = \strtolower( (string) $content );

		$stale_phrases = array(
			'preview-only',
			'apply/import of bundle content is not implemented',
			'apply not implemented',
			'not implemented; this screen is for inspection only',
			'not implemented (',
			'deferred apply',
		);

		foreach ( $stale_phrases as $phrase ) {
			$this->assertStringNotContainsString( \strtolower( $phrase ), $lower, 'Found stale contract phrase: ' . $phrase );
		}
	}

	/**
	 * Ensures the rendered UI copy is truthful for the implemented flow:
	 * upload -> preview -> conflict review -> scope selection -> confirmation apply (capability-gated).
	 */
	public function test_screen_ui_copy_mentions_upload_preview_conflicts_and_confirmed_apply(): void {
		$path    = $this->plugin_root() . '/src/Admin/Screens/Industry/Industry_Bundle_Import_Preview_Screen.php';
		$content = \file_get_contents( $path );
		$this->assertNotFalse( $content );
		$lower = \strtolower( (string) $content );

		$this->assertStringContainsString( 'upload an industry pack bundle', $lower );
		$this->assertStringContainsString( 'preview bundle', $lower );
		$this->assertStringContainsString( 'conflicts and actions', $lower );
		$this->assertStringContainsString( 'scope', $lower );
		$this->assertStringContainsString( 'confirm apply', $lower );

		// Capability notice text should explain gating, not deferred functionality.
		$this->assertStringContainsString( 'applying requires manage settings permission', $lower );
		$this->assertStringNotContainsString( 'deferred', $lower );
	}
}
