<?php
/**
 * Admin UI markers for live preview toolbar (string-level checks).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Admin;

use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use PHPUnit\Framework\TestCase;

final class Template_Live_Preview_Admin_UI_Test extends TestCase {

	public function test_detail_screen_slugs_for_asset_scoping(): void {
		$this->assertSame( 'aio-page-builder-page-template-detail', Page_Template_Detail_Screen::SLUG );
		$this->assertSame( 'aio-page-builder-section-template-detail', Section_Template_Detail_Screen::SLUG );
	}
}
