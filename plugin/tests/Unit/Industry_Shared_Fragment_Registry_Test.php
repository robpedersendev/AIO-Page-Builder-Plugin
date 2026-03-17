<?php
/**
 * Unit tests for Industry_Shared_Fragment_Registry (Prompt 475). load, get, get_all, get_by_type, validation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Shared_Fragment_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Shared_Fragment_Registry.php';

final class Industry_Shared_Fragment_Registry_Test extends TestCase {

	public function test_load_empty_returns_empty_lookups(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array() );
		$this->assertNull( $registry->get( 'any' ) );
		$this->assertSame( array(), $registry->get_all() );
		$this->assertSame( array(), $registry->get_by_type( Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES ) );
	}

	public function test_load_valid_fragment_then_get(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'cta_contact_primary',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay', 'cta_guidance' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Prefer a single primary contact CTA above the fold.',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		$frag = $registry->get( 'cta_contact_primary' );
		$this->assertIsArray( $frag );
		$this->assertSame( 'cta_contact_primary', $frag[ Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY ] );
		$this->assertSame( 'Prefer a single primary contact CTA above the fold.', $frag[ Industry_Shared_Fragment_Registry::FIELD_CONTENT ] );
		$this->assertCount( 1, $registry->get_all() );
		$this->assertCount( 1, $registry->get_by_type( Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES ) );
		$this->assertCount( 0, $registry->get_by_type( Industry_Shared_Fragment_Registry::TYPE_CAUTION_SNIPPET ) );
	}

	public function test_load_skips_invalid_key(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'Invalid Key!',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Text',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_skips_invalid_type(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'valid_key',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => 'invalid_type',
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Text',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_skips_empty_allowed_consumers(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'valid_key',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array(),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Text',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_builtin_definitions_loads_seeded_fragments(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$builtin = Industry_Shared_Fragment_Registry::get_builtin_definitions();
		$registry->load( $builtin );
		// Seed set has at least CTA, SEO, caution, and guidance fragments.
		$this->assertGreaterThanOrEqual( 5, count( $registry->get_all() ), 'Built-in seed set should have multiple fragments' );
		$cta = $registry->get_by_type( Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES );
		$this->assertGreaterThanOrEqual( 1, count( $cta ), 'At least one CTA fragment in seed' );
	}

	public function test_duplicate_key_first_wins(): void {
		$registry = new Industry_Shared_Fragment_Registry();
		$registry->load( array(
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'dup',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'First',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
			array(
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_KEY      => 'dup',
				Industry_Shared_Fragment_Registry::FIELD_FRAGMENT_TYPE     => Industry_Shared_Fragment_Registry::TYPE_CTA_NOTES,
				Industry_Shared_Fragment_Registry::FIELD_ALLOWED_CONSUMERS => array( 'section_helper_overlay' ),
				Industry_Shared_Fragment_Registry::FIELD_CONTENT          => 'Second',
				Industry_Shared_Fragment_Registry::FIELD_STATUS           => Industry_Shared_Fragment_Registry::STATUS_ACTIVE,
			),
		) );
		$frag = $registry->get( 'dup' );
		$this->assertSame( 'First', $frag[ Industry_Shared_Fragment_Registry::FIELD_CONTENT ] );
	}
}
