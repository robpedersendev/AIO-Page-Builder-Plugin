<?php
/**
 * Unit tests for Page_Instantiation_Payload_Builder: create-ready and update-ready payloads from assembly (spec §17.7, §19, Prompt 046).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Payload_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Page/Page_Instantiation_Payload_Builder.php';

final class Page_Instantiation_Payload_Builder_Test extends TestCase {

	private function assembly_result( string $source_type, string $source_key, string $block_content = '<!-- wp:html --><div>X</div><!-- /wp:html -->', array $ordered_sections = array() ): Page_Block_Assembly_Result {
		return new Page_Block_Assembly_Result(
			$source_type,
			$source_key,
			$ordered_sections,
			$block_content,
			array(),
			array( 'durable_native_blocks' ),
			array()
		);
	}

	public function test_build_create_payload_from_page_template_source(): void {
		$assembly = $this->assembly_result(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_landing',
			'<!-- wp:html --><div>Content</div><!-- /wp:html -->',
			array( array( 'section_key' => 'st01_hero' ), array( 'section_key' => 'st02_cta' ) )
		);
		$builder = new Page_Instantiation_Payload_Builder();

		$payload = $builder->build_create_payload( $assembly, 'Landing Page', array( 'page_slug_candidate' => 'landing', 'post_status_candidate' => 'draft' ) );

		$this->assertSame( Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE, $payload['source_type'] );
		$this->assertSame( 'tpl_landing', $payload['source_key'] );
		$this->assertSame( 'Landing Page', $payload['page_title'] );
		$this->assertSame( 'landing', $payload['page_slug_candidate'] );
		$this->assertSame( 'draft', $payload['post_status_candidate'] );
		$this->assertSame( '<!-- wp:html --><div>Content</div><!-- /wp:html -->', $payload['post_content'] );
		$this->assertArrayHasKey( 'provenance_meta', $payload );
		$this->assertSame( 'page_template', $payload['provenance_meta'][ Page_Instantiation_Payload_Builder::META_SOURCE_TYPE ] );
		$this->assertSame( 'tpl_landing', $payload['provenance_meta'][ Page_Instantiation_Payload_Builder::META_SOURCE_KEY ] );
		$this->assertArrayHasKey( 'assignment_updates', $payload );
		$this->assertSame( array( 'st01_hero', 'st02_cta' ), $payload['assignment_updates']['section_keys'] );
		$this->assertSame( array( 'durable_native_blocks' ), $payload['survivability_notes'] );
		$this->assertArrayNotHasKey( 'target_post_id', $payload );
	}

	public function test_build_create_payload_from_composition_source(): void {
		$assembly = $this->assembly_result( Page_Block_Assembly_Result::SOURCE_TYPE_COMPOSITION, 'comp_abc-123' );
		$builder  = new Page_Instantiation_Payload_Builder();

		$payload = $builder->build_create_payload( $assembly, 'Composed Page', array( 'source_version' => '1.0' ) );

		$this->assertSame( Page_Block_Assembly_Result::SOURCE_TYPE_COMPOSITION, $payload['source_type'] );
		$this->assertSame( 'comp_abc-123', $payload['source_key'] );
		$this->assertSame( '1.0', $payload['source_version'] );
		$this->assertSame( '1.0', $payload['provenance_meta'][ Page_Instantiation_Payload_Builder::META_SOURCE_VERSION ] );
	}

	public function test_build_update_payload_includes_target_post_id(): void {
		$assembly = $this->assembly_result( Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE, 'tpl_one' );
		$builder  = new Page_Instantiation_Payload_Builder();

		$payload = $builder->build_update_payload( $assembly, 1001, array( 'page_title' => 'Updated Title' ) );

		$this->assertSame( 1001, $payload['target_post_id'] );
		$this->assertSame( 'Updated Title', $payload['page_title'] );
		$this->assertSame( $assembly->get_block_content(), $payload['post_content'] );
	}
}
