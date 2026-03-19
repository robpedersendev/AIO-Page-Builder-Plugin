<?php
/**
 * Build Plan workspace (detail) screen: three-zone shell, context rail, stepper (spec §31, build-plan-admin-ia-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\Screens\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Message_Component;
use AIOPageBuilder\Admin\Screens\Templates\Page_Template_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Template_Compare_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Renders Build Plan detail with three-zone layout. Consumes UI state from Build_Plan_UI_State_Builder.
 * Row/detail and step-specific tables are placeholders in this prompt.
 */
final class Build_Plan_Workspace_Screen {

	/** @var Service_Container|null */
	private $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	public function get_capability(): string {
		return Capabilities::VIEW_BUILD_PLANS;
	}

	/** Bulk nonce action (required contract). */
	public const NONCE_ACTION_BULK = 'aio_pb_build_plan_bulk_action';

	/** Nonce action for Step 7 (logs/rollback) rollback request. */
	public const NONCE_ACTION_ROLLBACK = 'aio_build_plan_rollback_request';

	/** Bulk nonce action for Step 6 finalization (required contract). */
	public const NONCE_ACTION_FINALIZE_BULK = 'aio_pb_finalize_plan_bulk';

	/**
	 * Returns row action nonce key for an item id (required contract).
	 *
	 * @param string $item_id
	 * @return string
	 */
	private function row_nonce_action( string $item_id ): string {
		$item_id = \sanitize_text_field( $item_id );
		return $item_id !== '' ? 'aio_pb_build_plan_row_action_' . $item_id : '';
	}

