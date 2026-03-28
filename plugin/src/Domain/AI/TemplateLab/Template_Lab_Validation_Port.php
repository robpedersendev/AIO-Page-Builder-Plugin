<?php
/**
 * Indirection for template-lab validation (test doubles avoid network/schema fixtures).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Validation_Report;

/**
 * Wraps {@see \AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator} in production.
 */
interface Template_Lab_Validation_Port {

	public function validate( mixed $raw, string $schema_ref, bool $is_repair_attempt ): Validation_Report;
}
