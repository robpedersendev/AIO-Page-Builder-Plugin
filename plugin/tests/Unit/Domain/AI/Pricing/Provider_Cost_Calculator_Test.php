<?php
/**
 * Tests for Provider_Cost_Calculator.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\AI\Pricing;

use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator
 */
final class Provider_Cost_Calculator_Test extends TestCase {

	private Provider_Cost_Calculator $calculator;

	protected function setUp(): void {
		$this->calculator = new Provider_Cost_Calculator( new Provider_Pricing_Registry() );
	}

	public function test_calculate_returns_float_for_known_provider_and_model(): void {
		$cost = $this->calculator->calculate( 'openai', 'gpt-4o', 1000, 500 );
		$this->assertNotNull( $cost );
		$this->assertIsFloat( $cost );
		$this->assertGreaterThan( 0.0, $cost );
	}

	public function test_calculate_uses_input_and_output_rates(): void {
		// gpt-4o: input $2.50/1M ($0.0000025/tok), output $10.00/1M ($0.00001/tok).
		// 1000 prompt + 500 completion = (1000 * 0.0000025) + (500 * 0.00001) = 0.0025 + 0.005 = 0.0075.
		$cost = $this->calculator->calculate( 'openai', 'gpt-4o', 1000, 500 );
		$this->assertEqualsWithDelta( 0.0075, $cost, 1.0e-8 );
	}

	public function test_calculate_returns_null_for_unknown_provider(): void {
		$this->assertNull( $this->calculator->calculate( 'unknown_provider', 'gpt-4o', 100, 50 ) );
	}

	public function test_calculate_returns_null_for_unknown_model(): void {
		$this->assertNull( $this->calculator->calculate( 'openai', 'gpt-99-super', 100, 50 ) );
	}

	public function test_calculate_zero_tokens_returns_zero(): void {
		$cost = $this->calculator->calculate( 'openai', 'gpt-4o', 0, 0 );
		$this->assertNotNull( $cost );
		$this->assertEqualsWithDelta( 0.0, $cost, 1.0e-10 );
	}

	public function test_calculate_returns_null_for_negative_prompt_tokens(): void {
		$this->assertNull( $this->calculator->calculate( 'openai', 'gpt-4o', -1, 100 ) );
	}

	public function test_calculate_returns_null_for_negative_completion_tokens(): void {
		$this->assertNull( $this->calculator->calculate( 'openai', 'gpt-4o', 100, -1 ) );
	}

	public function test_calculate_rounds_to_precision(): void {
		$cost = $this->calculator->calculate( 'openai', 'gpt-4o', 1, 1 );
		$this->assertNotNull( $cost );
		// Result must not have more decimal places than PRECISION allows.
		$formatted = number_format( $cost, Provider_Cost_Calculator::PRECISION + 1, '.', '' );
		$last_digit = substr( $formatted, -1 );
		$this->assertSame( '0', $last_digit, 'Cost should not have more digits than PRECISION' );
	}

	public function test_calculate_anthropic_model(): void {
		// claude-sonnet-4-20250514: $3.00/$15.00 per 1M.
		$cost = $this->calculator->calculate( 'anthropic', 'claude-sonnet-4-20250514', 2000, 1000 );
		$this->assertNotNull( $cost );
		// (2000 * 0.000003) + (1000 * 0.000015) = 0.006 + 0.015 = 0.021.
		$this->assertEqualsWithDelta( 0.021, $cost, 1.0e-8 );
	}

	public function test_has_pricing_true_for_known_model(): void {
		$this->assertTrue( $this->calculator->has_pricing( 'openai', 'gpt-4o' ) );
	}

	public function test_has_pricing_false_for_unknown_model(): void {
		$this->assertFalse( $this->calculator->has_pricing( 'openai', 'gpt-unknown' ) );
	}

	public function test_gpt4o_mini_cheaper_than_gpt4o(): void {
		$cost_4o      = $this->calculator->calculate( 'openai', 'gpt-4o', 1000, 1000 );
		$cost_4o_mini = $this->calculator->calculate( 'openai', 'gpt-4o-mini', 1000, 1000 );
		$this->assertLessThan( $cost_4o, $cost_4o_mini, 'gpt-4o-mini should be cheaper than gpt-4o' );
	}
}
