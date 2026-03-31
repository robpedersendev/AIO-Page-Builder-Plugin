<?php
/**
 * Pure tab resolution for the Plans & analytics hub (deep links vs selected tab).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

/**
 * Keeps ?plan_id= / ?id= navigation testable without loading the full hub renderer.
 */
final class Plans_Hub_Tab_Resolver {

	/**
	 * When a build-plan deep link is present and the user may view plans, the Build Plans tab must win.
	 *
	 * @param string $tab                            Current tab after whitelist and capability checks.
	 * @param string $sanitized_plan_id_from_request plan_id query value (already sanitized).
	 * @param string $sanitized_id_from_request      id query value (already sanitized).
	 * @param bool   $user_can_view_build_plans      Effective capability for {@see Capabilities::VIEW_BUILD_PLANS}.
	 * @return string Tab key to render.
	 */
	public static function apply_deep_link_to_tab(
		string $tab,
		string $sanitized_plan_id_from_request,
		string $sanitized_id_from_request,
		bool $user_can_view_build_plans
	): string {
		$deep = $sanitized_plan_id_from_request !== '' ? $sanitized_plan_id_from_request : $sanitized_id_from_request;
		if ( $deep !== '' && $user_can_view_build_plans ) {
			return 'build_plans';
		}
		return $tab;
	}
}
