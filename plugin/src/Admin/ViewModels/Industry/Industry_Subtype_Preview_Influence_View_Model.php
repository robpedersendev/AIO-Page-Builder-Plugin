<?php
/**
 * View model for subtype influence on section/page preview and detail (Prompt 441, industry-admin-screen-contract).
 * Exposes when subtype context is influencing the preview, composed docs, warnings, and recommendations.
 * Read-only; safe for admin preview. Escape on output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for subtype influence on industry preview: subtype label, refinement flags, caution notes, optional bundle context.
 */
final class Industry_Subtype_Preview_Influence_View_Model {

	public const KEY_HAS_SUBTYPE                 = 'has_subtype';
	public const KEY_SUBTYPE_KEY                  = 'subtype_key';
	public const KEY_SUBTYPE_LABEL                = 'subtype_label';
	public const KEY_SUBTYPE_SUMMARY              = 'subtype_summary';
	public const KEY_HELPER_REFINEMENT_APPLIED    = 'helper_refinement_applied';
	public const KEY_ONEPAGER_REFINEMENT_APPLIED  = 'onepager_refinement_applied';
	public const KEY_CAUTION_NOTES                = 'caution_notes';
	public const KEY_BUNDLE_CONTEXT               = 'bundle_context';

	/** @var bool */
	private bool $has_subtype;

	/** @var string */
	private string $subtype_key;

	/** @var string */
	private string $subtype_label;

	/** @var string */
	private string $subtype_summary;

	/** @var bool True when a subtype section-helper overlay was applied for this section. */
	private bool $helper_refinement_applied;

	/** @var bool True when a subtype page-one-pager overlay was applied for this template. */
	private bool $onepager_refinement_applied;

	/** @var list<string> Optional advisory caution or fit notes for the subtype. */
	private array $caution_notes;

	/** @var string Optional bundle context (e.g. selected subtype bundle label). */
	private string $bundle_context;

	/**
	 * @param bool   $has_subtype
	 * @param string $subtype_key
	 * @param string $subtype_label
	 * @param string $subtype_summary
	 * @param bool   $helper_refinement_applied
	 * @param bool   $onepager_refinement_applied
	 * @param list<string> $caution_notes
	 * @param string $bundle_context
	 */
	public function __construct(
		bool $has_subtype,
		string $subtype_key,
		string $subtype_label,
		string $subtype_summary,
		bool $helper_refinement_applied = false,
		bool $onepager_refinement_applied = false,
		array $caution_notes = array(),
		string $bundle_context = ''
	) {
		$this->has_subtype                 = $has_subtype;
		$this->subtype_key                 = $subtype_key;
		$this->subtype_label               = $subtype_label;
		$this->subtype_summary             = $subtype_summary;
		$this->helper_refinement_applied   = $helper_refinement_applied;
		$this->onepager_refinement_applied = $onepager_refinement_applied;
		$this->caution_notes               = $caution_notes;
		$this->bundle_context              = $bundle_context;
	}

	public function has_subtype(): bool {
		return $this->has_subtype;
	}

	public function get_subtype_key(): string {
		return $this->subtype_key;
	}

	public function get_subtype_label(): string {
		return $this->subtype_label;
	}

	public function get_subtype_summary(): string {
		return $this->subtype_summary;
	}

	public function is_helper_refinement_applied(): bool {
		return $this->helper_refinement_applied;
	}

	public function is_onepager_refinement_applied(): bool {
		return $this->onepager_refinement_applied;
	}

	/**
	 * @return list<string>
	 */
	public function get_caution_notes(): array {
		return $this->caution_notes;
	}

	public function get_bundle_context(): string {
		return $this->bundle_context;
	}

	/**
	 * Empty influence when no subtype is selected (for fallback).
	 *
	 * @return self
	 */
	public static function none(): self {
		return new self( false, '', '', '', false, false, array(), '' );
	}

	/**
	 * For view layer (escape on output).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			self::KEY_HAS_SUBTYPE                => $this->has_subtype,
			self::KEY_SUBTYPE_KEY               => $this->subtype_key,
			self::KEY_SUBTYPE_LABEL             => $this->subtype_label,
			self::KEY_SUBTYPE_SUMMARY           => $this->subtype_summary,
			self::KEY_HELPER_REFINEMENT_APPLIED => $this->helper_refinement_applied,
			self::KEY_ONEPAGER_REFINEMENT_APPLIED => $this->onepager_refinement_applied,
			self::KEY_CAUTION_NOTES              => $this->caution_notes,
			self::KEY_BUNDLE_CONTEXT            => $this->bundle_context,
		);
	}
}
