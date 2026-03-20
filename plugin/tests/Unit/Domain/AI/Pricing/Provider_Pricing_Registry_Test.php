<?php
/**
 * Tests for Provider_Pricing_Registry.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Pricing;

use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry
 */
final class Provider_Pricing_Registry_Test extends TestCase {

	private Provider_Pricing_Registry $registry;

	protected function setUp(): void {
		$this->registry = new Provider_Pricing_Registry();
	}

	public function test_known_openai_gpt4o_returns_rates(): void {
		$rates = $this->registry->get_rates( 'openai', 'gpt-4o' );
		$this->assertNotNull( $rates );
		$this->assertArrayHasKey( 'input', $rates );
		$this->assertArrayHasKey( 'output', $rates );
		$this->assertGreaterThan( 0.0, $rates['input'] );
		$this->assertGreaterThan( 0.0, $rates['output'] );
	}

	public function test_known_openai_gpt4o_mini_returns_rates(): void {
		$rates = $this->registry->get_rates( 'openai', 'gpt-4o-mini' );
		$this->assertNotNull( $rates );
		$this->assertLessThan(
			$this->registry->get_rates( 'openai', 'gpt-4o' )['input'],
			$rates['input'],
			'gpt-4o-mini input rate should be cheaper than gpt-4o'
		);
	}

	public function test_known_anthropic_claude_sonnet_returns_rates(): void {
		$rates = $this->registry->get_rates( 'anthropic', 'claude-sonnet-4-20250514' );
		$this->assertNotNull( $rates );
		$this->assertGreaterThan( 0.0, $rates['input'] );
		$this->assertGreaterThan( 0.0, $rates['output'] );
	}

	public function test_unknown_provider_returns_null(): void {
		$this->assertNull( $this->registry->get_rates( 'unknown_provider', 'gpt-4o' ) );
	}

	public function test_unknown_model_returns_null(): void {
		$this->assertNull( $this->registry->get_rates( 'openai', 'gpt-999-ultra' ) );
	}

	public function test_prefix_match_resolves_versioned_model_id(): void {
		// Providers sometimes append date stamps to model IDs (e.g. gpt-4-turbo-2024-04-09).
		$rates = $this->registry->get_rates( 'openai', 'gpt-4-turbo-2024-04-09' );
		$this->assertNotNull( $rates, 'Prefix match should resolve versioned model ID' );
	}

	public function test_has_rates_returns_true_for_known_model(): void {
		$this->assertTrue( $this->registry->has_rates( 'openai', 'gpt-4o' ) );
	}

	public function test_has_rates_returns_false_for_unknown_model(): void {
		$this->assertFalse( $this->registry->has_rates( 'openai', 'nonexistent-model' ) );
	}

	public function test_get_provider_ids_includes_openai_and_anthropic(): void {
		$ids = $this->registry->get_provider_ids();
		$this->assertContains( 'openai', $ids );
		$this->assertContains( 'anthropic', $ids );
	}

	public function test_get_model_ids_for_provider_returns_array(): void {
		$models = $this->registry->get_model_ids_for_provider( 'openai' );
		$this->assertNotEmpty( $models );
		$this->assertContains( 'gpt-4o', $models );
	}

	public function test_get_model_ids_for_unknown_provider_returns_empty_array(): void {
		$this->assertSame( array(), $this->registry->get_model_ids_for_provider( 'unknown' ) );
	}

	public function test_rates_are_per_token_not_per_million(): void {
		// Rates must be < 1.0 (they are fractions of a dollar per token).
		$rates = $this->registry->get_rates( 'openai', 'gpt-4o' );
		$this->assertLessThan( 1.0, $rates['input'] );
		$this->assertLessThan( 1.0, $rates['output'] );
	}

	public function test_output_rate_greater_than_input_rate_for_gpt4o(): void {
		$rates = $this->registry->get_rates( 'openai', 'gpt-4o' );
		$this->assertGreaterThan( $rates['input'], $rates['output'], 'Output tokens are more expensive than input for gpt-4o' );
	}
}
