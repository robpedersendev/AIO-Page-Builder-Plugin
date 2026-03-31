<?php
/**
 * Data access for Build Plan objects (spec §10.4, §30.3). Backing: CPT aio_build_plan; full definition in meta.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface;
use AIOPageBuilder\Domain\BuildPlan\Build_Plan_Template_Lab_Context;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

// * When this file is loaded via Composer PSR-4 only, bootstrap require order may not have run yet.
require_once __DIR__ . '/Build_Plan_Repository_Interface.php';
require_once __DIR__ . '/../../Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once __DIR__ . '/../../BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';

/**
 * Repository → storage: Object_Type_Keys::BUILD_PLAN (CPT).
 * Internal key: plan_id (e.g. UUID). Status: pending_review | approved | rejected | in_progress | completed | superseded.
 * Full plan definition (steps, items, etc.) stored in _aio_plan_definition meta.
 * Implements Plan_State_For_Execution_Interface for single-action executor; Build_Plan_List_Provider_Interface for analytics.
 */
final class Build_Plan_Repository extends Abstract_CPT_Repository implements Build_Plan_Repository_Interface, Plan_State_For_Execution_Interface, Build_Plan_List_Provider_Interface {

	public const META_PLAN_DEFINITION = '_aio_plan_definition';

	/** Post meta: stable lineage UUID shared by all versions of a plan. */
	public const META_PLAN_LINEAGE_ID = '_aio_plan_lineage_id';

	/** Post meta: integer sequence within lineage (1, 2, 3…). */
	public const META_PLAN_VERSION_SEQ = '_aio_plan_version_seq';

	/** Post meta: AI run internal key that produced this plan (repair when JSON meta failed to persist). */
	public const META_PLAN_SOURCE_AI_RUN_REF = '_aio_plan_source_ai_run_ref';

	/**
	 * Max size for a single postmeta row; larger JSON is stored like {@see AI_Run_Repository::save_artifact_payload()} (`__part_*`, `__chunk_count`).
	 */
	private const PLAN_DEFINITION_JSON_CHUNK_BYTES = 524288;

	/** @inheritdoc */
	protected function get_post_type(): string {
		return Object_Type_Keys::BUILD_PLAN;
	}

