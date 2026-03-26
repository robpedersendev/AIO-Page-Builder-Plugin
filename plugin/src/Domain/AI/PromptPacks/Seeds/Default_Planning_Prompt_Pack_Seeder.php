<?php
/**
 * Idempotent activation seed: ensures at least one active planning pack exists for build-plan-draft (spec §26, §59.8).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Seeds;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\PromptPacks\Prompt_Pack_Registry_Service;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Prompt_Pack_Repository;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Seeds Default_Planning_Prompt_Pack_Definition when no eligible planning pack is already registered.
 */
final class Default_Planning_Prompt_Pack_Seeder {

	/**
	 * One-time guard for installs that never ran activation seed (e.g. manual copy) or failed mid-activation.
	 * Sets DEFAULT_PROMPT_PACK_SEEDED_V2 when seeding succeeds or an eligible pack already exists.
	 *
	 * @return void
	 */
	public static function maybe_ensure_once(): void {
		if ( \get_option( Option_Names::DEFAULT_PROMPT_PACK_SEEDED_V2, '' ) === '1' ) {
			return;
		}
		$repo   = new Prompt_Pack_Repository();
		$result = self::run( $repo );
		if ( $result['success'] ) {
			\update_option( Option_Names::DEFAULT_PROMPT_PACK_SEEDED_V2, '1', true );
		}
	}

	/**
	 * @return array{ success: bool, skipped: bool, post_id: int, errors: list<string> }
	 */
	public static function run( Prompt_Pack_Repository $repo ): array {
		$registry = new Prompt_Pack_Registry_Service( $repo );
		if ( $registry->select_for_planning( Build_Plan_Draft_Schema::SCHEMA_REF, null ) !== null ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::DEFAULT_PROMPT_PACK_SEED_RESULT, 'run skipped=1 existing_pack=1' );
			return array(
				'success' => true,
				'skipped' => true,
				'post_id' => 0,
				'errors'  => array(),
			);
		}

		$post_id = $repo->save_definition( Default_Planning_Prompt_Pack_Definition::get() );
		if ( $post_id <= 0 ) {
			Named_Debug_Log::event( Named_Debug_Log_Event::DEFAULT_PROMPT_PACK_SEED_RESULT, 'run success=0 save_failed=1' );
			return array(
				'success' => false,
				'skipped' => false,
				'post_id' => 0,
				'errors'  => array( 'default_prompt_pack_save_failed' ),
			);
		}

		Named_Debug_Log::event( Named_Debug_Log_Event::DEFAULT_PROMPT_PACK_SEED_RESULT, 'run success=1 post_id=' . (string) $post_id );
		return array(
			'success' => true,
			'skipped' => false,
			'post_id' => $post_id,
			'errors'  => array(),
		);
	}
}
