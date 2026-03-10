<?php
/**
 * Unit tests for Assignment_Types: stable map_type validation (spec §11.7, Prompt 020).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Assignments\Assignment_Types;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Assignments/Assignment_Types.php';

final class Assignment_Types_Test extends TestCase {

	public function test_all_returns_five_types(): void {
		$all = Assignment_Types::all();
		$this->assertCount( 5, $all );
		$this->assertContains( Assignment_Types::PAGE_FIELD_GROUP, $all );
		$this->assertContains( Assignment_Types::PAGE_TEMPLATE, $all );
		$this->assertContains( Assignment_Types::PLAN_OBJECT, $all );
		$this->assertContains( Assignment_Types::TEMPLATE_DEPENDENCY, $all );
		$this->assertContains( Assignment_Types::COMPOSITION_SECTION, $all );
	}

	public function test_constants_match_manifest(): void {
		$this->assertSame( 'page_field_group', Assignment_Types::PAGE_FIELD_GROUP );
		$this->assertSame( 'page_template', Assignment_Types::PAGE_TEMPLATE );
		$this->assertSame( 'plan_object', Assignment_Types::PLAN_OBJECT );
		$this->assertSame( 'template_dependency', Assignment_Types::TEMPLATE_DEPENDENCY );
		$this->assertSame( 'composition_section', Assignment_Types::COMPOSITION_SECTION );
	}

	public function test_is_valid_accepts_all_constants(): void {
		$this->assertTrue( Assignment_Types::is_valid( Assignment_Types::PAGE_FIELD_GROUP ) );
		$this->assertTrue( Assignment_Types::is_valid( Assignment_Types::PAGE_TEMPLATE ) );
		$this->assertTrue( Assignment_Types::is_valid( Assignment_Types::PLAN_OBJECT ) );
		$this->assertTrue( Assignment_Types::is_valid( Assignment_Types::TEMPLATE_DEPENDENCY ) );
		$this->assertTrue( Assignment_Types::is_valid( Assignment_Types::COMPOSITION_SECTION ) );
	}

	public function test_is_valid_rejects_invalid(): void {
		$this->assertFalse( Assignment_Types::is_valid( 'page-to-template' ) );
		$this->assertFalse( Assignment_Types::is_valid( 'unknown' ) );
		$this->assertFalse( Assignment_Types::is_valid( '' ) );
	}
}
