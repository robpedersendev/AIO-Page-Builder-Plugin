<?php
/**
 * Unit tests for Template_Preview_Presenter.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Domain;

use AIOPageBuilder\Domain\Preview\UI\Template_Preview_Presenter;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
defined( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES' ) || define( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES', dirname( __DIR__ ) . '/fixtures/wp-plugin-api-stub.php' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/tests/fixtures/wp-plugin-api-stub.php';
require_once $plugin_root . '/src/Domain/Preview/UI/Template_Preview_Presenter.php';

final class TemplatePreviewPresenterTest extends TestCase {

	public function test_structural_preview_labels_when_no_rendered_html(): void {
		$p = new Template_Preview_Presenter();
		$this->assertSame( 'Structural preview', $p->get_preview_title( false ) );
		$this->assertSame( 'Structural preview', $p->get_preview_aria_label( false ) );
	}

	public function test_preview_labels_when_rendered_html_exists(): void {
		$p = new Template_Preview_Presenter();
		$this->assertSame( 'Preview', $p->get_preview_title( true ) );
		$this->assertSame( 'Rendered preview', $p->get_preview_aria_label( true ) );
	}
}

