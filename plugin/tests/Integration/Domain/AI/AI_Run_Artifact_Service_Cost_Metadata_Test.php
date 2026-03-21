<?php
/**
 * Integration tests: cost_usd persists through AI_Run_Artifact_Service and reads back correctly.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Domain\AI;

use AIOPageBuilder\Domain\AI\Runs\Artifact_Category_Keys;
use AIOPageBuilder\Domain\AI\Runs\AI_Artifact_Repository_Interface;
use AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service;
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

/**
 * In-memory artifact store satisfying AI_Artifact_Repository_Interface without WordPress infrastructure.
 */
final class In_Memory_Artifact_Repo implements AI_Artifact_Repository_Interface {
	/** @var array<int, array<string, mixed>> */
	private array $data = array();

	public function get_artifact_payload( int $post_id, string $category ): mixed {
		return $this->data[ $post_id ][ $category ] ?? null;
	}

	public function save_artifact_payload( int $post_id, string $category, mixed $payload ): bool {
		$this->data[ $post_id ][ $category ] = $payload;
		return true;
	}
}

/**
 * @covers \AIOPageBuilder\Domain\AI\Runs\AI_Run_Artifact_Service
 */
final class AI_Run_Artifact_Service_Cost_Metadata_Test extends TestCase {

	private AI_Run_Artifact_Service $service;

	protected function setUp(): void {
		$this->service = new AI_Run_Artifact_Service( new In_Memory_Artifact_Repo() );
	}

	public function test_cost_usd_is_stored_and_retrieved_via_usage_metadata(): void {
		$usage = array(
			'prompt_tokens'     => 1000,
			'completion_tokens' => 500,
			'total_tokens'      => 1500,
			'cost_usd'          => 0.0075,
		);

		$stored = $this->service->store( 1, Artifact_Category_Keys::USAGE_METADATA, $usage );
		$this->assertTrue( $stored );

		$retrieved = $this->service->get( 1, Artifact_Category_Keys::USAGE_METADATA );
		$this->assertIsArray( $retrieved );
		$this->assertArrayHasKey( 'cost_usd', $retrieved );
		$this->assertEqualsWithDelta( 0.0075, $retrieved['cost_usd'], 1.0e-9 );
	}

	public function test_null_cost_usd_stores_and_reads_back_as_null(): void {
		$usage = array(
			'prompt_tokens'     => 100,
			'completion_tokens' => 50,
			'total_tokens'      => 150,
			'cost_usd'          => null,
		);

		$this->service->store( 2, Artifact_Category_Keys::USAGE_METADATA, $usage );
		$retrieved = $this->service->get( 2, Artifact_Category_Keys::USAGE_METADATA );
		$this->assertNull( $retrieved['cost_usd'] );
	}

	public function test_cost_usd_in_summary_review_shows_artifact_present(): void {
		$usage = array(
			'prompt_tokens'     => 500,
			'completion_tokens' => 250,
			'total_tokens'      => 750,
			'cost_usd'          => 0.0039,
		);
		$this->service->store( 3, Artifact_Category_Keys::USAGE_METADATA, $usage );

		$summary = $this->service->get_artifact_summary_for_review( 3, true );
		$this->assertArrayHasKey( Artifact_Category_Keys::USAGE_METADATA, $summary );
		$this->assertTrue( $summary[ Artifact_Category_Keys::USAGE_METADATA ]['present'] );
	}

	public function test_zero_cost_usd_is_distinct_from_null(): void {
		$usage_zero = array(
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
			'total_tokens'      => 0,
			'cost_usd'          => 0.0,
		);
		$usage_null = array(
			'prompt_tokens'     => 0,
			'completion_tokens' => 0,
			'total_tokens'      => 0,
			'cost_usd'          => null,
		);

		$this->service->store( 4, Artifact_Category_Keys::USAGE_METADATA, $usage_zero );
		$this->service->store( 5, Artifact_Category_Keys::USAGE_METADATA, $usage_null );

		$this->assertSame( 0.0, $this->service->get( 4, Artifact_Category_Keys::USAGE_METADATA )['cost_usd'] );
		$this->assertNull( $this->service->get( 5, Artifact_Category_Keys::USAGE_METADATA )['cost_usd'] );
	}
}
