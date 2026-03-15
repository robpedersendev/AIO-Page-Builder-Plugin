<?php
/**
 * Unit tests for Industry_Section_Preview_Resolver (Prompt 384).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\ViewModels\Sections\Industry_Section_Preview_View_Model;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Helper_Doc_Composer;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Preview_Resolver;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Resolver;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );
require_once __DIR__ . '/../bootstrap-sanitize.php';

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Admin/ViewModels/Sections/Industry_Section_Preview_View_Model.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Preview_Resolver.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Resolver.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Loader.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Helper_Doc_Composer.php';

final class Industry_Section_Preview_Resolver_Test extends TestCase {

	public function test_resolve_returns_empty_view_model_when_profile_repository_null(): void {
		$doc_registry = new Documentation_Registry( new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Loader( dirname( __DIR__, 2 ) . '/src/Domain/Registries/Docs' ) );
		$overlay      = new Industry_Section_Helper_Overlay_Registry();
		$composer     = new Industry_Helper_Doc_Composer( $doc_registry, $overlay );
		$resolver     = new Industry_Section_Preview_Resolver( null, null, new Industry_Section_Recommendation_Resolver(), $composer, null );
		$vm           = $resolver->resolve( 'hero_conv_02', array( 'internal_key' => 'hero_conv_02', 'section_purpose_family' => 'hero' ), array() );
		$this->assertInstanceOf( Industry_Section_Preview_View_Model::class, $vm );
		$this->assertFalse( $vm->has_industry() );
		$this->assertSame( '', $vm->get_primary_industry_key() );
		$this->assertSame( Industry_Section_Recommendation_Resolver::FIT_NEUTRAL, $vm->get_recommendation_fit() );
	}

}
