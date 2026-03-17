<?php
/**
 * Unit tests for Industry_Shared_Fragment_Resolver (Prompt 475). resolve(), consumer scope, safe failure.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Resolver;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Shared_Fragment_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Shared_Fragment_Resolver.php';

final class Industry_Shared_Fragment_Resolver_Test extends TestCase {

	private function registry_with_one_fragment(): Industry_Shared_Fragment_Registry {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'cta_primary',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'cta_guidance' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Use one primary CTA.',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		return $registry;
	}

	public function test_resolve_returns_content_when_consumer_allowed(): void {
		$registry = $this->registry_with_one_fragment();
		$resolver = new Industry_Shared_Fragment_Resolver( $registry );
		$this->assertSame( 'Use one primary CTA.', $resolver->resolve( 'cta_primary', 'section_helper_overlay' ) );
		$this->assertSame( 'Use one primary CTA.', $resolver->resolve( 'cta_primary', 'cta_guidance' ) );
	}

	public function test_resolve_returns_null_when_consumer_not_allowed(): void {
		$registry = $this->registry_with_one_fragment();
		$resolver = new Industry_Shared_Fragment_Resolver( $registry );
		$this->assertNull( $resolver->resolve( 'cta_primary', 'seo_guidance' ) );
		$this->assertNull( $resolver->resolve( 'cta_primary', 'compliance_caution' ) );
	}

	public function test_resolve_returns_null_when_key_missing(): void {
		$registry = $this->registry_with_one_fragment();
		$resolver = new Industry_Shared_Fragment_Resolver( $registry );
		$this->assertNull( $resolver->resolve( 'nonexistent', 'section_helper_overlay' ) );
	}

	public function test_resolve_returns_null_when_status_not_active(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'draft_frag',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Draft content',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => 'draft',
			),
		) );
		$resolver = new Industry_Shared_Fragment_Resolver( $registry );
		$this->assertNull( $resolver->resolve( 'draft_frag', 'section_helper_overlay' ) );
	}

	public function test_resolve_returns_null_for_empty_key(): void {
		$registry = $this->registry_with_one_fragment();
		$resolver = new Industry_Shared_Fragment_Resolver( $registry );
		$this->assertNull( $resolver->resolve( '', 'section_helper_overlay' ) );
	}
}
