<?php
/**
 * Integration-style test: Step 2 GET deny row uses redirect target with step2_row_deny_done (PHPUnit captures redirect via $GLOBALS['_aio_pb_test_capture_redirect']).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin\BuildPlan;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Bulk_Action_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Support\Testing\Redirect_Capture_Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen::maybe_handle_step2_action
 */
final class Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test extends TestCase {

	public const PLAN_ID      = 'e2e-step2-deny';
	public const PLAN_POST_ID = 42;

	/** @var array<string, mixed> */
	private array $saved_get = array();

	/** @var array<string, mixed> */
	private array $saved_server = array();

	protected function setUp(): void {
		parent::setUp();
		$this->saved_get    = $_GET;
		$this->saved_server = $_SERVER;
	}

	protected function tearDown(): void {
		$_GET    = $this->saved_get;
		$_SERVER = $this->saved_server;
		unset(
			$GLOBALS['_aio_pb_test_capture_redirect'],
			$GLOBALS['_aio_post_meta'],
			$GLOBALS['_aio_is_logged_in'],
			$GLOBALS['_aio_current_user_can_caps'],
			$GLOBALS['_aio_current_uid']
		);
		$this->reset_early_handlers_flag();
		parent::tearDown();
	}

	private function reset_early_handlers_flag(): void {
		$ref  = new ReflectionClass( Build_Plan_Workspace_Screen::class );
		$prop = $ref->getProperty( 'early_handlers_dispatched' );
		$prop->setAccessible( true );
		$prop->setValue( null, false );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function minimal_plan_definition(): array {
		$items = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'proposed_page_title' => 'Contact Us',
					'proposed_slug'       => 'contact-us',
					'purpose'             => 'Lead capture',
					'template_key'        => 'contact',
					'hierarchy_position'  => 'child of /about',
					'page_type'           => 'landing',
					'confidence'          => 'medium',
				),
			),
		);
		$steps = array(
			array(
				'step_type' => 'overview',
				'items'     => array(),
			),
			array(
				'step_type' => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
				'items'     => array(),
			),
			array(
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
				Build_Plan_Item_Schema::KEY_ITEMS     => $items,
			),
		);
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => self::PLAN_ID,
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	public function test_get_deny_item_redirects_with_step2_row_deny_done(): void {
		$GLOBALS['_aio_pb_test_capture_redirect'] = true;
		$GLOBALS['_aio_is_logged_in']             = true;
		$GLOBALS['_aio_current_user_can_caps']    = array(
			'manage_options' => true,
		);
		$GLOBALS['_aio_current_uid']              = 1;
		$GLOBALS['_aio_post_meta']                = array(
			(string) self::PLAN_POST_ID => array(
				Build_Plan_Repository::META_PLAN_DEFINITION => \wp_json_encode( $this->minimal_plan_definition() ),
			),
		);

		$nonce_action              = 'aio_pb_build_plan_row_action_plan_npc_0';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET                      = array(
			'step'     => '2',
			'action'   => 'deny_item',
			'item_id'  => 'plan_npc_0',
			'_wpnonce' => \wp_create_nonce( $nonce_action ),
		);

		$container = new Service_Container();
		$container->register(
			'build_plan_ui_state_builder',
			static function () {
				return new class() {
					/**
					 * @return array<string, mixed>|null
					 */
					public function build( string $plan_id ): ?array {
						if ( $plan_id !== Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_ID ) {
							return null;
						}
						return array(
							'plan_id'      => Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_ID,
							'plan_post_id' => Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_POST_ID,
						);
					}
				};
			}
		);
		$container->register(
			'new_page_creation_bulk_action_service',
			static function () {
				return new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
			}
		);

		$screen = new Build_Plan_Workspace_Screen( $container );

		try {
			$screen->dispatch_early_request_handlers( self::PLAN_ID );
			$this->fail( 'Expected Redirect_Capture_Exception' );
		} catch ( Redirect_Capture_Exception $e ) {
			$this->assertStringContainsString( 'step2_row_deny_done=1', $e->get_location() );
		}
	}

	public function test_get_deny_item_redirects_with_failed_when_item_not_pending(): void {
		$def = $this->minimal_plan_definition();
		$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0][ Build_Plan_Item_Schema::KEY_STATUS ] = Build_Plan_Item_Statuses::REJECTED;

		$GLOBALS['_aio_pb_test_capture_redirect'] = true;
		$GLOBALS['_aio_is_logged_in']             = true;
		$GLOBALS['_aio_current_user_can_caps']    = array(
			'manage_options' => true,
		);
		$GLOBALS['_aio_current_uid']              = 1;
		$GLOBALS['_aio_post_meta']                = array(
			(string) self::PLAN_POST_ID => array(
				Build_Plan_Repository::META_PLAN_DEFINITION => \wp_json_encode( $def ),
			),
		);

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET                      = array(
			'step'     => '2',
			'action'   => 'deny_item',
			'item_id'  => 'plan_npc_0',
			'_wpnonce' => \wp_create_nonce( 'aio_pb_build_plan_row_action_plan_npc_0' ),
		);

		$container = new Service_Container();
		$container->register(
			'build_plan_ui_state_builder',
			static function () {
				return new class() {
					/**
					 * @return array<string, mixed>|null
					 */
					public function build( string $plan_id ): ?array {
						if ( $plan_id !== Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_ID ) {
							return null;
						}
						return array(
							'plan_id'      => Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_ID,
							'plan_post_id' => Build_Plan_Workspace_Step2_Get_Deny_Redirect_Test::PLAN_POST_ID,
						);
					}
				};
			}
		);
		$container->register(
			'new_page_creation_bulk_action_service',
			static function () {
				return new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
			}
		);

		$screen = new Build_Plan_Workspace_Screen( $container );

		try {
			$screen->dispatch_early_request_handlers( self::PLAN_ID );
			$this->fail( 'Expected Redirect_Capture_Exception' );
		} catch ( Redirect_Capture_Exception $e ) {
			$this->assertStringContainsString( 'step2_row_deny_failed=1', $e->get_location() );
		}
	}
}
