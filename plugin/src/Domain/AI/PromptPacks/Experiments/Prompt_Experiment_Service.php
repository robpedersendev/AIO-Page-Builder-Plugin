<?php
/**
 * Controlled experiment framework for prompt packs and provider/model combinations (spec §26, §58.3, §59.8, Prompt 121).
 * Experiment definitions, experiment-run labeling, comparison summaries. Isolated from production defaults.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Experiments;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Runs\AI_Run_Service;
use AIOPageBuilder\Domain\Storage\Repositories\AI_Run_Repository;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Experiment definitions (stored in option); labels runs via metadata; builds comparison summaries.
 * Run metadata keys: is_experiment, experiment_id, experiment_variant_id, experiment_variant_label.
 */
final class Prompt_Experiment_Service {

	/** Option key for experiment definitions (prompt_experiment_definition list). */
	public const METADATA_IS_EXPERIMENT = 'is_experiment';
	public const METADATA_EXPERIMENT_ID = 'experiment_id';
	public const METADATA_VARIANT_ID    = 'experiment_variant_id';
	public const METADATA_VARIANT_LABEL = 'experiment_variant_label';

	private const OPTION_KEY      = Option_Names::PROMPT_EXPERIMENTS;
	private const DEFINITIONS_KEY = 'definitions';

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var AI_Run_Service */
	private AI_Run_Service $run_service;

	/** @var AI_Run_Repository */
	private AI_Run_Repository $run_repository;

	public function __construct( Settings_Service $settings, AI_Run_Service $run_service, AI_Run_Repository $run_repository ) {
		$this->settings       = $settings;
		$this->run_service    = $run_service;
		$this->run_repository = $run_repository;
	}

	/**
	 * Lists all experiment definitions (prompt_experiment_definition shape).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list_definitions(): array {
		$data = $this->get_experiments_option();
		$defs = $data[ self::DEFINITIONS_KEY ] ?? array();
		return is_array( $defs ) ? $defs : array();
	}

	/**
	 * Gets one definition by id.
	 *
	 * @param string $experiment_id Experiment id.
	 * @return array<string, mixed>|null
	 */
	public function get_definition( string $experiment_id ): ?array {
		foreach ( $this->list_definitions() as $def ) {
			if ( ( $def['id'] ?? '' ) === $experiment_id ) {
				return $def;
			}
		}
		return null;
	}

	/**
	 * Validates and saves an experiment definition. Shape: id, name, description?, variants (experiment_variant[]).
	 *
	 * @param array<string, mixed> $definition prompt_experiment_definition.
	 * @return array{ok: bool, message: string}
	 */
	public function save_definition( array $definition ): array {
		$err = $this->validate_definition( $definition );
		if ( $err !== '' ) {
			return array(
				'ok'      => false,
				'message' => $err,
			);
		}
		$defs = $this->list_definitions();
		$id   = isset( $definition['id'] ) && is_string( $definition['id'] ) ? trim( $definition['id'] ) : '';
		if ( $id === '' ) {
			$id               = 'exp-' . uniqid( '', true );
			$definition['id'] = $id;
		}
		if ( ! isset( $definition['created_at'] ) || $definition['created_at'] === '' ) {
			$definition['created_at'] = gmdate( 'Y-m-d\TH:i:s\Z' );
		}
		$existing_index = null;
		foreach ( $defs as $i => $d ) {
			if ( ( $d['id'] ?? '' ) === $id ) {
				$existing_index = $i;
				break;
			}
		}
		if ( $existing_index !== null ) {
			$defs[ $existing_index ] = $definition;
		} else {
			$defs[] = $definition;
		}
		$this->set_experiments_option( array( self::DEFINITIONS_KEY => $defs ) );
		return array(
			'ok'      => true,
			'message' => __( 'Experiment saved.', 'aio-page-builder' ),
		);
	}

	/**
	 * Deletes an experiment definition by id.
	 *
	 * @param string $experiment_id Experiment id.
	 * @return bool True if removed.
	 */
	public function delete_definition( string $experiment_id ): bool {
		$defs   = $this->list_definitions();
		$before = count( $defs );
		$defs   = array_values(
			array_filter(
				$defs,
				function ( $d ) use ( $experiment_id ) {
					return ( $d['id'] ?? '' ) !== $experiment_id;
				}
			)
		);
		if ( count( $defs ) === $before ) {
			return false;
		}
		$this->set_experiments_option( array( self::DEFINITIONS_KEY => $defs ) );
		return true;
	}

