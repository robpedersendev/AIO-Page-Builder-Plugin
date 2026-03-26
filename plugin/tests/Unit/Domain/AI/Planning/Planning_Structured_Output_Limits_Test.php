<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Planning;

use AIOPageBuilder\Domain\AI\Planning\Planning_Structured_Output_Limits;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Planning\Planning_Structured_Output_Limits
 */
final class Planning_Structured_Output_Limits_Test extends TestCase {

	public function test_clamp_preserves_requested_under_absolute_max(): void {
		$this->assertSame(
			16384,
			Planning_Structured_Output_Limits::clamp_for_provider_request( 16384 )
		);
	}

	public function test_clamp_reduces_to_absolute_max(): void {
		$this->assertSame(
			Planning_Structured_Output_Limits::ABSOLUTE_MAX_OUTPUT_TOKENS,
			Planning_Structured_Output_Limits::clamp_for_provider_request( 999999 )
		);
	}

	public function test_clamp_minimum_one(): void {
		$this->assertSame( 1, Planning_Structured_Output_Limits::clamp_for_provider_request( 0 ) );
		$this->assertSame( 1, Planning_Structured_Output_Limits::clamp_for_provider_request( -5 ) );
	}
}
