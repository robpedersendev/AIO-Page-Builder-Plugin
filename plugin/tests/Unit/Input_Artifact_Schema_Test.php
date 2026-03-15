<?php
/**
 * Unit tests for Input_Artifact_Schema: required sections, prohibited-field exclusion, attachment manifest (ai-input-artifact-schema.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/InputArtifacts/Input_Artifact_Schema.php';

final class Input_Artifact_Schema_Test extends TestCase {

	public function test_required_root_keys_include_artifact_id_and_redaction(): void {
		$keys = Input_Artifact_Schema::required_root_keys();
		$this->assertContains( Input_Artifact_Schema::ROOT_ARTIFACT_ID, $keys );
		$this->assertContains( Input_Artifact_Schema::ROOT_PROMPT_PACK_REF, $keys );
		$this->assertContains( Input_Artifact_Schema::ROOT_REDACTION, $keys );
		$this->assertContains( Input_Artifact_Schema::ROOT_SCHEMA_VERSION, $keys );
		$this->assertContains( Input_Artifact_Schema::ROOT_CREATED_AT, $keys );
		$this->assertCount( 5, $keys );
		$this->assertNotContains( Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT, $keys, 'industry_context is optional (Prompt 331)' );
	}

	public function test_valid_artifact_has_all_required_root_keys(): void {
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID     => 'art_001',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION => '1.0',
			Input_Artifact_Schema::ROOT_CREATED_AT     => '2025-07-15T12:00:00Z',
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF => array(
				Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => 'aio/build-plan-draft',
				Input_Artifact_Schema::PROMPT_PACK_REF_VERSION       => '1.0.0',
			),
			Input_Artifact_Schema::ROOT_REDACTION => array(
				Input_Artifact_Schema::REDACTION_APPLIED => true,
			),
		);
		foreach ( Input_Artifact_Schema::required_root_keys() as $key ) {
			$this->assertArrayHasKey( $key, $artifact );
		}
		$this->assertArrayHasKey( Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY, $artifact[ Input_Artifact_Schema::ROOT_PROMPT_PACK_REF ] );
		$this->assertTrue( $artifact[ Input_Artifact_Schema::ROOT_REDACTION ][ Input_Artifact_Schema::REDACTION_APPLIED ] );
	}

	public function test_prohibited_keys_detected(): void {
		$this->assertTrue( Input_Artifact_Schema::is_prohibited_key( 'api_key' ) );
		$this->assertTrue( Input_Artifact_Schema::is_prohibited_key( 'password' ) );
		$this->assertTrue( Input_Artifact_Schema::is_prohibited_key( 'secret' ) );
		$this->assertTrue( Input_Artifact_Schema::is_prohibited_key( 'access_token' ) );
		$this->assertFalse( Input_Artifact_Schema::is_prohibited_key( 'artifact_id' ) );
		$this->assertFalse( Input_Artifact_Schema::is_prohibited_key( 'profile' ) );
	}

	public function test_find_prohibited_keys_in_array(): void {
		$data = array(
			'artifact_id' => 'art_1',
			'profile'     => array(
				'source'  => 'payload',
				'payload' => array(
					'business_name' => 'Acme',
					'api_key'       => 'sk-bad',
				),
			),
		);
		$found = Input_Artifact_Schema::find_prohibited_keys_in_array( $data );
		$this->assertNotEmpty( $found );
		$this->assertContains( 'profile.payload.api_key', $found );
	}

	public function test_find_prohibited_keys_returns_empty_when_clean(): void {
		$data = array(
			'artifact_id' => 'art_1',
			'profile'     => array( 'source' => 'snapshot_ref', 'snapshot_id' => 's1' ),
			'redaction'   => array( 'redaction_applied' => true ),
		);
		$found = Input_Artifact_Schema::find_prohibited_keys_in_array( $data );
		$this->assertSame( array(), $found );
	}

	public function test_attachment_manifest_entry_required_keys(): void {
		$required = Input_Artifact_Schema::required_attachment_entry_keys();
		$this->assertContains( Input_Artifact_Schema::ATTACHMENT_FILE_ID, $required );
		$this->assertContains( Input_Artifact_Schema::ATTACHMENT_FILE_TYPE, $required );
		$this->assertContains( Input_Artifact_Schema::ATTACHMENT_PURPOSE, $required );
		$this->assertContains( Input_Artifact_Schema::ATTACHMENT_REDACTION_STATUS, $required );
		$this->assertCount( 6, $required );
	}

	public function test_valid_attachment_entry_has_required_keys(): void {
		$entry = array(
			Input_Artifact_Schema::ATTACHMENT_FILE_ID          => 'att_1',
			Input_Artifact_Schema::ATTACHMENT_FILE_TYPE        => 'image/png',
			Input_Artifact_Schema::ATTACHMENT_SOURCE_CATEGORY  => Input_Artifact_Schema::SOURCE_CATEGORY_PROFILE_ASSET,
			Input_Artifact_Schema::ATTACHMENT_PURPOSE          => 'Logo',
			Input_Artifact_Schema::ATTACHMENT_REDACTION_STATUS => Input_Artifact_Schema::REDACTION_STATUS_NONE,
			Input_Artifact_Schema::ATTACHMENT_ATTACHMENT_STATUS => Input_Artifact_Schema::ATTACHMENT_STATUS_ATTACHED,
		);
		foreach ( Input_Artifact_Schema::required_attachment_entry_keys() as $key ) {
			$this->assertArrayHasKey( $key, $entry );
		}
	}

	public function test_invalid_artifact_missing_redaction_rejected(): void {
		$artifact = array(
			Input_Artifact_Schema::ROOT_ARTIFACT_ID     => 'art_bad',
			Input_Artifact_Schema::ROOT_SCHEMA_VERSION   => '1.0',
			Input_Artifact_Schema::ROOT_CREATED_AT       => '2025-07-15T12:00:00Z',
			Input_Artifact_Schema::ROOT_PROMPT_PACK_REF  => array(
				Input_Artifact_Schema::PROMPT_PACK_REF_INTERNAL_KEY => 'aio/test',
				Input_Artifact_Schema::PROMPT_PACK_REF_VERSION       => '1.0.0',
			),
		);
		$this->assertArrayNotHasKey( Input_Artifact_Schema::ROOT_REDACTION, $artifact );
		$required = Input_Artifact_Schema::required_root_keys();
		$this->assertContains( Input_Artifact_Schema::ROOT_REDACTION, $required );
	}
}
