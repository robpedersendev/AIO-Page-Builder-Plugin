<?php
/**
 * View model for the Guided Repair review screen (Prompt 527). Holds repair candidates, replacements, warnings.
 * Read-only; escape on output. industry-guided-repair-workflow-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for repair review: list of candidates with issue summary, suggested ref/action, confidence, and action type.
 */
final class Industry_Guided_Repair_View_Model {

	public const KEY_CANDIDATES   = 'candidates';
	public const KEY_LINKS       = 'links';
	public const KEY_MESSAGE     = 'message';
	public const KEY_MESSAGE_TYPE = 'message_type';

	/** Issue source: health error. */
	public const SOURCE_HEALTH_ERROR = 'health_error';
	/** Issue source: health warning. */
	public const SOURCE_HEALTH_WARNING = 'health_warning';
	/** Issue source: override conflict. */
	public const SOURCE_OVERRIDE_CONFLICT = 'override_conflict';

	/** Action: no apply (advisory only). */
	public const ACTION_NONE = 'none';
	/** Action: apply suggested ref (profile merge). */
	public const ACTION_APPLY_REF = 'apply_ref';
	/** Action: migrate to replacement pack. */
	public const ACTION_MIGRATE = 'migrate';
	/** Action: activate (enable) pack. */
	public const ACTION_ACTIVATE_PACK = 'activate_pack';
	/** Action: resolve in Override Management (link only). */
	public const ACTION_LINK_OVERRIDE_MANAGEMENT = 'link_override_management';

	/** @var list<array{source: string, object_type: string, key: string, issue_summary: string, related_refs: list<string>, repair_suggestion: array|null, is_advisory_only: bool, action_type: string, conflict: array|null, profile_field: string, suggested_value: string}> */
	private array $candidates;

	/** @var array<string, string> */
	private array $links;

	private string $message;
	private string $message_type;

	/**
	 * @param list<array{source: string, object_type: string, key: string, issue_summary: string, related_refs: list<string>, repair_suggestion: array|null, is_advisory_only: bool, action_type: string, conflict: array|null, profile_field: string, suggested_value: string}> $candidates
	 * @param array<string, string> $links
	 */
	public function __construct(
		array $candidates = array(),
		array $links = array(),
		string $message = '',
		string $message_type = ''
	) {
		$this->candidates   = $candidates;
		$this->links         = $links;
		$this->message      = $message;
		$this->message_type = $message_type;
	}

	/** @return list<array{source: string, object_type: string, key: string, issue_summary: string, related_refs: list<string>, repair_suggestion: array|null, is_advisory_only: bool, action_type: string, conflict: array|null, profile_field: string, suggested_value: string}> */
	public function get_candidates(): array {
		return $this->candidates;
	}

	/** @return array<string, string> */
	public function get_links(): array {
		return $this->links;
	}

	public function get_message(): string {
		return $this->message;
	}

	public function get_message_type(): string {
		return $this->message_type;
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			self::KEY_CANDIDATES    => $this->candidates,
			self::KEY_LINKS         => $this->links,
			self::KEY_MESSAGE       => $this->message,
			self::KEY_MESSAGE_TYPE  => $this->message_type,
		);
	}
}
