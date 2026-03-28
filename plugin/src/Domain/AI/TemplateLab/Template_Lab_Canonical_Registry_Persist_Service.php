<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

final class Template_Lab_Canonical_Registry_Persist_Service implements Template_Lab_Canonical_Registry_Persist_Port {

	private Composition_Repository $compositions;

	private Page_Template_Repository $page_templates;

	private Section_Template_Repository $section_templates;

	public function __construct(
		Composition_Repository $compositions,
		Page_Template_Repository $page_templates,
		Section_Template_Repository $section_templates
	) {
		$this->compositions      = $compositions;
		$this->page_templates    = $page_templates;
		$this->section_templates = $section_templates;
	}

	/** @inheritdoc */
	public function persist_definition( string $target_kind, array $definition ): array {
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			$key = (string) ( $definition[ Composition_Schema::FIELD_COMPOSITION_ID ] ?? '' );
			$id  = $this->compositions->save_definition( $definition );
			return array( 'internal_key' => $key, 'post_id' => $id );
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			$key = (string) ( $definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$id  = $this->page_templates->save_definition( $definition );
			return array( 'internal_key' => $key, 'post_id' => $id );
		}
		$key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$id  = $this->section_templates->save_definition( $definition );
		return array( 'internal_key' => $key, 'post_id' => $id );
	}
}
