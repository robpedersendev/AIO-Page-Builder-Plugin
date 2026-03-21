<?php
/**
 * Tests that Onboarding_Screen contains no user-visible placeholder/deferred-work copy.
 * Guards against regression to "future update", "placeholder", or "out of scope for this prompt" wording.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Onboarding_Screen_Copy_Test extends TestCase {

	private function screen_source(): string {
		$path = dirname( __DIR__, 2 ) . '/src/Admin/Screens/AI/Onboarding_Screen.php';
		$src  = file_get_contents( $path );
		$this->assertNotFalse( $src, 'Onboarding_Screen.php must be readable.' );
		return (string) $src;
	}

	/** No user-visible "future update" copy may exist. */
	public function test_no_future_update_copy(): void {
		$src = $this->screen_source();
		$this->assertStringNotContainsString(
			'future update',
			$src,
			'Onboarding_Screen must not contain "future update" copy.'
		);
	}

	/** The method that renders step content must not be named render_step_placeholder. */
	public function test_step_render_method_not_named_placeholder(): void {
		$src = $this->screen_source();
		$this->assertStringNotContainsString(
			'render_step_placeholder',
			$src,
			'render_step_placeholder must be removed; step content render method must use a truthful name.'
		);
	}

	/** No docblock must reference "out of scope for this prompt". */
	public function test_no_out_of_scope_for_this_prompt(): void {
		$src = $this->screen_source();
		$this->assertStringNotContainsString(
			'out of scope for this prompt',
			$src,
			'Onboarding_Screen must not contain "out of scope for this prompt" in any docblock or comment.'
		);
	}

	/** Provider setup step must reference the AI Providers screen, not deferred copy. */
	public function test_provider_step_references_ai_providers_screen(): void {
		$src = $this->screen_source();
		$this->assertStringContainsString(
			'AI Providers screen',
			$src,
			'Provider setup step must direct users to the AI Providers screen.'
		);
	}

	/** The fallback step copy must not imply "form fields will be added". */
	public function test_no_form_fields_will_be_added_copy(): void {
		$src = $this->screen_source();
		$this->assertStringNotContainsString(
			'form fields will be added',
			$src,
			'Fallback step copy must not promise that form fields will be added.'
		);
	}
}
