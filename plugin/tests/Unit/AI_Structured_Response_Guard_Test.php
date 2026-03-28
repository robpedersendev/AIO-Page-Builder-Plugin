<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\AI_Structured_Response_Guard;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class AI_Structured_Response_Guard_Test extends TestCase {

	public function test_passes_through_when_no_schema_ref(): void {
		$req = array( 'request_id' => 'r1' );
		$res = array(
			'request_id'         => 'r1',
			'success'            => true,
			'structured_payload' => array( 'content' => 'not json' ),
			'provider_id'        => 'openai',
			'model_used'         => 'gpt-4o',
		);
		$out = AI_Structured_Response_Guard::ensure_json_channel_valid( $req, $res );
		$this->assertTrue( $out['success'] );
	}

	public function test_downgrades_when_structured_channel_not_json(): void {
		$req = array(
			'request_id'                   => 'r1',
			'structured_output_schema_ref' => 'aio/build-plan-draft-v1',
		);
		$res = array(
			'request_id'         => 'r1',
			'success'            => true,
			'structured_payload' => array( 'content' => 'plain text' ),
			'provider_id'        => 'openai',
			'model_used'         => 'gpt-4o',
		);
		$out = AI_Structured_Response_Guard::ensure_json_channel_valid( $req, $res );
		$this->assertFalse( $out['success'] );
		$this->assertSame( 'validation_failure', $out['normalized_error']['internal_code'] ?? '' );
	}

	public function test_allows_valid_json_object_in_content(): void {
		$req = array(
			'request_id'                   => 'r1',
			'structured_output_schema_ref' => 'aio/build-plan-draft-v1',
		);
		$res = array(
			'request_id'         => 'r1',
			'success'            => true,
			'structured_payload' => array( 'content' => '{"a":1}' ),
			'provider_id'        => 'openai',
			'model_used'         => 'gpt-4o',
		);
		$out = AI_Structured_Response_Guard::ensure_json_channel_valid( $req, $res );
		$this->assertTrue( $out['success'] );
	}
}