	/**
	 * Validates experiment definition shape. Returns empty string if valid, else error message.
	 *
	 * @param array<string, mixed> $definition
	 * @return string
	 */
	public function validate_definition( array $definition ): string {
		if ( empty( $definition['name'] ) || ! is_string( $definition['name'] ) ) {
			return __( 'Experiment name is required.', 'aio-page-builder' );
		}
		$variants = $definition['variants'] ?? array();
		if ( ! is_array( $variants ) || count( $variants ) === 0 ) {
			return __( 'At least one variant is required.', 'aio-page-builder' );
		}
		foreach ( $variants as $i => $v ) {
			if ( ! is_array( $v ) ) {
				return __( 'Variant must be an object.', 'aio-page-builder' );
			}
			$ref = $v['prompt_pack_ref'] ?? null;
			if ( ! is_array( $ref ) || empty( $ref['internal_key'] ) || empty( $ref['version'] ) ) {
				return __( 'Variant must have prompt_pack_ref with internal_key and version.', 'aio-page-builder' );
			}
			if ( empty( $v['provider_id'] ) || ! is_string( $v['provider_id'] ) ) {
				return __( 'Variant must have provider_id.', 'aio-page-builder' );
			}
			$vid = $v['variant_id'] ?? '';
			if ( $vid === '' && isset( $v['label'] ) ) {
				$vid = (string) $i;
			}
			if ( $vid === '' ) {
				return __( 'Variant must have variant_id or label.', 'aio-page-builder' );
			}
		}
		return '';
	}

	/**
	 * Creates an AI run with experiment metadata (labels only). Call after a run is produced for an experiment variant.
	 *
	 * @param string               $experiment_id   Experiment definition id.
	 * @param string               $variant_id     Variant id.
	 * @param string               $variant_label  UI-safe variant label.
	 * @param string               $run_id         Run id.
	 * @param array<string, mixed> $metadata       Run metadata (actor, provider_id, model_used, etc.).
	 * @param string               $status         completed | failed_validation | failed.
	 * @param array<string, mixed> $artifacts      Artifact category => payload.
	 * @return Experiment_Result
	 */
	public function record_experiment_run(
		string $experiment_id,
		string $variant_id,
		string $variant_label,
		string $run_id,
		array $metadata,
		string $status,
		array $artifacts
	): Experiment_Result {
		$metadata[ self::METADATA_IS_EXPERIMENT ] = true;
		$metadata[ self::METADATA_EXPERIMENT_ID ] = $experiment_id;
		$metadata[ self::METADATA_VARIANT_ID ]    = $variant_id;
		$metadata[ self::METADATA_VARIANT_LABEL ] = $variant_label;
		$post_id                                  = $this->run_service->create_run( $run_id, $metadata, $status, $artifacts );
		$message                                  = $post_id > 0
			? __( 'Experiment run recorded. View in AI Runs.', 'aio-page-builder' )
			: __( 'Failed to save experiment run.', 'aio-page-builder' );
		return new Experiment_Result( $run_id, $post_id, $status, $experiment_id, $variant_id, $variant_label, $message );
	}

	/**
	 * Lists runs that belong to an experiment (filtered by metadata).
	 *
	 * @param string $experiment_id Experiment id.
	 * @param int    $limit         Max runs to scan (recent first).
	 * @return list<array<string, mixed>>
	 */
	public function get_experiment_runs( string $experiment_id, int $limit = 200 ): array {
		$all = $this->run_repository->list_recent( $limit, 0 );
		$out = array();
		foreach ( $all as $run ) {
			$meta = $run['run_metadata'] ?? array();
			if ( empty( $meta[ self::METADATA_IS_EXPERIMENT ] ) || ( $meta[ self::METADATA_EXPERIMENT_ID ] ?? '' ) !== $experiment_id ) {
				continue;
			}
			$out[] = $run;
		}
		return $out;
	}

	/**
	 * Builds comparison summary across variants (experiment_run_result counts by variant and status).
	 *
	 * @param string $experiment_id Experiment id.
	 * @return array{experiment_id: string, variants: array<string, array{variant_id: string, variant_label: string, runs: array<string, int>, total: int}>} comparison_summary
	 */
	public function get_comparison_summary( string $experiment_id ): array {
		$runs       = $this->get_experiment_runs( $experiment_id );
		$by_variant = array();
		foreach ( $runs as $run ) {
			$meta   = $run['run_metadata'] ?? array();
			$vid    = (string) ( $meta[ self::METADATA_VARIANT_ID ] ?? '' );
			$label  = (string) ( $meta[ self::METADATA_VARIANT_LABEL ] ?? $vid );
			$status = (string) ( $run['status'] ?? 'unknown' );
			if ( $vid === '' ) {
				continue;
			}
			if ( ! isset( $by_variant[ $vid ] ) ) {
				$by_variant[ $vid ] = array(
					'variant_id'    => $vid,
					'variant_label' => $label,
					'runs'          => array(),
					'total'         => 0,
				);
			}
			$by_variant[ $vid ]['runs'][ $status ] = ( $by_variant[ $vid ]['runs'][ $status ] ?? 0 ) + 1;
			++$by_variant[ $vid ]['total'];
		}
		return array(
			'experiment_id' => $experiment_id,
			'variants'      => $by_variant,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_experiments_option(): array {
		if ( ! Option_Names::is_valid( self::OPTION_KEY ) ) {
			return array( self::DEFINITIONS_KEY => array() );
		}
		$raw = $this->settings->get( self::OPTION_KEY );
		return is_array( $raw ) ? $raw : array( self::DEFINITIONS_KEY => array() );
	}

	/**
	 * @param array<string, mixed> $data
	 * @return void
	 */
	private function set_experiments_option( array $data ): void {
		if ( Option_Names::is_valid( self::OPTION_KEY ) ) {
			$this->settings->set( self::OPTION_KEY, $data );
		}
	}
}
