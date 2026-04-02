<?php
/**
 * Builds design_token plan item arrays (generator, minimum merge, manual add/edit).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;

/**
 * Normalizes item shape to match {@see Build_Plan_Item_Generator} output.
 */
final class Design_Token_Plan_Item_Assembler {

	/**
	 * @param string $item_id        Stable item id.
	 * @param string $group          Token group.
	 * @param string $name           Token short name.
	 * @param string $proposed_value Sanitized proposed CSS value.
	 * @param string $rationale      Human rationale.
	 * @param int    $source_index   Source row index (-1 for synthetic).
	 * @param string $confidence     Payload confidence label.
	 * @return array<string, mixed>
	 */
	public static function item(
		string $item_id,
		string $group,
		string $name,
		string $proposed_value,
		string $rationale,
		int $source_index = -1,
		string $confidence = 'medium'
	): array {
		$payload = array(
			'token_group'    => $group,
			'token_name'     => $name,
			'proposed_value' => $proposed_value,
			'rationale'      => $rationale,
			'confidence'     => $confidence,
		);
		$item    = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID        => $item_id,
			Build_Plan_Item_Schema::KEY_ITEM_TYPE      => Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
			Build_Plan_Item_Schema::KEY_PAYLOAD        => $payload,
			Build_Plan_Item_Schema::KEY_SOURCE_SECTION => Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS,
			Build_Plan_Item_Schema::KEY_SOURCE_INDEX   => $source_index,
			Build_Plan_Item_Schema::KEY_STATUS         => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_CONFIDENCE     => $confidence,
		);
		return $item;
	}
}
