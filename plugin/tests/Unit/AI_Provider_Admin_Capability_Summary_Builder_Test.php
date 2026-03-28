<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_Admin_Capability_Summary_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Tests\Support\Fake_AI_Provider_Driver;
use PHPUnit\Framework\TestCase;

final class AI_Provider_Admin_Capability_Summary_Builder_Test extends TestCase {

	public function test_build_rows_reflects_capabilities_and_credential_state(): void {
		$container = new Service_Container();
		$container->register(
			'openai_provider_driver',
			static fn() => new Fake_AI_Provider_Driver( 'openai' )
		);
		$container->register(
			'anthropic_provider_driver',
			static fn() => new Fake_AI_Provider_Driver( 'anthropic' )
		);
		$secrets = $this->createMock( Provider_Secret_Store_Interface::class );
		$secrets->method( 'has_credential' )->willReturnCallback(
			static function ( string $pid ): bool {
				return $pid === 'openai';
			}
		);
		$builder = new AI_Provider_Admin_Capability_Summary_Builder( $container, $secrets );
		$rows    = $builder->build_rows();
		$this->assertCount( 2, $rows );
		$by_id = array();
		foreach ( $rows as $row ) {
			$by_id[ $row['provider_id'] ] = $row;
		}
		$this->assertSame( 'ready_structured', $by_id['openai']['readiness'] );
		$this->assertTrue( $by_id['openai']['credential_configured'] );
		$this->assertTrue( $by_id['openai']['structured_output_supported'] );
		$this->assertSame( 'needs_credential', $by_id['anthropic']['readiness'] );
		$this->assertFalse( $by_id['anthropic']['credential_configured'] );
	}
}