	/**
	 * Renders workspace for the given plan_id. Exits with not-found message if plan missing.
	 * Handles Step 2 then Step 1 actions before rendering.
	 *
	 * @param string $plan_id Plan ID from request.
	 * @return void
	 */
	public function render( string $plan_id ): void {
		$handled = $this->maybe_handle_step2_action( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_step1_action( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_step4_action( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_step5_action( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_navigation_action( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_rollback_request( $plan_id );
		if ( $handled ) {
			return;
		}
		$handled = $this->maybe_handle_finalize_plan( $plan_id );
		if ( $handled ) {
			return;
		}
		$state = $this->get_state( $plan_id );
		if ( $state === null ) {
			$this->render_not_found( $plan_id );
			return;
		}
		$current_step_index = $this->get_active_step_index( $state );
		$this->render_shell( $state, $current_step_index );
	}

	/**
	 * Handles Step 6 finalization action. Requires FINALIZE_PLAN_ACTIONS capability and nonce.
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if handled (redirect sent).
	 */
	private function maybe_handle_finalize_plan( string $plan_id ): bool {
		if ( ! \current_user_can( Capabilities::FINALIZE_PLAN_ACTIONS ) ) {
			return false;
		}
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' || ! isset( $_POST['aio_build_plan_action'] ) ) {
			return false;
		}
		$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
		if ( $action !== 'bulk_finalize_plan' ) {
			return false;
		}
		$nonce = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
		if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_FINALIZE_BULK ) ) {
			return false;
		}
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' || ! $this->container || ! $this->container->has( 'single_action_executor' ) || ! $this->container->has( 'build_plan_repository' ) ) {
			return false;
		}
		$redirect_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=6' );

		$repo = $this->container->get( 'build_plan_repository' );
		if ( ! $repo || ! \method_exists( $repo, 'get_by_key' ) || ! \method_exists( $repo, 'get_plan_definition' ) ) {
			return false;
		}
		$plan_record = $repo->get_by_key( $plan_id );
		$plan_post_id = is_array( $plan_record ) ? (int) ( $plan_record['id'] ?? 0 ) : 0;
		$definition   = $plan_post_id > 0 ? $repo->get_plan_definition( $plan_post_id ) : array();
		$plan_status  = is_array( $definition ) && isset( $definition[ Build_Plan_Schema::KEY_STATUS ] ) && is_string( $definition[ Build_Plan_Schema::KEY_STATUS ] ) ? $definition[ Build_Plan_Schema::KEY_STATUS ] : '';

		$now = gmdate( 'c' );
		$envelope = array(
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_ACTION_ID      => 'finalize_' . $plan_id . '_' . (string) time(),
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_ACTION_TYPE    => \AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types::FINALIZE_PLAN,
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_PLAN_ID        => $plan_id,
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID   => '',
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(),
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_APPROVAL_STATE => array(
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::APPROVAL_PLAN_STATUS => $plan_status,
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::APPROVAL_VERIFIED_AT => $now,
			),
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT  => array(
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_ACTOR_TYPE         => 'user',
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_ACTOR_ID           => (string) \get_current_user_id(),
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_CAPABILITY_CHECKED => Capabilities::FINALIZE_PLAN_ACTIONS,
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_CHECKED_AT         => $now,
			),
			\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ENVELOPE_CREATED_AT     => $now,
		);

		$executor = $this->container->get( 'single_action_executor' );
		$result   = $executor->execute( $envelope );

		// Minimal audit log (no secrets).
		if ( \is_object( $result ) && \method_exists( $result, 'get_status' ) ) {
			\error_log( '[AIO Page Builder] Finalize plan result: ' . (string) $result->get_status() . ' plan_id=' . $plan_id );
		}

		\wp_safe_redirect( \add_query_arg( array( 'finalize_result' => 'done' ), $redirect_url ) );
		exit;
	}

	/**
	 * Handles Step 1 single or bulk approve/deny; redirects on success. Caller must have APPROVE_BUILD_PLANS.
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent (exit); false to continue render.
	 */
	private function maybe_handle_step1_action( string $plan_id ): bool {
		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
			return false;
		}
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}
		$redirect_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=1' );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_build_plan_action'] ) ) {
			$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
			$nonce  = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_BULK ) ) {
				return false;
			}
			if ( $action !== 'bulk_approve_step1' && $action !== 'bulk_approve_selected_step1' && $action !== 'bulk_deny_step1' ) {
				return false;
			}
			if ( ! $this->container || ! $this->container->has( 'existing_page_update_bulk_action_service' ) ) {
				return false;
			}
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}
			$service = $this->container->get( 'existing_page_update_bulk_action_service' );
			if ( $action === 'bulk_approve_step1' ) {
				$service->bulk_approve_all_eligible( $plan_post_id );
			} elseif ( $action === 'bulk_approve_selected_step1' ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized via array_map below.
				$raw_step1 = isset( $_POST['aio_step1_selected_ids'] ) && is_array( $_POST['aio_step1_selected_ids'] ) ? \wp_unslash( $_POST['aio_step1_selected_ids'] ) : array();
				$selected  = array_map( 'sanitize_text_field', $raw_step1 );
				$selected  = array_values( array_filter( $selected ) );
				if ( empty( $selected ) ) {
					\wp_safe_redirect( \add_query_arg( array( 'step1_bulk_apply_error' => 'none_selected' ), $redirect_url ) );
					exit;
				}
				$service->bulk_approve_selected( $plan_post_id, $selected );
			} else {
				$service->bulk_deny_all_eligible( $plan_post_id );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}

		$get_action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$item_id    = isset( $_GET['item_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['item_id'] ) ) : '';
		$get_step   = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		$row_nonce_action = $this->row_nonce_action( $item_id );
		if ( $get_step === '1' && ( $get_action === 'approve_item' || $get_action === 'deny_item' ) && $item_id !== '' && $row_nonce_action !== '' && \wp_verify_nonce( $nonce, $row_nonce_action ) ) {
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 || ! $this->container || ! $this->container->has( 'existing_page_update_bulk_action_service' ) ) {
				return false;
			}
			$service = $this->container->get( 'existing_page_update_bulk_action_service' );
			if ( $get_action === 'approve_item' ) {
				$service->approve_item( $plan_post_id, $item_id );
			} else {
				$service->deny_item( $plan_post_id, $item_id );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}
		return false;
	}

	/**
	 * Handles Step 2 single approve (build-intent) or bulk Build All / Build Selected; redirects on success.
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent (exit); false to continue render.
	 */
	private function maybe_handle_step2_action( string $plan_id ): bool {
		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
			return false;
		}
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}
		$redirect_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=2' );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_build_plan_action'] ) ) {
			$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
			$nonce  = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_BULK ) ) {
				return false;
			}
			if ( $action !== 'bulk_build_all_step2' && $action !== 'bulk_build_selected_step2' && $action !== 'bulk_deny_all_step2' ) {
				return false;
			}
			if ( ! $this->container || ! $this->container->has( 'new_page_creation_bulk_action_service' ) ) {
				return false;
			}
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}
			$service = $this->container->get( 'new_page_creation_bulk_action_service' );
			if ( $action === 'bulk_build_all_step2' ) {
				$service->bulk_approve_all_eligible( $plan_post_id );
			} elseif ( $action === 'bulk_deny_all_step2' ) {
				$confirm = isset( $_POST['aio_step2_deny_all_confirm'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['aio_step2_deny_all_confirm'] ) ) : '';
				if ( $confirm !== '1' ) {
					\wp_safe_redirect( \add_query_arg( array( 'step2_bulk_deny_error' => 'confirm_required' ), $redirect_url ) );
					exit;
				}
				$count = (int) $service->bulk_deny_all_eligible( $plan_post_id );
				\wp_safe_redirect(
					\add_query_arg(
						array(
							'step2_bulk_deny_done'  => '1',
							'step2_bulk_deny_count' => (string) $count,
						),
						$redirect_url
					)
				);
				exit;
			} else {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized via array_map below.
				$raw_step2 = isset( $_POST['aio_step2_selected_ids'] ) && is_array( $_POST['aio_step2_selected_ids'] ) ? \wp_unslash( $_POST['aio_step2_selected_ids'] ) : array();
				$selected  = array_map( 'sanitize_text_field', $raw_step2 );
				$selected  = array_values( array_filter( $selected ) );
				if ( empty( $selected ) ) {
					\wp_safe_redirect( \add_query_arg( array( 'step2_bulk_build_error' => 'none_selected' ), $redirect_url ) );
					exit;
				}
				$service->bulk_approve_selected( $plan_post_id, $selected );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}

		$get_step   = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		$get_action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$item_id    = isset( $_GET['item_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['item_id'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		$row_nonce_action = $this->row_nonce_action( $item_id );
		if ( $get_step === '2' && ( $get_action === 'approve_item' || $get_action === 'deny_item' ) && $item_id !== '' && $row_nonce_action !== '' && \wp_verify_nonce( $nonce, $row_nonce_action ) ) {
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 || ! $this->container || ! $this->container->has( 'new_page_creation_bulk_action_service' ) ) {
				return false;
			}
			$service = $this->container->get( 'new_page_creation_bulk_action_service' );
			$updated = false;
			if ( $get_action === 'approve_item' ) {
				$updated = (bool) $service->approve_item( $plan_post_id, $item_id );
			}
			if ( $get_action === 'deny_item' ) {
				$updated = (bool) $service->deny_item( $plan_post_id, $item_id );
			}
			$target = ( $get_action === 'deny_item' && $updated )
				? \add_query_arg( array( 'step2_row_deny_done' => '1' ), $redirect_url )
				: $redirect_url;
			\wp_safe_redirect( $target );
			exit;
		}
		return false;
	}

	/**
	 * Handles Step 3 (navigation) single approve/deny or bulk Apply All / Deny All; redirects on success.
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent (exit); false to continue render.
	 */
	private function maybe_handle_navigation_action( string $plan_id ): bool {
		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
			return false;
		}
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}
		$nav_step_index = Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION;
		$redirect_url   = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $nav_step_index );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_build_plan_action'] ) ) {
			$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
			$nonce  = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_NAVIGATION_REVIEW ) ) {
				return false;
			}
			if ( $action !== 'bulk_approve_navigation' && $action !== 'bulk_deny_navigation' ) {
				return false;
			}
			if ( ! $this->container || ! $this->container->has( 'navigation_bulk_action_service' ) ) {
				return false;
			}
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}
			$service = $this->container->get( 'navigation_bulk_action_service' );
			if ( $action === 'bulk_approve_navigation' ) {
				$service->bulk_approve_all_eligible( $plan_post_id );
			} else {
				$service->bulk_deny_all_eligible( $plan_post_id );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}

		$get_step   = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		$get_action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$item_id    = isset( $_GET['item_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['item_id'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		if ( $get_step === (string) $nav_step_index && ( $get_action === 'approve_item' || $get_action === 'deny_item' ) && $item_id !== '' && \wp_verify_nonce( $nonce, self::NONCE_ACTION_NAVIGATION_REVIEW ) ) {
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 || ! $this->container || ! $this->container->has( 'navigation_bulk_action_service' ) ) {
				return false;
			}
			$service = $this->container->get( 'navigation_bulk_action_service' );
			if ( $get_action === 'approve_item' ) {
				$service->approve_item( $plan_post_id, $item_id );
			} else {
				$service->deny_item( $plan_post_id, $item_id );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}
		return false;
	}

	/**
	 * Handles Step 4 (design tokens) review (approve/deny) and token execution (execute/retry).
	 *
	 * - Bulk approve/deny uses POST + bulk nonce.
	 * - Row approve/deny/execute/retry uses GET with per-row nonce.
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent; false to continue render.
	 */
	private function maybe_handle_step4_action( string $plan_id ): bool {
		$tokens_step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service::STEP_INDEX_DESIGN_TOKENS;
		$plan_id           = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}

		$redirect_url = \admin_url(
			'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $tokens_step_index
		);

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_build_plan_action'] ) ) {
			if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
				return false;
			}
			$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
			$nonce  = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_BULK ) ) {
				return false;
			}
			if ( ! $this->container || ! $this->container->has( 'design_token_bulk_action_service' ) ) {
				return false;
			}
			if ( $action !== 'bulk_approve_all_step4' && $action !== 'bulk_approve_selected_step4' && $action !== 'bulk_deny_all_step4' ) {
				return false;
			}

			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}

			$service = $this->container->get( 'design_token_bulk_action_service' );
			if ( $action === 'bulk_approve_all_step4' ) {
				$service->bulk_approve_all_eligible( $plan_post_id );
			} elseif ( $action === 'bulk_deny_all_step4' ) {
				$service->bulk_deny_all_eligible( $plan_post_id );
			} else {
				$raw_selected = isset( $_POST['aio_step4_selected_ids'] ) && is_array( $_POST['aio_step4_selected_ids'] ) ? \wp_unslash( $_POST['aio_step4_selected_ids'] ) : array();
				$selected      = array_map( 'sanitize_text_field', $raw_selected );
				$selected      = array_values( array_filter( $selected ) );
				if ( empty( $selected ) ) {
					\wp_safe_redirect( \add_query_arg( array( 'step4_bulk_apply_error' => 'none_selected' ), $redirect_url ) );
					exit;
				}
				$service->bulk_approve_selected( $plan_post_id, $selected );
			}

			\wp_safe_redirect( $redirect_url );
			exit;
		}

		$get_action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$item_id    = isset( $_GET['item_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['item_id'] ) ) : '';
		$get_step   = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		$row_nonce_action = $this->row_nonce_action( $item_id );

		$is_review_action = in_array(
			$get_action,
			array( 'approve_token_item', 'deny_token_item' ),
			true
		);
		$is_execute_action = in_array(
			$get_action,
			array( 'execute_token_item', 'retry_token_item' ),
			true
		);

		if ( $get_step !== (string) $tokens_step_index || $item_id === '' || $row_nonce_action === '' || ! \wp_verify_nonce( $nonce, $row_nonce_action ) ) {
			return false;
		}

		if ( $is_review_action ) {
			if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) || ! $this->container || ! $this->container->has( 'design_token_bulk_action_service' ) ) {
				return false;
			}
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}
			$service = $this->container->get( 'design_token_bulk_action_service' );
			if ( $get_action === 'approve_token_item' ) {
				$service->approve_item( $plan_post_id, $item_id );
			} else {
				$service->deny_item( $plan_post_id, $item_id );
			}
			\wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( $is_execute_action ) {
			if ( ! \current_user_can( Capabilities::EXECUTE_BUILD_PLANS ) || ! $this->container || ! $this->container->has( 'execution_queue_service' ) ) {
				return false;
			}
			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}

			if ( $get_action === 'retry_token_item' ) {
				// Bulk_Executor only collects approved/in_progress items. Move failed->in_progress for eligible retries.
				$repo = $this->container->has( 'build_plan_repository' ) ? $this->container->get( 'build_plan_repository' ) : null;
				if ( $repo !== null && method_exists( $repo, 'get_plan_definition' ) ) {
					$definition = $repo->get_plan_definition( $plan_post_id );
					$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
						? $definition[ Build_Plan_Schema::KEY_STEPS ]
						: array();
					$step       = $steps[ $tokens_step_index ] ?? null;
					$items      = is_array( $step ) && isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
						? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
						: array();
					$can_retry  = false;
					foreach ( $items as $it ) {
						if ( ! is_array( $it ) ) {
							continue;
						}
						if ( (string) ( $it[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) !== $item_id ) {
							continue;
						}
						$it_type   = (string) ( $it[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
						$it_status = (string) ( $it['status'] ?? '' );
						if ( $it_type === Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN && $it_status === \AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses::FAILED ) {
							$can_retry = true;
						}
						break;
					}
					$updated = false;
					if ( $can_retry && method_exists( $repo, 'update_plan_item_status' ) ) {
						$updated = $repo->update_plan_item_status(
							$plan_post_id,
							$tokens_step_index,
							$item_id,
							\AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses::IN_PROGRESS
						);
					}
					\error_log(
						'[AIO Page Builder] ' . \wp_json_encode(
							array(
								'event'      => 'design_token_retry_status_update',
								'plan_post'  => $plan_post_id,
								'step_index' => $tokens_step_index,
								'item_id'    => $item_id,
								'updated'    => $updated,
								'new_status' => \AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses::IN_PROGRESS,
							)
						)
					); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}

			$actor_context = array(
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_ACTOR_TYPE           => 'user',
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_ACTOR_ID             => (string) \get_current_user_id(),
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_CAPABILITY_CHECKED => Capabilities::EXECUTE_BUILD_PLANS,
				\AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract::ACTOR_CHECKED_AT           => gmdate( 'c' ),
			);
			$this->container->get( 'execution_queue_service' )->request_bulk_execution(
				$plan_id,
				array( $item_id ),
				$actor_context,
				array( 'run_immediately' => true )
			);

			\wp_safe_redirect( $redirect_url );
			exit;
		}

		return false;
	}

	/**
	 * Handles Step 5 (SEO) review (approve/deny).
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent; false to continue render.
	 */
	private function maybe_handle_step5_action( string $plan_id ): bool {
		$seo_step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service::STEP_INDEX_SEO;
		$plan_id        = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}

		$redirect_url = \admin_url(
			'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $seo_step_index
		);

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_build_plan_action'] ) ) {
			if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) ) {
				return false;
			}
			$action = \sanitize_text_field( \wp_unslash( (string) $_POST['aio_build_plan_action'] ) );
			$nonce  = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_BULK ) ) {
				return false;
			}
			if ( ! $this->container || ! $this->container->has( 'seo_bulk_action_service' ) ) {
				return false;
			}
			if ( $action !== 'bulk_approve_all_step5' && $action !== 'bulk_approve_selected_step5' && $action !== 'bulk_deny_all_step5' ) {
				return false;
			}

			$state = $this->get_state( $plan_id );
			if ( $state === null ) {
				return false;
			}
			$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
			if ( $plan_post_id <= 0 ) {
				return false;
			}

			$service = $this->container->get( 'seo_bulk_action_service' );
			if ( $action === 'bulk_approve_all_step5' ) {
				$service->bulk_approve_all_eligible( $plan_post_id );
			} elseif ( $action === 'bulk_deny_all_step5' ) {
				$service->bulk_deny_all_eligible( $plan_post_id );
			} else {
				$raw_selected = isset( $_POST['aio_step5_selected_ids'] ) && is_array( $_POST['aio_step5_selected_ids'] ) ? \wp_unslash( $_POST['aio_step5_selected_ids'] ) : array();
				$selected      = array_map( 'sanitize_text_field', $raw_selected );
				$selected      = array_values( array_filter( $selected ) );
				if ( empty( $selected ) ) {
					\wp_safe_redirect( \add_query_arg( array( 'step5_bulk_apply_error' => 'none_selected' ), $redirect_url ) );
					exit;
				}
				$service->bulk_approve_selected( $plan_post_id, $selected );
			}

			\wp_safe_redirect( $redirect_url );
			exit;
		}

		$get_action = isset( $_GET['action'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['action'] ) ) : '';
		$item_id    = isset( $_GET['item_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['item_id'] ) ) : '';
		$get_step   = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		$nonce      = isset( $_GET['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		$row_nonce_action = $this->row_nonce_action( $item_id );

		$is_review_action = in_array(
			$get_action,
			array( 'approve_seo_item', 'deny_seo_item' ),
			true
		);

		if ( $get_step !== (string) $seo_step_index || $item_id === '' || $row_nonce_action === '' || ! \wp_verify_nonce( $nonce, $row_nonce_action ) ) {
			return false;
		}

		if ( ! $is_review_action ) {
			return false;
		}

		if ( ! \current_user_can( Capabilities::APPROVE_BUILD_PLANS ) || ! $this->container || ! $this->container->has( 'seo_bulk_action_service' ) ) {
			return false;
		}

		$state = $this->get_state( $plan_id );
		if ( $state === null ) {
			return false;
		}
		$plan_post_id = (int) ( $state['plan_post_id'] ?? 0 );
		if ( $plan_post_id <= 0 ) {
			return false;
		}

		$service = $this->container->get( 'seo_bulk_action_service' );
		if ( $get_action === 'approve_seo_item' ) {
			$service->approve_item( $plan_post_id, $item_id );
		} else {
			$service->deny_item( $plan_post_id, $item_id );
		}

		\wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handles Step 7 rollback request: validates permission and eligibility, enqueues rollback job, redirects (spec §38.5, §41.10).
	 *
	 * @param string $plan_id Plan ID.
	 * @return bool True if request was handled and redirect sent; false to continue render.
	 */
	private function maybe_handle_rollback_request( string $plan_id ): bool {
		if ( ! \current_user_can( Capabilities::EXECUTE_ROLLBACKS ) ) {
			return false;
		}
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return false;
		}
		$step_7_index = \AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK;
		$redirect_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $step_7_index );

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['aio_rollback_request'] ) ) {
			$nonce = isset( $_POST['_wpnonce'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['_wpnonce'] ) ) : '';
			if ( ! \wp_verify_nonce( $nonce, self::NONCE_ACTION_ROLLBACK ) ) {
				\wp_safe_redirect( \add_query_arg( 'rollback_error', 'nonce', $redirect_url ) );
				exit;
			}
			$pre_snapshot_id  = isset( $_POST['pre_snapshot_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['pre_snapshot_id'] ) ) : '';
			$post_snapshot_id = isset( $_POST['post_snapshot_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_POST['post_snapshot_id'] ) ) : '';
			if ( $pre_snapshot_id === '' || $post_snapshot_id === '' ) {
				\wp_safe_redirect( \add_query_arg( 'rollback_error', 'missing_snapshots', $redirect_url ) );
				exit;
			}
			if ( ! $this->container || ! $this->container->has( 'rollback_eligibility_service' ) || ! $this->container->has( 'execution_queue_service' ) ) {
				\wp_safe_redirect( \add_query_arg( 'rollback_error', 'unavailable', $redirect_url ) );
				exit;
			}
			$eligibility = $this->container->get( 'rollback_eligibility_service' )->evaluate( $pre_snapshot_id, $post_snapshot_id, array() );
			if ( ! $eligibility->is_eligible() ) {
				\wp_safe_redirect( \add_query_arg( 'rollback_error', 'ineligible', $redirect_url ) );
				exit;
			}
			$target_ref    = $eligibility->get_execution_ref() !== '' ? $eligibility->get_execution_ref() : ( $eligibility->get_pre_snapshot_id() . ':' . $eligibility->get_post_snapshot_id() );
			$payload       = array(
				'pre_snapshot_id'      => $pre_snapshot_id,
				'post_snapshot_id'     => $post_snapshot_id,
				'rollback_handler_key' => $eligibility->get_rollback_handler_key(),
				'target_ref'           => $target_ref,
				'execution_ref'        => $eligibility->get_execution_ref(),
				'build_plan_ref'       => $plan_id,
				'plan_item_ref'        => '',
			);
			$actor_context = array(
				'actor_type' => 'user',
				'actor_id'   => (string) \get_current_user_id(),
			);
			$result        = $this->container->get( 'execution_queue_service' )->request_rollback( $payload, $actor_context, array( 'run_immediately' => true ) );
			if ( isset( $result['status'] ) && $result['status'] === 'completed' ) {
				\wp_safe_redirect( \add_query_arg( 'rollback_done', '1', $redirect_url ) );
				exit;
			}
			$msg = isset( $result['message'] ) ? \rawurlencode( (string) $result['message'] ) : 'failed';
			\wp_safe_redirect( \add_query_arg( 'rollback_error', $msg, $redirect_url ) );
			exit;
		}
		return false;
	}

	private function get_state( string $plan_id ): ?array {
		if ( ! $this->container || ! $this->container->has( 'build_plan_ui_state_builder' ) ) {
			return null;
		}
		$builder = $this->container->get( 'build_plan_ui_state_builder' );
		return $builder->build( $plan_id );
	}

	private function get_active_step_index( array $state ): int {
		$steps = $state['stepper_steps'] ?? array();
		if ( empty( $steps ) ) {
			return 0;
		}
		$step_param = isset( $_GET['step'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step'] ) ) : '';
		if ( $step_param === '' ) {
			return 0;
		}
		if ( is_numeric( $step_param ) ) {
			$idx = (int) $step_param;
			return $idx >= 0 && $idx < count( $steps ) ? $idx : 0;
		}
		foreach ( $steps as $i => $s ) {
			if ( ( $s['step_type'] ?? '' ) === $step_param ) {
				return $i;
			}
		}
		return 0;
	}

	private function render_not_found( string $plan_id ): void {
		$list_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
		?>
		<div class="wrap aio-page-builder-screen aio-build-plan-workspace" role="main" aria-label="<?php \esc_attr_e( 'Build Plan', 'aio-page-builder' ); ?>">
			<h1><?php \esc_html_e( 'Build Plan', 'aio-page-builder' ); ?></h1>
			<p class="aio-admin-notice"><?php \esc_html_e( 'Plan not found.', 'aio-page-builder' ); ?></p>
			<p><a href="<?php echo \esc_url( $list_url ); ?>"><?php \esc_html_e( 'Back to Build Plans', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	private function render_shell( array $state, int $active_step_index ): void {
		$plan_id            = (string) ( $state['plan_id'] ?? '' );
		$rail               = $state['context_rail'] ?? array();
		$steps              = $state['stepper_steps'] ?? array();
		$definition         = $state['plan_definition'] ?? array();
		$current_step       = $steps[ $active_step_index ] ?? null;
		$base_url           = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) );
		$can_export         = \current_user_can( Capabilities::EXPORT_DATA ) || \current_user_can( Capabilities::DOWNLOAD_ARTIFACTS );
		$can_view_artifacts = \current_user_can( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );
		?>
		<div class="wrap aio-page-builder-screen aio-build-plan-workspace aio-build-plan-three-zone">
			<aside class="aio-build-plan-context-rail" role="complementary" aria-label="<?php \esc_attr_e( 'Plan context and actions', 'aio-page-builder' ); ?>">
				<?php $this->render_context_rail( $rail, $plan_id, $base_url, $can_export, $can_view_artifacts ); ?>
			</aside>
			<main class="aio-build-plan-main" id="aio-build-plan-main" aria-label="<?php \esc_attr_e( 'Build Plan steps and content', 'aio-page-builder' ); ?>">
				<div class="aio-build-plan-stepper">
					<?php $this->render_stepper( $steps, $active_step_index, $base_url ); ?>
				</div>
				<div class="aio-build-plan-workspace-content">
					<?php $this->render_step_workspace( $state, $current_step, $active_step_index, $definition, $base_url ); ?>
				</div>
			</main>
		</div>
		<?php
	}

	private function render_context_rail( array $rail, string $plan_id, string $base_url, bool $can_export, bool $can_view_artifacts ): void {
		$list_url = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG );
		?>
		<div class="aio-context-rail-inner">
			<h2 class="aio-context-rail-title"><?php echo \esc_html( (string) ( $rail['plan_title'] ?? __( 'Build Plan', 'aio-page-builder' ) ) ); ?></h2>
			<dl class="aio-context-rail-meta">
				<dt><?php \esc_html_e( 'Plan ID', 'aio-page-builder' ); ?></dt>
				<dd><code><?php echo \esc_html( (string) ( $rail['plan_id'] ?? '' ) ); ?></code></dd>
				<dt><?php \esc_html_e( 'Source AI run', 'aio-page-builder' ); ?></dt>
				<dd><code><?php echo \esc_html( (string) ( $rail['ai_run_ref'] ?? '' ) ); ?></code></dd>
				<dt><?php \esc_html_e( 'Status', 'aio-page-builder' ); ?></dt>
				<dd><span class="aio-status-badge aio-status-<?php echo \esc_attr( \sanitize_html_class( (string) ( $rail['plan_status'] ?? '' ) ) ); ?>"><?php echo \esc_html( (string) ( $rail['plan_status'] ?? '' ) ); ?></span></dd>
				<dt><?php \esc_html_e( 'Site purpose', 'aio-page-builder' ); ?></dt>
				<dd><?php echo \esc_html( (string) ( $rail['site_purpose_summary'] ?? '' ) ); ?></dd>
				<dt><?php \esc_html_e( 'Site flow', 'aio-page-builder' ); ?></dt>
				<dd><?php echo \esc_html( (string) ( $rail['site_flow_summary'] ?? '' ) ); ?></dd>
			</dl>
			<?php
			$subtype_context = $rail['subtype_context'] ?? array();
			if ( ! empty( $subtype_context['has_subtype_context'] ) && ! empty( $subtype_context['subtype_bundle_rationale_line'] ) ) :
				?>
				<div class="aio-context-rail-subtype" role="group" aria-label="<?php \esc_attr_e( 'Industry subtype context', 'aio-page-builder' ); ?>">
					<h3><?php \esc_html_e( 'Industry / subtype', 'aio-page-builder' ); ?></h3>
					<p class="description"><?php echo \esc_html( (string) $subtype_context['subtype_bundle_rationale_line'] ); ?></p>
					<?php if ( ! empty( $subtype_context['subtype_caution_notes'] ) && is_array( $subtype_context['subtype_caution_notes'] ) ) : ?>
						<ul class="aio-subtype-caution-notes">
							<?php foreach ( array_slice( $subtype_context['subtype_caution_notes'], 0, 3 ) as $note ) : ?>
								<li><?php echo \esc_html( (string) $note ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php
			$warnings = $rail['warnings_summary'] ?? array();
			if ( ! empty( $warnings ) ) :
				?>
				<div class="aio-context-rail-warnings">
					<h3><?php \esc_html_e( 'Warnings', 'aio-page-builder' ); ?></h3>
					<ul>
						<?php foreach ( $warnings as $w ) : ?>
							<li><?php echo \esc_html( is_array( $w ) ? (string) ( $w['message'] ?? \wp_json_encode( $w ) ) : (string) $w ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<div class="aio-context-rail-actions">
				<p><a href="<?php echo \esc_url( $list_url ); ?>" class="button"><?php \esc_html_e( 'Save and exit', 'aio-page-builder' ); ?></a></p>
				<?php if ( $can_export ) : ?>
					<p><span class="button button-secondary" role="button" aria-disabled="true"><?php \esc_html_e( 'Export plan', 'aio-page-builder' ); ?></span> <span class="description"><?php \esc_html_e( '(Coming soon)', 'aio-page-builder' ); ?></span></p>
				<?php endif; ?>
				<?php if ( $can_view_artifacts && (string) ( $rail['ai_run_ref'] ?? '' ) !== '' ) : ?>
					<p><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=aio-page-builder-ai-runs&run_id=' . \rawurlencode( (string) $rail['ai_run_ref'] ) ) ); ?>" class="button button-secondary"><?php \esc_html_e( 'View source artifacts', 'aio-page-builder' ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function render_stepper( array $steps, int $active_index, string $base_url ): void {
		?>
		<nav class="aio-stepper-nav" aria-label="<?php \esc_attr_e( 'Plan steps', 'aio-page-builder' ); ?>">
			<ol class="aio-stepper-list">
				<?php foreach ( $steps as $idx => $step ) : ?>
					<?php
					$step_type  = (string) ( $step['step_type'] ?? '' );
					$title      = (string) ( $step['title'] ?? $step_type );
					$badge      = (string) ( $step['status_badge'] ?? '' );
					$unresolved = (int) ( $step['unresolved_count'] ?? 0 );
					$is_blocked = ! empty( $step['is_blocked'] );
					$is_active  = $idx === $active_index;
					$step_url   = $base_url . '&step=' . $idx;
					$can_go     = $idx <= $active_index || ! $is_blocked;
					?>
					<li class="aio-stepper-item <?php echo $is_active ? 'aio-stepper-item-active' : ''; ?> <?php echo $is_blocked ? 'aio-stepper-item-blocked' : ''; ?>">
						<?php if ( $can_go ) : ?>
							<a href="<?php echo \esc_url( $step_url ); ?>" class="aio-stepper-link">
								<span class="aio-stepper-number"><?php echo \esc_html( (string) ( $step['step_number'] ?? ( $idx + 1 ) ) ); ?></span>
								<span class="aio-stepper-title"><?php echo \esc_html( $title ); ?></span>
								<span class="aio-stepper-badge aio-badge-<?php echo \esc_attr( $badge ); ?>"><?php echo \esc_html( $badge ); ?></span>
								<?php if ( $unresolved > 0 ) : ?>
									<span class="aio-stepper-unresolved"><?php echo \esc_html( (string) $unresolved ); ?></span>
								<?php endif; ?>
							</a>
						<?php else : ?>
							<span class="aio-stepper-link aio-stepper-link-disabled">
								<span class="aio-stepper-number"><?php echo \esc_html( (string) ( $step['step_number'] ?? ( $idx + 1 ) ) ); ?></span>
								<span class="aio-stepper-title"><?php echo \esc_html( $title ); ?></span>
								<span class="aio-stepper-badge aio-badge-<?php echo \esc_attr( $badge ); ?>"><?php echo \esc_html( $badge ); ?></span>
								<?php if ( $unresolved > 0 ) : ?>
									<span class="aio-stepper-unresolved"><?php echo \esc_html( (string) $unresolved ); ?></span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
	}

	private function render_step_workspace( array $state, ?array $current_step, int $active_step_index, array $definition, string $base_url ): void {
		if ( $current_step === null ) {
			echo '<p class="aio-empty-state">' . \esc_html__( 'No step selected.', 'aio-page-builder' ) . '</p>';
			return;
		}
		$step_type  = (string) ( $current_step['step_type'] ?? '' );
		$is_blocked = ! empty( $current_step['is_blocked'] );
		$unresolved = (int) ( $current_step['unresolved_count'] ?? 0 );

		if ( $is_blocked ) {
			echo '<div class="aio-empty-state aio-empty-state-blocked"><p>' . \esc_html__( 'This step is blocked until earlier required actions are completed.', 'aio-page-builder' ) . '</p></div>';
			return;
		}

		switch ( $step_type ) {
			case Build_Plan_Schema::STEP_TYPE_OVERVIEW:
				$this->render_overview_shell( $definition );
				break;
			case Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW:
				$this->render_hierarchy_shell( $definition );
				break;
			case Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES:
			case Build_Plan_Schema::STEP_TYPE_NEW_PAGES:
			case Build_Plan_Schema::STEP_TYPE_NAVIGATION:
			case Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS:
			case Build_Plan_Schema::STEP_TYPE_SEO:
			case Build_Plan_Schema::STEP_TYPE_CONFIRMATION:
			case Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK:
				$this->render_actionable_step_workspace( $state, $current_step, $active_step_index, $definition );
				break;
			default:
				echo '<div class="aio-empty-state"><p>' . \esc_html__( 'No recommendations were generated for this step.', 'aio-page-builder' ) . '</p></div>';
		}
	}

	/**
	 * Renders table + detail + bulk bar for an actionable step using shared components.
	 *
	 * @param array<string, mixed> $state Full UI state (plan_id, plan_definition, etc.).
	 * @param array<string, mixed> $current_step Current stepper step data.
	 * @param int                  $active_step_index Step index.
	 * @param array<string, mixed> $definition Plan definition.
	 */
	private function render_actionable_step_workspace( array $state, array $current_step, int $active_step_index, array $definition ): void {
		$plan_id           = (string) ( $state['plan_id'] ?? $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' );
		$unresolved        = (int) ( $current_step['unresolved_count'] ?? 0 );
		$step_type         = (string) ( $current_step['step_type'] ?? '' );
		$always_show_shell = in_array( $step_type, array( Build_Plan_Schema::STEP_TYPE_CONFIRMATION, Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK ), true );
		if ( $unresolved === 0 && ! $always_show_shell ) {
			echo '<div class="aio-empty-state"><p>' . \esc_html__( 'All recommendations in this step have already been resolved.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		if ( ! $this->container || ! $this->container->has( 'build_plan_ui_state_builder' ) ) {
			echo '<div class="aio-empty-state"><p>' . \esc_html__( 'Item list is not available.', 'aio-page-builder' ) . '</p></div>';
			return;
		}
		$detail_item_id = isset( $_GET['detail'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['detail'] ) ) : null;
		if ( $detail_item_id === '' ) {
			$detail_item_id = null;
		}
		$selected_ids = array();
		$capabilities = array(
			'can_approve'        => \current_user_can( Capabilities::APPROVE_BUILD_PLANS ),
			'can_execute'        => \current_user_can( Capabilities::EXECUTE_BUILD_PLANS ),
			'can_view_artifacts' => \current_user_can( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS ),
			'can_rollback'       => \current_user_can( Capabilities::EXECUTE_ROLLBACKS ),
			'can_finalize'       => \current_user_can( Capabilities::FINALIZE_PLAN_ACTIONS ),
		);
		$builder      = $this->container->get( 'build_plan_ui_state_builder' );
		$workspace    = $builder->build_step_workspace( $plan_id, $active_step_index, $capabilities, $detail_item_id, $selected_ids );

		$step_type = (string) ( $current_step['step_type'] ?? '' );
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES ) {
			$this->inject_step1_action_urls( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_NEW_PAGES ) {
			$this->inject_step2_action_urls( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS ) {
			$this->inject_step4_action_urls( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_SEO ) {
			$this->inject_step5_action_urls( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_NAVIGATION ) {
			$this->inject_navigation_action_urls( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_CONFIRMATION ) {
			$this->inject_finalization_links( $workspace, $plan_id );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK ) {
			$this->inject_rollback_entry_data( $workspace, $plan_id );
		}

		$step_messages  = $workspace['step_messages'] ?? array();
		$selection_name = 'selected[]';
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES ) {
			$selection_name = 'aio_step1_selected_ids[]';
		} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_NEW_PAGES ) {
			$selection_name = 'aio_step2_selected_ids[]';
		} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS ) {
			$selection_name = 'aio_step4_selected_ids[]';
		} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_SEO ) {
			$selection_name = 'aio_step5_selected_ids[]';
		}
		$list_payload   = array(
			Step_Item_List_Component::KEY_STEP_LIST_ROWS => $workspace['step_list_rows'] ?? array(),
			Step_Item_List_Component::KEY_COLUMN_ORDER   => $workspace['column_order'] ?? array(),
			Step_Item_List_Component::KEY_SELECTION_FIELD_NAME => $selection_name,
		);
		$bulk_payload   = array( Bulk_Action_Bar_Component::KEY_BULK_ACTION_STATES => $workspace['bulk_action_states'] ?? array() );
		$detail_payload = $workspace['detail_panel'] ?? array();

		$message_component = new Step_Message_Component();
		$bulk_component    = new Bulk_Action_Bar_Component();
		$list_component    = new Step_Item_List_Component();
		$detail_component  = new Detail_Panel_Component();
		$bulk_nonce_html   = \wp_nonce_field( self::NONCE_ACTION_BULK, '_wpnonce', false, false );
		$nav_bulk_nonce    = $step_type === Build_Plan_Schema::STEP_TYPE_NAVIGATION ? $bulk_nonce_html : '';
		$step_title        = (string) ( $current_step['title'] ?? $current_step['step_type'] ?? __( 'Step', 'aio-page-builder' ) );
		?>
		<div class="aio-step-workspace-actionable" role="region" aria-labelledby="aio-step-workspace-heading">
			<h2 id="aio-step-workspace-heading" class="screen-reader-text"><?php echo \esc_html( $step_title ); ?></h2>
			<?php $message_component->render_list( $step_messages ); ?>
			<?php if ( $step_type === Build_Plan_Schema::STEP_TYPE_CONFIRMATION ) : ?>
				<form method="post" style="margin: 12px 0;">
					<?php echo \wp_nonce_field( self::NONCE_ACTION_FINALIZE_BULK, '_wpnonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="hidden" name="aio_build_plan_action" value="bulk_finalize_plan" />
					<button type="submit" class="button button-primary" <?php echo \current_user_can( Capabilities::FINALIZE_PLAN_ACTIONS ) ? '' : 'disabled'; ?>>
						<?php \esc_html_e( 'Finalize plan', 'aio-page-builder' ); ?>
					</button>
				</form>
			<?php endif; ?>
			<?php
			if ( $step_type === Build_Plan_Schema::STEP_TYPE_NAVIGATION && ! empty( $workspace['validation_summary']['messages'] ) ) {
				echo '<div class="aio-navigation-validation-notice notice notice-warning"><p>' . \esc_html__( 'Validation messages:', 'aio-page-builder' ) . ' ' . \esc_html( implode( ' ', array_slice( $workspace['validation_summary']['messages'], 0, 3 ) ) ) . '</p></div>';
			}
			if (
				in_array(
					$step_type,
					array(
						Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
						Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
						Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS,
						Build_Plan_Schema::STEP_TYPE_SEO,
					),
					true
				)
			) {
				// Bulk actions for Steps 1/2/4/5 are rendered within the list form to submit selection.
			} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_NAVIGATION ) {
				$this->render_navigation_bulk_forms( $workspace['bulk_action_states'] ?? array(), $nav_bulk_nonce );
			} else {
				$bulk_component->render( $bulk_payload );
			}
			?>
			<div class="aio-step-workspace-list-detail">
				<div class="aio-step-workspace-list">
					<?php if ( in_array( $step_type, array( Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES, Build_Plan_Schema::STEP_TYPE_NEW_PAGES, Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS, Build_Plan_Schema::STEP_TYPE_SEO ), true ) ) : ?>
						<form method="post" class="aio-step-bulk-and-list-form">
							<?php
							if ( $step_type === Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES ) {
								$this->render_step1_bulk_forms( $workspace['bulk_action_states'] ?? array(), $bulk_nonce_html );
							} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS ) {
								$this->render_step4_bulk_forms( $workspace['bulk_action_states'] ?? array(), $bulk_nonce_html );
							} elseif ( $step_type === Build_Plan_Schema::STEP_TYPE_SEO ) {
								$this->render_step5_bulk_forms( $workspace['bulk_action_states'] ?? array(), $bulk_nonce_html );
							} else {
								$this->render_step2_bulk_forms( $workspace['bulk_action_states'] ?? array(), $bulk_nonce_html, array() );
							}
							$list_component->render( $list_payload, $detail_item_id );
							?>
						</form>
					<?php else : ?>
						<?php $list_component->render( $list_payload, $detail_item_id ); ?>
					<?php endif; ?>
				</div>
				<div class="aio-step-workspace-detail">
					<?php $detail_component->render( $detail_payload ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Injects approve/deny action URLs with nonce into Step 1 workspace payload (row_actions and detail_panel).
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id   Plan ID.
	 */
	private function inject_step1_action_urls( array &$workspace, string $plan_id ): void {
		$router = $this->container && $this->container->has( 'admin_router' ) ? $this->container->get( 'admin_router' ) : null;
		$base   = $router ? (string) $router->url( 'build_plan_workspace', array( 'plan_id' => $plan_id, 'step' => 1 ) ) : \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=1' );
		$rows  = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$item_id = (string) ( $row['item_id'] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}
				$nonce   = \wp_create_nonce( $this->row_nonce_action( $item_id ) );
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				$workspace['step_list_rows'][ $i ]['row_actions'] = $this->add_urls_to_approve_deny( $actions, $item_id, $base, $nonce );
				$template_key                                     = (string) ( $row['summary_columns']['target_template'] ?? $row['existing_page_template_change_summary']['template_key'] ?? '' );
				if ( $template_key !== '' ) {
					$detail_url  = $router ? (string) $router->url( 'page_template_detail', array( 'template' => $template_key ) ) : \add_query_arg(
						array(
							'page'     => Page_Template_Detail_Screen::SLUG,
							'template' => $template_key,
						),
						\admin_url( 'admin.php' )
					);
					$compare_url = Template_Compare_Screen::get_compare_add_url( 'page', $template_key );
					$workspace['step_list_rows'][ $i ]['template_detail_url']               = $detail_url;
					$workspace['step_list_rows'][ $i ]['template_compare_add_url']          = $compare_url;
					$workspace['step_list_rows'][ $i ]['summary_columns']['template_links'] = '<a href="' . \esc_url( $detail_url ) . '">' . \esc_html__( 'View template', 'aio-page-builder' ) . '</a> | <a href="' . \esc_url( $compare_url ) . '">' . \esc_html__( 'Add to compare', 'aio-page-builder' ) . '</a>';
				}
			}
		}
		$detail = &$workspace['detail_panel'];
		if ( is_array( $detail ) && isset( $detail['row_actions'] ) && is_array( $detail['row_actions'] ) ) {
			$detail_item_id        = (string) ( $detail['item_id'] ?? '' );
			$nonce                 = $detail_item_id !== '' ? \wp_create_nonce( $this->row_nonce_action( $detail_item_id ) ) : '';
			$detail['row_actions'] = $this->add_urls_to_approve_deny( $detail['row_actions'], $detail_item_id, $base, $nonce );
		}

		$step_messages = isset( $workspace['step_messages'] ) && is_array( $workspace['step_messages'] ) ? $workspace['step_messages'] : array();
		$apply_error   = isset( $_GET['step1_bulk_apply_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step1_bulk_apply_error'] ) ) : '';
		if ( $apply_error !== '' ) {
			$err_msg = $apply_error === 'none_selected'
				? \__( 'Select one or more rows to apply selected updates.', 'aio-page-builder' )
				: \__( 'Apply selected failed.', 'aio-page-builder' );
			array_unshift(
				$step_messages,
				array(
					'severity' => 'error',
					'message'  => $err_msg,
					'level'    => 'step',
				)
			);
		}
		$workspace['step_messages'] = $step_messages;
	}

	/**
	 * Injects preview/history URL into finalization step payload.
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id   Plan ID.
	 */
	private function inject_finalization_links( array &$workspace, string $plan_id ): void {
		if ( ! isset( $workspace['preview_link'] ) || ! is_array( $workspace['preview_link'] ) ) {
			return;
		}
		$router = $this->container && $this->container->has( 'admin_router' ) ? $this->container->get( 'admin_router' ) : null;
		$workspace['preview_link']['url'] = $router
			? (string) $router->url(
				'build_plan_workspace',
				array(
					'plan_id' => $plan_id,
					'step'    => \AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK,
				)
			)
			: \admin_url(
				'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . \AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK
			);
	}

	/**
	 * Injects approve action URLs with nonce into Step 2 workspace payload (row_actions and detail_panel).
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id  Plan ID.
	 */
	private function inject_step2_action_urls( array &$workspace, string $plan_id ): void {
		$router = $this->container && $this->container->has( 'admin_router' ) ? $this->container->get( 'admin_router' ) : null;
		$base   = $router ? (string) $router->url( 'build_plan_workspace', array( 'plan_id' => $plan_id, 'step' => 2 ) ) : \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=2' );
		$rows  = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$item_id = (string) ( $row['item_id'] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}
				$nonce   = \wp_create_nonce( $this->row_nonce_action( $item_id ) );
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				$workspace['step_list_rows'][ $i ]['row_actions'] = $this->add_urls_to_approve_deny( $actions, $item_id, $base, $nonce );
				$template_key                                     = (string) ( $row['summary_columns']['template_key'] ?? '' );
				if ( $template_key !== '' ) {
					$detail_url  = $router ? (string) $router->url( 'page_template_detail', array( 'template' => $template_key ) ) : \add_query_arg(
						array(
							'page'     => Page_Template_Detail_Screen::SLUG,
							'template' => $template_key,
						),
						\admin_url( 'admin.php' )
					);
					$compare_url = Template_Compare_Screen::get_compare_add_url( 'page', $template_key );
					$workspace['step_list_rows'][ $i ]['template_detail_url']               = $detail_url;
					$workspace['step_list_rows'][ $i ]['template_compare_add_url']          = $compare_url;
					$workspace['step_list_rows'][ $i ]['summary_columns']['template_links'] = '<a href="' . \esc_url( $detail_url ) . '">' . \esc_html__( 'View template', 'aio-page-builder' ) . '</a> | <a href="' . \esc_url( $compare_url ) . '">' . \esc_html__( 'Add to compare', 'aio-page-builder' ) . '</a>';
				}
			}
		}
		$detail = &$workspace['detail_panel'];
		if ( is_array( $detail ) && isset( $detail['row_actions'] ) && is_array( $detail['row_actions'] ) ) {
			$detail_item_id        = (string) ( $detail['item_id'] ?? '' );
			$nonce                 = $detail_item_id !== '' ? \wp_create_nonce( $this->row_nonce_action( $detail_item_id ) ) : '';
			$detail['row_actions'] = $this->add_urls_to_approve_deny( $detail['row_actions'], $detail_item_id, $base, $nonce );
		}

		$step_messages = isset( $workspace['step_messages'] ) && is_array( $workspace['step_messages'] ) ? $workspace['step_messages'] : array();
		$bulk_done     = isset( $_GET['step2_bulk_deny_done'] ) && $_GET['step2_bulk_deny_done'] === '1';
		$bulk_count    = isset( $_GET['step2_bulk_deny_count'] ) && is_numeric( $_GET['step2_bulk_deny_count'] )
			? (int) $_GET['step2_bulk_deny_count']
			: 0;
		$row_deny_done = isset( $_GET['step2_row_deny_done'] ) && $_GET['step2_row_deny_done'] === '1';
		$bulk_error    = isset( $_GET['step2_bulk_deny_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step2_bulk_deny_error'] ) ) : '';
		$build_error   = isset( $_GET['step2_bulk_build_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['step2_bulk_build_error'] ) ) : '';

		if ( $bulk_done ) {
			$msg = sprintf(
				_n( 'Denied %d eligible new page recommendation.', 'Denied %d eligible new page recommendations.', $bulk_count, 'aio-page-builder' ),
				$bulk_count
			);
			array_unshift(
				$step_messages,
				array(
					'severity' => 'success',
					'message'  => $msg,
					'level'    => 'step',
				)
			);
		}
		if ( $row_deny_done ) {
			array_unshift(
				$step_messages,
				array(
					'severity' => 'info',
					'message'  => \__( 'Denied the selected new page recommendation.', 'aio-page-builder' ),
					'level'    => 'step',
				)
			);
		}
		if ( $bulk_error !== '' ) {
			$err_msg = $bulk_error === 'confirm_required'
				? \__( 'Please confirm bulk denial to continue.', 'aio-page-builder' )
				: \__( 'Bulk denial failed.', 'aio-page-builder' );
			array_unshift(
				$step_messages,
				array(
					'severity' => 'error',
					'message'  => $err_msg,
					'level'    => 'step',
				)
			);
		}
		if ( $build_error !== '' ) {
			$err_msg = $build_error === 'none_selected'
				? \__( 'Select one or more rows to build selected pages.', 'aio-page-builder' )
				: \__( 'Bulk build selected failed.', 'aio-page-builder' );
			array_unshift(
				$step_messages,
				array(
					'severity' => 'error',
					'message'  => $err_msg,
					'level'    => 'step',
				)
			);
		}

		$workspace['step_messages'] = $step_messages;
	}

	/**
	 * Injects token/execute/retry action URLs with nonce into Step 4 workspace payload.
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id   Plan ID.
	 */
	private function inject_step4_action_urls( array &$workspace, string $plan_id ): void {
		$tokens_step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service::STEP_INDEX_DESIGN_TOKENS;
		$router             = $this->container && $this->container->has( 'admin_router' ) ? $this->container->get( 'admin_router' ) : null;
		$base               = $router
			? (string) $router->url( 'build_plan_workspace', array( 'plan_id' => $plan_id, 'step' => $tokens_step_index ) )
			: \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $tokens_step_index );

		$action_map = array(
			Build_Plan_Row_Action_Resolver::ACTION_APPROVE  => 'approve_token_item',
			Build_Plan_Row_Action_Resolver::ACTION_DENY     => 'deny_token_item',
			Build_Plan_Row_Action_Resolver::ACTION_EXECUTE  => 'execute_token_item',
			Build_Plan_Row_Action_Resolver::ACTION_RETRY    => 'retry_token_item',
		);

		$rows = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$item_id = (string) ( $row['item_id'] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}

				$nonce   = \wp_create_nonce( $this->row_nonce_action( $item_id ) );
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				foreach ( $actions as $j => $a ) {
					$action_id = (string) ( $a['action_id'] ?? '' );
					if ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DEPENDENCIES ) {
						// No server handler is implemented for this action yet, so disable it to avoid a no-op button.
						$a['enabled'] = false;
					} elseif ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DETAIL ) {
						// Updates $detail_item_id via query param to render the detail panel.
						if ( ! empty( $a['enabled'] ) ) {
							$a['url'] = $base . '&detail=' . \rawurlencode( $item_id );
						}
					} elseif ( isset( $action_map[ $action_id ] ) && ! empty( $a['enabled'] ) ) {
						$a['url'] = $base . '&action=' . \rawurlencode( $action_map[ $action_id ] ) . '&item_id=' . \rawurlencode( $item_id ) . '&_wpnonce=' . $nonce;
					}
					$actions[ $j ] = $a;
				}
				$workspace['step_list_rows'][ $i ]['row_actions'] = $actions;
			}
		}

		$detail = &$workspace['detail_panel'];
		if ( is_array( $detail ) && isset( $detail['row_actions'] ) && is_array( $detail['row_actions'] ) ) {
			$detail_item_id = (string) ( $detail['item_id'] ?? '' );
			if ( $detail_item_id !== '' ) {
				$nonce   = \wp_create_nonce( $this->row_nonce_action( $detail_item_id ) );
				$actions = $detail['row_actions'];
				foreach ( $actions as $j => $a ) {
					$action_id = (string) ( $a['action_id'] ?? '' );
					if ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DEPENDENCIES ) {
						$a['enabled'] = false;
					} elseif ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DETAIL ) {
						if ( ! empty( $a['enabled'] ) ) {
							$a['url'] = $base . '&detail=' . \rawurlencode( $detail_item_id );
						}
					} elseif ( isset( $action_map[ $action_id ] ) && ! empty( $a['enabled'] ) ) {
						$a['url'] = $base . '&action=' . \rawurlencode( $action_map[ $action_id ] ) . '&item_id=' . \rawurlencode( $detail_item_id ) . '&_wpnonce=' . $nonce;
					}
					$actions[ $j ] = $a;
				}
				$detail['row_actions'] = $actions;
			}
		}
	}

	/**
	 * Injects SEO approve/deny action URLs with nonce into Step 5 workspace payload.
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id   Plan ID.
	 */
	private function inject_step5_action_urls( array &$workspace, string $plan_id ): void {
		$seo_step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service::STEP_INDEX_SEO;
		$router         = $this->container && $this->container->has( 'admin_router' ) ? $this->container->get( 'admin_router' ) : null;
		$base           = $router
			? (string) $router->url( 'build_plan_workspace', array( 'plan_id' => $plan_id, 'step' => $seo_step_index ) )
			: \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $seo_step_index );

		$action_map = array(
			Build_Plan_Row_Action_Resolver::ACTION_APPROVE => 'approve_seo_item',
			Build_Plan_Row_Action_Resolver::ACTION_DENY    => 'deny_seo_item',
		);

		$rows = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$item_id = (string) ( $row['item_id'] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}

				$nonce   = \wp_create_nonce( $this->row_nonce_action( $item_id ) );
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				foreach ( $actions as $j => $a ) {
					$action_id = (string) ( $a['action_id'] ?? '' );
					if ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DEPENDENCIES ) {
						// No server handler is implemented for this action yet, so disable it to avoid a no-op button.
						$a['enabled'] = false;
					} elseif ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DETAIL ) {
						if ( ! empty( $a['enabled'] ) ) {
							$a['url'] = $base . '&detail=' . \rawurlencode( $item_id );
						}
					} elseif ( isset( $action_map[ $action_id ] ) && ! empty( $a['enabled'] ) ) {
						$a['url'] = $base . '&action=' . \rawurlencode( $action_map[ $action_id ] ) . '&item_id=' . \rawurlencode( $item_id ) . '&_wpnonce=' . $nonce;
					}
					$actions[ $j ] = $a;
				}
				$workspace['step_list_rows'][ $i ]['row_actions'] = $actions;
			}
		}

		$detail = &$workspace['detail_panel'];
		if ( is_array( $detail ) && isset( $detail['row_actions'] ) && is_array( $detail['row_actions'] ) ) {
			$detail_item_id = (string) ( $detail['item_id'] ?? '' );
			if ( $detail_item_id !== '' ) {
				$nonce   = \wp_create_nonce( $this->row_nonce_action( $detail_item_id ) );
				$actions = $detail['row_actions'];
				foreach ( $actions as $j => $a ) {
					$action_id = (string) ( $a['action_id'] ?? '' );
					if ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DEPENDENCIES ) {
						$a['enabled'] = false;
					} elseif ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_VIEW_DETAIL ) {
						if ( ! empty( $a['enabled'] ) ) {
							$a['url'] = $base . '&detail=' . \rawurlencode( $detail_item_id );
						}
					} elseif ( isset( $action_map[ $action_id ] ) && ! empty( $a['enabled'] ) ) {
						$a['url'] = $base . '&action=' . \rawurlencode( $action_map[ $action_id ] ) . '&item_id=' . \rawurlencode( $detail_item_id ) . '&_wpnonce=' . $nonce;
					}
					$actions[ $j ] = $a;
				}
				$detail['row_actions'] = $actions;
			}
		}
	}

	/**
	 * Injects approve/deny action URLs with nonce into Step 3 (navigation) workspace payload.
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id  Plan ID.
	 */
	private function inject_navigation_action_urls( array &$workspace, string $plan_id ): void {
		$nav_step_index = Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION;
		$base           = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $nav_step_index );
		$nonce          = \wp_create_nonce( self::NONCE_ACTION_NAVIGATION_REVIEW );
		$rows           = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$item_id = (string) ( $row['item_id'] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				$workspace['step_list_rows'][ $i ]['row_actions'] = $this->add_urls_to_approve_deny( $actions, $item_id, $base, $nonce );
			}
		}
		$detail = &$workspace['detail_panel'];
		if ( is_array( $detail ) && isset( $detail['row_actions'] ) && is_array( $detail['row_actions'] ) ) {
			$detail_item_id        = (string) ( $detail['item_id'] ?? '' );
			$detail['row_actions'] = $this->add_urls_to_approve_deny( $detail['row_actions'], $detail_item_id, $base, $nonce );
		}
	}

	/**
	 * Injects rollback nonce, action URL, and per-row form data for Step 7 (spec §41.10). Surfaces rollback_done/rollback_error.
	 *
	 * @param array<string, mixed> $workspace Workspace payload (mutated).
	 * @param string               $plan_id  Plan ID.
	 */
	private function inject_rollback_entry_data( array &$workspace, string $plan_id ): void {
		$step_7_index                     = \AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK;
		$workspace['rollback_nonce']      = \wp_create_nonce( self::NONCE_ACTION_ROLLBACK );
		$workspace['rollback_action_url'] = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $step_7_index );
		$rows                             = &$workspace['step_list_rows'];
		if ( is_array( $rows ) ) {
			foreach ( $rows as $i => $row ) {
				$pre_id  = isset( $row['pre_snapshot_id'] ) ? (string) $row['pre_snapshot_id'] : '';
				$post_id = isset( $row['post_snapshot_id'] ) ? (string) $row['post_snapshot_id'] : '';
				$actions = isset( $row['row_actions'] ) && is_array( $row['row_actions'] ) ? $row['row_actions'] : array();
				foreach ( $actions as $j => $action ) {
					if ( ( (string) ( $action['action_id'] ?? '' ) ) === 'request_rollback' && $pre_id !== '' && $post_id !== '' ) {
						$actions[ $j ]['form_post']     = true;
						$actions[ $j ]['form_action']   = $workspace['rollback_action_url'];
						$actions[ $j ]['hidden_fields'] = array(
							'_wpnonce'             => $workspace['rollback_nonce'],
							'aio_rollback_request' => '1',
							'pre_snapshot_id'      => $pre_id,
							'post_snapshot_id'     => $post_id,
						);
						break;
					}
				}
				$workspace['step_list_rows'][ $i ]['row_actions'] = $actions;
			}
		}
		$rollback_done = isset( $_GET['rollback_done'] ) && $_GET['rollback_done'] === '1';
		$rollback_err  = isset( $_GET['rollback_error'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['rollback_error'] ) ) : '';
		$step_messages = isset( $workspace['step_messages'] ) && is_array( $workspace['step_messages'] ) ? $workspace['step_messages'] : array();
		if ( $rollback_done ) {
			array_unshift(
				$step_messages,
				array(
					'severity' => 'success',
					'message'  => \__( 'Rollback completed successfully.', 'aio-page-builder' ),
					'level'    => 'step',
				)
			);
		}
		if ( $rollback_err !== '' ) {
			$err_msg = $rollback_err === 'nonce' ? \__( 'Security check failed. Please try again.', 'aio-page-builder' )
				: ( $rollback_err === 'missing_snapshots' ? \__( 'Missing snapshot references.', 'aio-page-builder' )
				: ( $rollback_err === 'ineligible' ? \__( 'Rollback is not eligible for this pair.', 'aio-page-builder' )
				: ( $rollback_err === 'unavailable' ? \__( 'Rollback service unavailable.', 'aio-page-builder' )
				: \__( 'Rollback failed.', 'aio-page-builder' ) ) ) );
			array_unshift(
				$step_messages,
				array(
					'severity' => 'error',
					'message'  => $err_msg,
					'level'    => 'step',
				)
			);
		}
		$workspace['step_messages'] = $step_messages;
	}

	/**
	 * Adds url to approve and deny actions when enabled.
	 *
	 * @param array<int, array<string, mixed>> $actions  Row actions to augment.
	 * @param string                           $item_id  Item identifier for query params.
	 * @param string                           $base_url Base URL for action links.
	 * @param string                           $nonce    Nonce value for links.
	 * @return array<int, array<string, mixed>>
	 */
	private function add_urls_to_approve_deny( array $actions, string $item_id, string $base_url, string $nonce ): array {
		$out = array();
		foreach ( $actions as $a ) {
			$action_id = (string) ( $a['action_id'] ?? '' );
			if ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_APPROVE && ! empty( $a['enabled'] ) ) {
				$a['url'] = $base_url . '&action=approve_item&item_id=' . \rawurlencode( $item_id ) . '&_wpnonce=' . $nonce;
			} elseif ( $action_id === Build_Plan_Row_Action_Resolver::ACTION_DENY && ! empty( $a['enabled'] ) ) {
				$a['url'] = $base_url . '&action=deny_item&item_id=' . \rawurlencode( $item_id ) . '&_wpnonce=' . $nonce;
			}
			$out[] = $a;
		}
		return $out;
	}

	/**
	 * Renders Step 1 bulk action forms (Make All Updates, Deny All Updates) and remaining bulk bar buttons.
	 *
	 * @param array<string, array<string, mixed>> $bulk_states From workspace bulk_action_states.
	 * @param string                              $nonce_html  Escaped nonce field HTML.
	 */
	private function render_step1_bulk_forms( array $bulk_states, string $nonce_html ): void {
		$make_all     = $bulk_states['apply_to_all_eligible'] ?? array();
		$deny_all     = $bulk_states['deny_all_eligible'] ?? array();
		$apply_sel    = $bulk_states['apply_to_selected'] ?? array();
		$clear_sel    = $bulk_states['clear_selection'] ?? array();
		$make_enabled = ! empty( $make_all['enabled'] );
		$deny_enabled = ! empty( $deny_all['enabled'] );
		$make_count   = (int) ( $make_all['count_eligible'] ?? 0 );
		$deny_count   = (int) ( $deny_all['count_eligible'] ?? 0 );
		$apply_sel_enabled = ! empty( $apply_sel['enabled'] );
		$clear_enabled     = ! empty( $clear_sel['enabled'] );
		$apply_sel_count   = (int) ( $apply_sel['count_selected'] ?? 0 );
		$plan_id           = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		$base_url          = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=1' );
		?>
		<div class="aio-bulk-action-bar aio-step1-bulk-bar">
			<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_step1" class="button button-secondary" <?php echo $make_enabled ? '' : ' disabled="disabled"'; /* Safe: fixed attribute value */ ?>>
				<?php \esc_html_e( 'Make All Updates', 'aio-page-builder' ); ?>
				<?php if ( $make_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $make_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_selected_step1" class="button button-secondary" <?php echo $apply_sel_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Apply to selected', 'aio-page-builder' ); ?>
				<?php if ( $apply_sel_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $apply_sel_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_deny_step1" class="button button-secondary" <?php echo $deny_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Deny All Updates', 'aio-page-builder' ); ?>
				<?php if ( $deny_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $deny_count; ?>)</span>
				<?php endif; ?>
			</button>
			<?php if ( $clear_enabled ) : ?>
				<a href="<?php echo \esc_url( $base_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></a>
			<?php else : ?>
				<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders Step 2 bulk action forms (Build All Pages, Build Selected Pages) and Clear selection link.
	 *
	 * @param array<string, array<string, mixed>> $bulk_states From workspace bulk_action_states.
	 * @param string                              $nonce_html  Escaped nonce field HTML.
	 * @param array<int, string>                  $selected_ids Currently selected item IDs from request.
	 */
	private function render_step2_bulk_forms( array $bulk_states, string $nonce_html, array $selected_ids ): void {
		$build_all         = $bulk_states['apply_to_all_eligible'] ?? array();
		$build_sel         = $bulk_states['apply_to_selected'] ?? array();
		$deny_all          = $bulk_states['deny_all_eligible'] ?? array();
		$clear_sel         = $bulk_states['clear_selection'] ?? array();
		$build_all_enabled = ! empty( $build_all['enabled'] );
		$build_sel_enabled = ! empty( $build_sel['enabled'] );
		$deny_enabled      = ! empty( $deny_all['enabled'] );
		$clear_enabled     = ! empty( $clear_sel['enabled'] );
		$build_all_count   = (int) ( $build_all['count_eligible'] ?? 0 );
		$build_sel_count   = (int) ( $build_sel['count_selected'] ?? 0 );
		$deny_count        = (int) ( $deny_all['count_eligible'] ?? 0 );
		$plan_id           = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		$base_url          = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=2' );
		?>
		<div class="aio-bulk-action-bar aio-step2-bulk-bar">
			<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button type="submit" name="aio_build_plan_action" value="bulk_build_all_step2" class="button button-secondary" <?php echo $build_all_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Build All Pages', 'aio-page-builder' ); ?>
				<?php if ( $build_all_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $build_all_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_build_selected_step2" class="button button-secondary" <?php echo $build_sel_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Build Selected Pages', 'aio-page-builder' ); ?>
				<?php if ( $build_sel_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $build_sel_count; ?>)</span>
				<?php endif; ?>
			</button>
			<label style="margin: 0 0.75em;">
				<input
					type="checkbox"
					name="aio_step2_deny_all_confirm"
					value="1"
					<?php echo $deny_enabled ? 'required="required"' : 'disabled="disabled"'; ?>
				/>
				<?php \esc_html_e( 'Confirm denial', 'aio-page-builder' ); ?>
			</label>
			<button type="submit" name="aio_build_plan_action" value="bulk_deny_all_step2" class="button button-secondary" <?php echo $deny_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Deny All Eligible', 'aio-page-builder' ); ?>
				<?php if ( $deny_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $deny_count; ?>)</span>
				<?php endif; ?>
			</button>
			<?php if ( $clear_enabled ) : ?>
				<a href="<?php echo \esc_url( $base_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></a>
			<?php else : ?>
				<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders Step 4 (design tokens) bulk action forms (Apply all, Apply selected, Deny all).
	 *
	 * @param array<string, array<string, mixed>> $bulk_states From workspace bulk_action_states.
	 * @param string                              $nonce_html  Escaped nonce field HTML.
	 */
	private function render_step4_bulk_forms( array $bulk_states, string $nonce_html ): void {
		$apply_all = $bulk_states['apply_to_all_eligible'] ?? array();
		$apply_sel = $bulk_states['apply_to_selected'] ?? array();
		$deny_all  = $bulk_states['deny_all_eligible'] ?? array();
		$clear_sel = $bulk_states['clear_selection'] ?? array();

		$apply_all_enabled = ! empty( $apply_all['enabled'] );
		$apply_sel_enabled = ! empty( $apply_sel['enabled'] );
		$deny_enabled      = ! empty( $deny_all['enabled'] );
		$clear_enabled     = ! empty( $clear_sel['enabled'] );

		$apply_all_count = (int) ( $apply_all['count_eligible'] ?? 0 );
		$apply_sel_count = (int) ( $apply_sel['count_selected'] ?? 0 );
		$deny_count      = (int) ( $deny_all['count_eligible'] ?? 0 );

		$plan_id    = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		$step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service::STEP_INDEX_DESIGN_TOKENS;
		$base_url   = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $step_index );

		?>
		<div class="aio-bulk-action-bar aio-step4-bulk-bar">
			<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_all_step4" class="button button-secondary" <?php echo $apply_all_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Apply all tokens', 'aio-page-builder' ); ?>
				<?php if ( $apply_all_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $apply_all_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_selected_step4" class="button button-secondary" <?php echo $apply_sel_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Apply to selected', 'aio-page-builder' ); ?>
				<?php if ( $apply_sel_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $apply_sel_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_deny_all_step4" class="button button-secondary" <?php echo $deny_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php \esc_html_e( 'Deny all', 'aio-page-builder' ); ?>
				<?php if ( $deny_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $deny_count; ?>)</span>
				<?php endif; ?>
			</button>
			<?php if ( $clear_enabled ) : ?>
				<a href="<?php echo \esc_url( $base_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></a>
			<?php else : ?>
				<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders Step 5 (SEO) bulk action forms (Apply all, Apply selected, Deny all).
	 *
	 * @param array<string, array<string, mixed>> $bulk_states From workspace bulk_action_states.
	 * @param string                              $nonce_html  Escaped nonce field HTML.
	 */
	private function render_step5_bulk_forms( array $bulk_states, string $nonce_html ): void {
		$apply_all = $bulk_states['apply_to_all_eligible'] ?? array();
		$apply_sel = $bulk_states['apply_to_selected'] ?? array();
		$deny_all  = $bulk_states['deny_all_eligible'] ?? array();
		$clear_sel = $bulk_states['clear_selection'] ?? array();

		$apply_all_enabled = ! empty( $apply_all['enabled'] );
		$apply_sel_enabled = ! empty( $apply_sel['enabled'] );
		$deny_enabled      = ! empty( $deny_all['enabled'] );
		$clear_enabled     = ! empty( $clear_sel['enabled'] );

		$apply_all_count = (int) ( $apply_all['count_eligible'] ?? 0 );
		$apply_sel_count = (int) ( $apply_sel['count_selected'] ?? 0 );
		$deny_count      = (int) ( $deny_all['count_eligible'] ?? 0 );

		$plan_id    = isset( $_GET['plan_id'] ) ? \sanitize_text_field( \wp_unslash( (string) $_GET['plan_id'] ) ) : '';
		$step_index = \AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service::STEP_INDEX_SEO;
		$base_url   = \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( $plan_id ) . '&step=' . $step_index );

		$apply_all_label = (string) ( $apply_all['label'] ?? \__( 'Apply all', 'aio-page-builder' ) );
		$apply_sel_label = (string) ( $apply_sel['label'] ?? \__( 'Apply to selected', 'aio-page-builder' ) );
		$deny_label      = (string) ( $deny_all['label'] ?? \__( 'Deny all', 'aio-page-builder' ) );

		?>
		<div class="aio-bulk-action-bar aio-step5-bulk-bar">
			<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_all_step5" class="button button-secondary" <?php echo $apply_all_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php echo \esc_html( $apply_all_label ); ?>
				<?php if ( $apply_all_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $apply_all_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_approve_selected_step5" class="button button-secondary" <?php echo $apply_sel_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php echo \esc_html( $apply_sel_label ); ?>
				<?php if ( $apply_sel_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $apply_sel_count; ?>)</span>
				<?php endif; ?>
			</button>
			<button type="submit" name="aio_build_plan_action" value="bulk_deny_all_step5" class="button button-secondary" <?php echo $deny_enabled ? '' : ' disabled="disabled"'; ?>>
				<?php echo \esc_html( $deny_label ); ?>
				<?php if ( $deny_count > 0 ) : ?>
					<span class="aio-bulk-count">(<?php echo (int) $deny_count; ?>)</span>
				<?php endif; ?>
			</button>
			<?php if ( $clear_enabled ) : ?>
				<a href="<?php echo \esc_url( $base_url ); ?>" class="button button-secondary"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></a>
			<?php else : ?>
				<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders Step 3 (navigation) bulk action forms: Apply All, Deny All, Apply to selected, Clear selection.
	 *
	 * @param array<string, array<string, mixed>> $bulk_states From workspace bulk_action_states.
	 * @param string                              $nonce_html  Escaped nonce field HTML.
	 */
	private function render_navigation_bulk_forms( array $bulk_states, string $nonce_html ): void {
		$apply_all     = $bulk_states['apply_to_all_eligible'] ?? array();
		$deny_all      = $bulk_states['deny_all_eligible'] ?? array();
		$apply_sel     = $bulk_states['apply_to_selected'] ?? array();
		$clear_sel     = $bulk_states['clear_selection'] ?? array();
		$apply_enabled = ! empty( $apply_all['enabled'] );
		$deny_enabled  = ! empty( $deny_all['enabled'] );
		$clear_enabled = ! empty( $clear_sel['enabled'] );
		$apply_count   = (int) ( $apply_all['count_eligible'] ?? 0 );
		$deny_count    = (int) ( $deny_all['count_eligible'] ?? 0 );
		?>
		<div class="aio-bulk-action-bar aio-navigation-bulk-bar">
			<form method="post" class="aio-bulk-form aio-bulk-approve-navigation" style="display:inline">
				<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="aio_build_plan_action" value="bulk_approve_navigation" />
				<button type="submit" class="button button-secondary" <?php echo $apply_enabled ? '' : ' disabled="disabled"'; ?>>
					<?php \esc_html_e( 'Apply All Navigation Changes', 'aio-page-builder' ); ?>
					<?php if ( $apply_count > 0 ) : ?>
						<span class="aio-bulk-count">(<?php echo (int) $apply_count; ?>)</span>
					<?php endif; ?>
				</button>
			</form>
			<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Apply to selected', 'aio-page-builder' ); ?></button>
			<form method="post" class="aio-bulk-form aio-bulk-deny-navigation" style="display:inline">
				<?php echo $nonce_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="aio_build_plan_action" value="bulk_deny_navigation" />
				<button type="submit" class="button button-secondary" <?php echo $deny_enabled ? '' : ' disabled="disabled"'; ?>>
					<?php \esc_html_e( 'Deny All Navigation Changes', 'aio-page-builder' ); ?>
					<?php if ( $deny_count > 0 ) : ?>
						<span class="aio-bulk-count">(<?php echo (int) $deny_count; ?>)</span>
					<?php endif; ?>
				</button>
			</form>
			<button type="button" class="button button-secondary" disabled="disabled"><?php \esc_html_e( 'Clear selection', 'aio-page-builder' ); ?></button>
		</div>
		<?php
	}

	private function render_overview_shell( array $definition ): void {
		$summary  = (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_SUMMARY ] ?? '' );
		$steps    = $definition[ Build_Plan_Schema::KEY_STEPS ] ?? array();
		$overview = null;
		foreach ( is_array( $steps ) ? $steps : array() as $step ) {
			if ( is_array( $step ) && ( $step['step_type'] ?? '' ) === Build_Plan_Schema::STEP_TYPE_OVERVIEW ) {
				$items = $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
				if ( ! empty( $items ) && is_array( $items[0] ?? null ) ) {
					$overview = $items[0];
					break;
				}
			}
		}
		$payload       = is_array( $overview ) ? ( $overview['payload'] ?? array() ) : array();
		$planning_mode = (string) ( $payload['planning_mode'] ?? 'mixed' );
		$confidence    = (string) ( $payload['overall_confidence'] ?? 'medium' );
		?>
		<div class="aio-step-overview">
			<p class="aio-plan-summary"><?php echo \esc_html( ( $summary !== '' && $summary !== null ) ? $summary : __( 'No summary.', 'aio-page-builder' ) ); ?></p>
			<p><strong><?php \esc_html_e( 'Planning mode:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( $planning_mode ); ?> | <strong><?php \esc_html_e( 'Confidence:', 'aio-page-builder' ); ?></strong> <?php echo \esc_html( $confidence ); ?></p>
			<p class="aio-overview-actions"><a href="<?php echo \esc_url( \admin_url( 'admin.php?page=' . Build_Plans_Screen::SLUG . '&plan_id=' . \rawurlencode( (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ) ) . '&step=1' ) ); ?>" class="button button-primary"><?php \esc_html_e( 'Start review', 'aio-page-builder' ); ?></a></p>
		</div>
		<?php
	}

	private function render_hierarchy_shell( array $definition ): void {
		$site_flow = (string) ( $definition[ Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY ] ?? '' );
		?>
		<div class="aio-step-hierarchy">
			<p><?php echo \esc_html( ( $site_flow !== '' && $site_flow !== null ) ? $site_flow : __( 'No hierarchy or flow summary for this plan.', 'aio-page-builder' ) ); ?></p>
		</div>
		<?php
	}

	private function render_confirmation_shell( array $definition ): void {
		$status    = (string) ( $definition[ Build_Plan_Schema::KEY_STATUS ] ?? '' );
		$completed = $status === Build_Plan_Schema::STATUS_COMPLETED;
		if ( $completed ) {
			?>
			<div class="aio-step-confirmation aio-completion-state">
				<p class="aio-completion-banner"><?php \esc_html_e( 'Plan completed.', 'aio-page-builder' ); ?></p>
				<p><?php \esc_html_e( 'Counts and links to logs/export will be added in a later prompt.', 'aio-page-builder' ); ?></p>
			</div>
			<?php
		} else {
			?>
			<div class="aio-step-confirmation">
				<p><?php \esc_html_e( 'Review approved and denied items. Execution is started from the plan run/queue flow, not from this step.', 'aio-page-builder' ); ?></p>
			</div>
			<?php
		}
	}
}
