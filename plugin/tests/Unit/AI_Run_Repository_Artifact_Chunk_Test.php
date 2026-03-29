<?php
/**
 * Large AI run artifacts: chunked post meta persistence (spec §29).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/AI_Run_Repository.php';

final class AI_Run_Repository_Artifact_Chunk_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
	}

	public function test_small_artifact_stores_single_meta_row(): void {
		$repo    = new AI_Run_Repository();
		$payload = array(
			'system_prompt' => 'hi',
			'user_message'  => 'there',
		);
		$this->assertTrue( $repo->save_artifact_payload( 42, 'raw_prompt', $payload ) );
		$base = AI_Run_Repository::META_ARTIFACT_PREFIX . 'raw_prompt';
		$this->assertSame( '', (string) \get_post_meta( 42, $base . '__chunk_count', true ) );
		$this->assertSame( $payload, $repo->get_artifact_payload( 42, 'raw_prompt' ) );
	}

	public function test_large_artifact_round_trips_via_chunks(): void {
		$repo    = new AI_Run_Repository();
		$big     = str_repeat( 'x', 700000 );
		$payload = array(
			'system_prompt' => $big,
			'user_message'  => 'short',
		);
		$this->assertTrue( $repo->save_artifact_payload( 7, 'raw_prompt', $payload ) );
		$this->assertSame( $payload, $repo->get_artifact_payload( 7, 'raw_prompt' ) );
		$base = AI_Run_Repository::META_ARTIFACT_PREFIX . 'raw_prompt';
		$cnt  = (int) \get_post_meta( 7, $base . '__chunk_count', true );
		$this->assertGreaterThan( 1, $cnt );
	}

	public function test_overwrite_chunked_with_small_clears_part_keys(): void {
		$repo = new AI_Run_Repository();
		$this->assertTrue( $repo->save_artifact_payload( 3, 'raw_prompt', array( 'system_prompt' => str_repeat( 'a', 600000 ) ) ) );
		$this->assertTrue( $repo->save_artifact_payload( 3, 'raw_prompt', array( 'x' => 1 ) ) );
		$base = AI_Run_Repository::META_ARTIFACT_PREFIX . 'raw_prompt';
		$this->assertSame( '', (string) \get_post_meta( 3, $base . '__chunk_count', true ) );
		$this->assertSame( array( 'x' => 1 ), $repo->get_artifact_payload( 3, 'raw_prompt' ) );
	}
}
