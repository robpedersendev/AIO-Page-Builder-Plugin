<?php
/**
 * Default template-lab validation adapter (AI_Output_Validator).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\TemplateLab;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\AI_Output_Validator;
use AIOPageBuilder\Domain\AI\Validation\Validation_Report;

final class Template_Lab_Validation_Port_Default implements Template_Lab_Validation_Port {

	private AI_Output_Validator $validator;

	public function __construct( AI_Output_Validator $validator ) {
		$this->validator = $validator;
	}

	public function validate( mixed $raw, string $schema_ref, bool $is_repair_attempt ): Validation_Report {
		return $this->validator->validate( $raw, $schema_ref, $is_repair_attempt );
	}
}
