<?php
/**
 * Minimum design token pairs every build plan should carry (core style spec + template rendering).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

/**
 * Registry-backed pairs: merger skips pairs not allowed for the active spec.
 */
final class Design_Token_Required_Set {

	/**
	 * @var array<int, array{0: string, 1: string}> group, token short name
	 */
	public const REQUIRED_PAIRS = array(
		array( 'color', 'primary' ),
		array( 'color', 'surface' ),
		array( 'color', 'text' ),
		array( 'color', 'accent' ),
		array( 'color', 'background' ),
		array( 'typography', 'heading' ),
		array( 'typography', 'body' ),
		array( 'spacing', 'md' ),
		array( 'spacing', 'section' ),
		array( 'radius', 'button' ),
		array( 'radius', 'card' ),
		array( 'shadow', 'card' ),
	);

	/**
	 * Default proposed values when AI did not supply a token (by group).
	 *
	 * @param string $group Token group.
	 * @param string $name  Token name.
	 */
	public static function default_proposed_value( string $group, string $name ): string {
		if ( $group === 'color' ) {
			$map = array(
				'primary'    => '#2271b1',
				'surface'    => '#ffffff',
				'text'       => '#1d2327',
				'text-muted' => '#646970',
				'accent'     => '#135e96',
				'background' => '#f0f0f1',
			);
			return $map[ $name ] ?? '#2271b1';
		}
		if ( $group === 'typography' ) {
			return 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
		}
		if ( $group === 'spacing' ) {
			return '1rem';
		}
		if ( $group === 'radius' ) {
			return '6px';
		}
		if ( $group === 'shadow' ) {
			return $name === 'none' ? 'none' : '0 4px 12px rgba(0,0,0,0.08)';
		}
		return '';
	}
}
