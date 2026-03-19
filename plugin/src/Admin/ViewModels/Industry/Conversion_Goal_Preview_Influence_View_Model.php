<?php
/**
 * View model for conversion-goal influence on section/page preview and detail (Prompt 513).
 * Exposes when conversion goal is influencing preview, composed docs, caution notes, and preset context.
 * Read-only; safe for admin preview. Escape on output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for conversion-goal influence on industry preview: goal label, refinement flags, caution notes, optional preset context.
 */
final class Conversion_Goal_Preview_Influence_View_Model {

	public const KEY_HAS_GOAL                    = 'has_goal';
	public const KEY_GOAL_KEY                    = 'goal_key';
	public const KEY_GOAL_LABEL                  = 'goal_label';
	public const KEY_HELPER_REFINEMENT_APPLIED   = 'helper_refinement_applied';
	public const KEY_ONEPAGER_REFINEMENT_APPLIED = 'onepager_refinement_applied';
	public const KEY_GOAL_CAUTION_NOTES          = 'goal_caution_notes';
	public const KEY_GOAL_PRESET_CONTEXT         = 'goal_preset_context';

	/** @var bool */
	private bool $has_goal;

	/** @var string */
	private string $goal_key;

	/** @var string */
	private string $goal_label;

	/** @var bool True when a goal section-helper overlay was applied for this section. */
	private bool $helper_refinement_applied;

	/** @var bool True when a goal page-one-pager overlay was applied for this template. */
	private bool $onepager_refinement_applied;

	/** @var list<string> Optional advisory goal-related caution or fit notes. */
	private array $goal_caution_notes;

	/** @var string Optional preset/bundle context (e.g. goal-aware preset overlay applied). */
	private string $goal_preset_context;

	/**
	 * @param bool         $has_goal
	 * @param string       $goal_key
	 * @param string       $goal_label
	 * @param bool         $helper_refinement_applied
	 * @param bool         $onepager_refinement_applied
	 * @param list<string> $goal_caution_notes
	 * @param string       $goal_preset_context
	 */
	public function __construct(
		bool $has_goal,
		string $goal_key,
		string $goal_label,
		bool $helper_refinement_applied = false,
		bool $onepager_refinement_applied = false,
		array $goal_caution_notes = array(),
		string $goal_preset_context = ''
	) {
		$this->has_goal                    = $has_goal;
		$this->goal_key                    = $goal_key;
		$this->goal_label                  = $goal_label;
		$this->helper_refinement_applied   = $helper_refinement_applied;
		$this->onepager_refinement_applied = $onepager_refinement_applied;
		$this->goal_caution_notes          = $goal_caution_notes;
		$this->goal_preset_context         = $goal_preset_context;
	}

	public function has_goal(): bool {
		return $this->has_goal;
	}

	public function get_goal_key(): string {
		return $this->goal_key;
	}

	public function get_goal_label(): string {
		return $this->goal_label;
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
	public function get_goal_caution_notes(): array {
		return $this->goal_caution_notes;
	}

	public function get_goal_preset_context(): string {
		return $this->goal_preset_context;
	}

	/**
	 * Empty influence when no conversion goal is set (fallback).
	 *
	 * @return self
	 */
	public static function none(): self {
		return new self( false, '', '', false, false, array(), '' );
	}

	/**
	 * Human-readable label for a goal key (launch set). Escape on output.
	 *
	 * @param string $goal_key Conversion goal key.
	 * @return string
	 */
	public static function goal_key_to_label( string $goal_key ): string {
		$map = array(
			'calls'         => 'Calls',
			'bookings'      => 'Bookings',
			'estimates'     => 'Estimates / quotes',
			'consultations' => 'Consultations',
			'valuations'    => 'Valuations',
			'lead_capture'  => 'Lead capture',
		);
		$k   = trim( $goal_key );
		return $map[ $k ] ?? $k;
	}

	/**
	 * For view layer (escape on output).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			self::KEY_HAS_GOAL                    => $this->has_goal,
			self::KEY_GOAL_KEY                    => $this->goal_key,
			self::KEY_GOAL_LABEL                  => $this->goal_label,
			self::KEY_HELPER_REFINEMENT_APPLIED   => $this->helper_refinement_applied,
			self::KEY_ONEPAGER_REFINEMENT_APPLIED => $this->onepager_refinement_applied,
			self::KEY_GOAL_CAUTION_NOTES          => $this->goal_caution_notes,
			self::KEY_GOAL_PRESET_CONTEXT         => $this->goal_preset_context,
		);
	}
}
