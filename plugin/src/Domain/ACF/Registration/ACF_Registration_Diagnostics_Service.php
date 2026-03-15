<?php
/**
 * Bounded diagnostics for ACF conditional registration (Prompt 291).
 * Records registration mode, section-key count, cache usage, and full-registration path.
 * Admin/support only; no sensitive data or noisy public logging.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Registration;

defined( 'ABSPATH' ) || exit;

/**
 * Request-scoped registration diagnostics. Safe to call from resolvers and controller.
 */
final class ACF_Registration_Diagnostics_Service {

	/** Registration mode: front-end skip. */
	public const MODE_FRONT_END_SKIP = 'front_end_skip';

	/** Registration mode: existing-page edit. */
	public const MODE_EXISTING_PAGE = 'existing_page';

	/** Registration mode: new-page edit (template/composition). */
	public const MODE_NEW_PAGE = 'new_page';

	/** Registration mode: non-page admin (zero groups). */
	public const MODE_NON_PAGE_ADMIN = 'non_page_admin';

	/** @var bool Request-scoped: whether section-key cache was used for this resolution. */
	private bool $request_cache_used = false;

	/** @var array<string, mixed>|null Last recorded registration (mode, section_key_count, cache_used, full_registration_invoked). */
	private ?array $last_registration = null;

	/** @var callable(): bool Whether to record (default: is_admin). */
	private $admin_check;

	public function __construct( ?callable $admin_check = null ) {
		$this->admin_check = $admin_check ?? function (): bool {
			return is_admin();
		};
	}

	/**
	 * Sets whether the current resolution used the section-key cache. Called by resolvers.
	 *
	 * @param bool $used
	 */
	public function set_request_cache_used( bool $used ): void {
		$this->request_cache_used = $used;
	}

	/**
	 * Returns whether the current request's resolution used the cache.
	 *
	 * @return bool
	 */
	public function get_request_cache_used(): bool {
		return $this->request_cache_used;
	}

	/**
	 * Records the registration run. Call from bootstrap controller after deciding path.
	 * Only records when in admin to avoid storing on front-end.
	 *
	 * @param string $mode One of MODE_* constants.
	 * @param int    $section_key_count Number of section keys resolved (0 if skipped).
	 * @param bool   $cache_used Whether section-key cache was used.
	 * @param bool   $full_registration_invoked Whether register_all was invoked (unexpected in normal flow).
	 */
	public function record_registration( string $mode, int $section_key_count, bool $cache_used, bool $full_registration_invoked ): void {
		if ( ! ( $this->admin_check )() ) {
			return;
		}
		$this->last_registration = array(
			'mode'                        => $mode,
			'section_key_count'           => $section_key_count,
			'cache_used'                  => $cache_used,
			'full_registration_invoked'   => $full_registration_invoked,
		);
	}

	/**
	 * Returns the last recorded registration payload for support/debug. Null if none.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_last_registration(): ?array {
		return $this->last_registration !== null ? $this->last_registration : null;
	}
}