	/**
	 * Lists plans by most recent first (any status). For admin list screen.
	 *
	 * @param int $limit  Max items (default 50).
	 * @param int $offset Offset for pagination.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0 ): array {
		$limit = $limit > 0 ? $limit : self::DEFAULT_LIST_LIMIT;
		$query = new \WP_Query(
			array(
				'post_type'              => $this->get_post_type(),
				'posts_per_page'         => $limit,
				'offset'                 => $offset,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			)
		);
		$out   = array();
		foreach ( $query->get_posts() as $post ) {
			$meta  = $this->get_meta( $post->ID );
			$out[] = $this->post_to_record( $post, $meta );
		}
		return $out;
	}

	/**
	 * Returns the full plan definition (root payload with steps) for a plan post.
	 *
	 * @param int $post_id Plan post ID.
	 * @return array<string, mixed> Decoded plan definition or empty array.
	 */
	public function get_plan_definition( int $post_id ): array {
		$assembled = self::assemble_plan_definition_json_string_from_post_meta( $post_id );
		$raw       = $assembled !== '' ? $assembled : \get_post_meta( $post_id, self::META_PLAN_DEFINITION, true );
		$parsed    = self::plan_definition_from_meta_raw( $raw );
		if ( Named_Debug_Log::build_plan_meta_trace_enabled() ) {
			$steps_n = isset( $parsed[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $parsed[ Build_Plan_Schema::KEY_STEPS ] )
				? count( $parsed[ Build_Plan_Schema::KEY_STEPS ] )
				: -1;
			$detail  = 'post_id=' . (string) $post_id . ' raw_type=' . get_debug_type( $raw );
			if ( is_string( $raw ) ) {
				$detail .= ' raw_len=' . (string) strlen( $raw );
				if ( $parsed === array() && $raw !== '' ) {
					\json_decode( $raw, true, 512, self::json_decode_plan_flags() );
					$detail .= ' json_err=' . \json_last_error_msg();
				}
			}
			$detail .= ' steps_n=' . (string) $steps_n . ' has_steps=' . ( self::plan_definition_has_non_empty_steps( $parsed ) ? '1' : '0' );
			Named_Debug_Log::event( Named_Debug_Log_Event::BP_PLAN_DEFINITION_GET_TRACE, $detail );
		}
		return $parsed;
	}

	/**
	 * Flags for decoding stored plan JSON (tolerate invalid UTF-8 in legacy blobs).
	 *
	 * @return int
	 */
	private static function json_decode_plan_flags(): int {
		$flags = JSON_BIGINT_AS_STRING;
		if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$flags |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		return $flags;
	}

	/**
	 * Decodes a JSON plan blob; tries wp_unslash (raw DB / mis-slashed meta), BOM trim, and trim.
	 *
	 * @param string $raw Meta string (may be slash-escaped per WordPress DB storage when read via $wpdb).
	 * @return array<string, mixed>
	 */
	private static function decode_plan_definition_json_string( string $raw ): array {
		if ( $raw === '' ) {
			return array();
		}
		$flags        = self::json_decode_plan_flags();
		$candidates   = array( $raw );
		$bom_stripped = strncmp( $raw, "\xEF\xBB\xBF", 3 ) === 0 ? substr( $raw, 3 ) : null;
		if ( is_string( $bom_stripped ) && $bom_stripped !== '' && $bom_stripped !== $raw ) {
			$candidates[] = $bom_stripped;
		}
		if ( \function_exists( 'wp_unslash' ) ) {
			$u = \wp_unslash( $raw );
			if ( $u !== $raw ) {
				$candidates[] = $u;
			}
			if ( is_string( $bom_stripped ) && $bom_stripped !== '' ) {
				$bu = \wp_unslash( $bom_stripped );
				if ( $bu !== $bom_stripped && $bu !== $u ) {
					$candidates[] = $bu;
				}
			}
		}
		$trim = trim( $raw );
		if ( $trim !== $raw ) {
			$candidates[] = $trim;
		}
		if ( \function_exists( 'wp_unslash' ) && $trim !== '' ) {
			$tu = \wp_unslash( $trim );
			if ( $tu !== $trim ) {
				$candidates[] = $tu;
			}
		}
		foreach ( $candidates as $candidate ) {
			if ( ! is_string( $candidate ) || $candidate === '' ) {
				continue;
			}
			$decoded = \json_decode( $candidate, true, 512, $flags );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}
		return array();
	}

	/**
	 * Normalizes meta read for {@see get_plan_definition()}: JSON string, PHP-serialized array string, or array (WP unserialized).
	 *
	 * @param mixed $raw Value from get_post_meta( …, single true ).
	 * @return array<string, mixed>
	 */
	private static function plan_definition_from_meta_raw( mixed $raw ): array {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$decoded = self::decode_plan_definition_json_string( $raw );
		if ( $decoded !== array() ) {
			return $decoded;
		}
		if ( \function_exists( 'is_serialized' ) && \is_serialized( $raw, false ) ) {
			$un = \maybe_unserialize( $raw );
			return is_array( $un ) ? $un : array();
		}
		return array();
	}

	/**
	 * @param array<string, mixed> $definition Root plan payload.
	 */
	private static function plan_definition_has_non_empty_steps( array $definition ): bool {
		$steps = $definition[ Build_Plan_Schema::KEY_STEPS ] ?? null;
		return is_array( $steps ) && $steps !== array();
	}

	/**
	 * When chunked rows exist, returns concatenated JSON; otherwise '' (caller uses single meta key).
	 */
	private static function assemble_plan_definition_json_string_from_post_meta( int $post_id ): string {
		$chunk_count = \get_post_meta( $post_id, self::META_PLAN_DEFINITION . '__chunk_count', true );
		$n           = is_numeric( $chunk_count ) ? (int) $chunk_count : 0;
		if ( $n <= 0 ) {
			return '';
		}
		$pieces = array();
		for ( $i = 0; $i < $n; $i++ ) {
			$part = \get_post_meta( $post_id, self::META_PLAN_DEFINITION . '__part_' . (string) $i, true );
			if ( ! is_string( $part ) || $part === '' ) {
				return '';
			}
			$pieces[] = $part;
		}
		return implode( '', $pieces );
	}

	/**
	 * Clears single-row and chunked plan definition meta before rewriting.
	 */
	private function clear_plan_definition_storage( int $post_id ): void {
		$c = \get_post_meta( $post_id, self::META_PLAN_DEFINITION . '__chunk_count', true );
		$n = is_numeric( $c ) ? (int) $c : 0;
		for ( $i = 0; $i < $n; $i++ ) {
			\delete_post_meta( $post_id, self::META_PLAN_DEFINITION . '__part_' . (string) $i );
		}
		\delete_post_meta( $post_id, self::META_PLAN_DEFINITION . '__chunk_count' );
		\delete_post_meta( $post_id, self::META_PLAN_DEFINITION );
	}

	/**
	 * Direct DB insert for postmeta when update_post_meta is blocked (aio_build_plan + AIO keys only).
	 */
	private function insert_postmeta_row_direct_for_plan( int $post_id, string $meta_key, string $meta_value ): bool {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! isset( $wpdb->postmeta ) || ! \is_string( $wpdb->postmeta )
			|| ! \method_exists( $wpdb, 'insert' ) ) {
			return false;
		}
		$slashed = \function_exists( 'wp_slash' ) ? \wp_slash( $meta_value ) : addslashes( $meta_value );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Scoped fallback when hooks block update_post_meta for this CPT.
		$r = $wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $post_id,
				'meta_key'   => $meta_key,
				'meta_value' => $slashed,
			),
			array( '%d', '%s', '%s' )
		);
		$le_ok = ! \property_exists( $wpdb, 'last_error' ) || (string) $wpdb->last_error === '';
		return $r !== false && $le_ok;
	}

	/**
	 * True when the post is the Build Plan CPT (direct meta fallback is allowed only here).
	 */
	private function is_build_plan_post_for_meta_persist( int $post_id ): bool {
		if ( $post_id <= 0 || ! \function_exists( 'get_post_type' ) ) {
			return false;
		}
		$pt = \get_post_type( $post_id );
		return is_string( $pt ) && $pt === $this->get_post_type();
	}

	/**
	 * Reads canonical JSON string from postmeta (chunked or single), bypassing PHP meta cache where $wpdb is available.
	 */
	private function read_plan_definition_json_string_from_db( int $post_id ): string {
		global $wpdb;
		if ( ! is_object( $wpdb ) || ! isset( $wpdb->postmeta ) || ! \is_string( $wpdb->postmeta )
			|| ! \method_exists( $wpdb, 'get_var' ) || ! \method_exists( $wpdb, 'prepare' ) ) {
			return '';
		}
		$table = $wpdb->postmeta;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Post-save verification reads DB truth.
		$chunk_count_raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$table} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
				$post_id,
				self::META_PLAN_DEFINITION . '__chunk_count'
			)
		);
		$n = is_numeric( $chunk_count_raw ) ? (int) $chunk_count_raw : 0;
		if ( $n > 0 ) {
			$pieces = array();
			for ( $i = 0; $i < $n; $i++ ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$part = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT meta_value FROM {$table} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
						$post_id,
						self::META_PLAN_DEFINITION . '__part_' . (string) $i
					)
				);
				if ( ! is_string( $part ) || $part === '' ) {
					return '';
				}
				$pieces[] = \function_exists( 'wp_unslash' ) ? \wp_unslash( $part ) : $part;
			}
			return implode( '', $pieces );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$table} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
				$post_id,
				self::META_PLAN_DEFINITION
			)
		);
		if ( ! is_string( $row ) ) {
			return '';
		}
		return \function_exists( 'wp_unslash' ) ? \wp_unslash( $row ) : $row;
	}

	/**
	 * CHAR_LENGTH(meta_value) for the newest postmeta row (DB stores slash-escaped values).
	 */
	private function postmeta_db_char_length( int $post_id, string $meta_key ): int {
		global $wpdb;
		if ( ! $wpdb instanceof \wpdb || ! isset( $wpdb->postmeta ) ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Length probe for persistence integrity.
		$len = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT CHAR_LENGTH(meta_value) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
				$post_id,
				$meta_key
			)
		);
		return is_numeric( $len ) ? (int) $len : 0;
	}

	/**
	 * Expected DB CHAR_LENGTH for a meta value WordPress will slash before insert.
	 */
	private static function expected_slashed_meta_char_length( string $value ): int {
		$slashed = \function_exists( 'wp_slash' ) ? \wp_slash( $value ) : $value;
		return strlen( $slashed );
	}

	/**
	 * True when the newest _aio_plan_definition row’s stored length matches wp_slash( $json ).
	 */
	private function plan_definition_single_row_db_length_matches( int $post_id, string $json ): bool {
		$exp = self::expected_slashed_meta_char_length( $json );
		$act = $this->postmeta_db_char_length( $post_id, self::META_PLAN_DEFINITION );
		return $exp > 0 && $act === $exp;
	}

	/**
	 * @param mixed $updated_return Value from update_post_meta.
	 */
	private function log_update_meta_failed_diag( int $post_id, int $value_len, mixed $updated_return ): void {
		global $wpdb;
		$ur_str = is_bool( $updated_return ) ? ( $updated_return ? 'bool_true' : 'bool_false' ) : get_debug_type( $updated_return );
		$extra  = '';
		if ( $wpdb instanceof \wpdb && isset( $wpdb->last_error ) && (string) $wpdb->last_error !== '' ) {
			$extra = ' wpdb_err=' . substr( preg_replace( '/\s+/', ' ', (string) $wpdb->last_error ), 0, 120 );
		}
		Named_Debug_Log::event(
			Named_Debug_Log_Event::BP_PLAN_DEFINITION_UPDATE_META_FAILED,
			'post_id=' . (string) $post_id . ' json_len=' . (string) $value_len . ' update_return=' . $ur_str . $extra
		);
	}

	/**
	 * @return bool Success writing all rows (API or direct insert fallback for aio_build_plan only).
	 */
	private function persist_single_plan_definition_row( int $post_id, string $json ): bool {
		$updated = \update_post_meta( $post_id, self::META_PLAN_DEFINITION, $json );
		$ok      = $updated !== false;
		if ( ! $ok ) {
			$existing_raw = \get_post_meta( $post_id, self::META_PLAN_DEFINITION, true );
			if ( \is_string( $existing_raw ) && $existing_raw === $json ) {
				$ok = true;
			} elseif ( is_array( $existing_raw ) ) {
				$roundtrip = \wp_json_encode( $existing_raw );
				$ok        = \is_string( $roundtrip ) && $roundtrip === $json;
			}
		}
		if ( $ok && ! $this->plan_definition_single_row_db_length_matches( $post_id, $json ) ) {
			$exp = self::expected_slashed_meta_char_length( $json );
			$act = $this->postmeta_db_char_length( $post_id, self::META_PLAN_DEFINITION );
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL,
				'post_id=' . (string) $post_id . ' phase=post_update_meta_length expected_db_char_len=' . (string) $exp . ' actual_db_char_len=' . (string) $act
					. ' intended_sha1=' . substr( \hash( 'sha1', $json ), 0, 12 )
			);
			\delete_post_meta( $post_id, self::META_PLAN_DEFINITION );
			$ok = false;
		}
		if ( $ok ) {
			return true;
		}
		$this->log_update_meta_failed_diag( $post_id, strlen( $json ), $updated );
		if ( $this->is_build_plan_post_for_meta_persist( $post_id ) && $this->insert_postmeta_row_direct_for_plan( $post_id, self::META_PLAN_DEFINITION, $json ) ) {
			$this->refresh_post_meta_runtime_cache( $post_id );
			if ( ! $this->plan_definition_single_row_db_length_matches( $post_id, $json ) ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL,
					'post_id=' . (string) $post_id . ' phase=direct_insert_length expected_db_char_len=' . (string) self::expected_slashed_meta_char_length( $json )
						. ' actual_db_char_len=' . (string) $this->postmeta_db_char_length( $post_id, self::META_PLAN_DEFINITION )
				);
				return false;
			}
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_DB_INSERT_FALLBACK_OK,
				'post_id=' . (string) $post_id . ' json_len=' . (string) strlen( $json ) . ' mode=single'
			);
			return true;
		}
		return false;
	}

	/**
	 * @return bool Success writing part rows and chunk_count.
	 */
	private function persist_chunked_plan_definition_rows( int $post_id, string $json ): bool {
		$len    = strlen( $json );
		$offset = 0;
		$index  = 0;
		while ( $offset < $len ) {
			$piece = substr( $json, $offset, self::PLAN_DEFINITION_JSON_CHUNK_BYTES );
			if ( $piece === '' ) {
				break;
			}
			$pkey = self::META_PLAN_DEFINITION . '__part_' . (string) $index;
			if ( \update_post_meta( $post_id, $pkey, $piece ) === false ) {
				$this->log_update_meta_failed_diag( $post_id, strlen( $piece ), false );
				if ( ! $this->is_build_plan_post_for_meta_persist( $post_id )
					|| ! $this->insert_postmeta_row_direct_for_plan( $post_id, $pkey, $piece ) ) {
					return false;
				}
				Named_Debug_Log::event(
					Named_Debug_Log_Event::BP_PLAN_DEFINITION_DB_INSERT_FALLBACK_OK,
					'post_id=' . (string) $post_id . ' json_len=' . (string) strlen( $piece ) . ' mode=chunked_part part=' . (string) $index
				);
				$this->refresh_post_meta_runtime_cache( $post_id );
			}
			$piece_len = strlen( $piece );
			if ( $piece_len === 0 ) {
				break;
			}
			$offset += $piece_len;
			++$index;
		}
		if ( $index <= 0 ) {
			return false;
		}
		$cc_key = self::META_PLAN_DEFINITION . '__chunk_count';
		$cc_val = (string) $index;
		if ( \update_post_meta( $post_id, $cc_key, $cc_val ) === false ) {
			$this->log_update_meta_failed_diag( $post_id, strlen( $cc_val ), false );
			if ( ! $this->is_build_plan_post_for_meta_persist( $post_id )
				|| ! $this->insert_postmeta_row_direct_for_plan( $post_id, $cc_key, $cc_val ) ) {
				return false;
			}
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_DB_INSERT_FALLBACK_OK,
				'post_id=' . (string) $post_id . ' json_len=' . (string) strlen( $cc_val ) . ' mode=chunk_count parts=' . (string) $index
			);
			$this->refresh_post_meta_runtime_cache( $post_id );
		}
		$this->refresh_post_meta_runtime_cache( $post_id );
		$round = $this->read_plan_definition_json_string_from_db( $post_id );
		if ( $round !== $json ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL,
				'post_id=' . (string) $post_id . ' phase=chunked_roundtrip read_len=' . (string) strlen( $round ) . ' json_len=' . (string) strlen( $json )
			);
			return false;
		}
		return true;
	}

	/**
	 * Persists JSON after {@see clear_plan_definition_storage()}; uses chunking when over {@see PLAN_DEFINITION_JSON_CHUNK_BYTES}.
	 */
	private function persist_plan_definition_payload( int $post_id, string $json ): bool {
		if ( strlen( $json ) <= self::PLAN_DEFINITION_JSON_CHUNK_BYTES ) {
			return $this->persist_single_plan_definition_row( $post_id, $json );
		}
		if ( Named_Debug_Log::build_plan_meta_trace_enabled() ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_CHUNKED_WRITE,
				'post_id=' . (string) $post_id . ' json_len=' . (string) strlen( $json ) . ' chunk_bytes=' . (string) self::PLAN_DEFINITION_JSON_CHUNK_BYTES
			);
		}
		return $this->persist_chunked_plan_definition_rows( $post_id, $json );
	}

	/**
	 * Reloads plan definition from DB (newest meta row) bypassing stale object cache, then falls back to get_post_meta.
	 *
	 * @param int $post_id Plan post ID.
	 * @return array<string, mixed>
	 */
	private function load_plan_definition_after_persist_for_verify( int $post_id ): array {
		$blob = $this->read_plan_definition_json_string_from_db( $post_id );
		if ( $blob !== '' ) {
			return self::plan_definition_from_meta_raw( $blob );
		}
		$assembled = self::assemble_plan_definition_json_string_from_post_meta( $post_id );
		if ( $assembled !== '' ) {
			return self::plan_definition_from_meta_raw( $assembled );
		}
		$raw = \get_post_meta( $post_id, self::META_PLAN_DEFINITION, true );
		return self::plan_definition_from_meta_raw( $raw );
	}

	/**
	 * Safe diagnostic string when persisted definition cannot be read back with steps.
	 *
	 * @param int $post_id Plan post ID.
	 */
	private function plan_definition_persist_failure_diag( int $post_id ): string {
		global $wpdb;
		$parts = array( 'post_id=' . (string) $post_id );
		if ( $wpdb instanceof \wpdb && isset( $wpdb->postmeta ) ) {
			$table = $wpdb->postmeta;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Diagnostics only under WP_DEBUG.
			$cnt     = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE post_id = %d AND meta_key = %s",
					$post_id,
					self::META_PLAN_DEFINITION
				)
			);
			$parts[] = 'meta_rows=' . (string) $cnt;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$cc_raw = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$table} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1",
					$post_id,
					self::META_PLAN_DEFINITION . '__chunk_count'
				)
			);
			if ( is_numeric( $cc_raw ) ) {
				$parts[] = 'chunk_count=' . (string) (int) $cc_raw;
			}
		}
		$gm      = \get_post_meta( $post_id, self::META_PLAN_DEFINITION, true );
		$parts[] = 'get_post_meta_type=' . get_debug_type( $gm );
		if ( is_string( $gm ) ) {
			$parts[] = 'get_post_meta_len=' . (string) strlen( $gm );
			$parts[] = 'head_sha1=' . substr( \hash( 'sha1', $gm ), 0, 12 );
			\json_decode( $gm, true, 512, self::json_decode_plan_flags() );
			$parts[] = 'json_err=' . \json_last_error_msg();
			if ( \function_exists( 'wp_unslash' ) ) {
				$u = \wp_unslash( $gm );
				if ( $u !== $gm ) {
					\json_decode( $u, true, 512, self::json_decode_plan_flags() );
					$parts[] = 'json_err_after_unslash=' . \json_last_error_msg();
				}
			}
		}
		return implode( ' ', $parts );
	}

	/**
	 * Drops in-process meta cache for this post so subsequent get_post_meta matches DB.
	 */
	private function refresh_post_meta_runtime_cache( int $post_id ): void {
		if ( \function_exists( 'clean_post_cache' ) ) {
			\clean_post_cache( $post_id );
		}
		if ( \function_exists( 'wp_cache_delete' ) ) {
			\wp_cache_delete( $post_id, 'post_meta' );
		}
		if ( \function_exists( 'update_postmeta_cache' ) ) {
			\update_postmeta_cache( array( $post_id ) );
		}
	}

	/**
	 * Saves the full plan definition for a plan post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $definition Plan root payload (plan_id, status, steps, etc.).
	 * @return bool Success.
	 */
	public function save_plan_definition( int $post_id, array $definition ): bool {
		if ( array_key_exists( Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT, $definition ) ) {
			$tl = $definition[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ];
			$definition[ Build_Plan_Schema::KEY_TEMPLATE_LAB_CONTEXT ] = Build_Plan_Template_Lab_Context::sanitize(
				is_array( $tl ) ? $tl : null
			);
		}
		$encode_opts = 0;
		if ( \defined( 'JSON_INVALID_UTF8_SUBSTITUTE' ) ) {
			$encode_opts |= JSON_INVALID_UTF8_SUBSTITUTE;
		}
		if ( \defined( 'JSON_UNESCAPED_UNICODE' ) ) {
			$encode_opts |= JSON_UNESCAPED_UNICODE;
		}
		$json = \wp_json_encode( $definition, $encode_opts );
		if ( $json === false ) {
			return false;
		}
		$roundtrip = self::decode_plan_definition_json_string( $json );
		if ( self::plan_definition_has_non_empty_steps( $definition ) ) {
			if ( ! self::plan_definition_has_non_empty_steps( $roundtrip ) ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL,
					'post_id=' . (string) $post_id . ' phase=pre_save_roundtrip json_len=' . (string) strlen( $json ) . ' json_err=encode_roundtrip_no_steps'
				);
				return false;
			}
		}
		// * Remove single-row and chunked rows so duplicate postmeta cannot mask the new blob.
		$this->clear_plan_definition_storage( $post_id );
		if ( ! $this->persist_plan_definition_payload( $post_id, $json ) ) {
			return false;
		}
		$this->sync_lineage_meta_from_definition( $post_id, $definition );
		$this->sync_source_ai_run_meta_from_definition( $post_id, $definition );
		$this->refresh_post_meta_runtime_cache( $post_id );

		$expect_steps = self::plan_definition_has_non_empty_steps( $definition );
		if ( $expect_steps ) {
			$verified = $this->load_plan_definition_after_persist_for_verify( $post_id );
			if ( ! self::plan_definition_has_non_empty_steps( $verified ) ) {
				Named_Debug_Log::event(
					Named_Debug_Log_Event::BP_PLAN_DEFINITION_PERSIST_VERIFY_FAIL,
					$this->plan_definition_persist_failure_diag( $post_id )
				);
				return false;
			}
			if ( Named_Debug_Log::build_plan_meta_trace_enabled() ) {
				$n = count( $verified[ Build_Plan_Schema::KEY_STEPS ] );
				Named_Debug_Log::event(
					Named_Debug_Log_Event::BP_PLAN_DEFINITION_SAVE_VERIFY_OK,
					'post_id=' . (string) $post_id . ' verified_steps_n=' . (string) $n . ' saved_json_len=' . (string) strlen( $json )
				);
			}
		}
		return true;
	}

	/**
	 * Persists source run ref for empty-definition repair when run metadata lacks build_plan_ref.
	 *
	 * @param array<string, mixed> $definition Plan root.
	 */
	private function sync_source_ai_run_meta_from_definition( int $post_id, array $definition ): void {
		$ref = isset( $definition[ Build_Plan_Schema::KEY_AI_RUN_REF ] ) ? trim( (string) $definition[ Build_Plan_Schema::KEY_AI_RUN_REF ] ) : '';
		if ( $ref !== '' ) {
			\update_post_meta( $post_id, self::META_PLAN_SOURCE_AI_RUN_REF, \sanitize_text_field( $ref ) );
		}
	}

	/**
	 * Mirrors lineage fields from the JSON definition into queryable post meta.
	 *
	 * @param array<string, mixed> $definition Plan root.
	 */
	private function sync_lineage_meta_from_definition( int $post_id, array $definition ): void {
		$lid = isset( $definition[ Build_Plan_Schema::KEY_PLAN_LINEAGE_ID ] ) ? trim( (string) $definition[ Build_Plan_Schema::KEY_PLAN_LINEAGE_ID ] ) : '';
		if ( $lid !== '' ) {
			\update_post_meta( $post_id, self::META_PLAN_LINEAGE_ID, $lid );
		}
		if ( isset( $definition[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] ) ) {
			\update_post_meta( $post_id, self::META_PLAN_VERSION_SEQ, (int) $definition[ Build_Plan_Schema::KEY_PLAN_VERSION_SEQ ] );
		}
	}

	/**
	 * Finds the step index that contains the given plan item id (for executor state updates).
	 *
	 * @param array<string, mixed> $definition Plan definition (steps array).
	 * @param string               $plan_item_id Item id to find.
	 * @return int|null Step index (0-based) or null if not found.
	 */
	public function find_step_index_for_item( array $definition, string $plan_item_id ): ?int {
		if ( $plan_item_id === '' ) {
			return null;
		}
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $idx => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( is_array( $item ) && (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $plan_item_id ) {
					return $idx;
				}
			}
		}
		return null;
	}

	/**
	 * Updates a single plan item's status and optionally execution artifact (spec §32.5, §37.6, plan history).
	 *
	 * @param int                       $post_id    Plan post ID.
	 * @param int                       $step_index Step index in steps array.
	 * @param string                    $item_id    Item id to update.
	 * @param string                    $new_status New status (e.g. approved, completed).
	 * @param array<string, mixed>|null $execution_artifact Optional artifact (e.g. post_id, target_post_id) for finalization publish.
	 * @param array<string, mixed>|null $review_decision    Optional deny audit when status is rejected.
	 * @return bool True if item was found and updated; false otherwise.
	 */
	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status, ?array $execution_artifact = null, ?array $review_decision = null ): bool {
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return false;
		}
		$items   = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$updated = false;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $item_id ) {
				$items[ $i ]['status'] = $new_status;
				if ( $execution_artifact !== null ) {
					$items[ $i ]['execution_artifact'] = $execution_artifact;
				}
				if ( $review_decision !== null && $new_status === Build_Plan_Item_Statuses::REJECTED ) {
					$items[ $i ][ Build_Plan_Item_Schema::KEY_REVIEW_DECISION ] = $this->normalize_review_decision( $review_decision );
				}
				$updated = true;
				break;
			}
		}
		if ( ! $updated ) {
			return false;
		}
		$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		return $this->save_plan_definition( $post_id, $definition );
	}

	/**
	 * @param array<string, mixed> $raw From {@see \AIOPageBuilder\Domain\BuildPlan\Build_Plan_Review_Decision_Meta::for_rejection()}.
	 * @return array<string, mixed>
	 */
	private function normalize_review_decision( array $raw ): array {
		$src = isset( $raw['source'] ) && is_string( $raw['source'] ) ? $raw['source'] : 'row';
		if ( ! in_array( $src, array( 'row', 'bulk_all', 'bulk_selected' ), true ) ) {
			$src = 'row';
		}
		$at = isset( $raw['decided_at'] ) && is_string( $raw['decided_at'] ) ? substr( $raw['decided_at'], 0, 40 ) : gmdate( 'c' );

		return array(
			'decision'      => 'rejected',
			'decided_at'    => $at,
			'actor_user_id' => isset( $raw['actor_user_id'] ) ? max( 0, (int) $raw['actor_user_id'] ) : 0,
			'source'        => $src,
		);
	}

	/**
	 * Updates all items in a step that match a status predicate to a new status (e.g. bulk approve/deny).
	 *
	 * @param int    $post_id     Plan post ID.
	 * @param int    $step_index  Step index.
	 * @param string $from_status Only change items with this status (e.g. pending).
	 * @param string $to_status   New status to set.
	 * @return int Number of items updated.
	 */
	public function update_plan_step_items_by_status( int $post_id, int $step_index, string $from_status, string $to_status ): int {
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}
		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$count = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) === $from_status ) {
				$items[ $i ]['status'] = $to_status;
				++$count;
			}
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->save_plan_definition( $post_id, $definition );
		}
		return $count;
	}

	/**
	 * Updates status for specific item IDs in a step (e.g. Build Selected for Step 2).
	 * Only items with from_status are updated. Ids not found or wrong status are skipped.
	 *
	 * @param int    $post_id     Plan post ID.
	 * @param int    $step_index  Step index.
	 * @param array  $item_ids    Item ids to update (e.g. selected).
	 * @param string $new_status  New status to set.
	 * @param string $from_status Only change items with this status (default pending).
	 * @return int Number of items updated.
	 */
	public function update_plan_items_by_ids( int $post_id, int $step_index, array $item_ids, string $new_status, string $from_status = 'pending' ): int {
		if ( empty( $item_ids ) ) {
			return 0;
		}
		$definition = $this->get_plan_definition( $post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}
		$items  = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$id_set = array_flip( array_map( 'strval', $item_ids ) );
		$count  = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = (string) ( $item['item_id'] ?? '' );
			if ( $id === '' || ! isset( $id_set[ $id ] ) ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) !== $from_status ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ $step_index ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->save_plan_definition( $post_id, $definition );
		}
		return $count;
	}

	/** @inheritdoc */
	protected function get_meta( int $post_id ): array {
		$base                    = parent::get_meta( $post_id );
		$base['plan_definition'] = $this->get_plan_definition( $post_id );
		return $base;
	}

	/** @inheritdoc */
	protected function post_to_record( $post, array $meta ): array {
		$base              = parent::post_to_record( $post, $meta );
		$p                 = is_array( $post ) ? $post : (array) $post;
		$base['post_date'] = (string) ( $p['post_date'] ?? '' );
		if ( ! empty( $meta['plan_definition'] ) && is_array( $meta['plan_definition'] ) ) {
			$base = array_merge( $base, $meta['plan_definition'] );
		}
		return $base;
	}

	/** @inheritdoc */
	public function save( array $data ): int {
		$definition        = isset( $data['plan_definition'] ) && is_array( $data['plan_definition'] ) ? $data['plan_definition'] : null;
		$data_for_parent   = $data;
		$requested_post_id = (int) ( $data['id'] ?? 0 );
		if ( $definition !== null ) {
			$data_for_parent = array(
				'id'           => $data['id'] ?? 0,
				'internal_key' => $definition['plan_id'] ?? $data['internal_key'] ?? '',
				'post_title'   => $definition['plan_title'] ?? $data['post_title'] ?? 'Build Plan',
				'status'       => $definition['status'] ?? $data['status'] ?? 'pending_review',
			);
		}
		$id = parent::save( $data_for_parent );
		if ( $id > 0 && $definition !== null ) {
			if ( ! $this->save_plan_definition( $id, $definition ) ) {
				if ( $requested_post_id === 0 ) {
					\wp_delete_post( $id, true );
				}
				return 0;
			}
		}
		return $id;
	}
}
