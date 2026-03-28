<?php
/**
 * Central template-lab capability policy (REST, admin screen, admin-post handlers, domain session gates).
 *
 * Matrix (defaults): screen and REST list/create use {@see Capabilities::MANAGE_COMPOSITIONS} with
 * {@see Capabilities::current_user_can_or_site_admin()} so site admins keep access if role grants lag.
 * Session read/write and prompt submission require the same gate plus per-session ownership checks in handlers
 * (except elevated site operators, which may operate on any session). Approve/apply use target-specific registry
 * capabilities via {@see self::capability_for_approved_target_kind()}.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

use AIOPageBuilder\Domain\AI\TemplateLab\Template_Lab_Approved_Snapshot_Ref_Keys;

defined( 'ABSPATH' ) || exit;

final class Template_Lab_Access {

	public static function can_manage_template_lab(): bool {
		return Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_COMPOSITIONS );
	}

	public static function can_access_template_lab_shell(): bool {
		return self::can_manage_template_lab();
	}

	public static function can_use_template_lab_rest_routes(): bool {
		return self::can_manage_template_lab();
	}

	public static function can_submit_template_lab_prompts(): bool {
		return self::can_manage_template_lab();
	}

	public static function can_create_template_lab_sessions_via_admin_post(): bool {
		return self::can_manage_template_lab();
	}

	public static function capability_for_approved_target_kind( string $target_kind ): string {
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_COMPOSITION ) {
			return Capabilities::MANAGE_COMPOSITIONS;
		}
		if ( $target_kind === Template_Lab_Approved_Snapshot_Ref_Keys::TARGET_PAGE ) {
			return Capabilities::MANAGE_PAGE_TEMPLATES;
		}
		return Capabilities::MANAGE_SECTION_TEMPLATES;
	}

	public static function current_user_can_approve_or_apply_for_target( string $target_kind ): bool {
		return Capabilities::current_user_can_for_route( self::capability_for_approved_target_kind( $target_kind ) );
	}

	public static function can_view_template_lab_diagnostics(): bool {
		return Capabilities::current_user_can_for_route( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );
	}

	/**
	 * Session ownership: owner matches, or full site operator (manage_options / network super admin).
	 *
	 * @param array<string, mixed> $session Session row from {@see AI_Chat_Session_Repository_Interface::get_session()}.
	 */
	public static function actor_may_use_chat_session( int $actor_user_id, array $session ): bool {
		$owner = (int) ( $session['owner_user_id'] ?? 0 );
		if ( $actor_user_id > 0 && $owner === $actor_user_id ) {
			return true;
		}
		return self::actor_is_privileged_site_operator();
	}

	public static function actor_is_privileged_site_operator(): bool {
		if ( \current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( \function_exists( 'is_multisite' ) && \is_multisite() && \function_exists( 'is_super_admin' ) && \is_super_admin() ) {
			return true;
		}
		return false;
	}
}
