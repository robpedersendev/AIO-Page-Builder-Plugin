<?php
/**
 * Result of page template registry create/update/deprecate operations.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

/**
 * Success: post_id > 0, definition present. Failure: errors list, post_id 0.
 */
final class Page_Template_Registry_Result {

	/** @var bool */
	public readonly bool $success;

	/** @var array<int, string> */
	public readonly array $errors;

	/** @var int */
	public readonly int $post_id;

	/** @var array<string, mixed>|null */
	public readonly ?array $definition;

	public function __construct( bool $success, array $errors, int $post_id, ?array $definition = null ) {
		$this->success    = $success;
		$this->errors     = $errors;
		$this->post_id    = $post_id;
		$this->definition = $definition;
	}

	public static function success( int $post_id, array $definition ): self {
		return new self( true, array(), $post_id, $definition );
	}

	public static function failure( array $errors, int $post_id = 0 ): self {
		return new self( false, $errors, $post_id, null );
	}
}
