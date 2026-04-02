<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Definition_Json_Legacy_Repair;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Build_Plan_Definition_Json_Legacy_Repair_Test extends TestCase {

	public function test_repairs_quoted_section_guidance_embedding_json_array(): void {
		$bad   = <<<'JSON'
{"plan_id":"p","steps":[{"items":[{"payload":{"section_guidance":"["hero","offering"]","x":1}}]}]}
JSON;
		$fixed = Build_Plan_Definition_Json_Legacy_Repair::try_repair_corrupt_section_guidance( $bad );
		$this->assertNotSame( $bad, $fixed );
		$decoded = \json_decode( $fixed, true );
		$this->assertIsArray( $decoded );
		$this->assertSame( array( 'hero', 'offering' ), $decoded['steps'][0]['items'][0]['payload']['section_guidance'] );
	}

	public function test_repairs_multiple_section_guidance_occurrences(): void {
		$bad   = <<<'JSON'
{"items":[{"payload":{"section_guidance":"["a","b"]"}},{"payload":{"section_guidance":"["c"]"}}]}
JSON;
		$fixed = Build_Plan_Definition_Json_Legacy_Repair::try_repair_corrupt_section_guidance( $bad );
		$d     = \json_decode( $fixed, true );
		$this->assertIsArray( $d );
		$this->assertSame( array( 'a', 'b' ), $d['items'][0]['payload']['section_guidance'] );
		$this->assertSame( array( 'c' ), $d['items'][1]['payload']['section_guidance'] );
	}

	public function test_noop_when_already_valid(): void {
		$ok = '{"section_guidance":["hero","a"]}';
		$this->assertSame( $ok, Build_Plan_Definition_Json_Legacy_Repair::try_repair_corrupt_section_guidance( $ok ) );
	}
}
