<?php
/**
 * Central template-lab capability policy (REST, admin screen, admin-post handlers align here).
 *
 * Matrix (defaults): screen and REST list/create use {@see Capabilities::MANAGE_COMPOSITIONS} with
 * {@see Capabilities::current_user_can_or_site_admin()} so site admins keep access if role grants lag.
 * Session read/write and prompt submission require the same gate plus per-session ownership checks in handlers
 * (except manage_options, which may operate on any session). Approve/apply remain on dedicated admin-post + apply service
 * with the same composition capability and nonces.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Config;

defined( 'ABSPATH' ) || exit;

final class Template_Lab_Access {

	public static function can_manage_template_lab(): bool {
		return Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_COMPOSITIONS );
	}
}
