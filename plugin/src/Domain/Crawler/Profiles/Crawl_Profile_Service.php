<?php
/**
 * Bounded crawl profile definitions: limits, labels, and summary payload (spec §24, §24.5, §24.8, §59.7; Prompt 128).
 * Profiles only tune allowed bounds within the approved model; no relaxation of safety rules.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes approved crawl profiles, profile-specific limits, and stable summary payload for diagnostics.
 */
final class Crawl_Profile_Service {

	/** Spec §24.2: maximum pages per run (contract ceiling). */
	private const CONTRACT_MAX_PAGES = 500;

	/** Spec §24.2: maximum crawl depth (contract ceiling). */
	private const CONTRACT_MAX_DEPTH = 4;

	/** Profile definitions: key => [ label, description, max_pages, max_depth ]. All bounds <= contract. */
	private const PROFILES = array(
		Crawl_Profile_Keys::QUICK_CONTEXT_REFRESH => array(
			'label'       => 'Quick context refresh',
			'description' => 'Fewer pages and depth for fast site-context updates.',
			'max_pages'   => 50,
			'max_depth'   => 2,
		),
		Crawl_Profile_Keys::FULL_PUBLIC_BASELINE  => array(
			'label'       => 'Full public-site baseline',
			'description' => 'Spec default: up to 500 pages, depth 4.',
			'max_pages'   => 500,
			'max_depth'   => 4,
		),
		Crawl_Profile_Keys::SUPPORT_TRIAGE_CRAWL  => array(
			'label'       => 'Support triage crawl',
			'description' => 'Moderate bounds for support and diagnostics use.',
			'max_pages'   => 100,
			'max_depth'   => 3,
		),
	);

	/**
	 * Returns the approved profile key, or default if the given key is not approved.
	 * Prevents unsupported custom profiles from bypassing rules.
	 *
	 * @param string $key Requested profile key.
	 * @return string Approved key or Crawl_Profile_Keys::DEFAULT.
	 */
	public function resolve_profile_key( string $key ): string {
		$key = trim( $key );
		return Crawl_Profile_Keys::is_approved( $key ) ? $key : Crawl_Profile_Keys::DEFAULT;
	}

	/**
	 * Returns true if the given key is an approved profile.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function is_approved_profile( string $key ): bool {
		return Crawl_Profile_Keys::is_approved( trim( $key ) );
	}

	/**
	 * Returns profile-specific max pages (capped by contract).
	 *
	 * @param string $profile_key Approved profile key.
	 * @return int
	 */
	public function get_max_pages_for_profile( string $profile_key ): int {
		$key = $this->resolve_profile_key( $profile_key );
		$def = self::PROFILES[ $key ] ?? null;
		if ( $def === null ) {
			return self::CONTRACT_MAX_PAGES;
		}
		$max = (int) ( $def['max_pages'] ?? self::CONTRACT_MAX_PAGES );
		return min( max( 1, $max ), self::CONTRACT_MAX_PAGES );
	}

	/**
	 * Returns profile-specific max depth (capped by contract).
	 *
	 * @param string $profile_key Approved profile key.
	 * @return int
	 */
	public function get_max_depth_for_profile( string $profile_key ): int {
		$key = $this->resolve_profile_key( $profile_key );
		$def = self::PROFILES[ $key ] ?? null;
		if ( $def === null ) {
			return self::CONTRACT_MAX_DEPTH;
		}
		$max = (int) ( $def['max_depth'] ?? self::CONTRACT_MAX_DEPTH );
		return min( max( 1, $max ), self::CONTRACT_MAX_DEPTH );
	}

	/**
	 * Returns list of profiles for admin selection. Keys and labels only.
	 *
	 * @return array<int, array{key: string, label: string}>
	 */
	public function list_profiles_for_selection(): array {
		$out = array();
		foreach ( Crawl_Profile_Keys::all() as $key ) {
			$def   = self::PROFILES[ $key ] ?? null;
			$out[] = array(
				'key'   => $key,
				'label' => $def !== null ? (string) $def['label'] : $key,
			);
		}
		return $out;
	}

	/**
	 * Returns a single crawl_profile payload for the given key (for session metadata / API).
	 *
	 * @param string $profile_key Approved profile key.
	 * @return array{key: string, label: string, description: string, max_pages: int, max_depth: int}
	 */
	public function get_profile_payload( string $profile_key ): array {
		$key = $this->resolve_profile_key( $profile_key );
		$def = self::PROFILES[ $key ] ?? array(
			'label'       => $key,
			'description' => '',
			'max_pages'   => self::CONTRACT_MAX_PAGES,
			'max_depth'   => self::CONTRACT_MAX_DEPTH,
		);
		return array(
			'key'         => $key,
			'label'       => (string) ( $def['label'] ?? $key ),
			'description' => (string) ( $def['description'] ?? '' ),
			'max_pages'   => $this->get_max_pages_for_profile( $key ),
			'max_depth'   => $this->get_max_depth_for_profile( $key ),
		);
	}

	/**
	 * Returns stable crawl_profile_summary payload: list of profiles with bounds and labeling (diagnostics / admin).
	 *
	 * @return array{profiles: array<int, array{key: string, label: string, description: string, max_pages: int, max_depth: int}>, contract_max_pages: int, contract_max_depth: int}
	 */
	public function get_profile_summary(): array {
		$profiles = array();
		foreach ( Crawl_Profile_Keys::all() as $key ) {
			$profiles[] = $this->get_profile_payload( $key );
		}
		return array(
			'profiles'           => $profiles,
			'contract_max_pages' => self::CONTRACT_MAX_PAGES,
			'contract_max_depth' => self::CONTRACT_MAX_DEPTH,
		);
	}
}
