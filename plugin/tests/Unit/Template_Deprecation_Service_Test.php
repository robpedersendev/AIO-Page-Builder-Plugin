<?php
/**
 * Unit tests for Template_Deprecation_Service: deprecation summary, replacement refs, decision-log entry, changelog snippet (Prompt 189, spec §12.15, §13.13, §61.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Deprecation_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Versioning/Template_Deprecation_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Template_Deprecation_Service_Test extends TestCase {

	private Template_Deprecation_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$section_repo = new Section_Template_Repository();
		$page_repo    = new Page_Template_Repository();
		$registry_dep = new Registry_Deprecation_Service( $section_repo, $page_repo );
		$this->service = new Template_Deprecation_Service( $registry_dep );
	}

	public function test_get_deprecation_summary_section_active(): void {
		$def = array(
			Section_Schema::FIELD_STATUS => 'active',
			'deprecation'                => array(),
		);
		$summary = $this->service->get_deprecation_summary( $def, 'section' );
		$this->assertFalse( $summary['is_deprecated'] );
		$this->assertSame( '', $summary['reason'] );
		$this->assertSame( array(), $summary['replacement_keys'] );
	}

	public function test_get_deprecation_summary_section_deprecated_with_replacement(): void {
		$def = array(
			Section_Schema::FIELD_STATUS => 'deprecated',
			'deprecation'                => array(
				'deprecated'              => true,
				'reason'                   => 'Superseded by new variant',
				'deprecated_at'            => '2025-03-13T12:00:00Z',
				'replacement_section_key'  => 'st01_hero_intro',
			),
			'replacement_section_suggestions' => array( 'st01_hero_intro' ),
		);
		$summary = $this->service->get_deprecation_summary( $def, 'section' );
		$this->assertTrue( $summary['is_deprecated'] );
		$this->assertSame( 'Superseded by new variant', $summary['reason'] );
		$this->assertSame( array( 'st01_hero_intro' ), $summary['replacement_keys'] );
		$this->assertSame( '2025-03-13T12:00:00Z', $summary['deprecated_at'] );
	}

	public function test_get_deprecation_summary_page_deprecated(): void {
		$def = array(
			Page_Template_Schema::FIELD_STATUS => 'deprecated',
			'deprecation'                      => array( 'reason' => 'Use pt_home_02', 'replacement_template_key' => 'pt_home_02' ),
			'replacement_template_refs'        => array( 'pt_home_02' ),
		);
		$summary = $this->service->get_deprecation_summary( $def, 'page' );
		$this->assertTrue( $summary['is_deprecated'] );
		$this->assertSame( 'Use pt_home_02', $summary['reason'] );
		$this->assertSame( array( 'pt_home_02' ), $summary['replacement_keys'] );
	}

	public function test_build_decision_log_entry_has_required_fields(): void {
		$entry = $this->service->build_decision_log_entry(
			'DL-001',
			'Cap hero family at 12',
			'Prevents dominance.',
			'Technical Lead',
			'approved',
			'1',
			array( 'st01_hero' ),
			array( 'pt_home_01' ),
			'Allow unbounded growth.'
		);
		$this->assertSame( 'DL-001', $entry['decision_id'] );
		$this->assertArrayHasKey( 'date', $entry );
		$this->assertSame( 'Technical Lead', $entry['owner'] );
		$this->assertSame( 'approved', $entry['status'] );
		$this->assertSame( 'Cap hero family at 12', $entry['summary'] );
		$this->assertSame( 'Prevents dominance.', $entry['rationale'] );
		$this->assertSame( array( 'st01_hero' ), $entry['impacted_section_keys'] );
		$this->assertSame( array( 'pt_home_01' ), $entry['impacted_template_keys'] );
		$this->assertSame( '1', $entry['effective_version'] );
	}

	public function test_build_changelog_snippet_for_deprecation_section_with_replacement(): void {
		$snippet = $this->service->build_changelog_snippet_for_deprecation(
			'st_old_hero',
			'section',
			'Superseded by layout variant.',
			array( 'st01_hero_intro' )
		);
		$this->assertStringContainsString( 'Section template', $snippet );
		$this->assertStringContainsString( 'st_old_hero', $snippet );
		$this->assertStringContainsString( 'Superseded by layout variant', $snippet );
		$this->assertStringContainsString( 'st01_hero_intro', $snippet );
	}

	public function test_build_changelog_snippet_for_deprecation_page_no_replacement(): void {
		$snippet = $this->service->build_changelog_snippet_for_deprecation( 'pt_legacy', 'page', 'Retired.', array() );
		$this->assertStringContainsString( 'Page template', $snippet );
		$this->assertStringContainsString( 'pt_legacy', $snippet );
		$this->assertStringContainsString( 'No recommended replacement', $snippet );
	}

	public function test_get_section_deprecation_block_includes_status(): void {
		$block = $this->service->get_section_deprecation_block( 'Legacy', 'st01_hero' );
		$this->assertSame( 'deprecated', $block['status'] );
		$this->assertSame( array( 'st01_hero' ), $block['replacement_section_suggestions'] );
		$this->assertSame( 'Legacy', $block['reason'] );
	}

	public function test_validate_section_deprecation_empty_reason_invalid(): void {
		$result = $this->service->validate_section_deprecation( 999, '', '' );
		$this->assertFalse( $result->valid );
		$this->assertStringContainsString( 'reason', strtolower( implode( ' ', $result->errors ) ) );
	}

	/**
	 * Deprecation continuity: definition with deprecation metadata yields correct summary after upgrade (Prompt 202).
	 */
	public function test_deprecation_continuity_section_definition_with_deprecation_metadata(): void {
		$def = array(
			Section_Schema::FIELD_STATUS => 'deprecated',
			'deprecation'                => array(
				Deprecation_Metadata::IS_DEPRECATED   => true,
				Deprecation_Metadata::DEPRECATED_REASON => 'Replaced by st02_hero',
				Deprecation_Metadata::DEPRECATED_AT  => '2025-03-01T00:00:00Z',
			),
			'replacement_section_suggestions' => array( 'st02_hero' ),
		);
		$summary = $this->service->get_deprecation_summary( $def, 'section' );
		$this->assertTrue( $summary['is_deprecated'] );
		$this->assertSame( 'Replaced by st02_hero', $summary['reason'] );
		$this->assertSame( array( 'st02_hero' ), $summary['replacement_keys'] );
		$this->assertSame( '2025-03-01T00:00:00Z', $summary['deprecated_at'] );
	}
}
