<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Tests\Support\Fake_AI_Provider_Driver;
use PHPUnit\Framework\TestCase;

final class Fake_AI_Provider_Driver_Test extends TestCase {

	public function test_returns_configured_success_response(): void {
		$fake = new Fake_AI_Provider_Driver( 'test-openai' );
		$fake->set_next_response(
			array(
				'request_id'         => 'r1',
				'success'            => true,
				'structured_payload' => array( 'content' => '{"x":1}' ),
				'provider_id'        => 'test-openai',
				'model_used'         => 'fake-model',
				'usage'              => null,
				'normalized_error'   => null,
			)
		);
		$out = $fake->request(
			array(
				'request_id' => 'r1',
				'model'      => 'fake-model',
			)
		);
		$this->assertTrue( $out['success'] );
		$this->assertSame( 'test-openai', $out['provider_id'] );
	}

	public function test_default_is_normalized_error(): void {
		$fake = new Fake_AI_Provider_Driver();
		$out  = $fake->request(
			array(
				'request_id' => 'r2',
				'model'      => 'm',
			)
		);
		$this->assertFalse( $out['success'] );
		$this->assertSame( Provider_Response_Normalizer::ERROR_PROVIDER_ERROR, $out['normalized_error']['category'] ?? '' );
	}
}
