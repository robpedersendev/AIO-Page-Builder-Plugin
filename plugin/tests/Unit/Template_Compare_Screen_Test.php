<?php
/**
 * Unit tests for Template_Compare_Screen (Prompt 218).
 *
 * Covers site-scoped compare list meta key under multisite: get_compare_meta_key()
 * returns distinct keys per blog when $blog_id is provided, and base key when null
 * (single-site or current context).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Admin/Screens/Templates/Template_Compare_Screen.php';

final class Template_Compare_Screen_Test extends TestCase {

	public function test_get_compare_meta_key_section_with_blog_id_returns_site_scoped_key(): void {
		$this->assertSame(
			'_aio_compare_section_templates_blog_2',
			Template_Compare_Screen::get_compare_meta_key( 'section', 2 )
		);
	}

	public function test_get_compare_meta_key_page_with_blog_id_returns_site_scoped_key(): void {
		$this->assertSame(
			'_aio_compare_page_templates_blog_2',
			Template_Compare_Screen::get_compare_meta_key( 'page', 2 )
		);
	}

	public function test_get_compare_meta_key_different_blog_ids_return_different_keys(): void {
		$key_2 = Template_Compare_Screen::get_compare_meta_key( 'section', 2 );
		$key_3 = Template_Compare_Screen::get_compare_meta_key( 'section', 3 );
		$this->assertNotSame( $key_2, $key_3 );
		$this->assertStringEndsWith( '_blog_2', $key_2 );
		$this->assertStringEndsWith( '_blog_3', $key_3 );
	}

	public function test_get_compare_meta_key_section_null_returns_base_key_single_site(): void {
		$this->assertSame(
			Template_Compare_Screen::USER_META_SECTION,
			Template_Compare_Screen::get_compare_meta_key( 'section', null )
		);
	}

	public function test_get_compare_meta_key_page_null_returns_base_key_single_site(): void {
		$this->assertSame(
			Template_Compare_Screen::USER_META_PAGE,
			Template_Compare_Screen::get_compare_meta_key( 'page', null )
		);
	}
}
