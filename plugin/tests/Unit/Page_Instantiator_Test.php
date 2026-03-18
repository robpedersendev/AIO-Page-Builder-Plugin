<?php
/**
 * Unit tests for Page_Instantiator: create_page, update_page, validation, provenance (spec §17.7, §19, Prompt 046).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Payload_Builder;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Result;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Page/Page_Instantiation_Payload_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Page/Page_Instantiation_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Page/Page_Instantiator.php';

final class Page_Instantiator_Test extends TestCase {

	private function valid_create_payload(): array {
		return array(
			'source_type'           => 'page_template',
			'source_key'            => 'tpl_landing',
			'source_version'        => '',
			'page_title'            => 'Test Landing',
			'page_slug_candidate'   => 'test-landing',
			'post_content'          => '<!-- wp:html --><div class="aio-s-hero">Content</div><!-- /wp:html -->',
			'post_status_candidate' => 'draft',
			'provenance_meta'       => array(
				'_aio_build_source_type'    => 'page_template',
				'_aio_build_source_key'     => 'tpl_landing',
				'_aio_build_source_version' => '',
			),
			'assignment_updates'    => array( 'section_keys' => array( 'st01_hero' ) ),
			'survivability_notes'   => array( 'durable_native_blocks' ),
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
	}

	public function test_create_page_success_stores_provenance(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 42;
		$builder                               = new Page_Instantiation_Payload_Builder();
		$instantiator                          = new Page_Instantiator( $builder );
		$payload                               = $this->valid_create_payload();

		$result = $instantiator->create_page( $payload );

		$this->assertInstanceOf( Page_Instantiation_Result::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 42, $result->get_post_id() );
		$this->assertEmpty( $result->get_errors() );
		$this->assertArrayHasKey( '42', $GLOBALS['_aio_post_meta'] );
		$this->assertSame( 'page_template', $GLOBALS['_aio_post_meta']['42']['_aio_build_source_type'] );
		$this->assertSame( 'tpl_landing', $GLOBALS['_aio_post_meta']['42']['_aio_build_source_key'] );
	}

	public function test_create_page_invalid_payload_returns_failure(): void {
		$builder               = new Page_Instantiation_Payload_Builder();
		$instantiator          = new Page_Instantiator( $builder );
		$payload               = $this->valid_create_payload();
		$payload['page_title'] = '';

		$result = $instantiator->create_page( $payload );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_post_id() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_create_page_missing_post_content_fails_validation(): void {
		$builder      = new Page_Instantiation_Payload_Builder();
		$instantiator = new Page_Instantiator( $builder );
		$payload      = $this->valid_create_payload();
		unset( $payload['post_content'] );

		$result = $instantiator->create_page( $payload );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_post_id() );
	}

	public function test_update_page_success_stores_provenance(): void {
		$GLOBALS['_aio_get_post_return']       = new \WP_Post(
			array(
				'ID'         => 100,
				'post_type'  => 'page',
				'post_title' => 'Old',
			)
		);
		$GLOBALS['_aio_wp_update_post_return'] = 100;
		$builder                               = new Page_Instantiation_Payload_Builder();
		$instantiator                          = new Page_Instantiator( $builder );
		$payload                               = $this->valid_create_payload();
		$payload['target_post_id']             = 100;
		$payload['post_content']               = '<!-- wp:html --><div>Updated</div><!-- /wp:html -->';

		$result = $instantiator->update_page( $payload );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 100, $result->get_post_id() );
		$this->assertSame( 'page_template', $GLOBALS['_aio_post_meta']['100']['_aio_build_source_type'] );
	}

	public function test_update_page_invalid_target_fails(): void {
		$GLOBALS['_aio_get_post_return'] = null;
		$builder                         = new Page_Instantiation_Payload_Builder();
		$instantiator                    = new Page_Instantiator( $builder );
		$payload                         = $this->valid_create_payload();
		$payload['target_post_id']       = 999;

		$result = $instantiator->update_page( $payload );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_post_id() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_update_payload_validation_requires_target_post_id(): void {
		$builder                   = new Page_Instantiation_Payload_Builder();
		$instantiator              = new Page_Instantiator( $builder );
		$payload                   = $this->valid_create_payload();
		$payload['target_post_id'] = 0;

		$result = $instantiator->update_page( $payload );

		$this->assertFalse( $result->is_success() );
	}

	public function test_build_create_payload_via_instantiator_produces_create_ready_shape(): void {
		$assembly     = new Page_Block_Assembly_Result( 'page_template', 'tpl_x', array(), '<!-- wp:html -->x<!-- /wp:html -->', array(), array(), array() );
		$builder      = new Page_Instantiation_Payload_Builder();
		$instantiator = new Page_Instantiator( $builder );

		$payload = $instantiator->build_create_payload( $assembly, 'My Page', array( 'post_status_candidate' => 'publish' ) );

		$this->assertSame( 'My Page', $payload['page_title'] );
		$this->assertSame( 'publish', $payload['post_status_candidate'] );
		$this->assertArrayNotHasKey( 'target_post_id', $payload );
	}

	public function test_result_payload_used_snapshot(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 7;
		$builder                               = new Page_Instantiation_Payload_Builder();
		$instantiator                          = new Page_Instantiator( $builder );
		$payload                               = $this->valid_create_payload();

		$result = $instantiator->create_page( $payload );

		$this->assertSame( $payload, $result->get_payload_used() );
	}
}
