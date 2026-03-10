<?php
/**
 * Storage classifications for the strategy matrix (spec §8, §9). Use for validation and docs only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Stable constants for retention, sensitivity, storage primitive, and data owner.
 * See docs/contracts/storage-strategy-matrix.md. No storage logic here.
 */
final class Storage_Classifications {

	// --- Retention (spec §8.6) ---

	public const RETENTION_PERMANENT_UNTIL_USER_DELETION = 'permanent_until_user_deletion';
	public const RETENTION_LONG_LIVED_OPERATIONAL       = 'long_lived_operational';
	public const RETENTION_MEDIUM_LIVED_OPERATIONAL      = 'medium_lived_operational';
	public const RETENTION_SHORT_LIVED_OPERATIONAL       = 'short_lived_operational';
	public const RETENTION_EPHEMERAL_CACHE               = 'ephemeral_cache';
	public const RETENTION_EXPORT_PACKAGE_ONLY           = 'export_package_only';
	public const RETENTION_UNINSTALL_REMOVABLE          = 'uninstall_removable';
	public const RETENTION_UNINSTALL_PRESERVED_BY_CHOICE = 'uninstall_preserved_by_choice';

	// --- Sensitivity (spec §8.7) ---

	public const SENSITIVITY_PUBLIC_CONTENT                 = 'public_content';
	public const SENSITIVITY_INTERNAL_OPERATIONAL          = 'internal_operational';
	public const SENSITIVITY_ADMIN_VISIBLE_RESTRICTED      = 'admin_visible_restricted';
	public const SENSITIVITY_PRIVILEGED_RESTRICTED         = 'privileged_restricted';
	public const SENSITIVITY_SECRET                        = 'secret';
	public const SENSITIVITY_PROHIBITED_FROM_REPORTING     = 'prohibited_from_reporting';
	public const SENSITIVITY_PROHIBITED_FROM_EXPORT        = 'prohibited_from_export_without_explicit_intent';

	// --- Storage primitive (spec §9) ---

	public const PRIMITIVE_OPTIONS       = 'options';
	public const PRIMITIVE_CPT            = 'cpt';
	public const PRIMITIVE_POST_META     = 'post_meta';
	public const PRIMITIVE_CUSTOM_TABLE  = 'custom_table';
	public const PRIMITIVE_USER_META     = 'user_meta';
	public const PRIMITIVE_TRANSIENT    = 'transient';
	public const PRIMITIVE_UPLOADS       = 'uploads';
	public const PRIMITIVE_UPLOADS_ZIP   = 'uploads_zip';
	public const PRIMITIVE_SEGREGATED    = 'segregated'; // Secrets; not exportable.

	// --- Owner ---

	public const OWNER_HUMAN  = 'human';
	public const OWNER_SYSTEM = 'system';
	public const OWNER_MIXED  = 'mixed';

	// --- Scope ---

	public const SCOPE_SITE   = 'site';
	public const SCOPE_USER   = 'user';
	public const SCOPE_OBJECT = 'object';
}
