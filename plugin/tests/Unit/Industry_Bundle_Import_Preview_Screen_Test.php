<?php
/**
 * Unit tests for Industry Bundle Import Preview screen (SPR-007, Prompt 639).
 *
 * Screen is preview-only in v1; direct apply of JSON bundles is not supported. Asserts capability, slug, and no-apply contract.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Bundle_Import_Preview_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Bundle_Import_Preview_Screen.php';

final class Industry_Bundle_Import_Preview_Screen_Test extends TestCase {

	public function test_screen_uses_manage_settings_capability(): void {
		$screen = new Industry_Bundle_Import_Preview_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SETTINGS, $screen->get_capability(), 'SPR-007: preview screen gated by MANAGE_SETTINGS.' );
	}

	public function test_screen_has_expected_slug(): void {
		$this->assertSame( 'aio-page-builder-industry-bundle-import-preview', Industry_Bundle_Import_Preview_Screen::SLUG );
	}

	public function test_screen_has_non_empty_title(): void {
		$screen = new Industry_Bundle_Import_Preview_Screen( null );
		$this->assertNotEmpty( $screen->get_title() );
	}

	/** V1: screen is preview-only; no apply/confirm action. Title must indicate preview. */
	public function test_screen_title_indicates_preview(): void {
		$screen = new Industry_Bundle_Import_Preview_Screen( null );
		$title  = $screen->get_title();
		$this->assertStringContainsString( 'Preview', $title, 'Screen title must indicate preview-only (v1 de-scope).' );
	}

	/** V1: no apply/import nonce action; only preview and clear-preview (prevents accidental apply affordance). */
	public function test_no_apply_action_constant(): void {
		$ref  = new \ReflectionClass( Industry_Bundle_Import_Preview_Screen::class );
		$all  = $ref->getReflectionConstants();
		$names = array_map( static function ( \ReflectionClassConstant $c ): string {
			return $c->getName();
		}, $all );
		$this->assertNotContains( 'NONCE_ACTION_APPLY', $names );
		$this->assertNotContains( 'NONCE_ACTION_IMPORT', $names );
		$this->assertContains( 'NONCE_ACTION_PREVIEW', $names );
		$this->assertContains( 'NONCE_ACTION_CLEAR', $names );
	}
}
