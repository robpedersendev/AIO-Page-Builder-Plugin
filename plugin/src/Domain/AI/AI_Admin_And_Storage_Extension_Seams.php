<?php
/**
 * Code-adjacent topology for template-lab, scoped admin chat UX, and provider routing.
 *
 * Spec anchors: §0.5 / §2.3.7 (not a generic chatbot—UX must stay bounded to structured planning
 * and template/composition authoring); §10.5 (AI Run = auditable per-provider interaction;
 * multi-turn UX should link multiple runs or explicit session records); §14 (compositions stay
 * registry-validated); §25 (normalized provider contract, failover); §26 (prompt-pack versioning
 * on runs).
 *
 * Bootstrap: Plugin::run() builds Service_Container via Module_Registrar; admin uses non-null
 * container from Plugin::register_admin_menu() → Admin_Menu( $container ). admin_init registers
 * Admin_Post_Handler_Registrar::register_all( $container ) and Admin_Early_Redirect_Coordinator.
 * Do not add nullable Service_Container on core admin screens or admin-post paths.
 *
 * Extension seams (where to attach later work):
 * - Provider router: Domain\AI\Routing\Default_AI_Provider_Router registered as ai_provider_router;
 *   feature code resolves task → primary provider id (+ optional model override); drivers stay in
 *   AI_Provider_Drivers_Provider (openai_provider_driver, anthropic_provider_driver). Failover
 *   remains Provider_Failover_Service + container driver resolution.
 * - Onboarding planning orchestration: Onboarding_Planning_Request_Orchestrator (uses router +
 *   AI_Structured_Response_Guard). Build-plan generation stays downstream of validation; executor
 *   unchanged.
 * - Template-lab admin UI: new screens registered in Admin_Menu / hub routing same as
 *   Compositions_Screen (MANAGE_COMPOSITIONS) and template library caps; REST lives beside existing
 *   admin REST patterns with permission_callback + nonces for mutations.
 * - Chat session/message persistence: see AI_Chat_Session_Repository_Interface; canonical registry
 *   state still written only via explicit Apply through Composition/Page/Section repositories—not
 *   from chat rows alone. External thread ids = UX transport metadata linked to local session rows;
 *   approved JSON snapshots stored as today’s registry payloads.
 * - Structured draft schema refs: AI_Template_Lab_Draft_Schema_Refs + AI_Output_Validator; translate
 *   drafts to canonical shapes in future apply services, not inside drivers.
 *
 * Risks to avoid: provider branching in UI; secrets in repositories or artifacts; REST without
 * permission_callback; chat content bypassing validation/approval for canonical writes.
 *
 * Template-lab MVP verification (release / QA): activate without provider secrets; open template library
 * hub (template lab tab for elevated admins per cap matrix); create and fork a session; approve + apply
 * with registry drift guard; create a build plan from a completed run with optional template-lab session id;
 * export optional template-lab snapshot refs; confirm uninstall/registry policy for chat + telemetry options.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Marker type: holds no runtime API; documents extension topology for implementers.
 */
final class AI_Admin_And_Storage_Extension_Seams {

	private function __construct() {
	}
}
