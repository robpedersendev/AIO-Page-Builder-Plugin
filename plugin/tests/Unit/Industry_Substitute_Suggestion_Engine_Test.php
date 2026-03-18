<?php
/**
 * Unit tests for Industry_Substitute_Suggestion_Engine and Industry_Substitute_Suggestion_Result (Prompt 378).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Page_Template_Recommendation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Section_Recommendation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Engine;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Substitute_Suggestion_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Substitute_Suggestion_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Substitute_Suggestion_Engine.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Section_Recommendation_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Page_Template_Recommendation_Result.php';

final class Industry_Substitute_Suggestion_Engine_Test extends TestCase {

	/** @var Industry_Substitute_Suggestion_Engine */
	private $engine;

	protected function setUp(): void {
		parent::setUp();
		$this->engine = new Industry_Substitute_Suggestion_Engine();
	}

	public function test_result_create_returns_shape(): void {
		$r = Industry_Substitute_Suggestion_Result::create( 'sec_a', 'sec_b', Industry_Substitute_Suggestion_Result::REASON_SAME_FAMILY_BETTER_FIT, 25, array( 'cta_mismatch' ) );
		$this->assertSame( 'sec_a', $r[ Industry_Substitute_Suggestion_Result::KEY_ORIGINAL_KEY ] );
		$this->assertSame( 'sec_b', $r[ Industry_Substitute_Suggestion_Result::KEY_SUGGESTED_REPLACEMENT_KEY ] );
		$this->assertSame( 25, $r[ Industry_Substitute_Suggestion_Result::KEY_FIT_SCORE_DELTA ] );
		$this->assertContains( 'cta_mismatch', $r[ Industry_Substitute_Suggestion_Result::KEY_WARNING_FLAGS ] );
	}

	public function test_result_from_array_normalizes(): void {
		$r = Industry_Substitute_Suggestion_Result::from_array(
			array(
				'original_key'              => 'x',
				'suggested_replacement_key' => 'y',
			)
		);
		$this->assertSame( 'x', $r[ Industry_Substitute_Suggestion_Result::KEY_ORIGINAL_KEY ] );
		$this->assertSame( 'y', $r[ Industry_Substitute_Suggestion_Result::KEY_SUGGESTED_REPLACEMENT_KEY ] );
		$this->assertSame( Industry_Substitute_Suggestion_Result::REASON_RECOMMENDED_ALTERNATIVE, $r[ Industry_Substitute_Suggestion_Result::KEY_SUBSTITUTE_REASON ] );
		$this->assertSame( 0, $r[ Industry_Substitute_Suggestion_Result::KEY_FIT_SCORE_DELTA ] );
	}

	public function test_section_substitutes_empty_when_fit_recommended(): void {
		$result      = new Industry_Section_Recommendation_Result(
			array(
				array(
					'section_key'          => 's1',
					'score'                => 50,
					'fit_classification'   => 'recommended',
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'warning_flags'        => array(),
				),
			)
		);
		$suggestions = $this->engine->suggest_section_substitutes( 's1', 'recommended', $result, array(), 5 );
		$this->assertSame( array(), $suggestions );
	}

	public function test_section_substitutes_returns_better_fit_same_family_first(): void {
		$items       = array(
			array(
				'section_key'          => 'discouraged_1',
				'score'                => -20,
				'fit_classification'   => 'discouraged',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'warning_flags'        => array(),
			),
			array(
				'section_key'          => 'rec_same_fam',
				'score'                => 40,
				'fit_classification'   => 'recommended',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'warning_flags'        => array(),
			),
			array(
				'section_key'          => 'rec_other',
				'score'                => 50,
				'fit_classification'   => 'recommended',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'warning_flags'        => array(),
			),
		);
		$result      = new Industry_Section_Recommendation_Result( $items );
		$defs        = array(
			array(
				'internal_key'           => 'discouraged_1',
				'section_purpose_family' => 'offer',
			),
			array(
				'internal_key'           => 'rec_same_fam',
				'section_purpose_family' => 'offer',
			),
			array(
				'internal_key'           => 'rec_other',
				'section_purpose_family' => 'explainer',
			),
		);
		$suggestions = $this->engine->suggest_section_substitutes( 'discouraged_1', 'discouraged', $result, $defs, 5 );
		$this->assertGreaterThan( 0, count( $suggestions ) );
		$this->assertSame( 'discouraged_1', $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_ORIGINAL_KEY ] );
		$this->assertSame( 'rec_same_fam', $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_SUGGESTED_REPLACEMENT_KEY ] );
		$this->assertSame( Industry_Substitute_Suggestion_Result::REASON_SAME_FAMILY_BETTER_FIT, $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_SUBSTITUTE_REASON ] );
		$this->assertSame( 60, $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_FIT_SCORE_DELTA ] );
	}

	public function test_template_substitutes_empty_when_no_recommended_candidates(): void {
		$result      = new Industry_Page_Template_Recommendation_Result(
			array(
				array(
					'page_template_key'    => 't_weak',
					'score'                => 5,
					'fit_classification'   => 'allowed_weak_fit',
					'explanation_reasons'  => array(),
					'industry_source_refs' => array(),
					'hierarchy_fit'        => '',
					'lpagery_fit'          => '',
					'warning_flags'        => array(),
				),
			)
		);
		$suggestions = $this->engine->suggest_template_substitutes( 't_weak', 'allowed_weak_fit', $result, array(), 5 );
		$this->assertSame( array(), $suggestions );
	}

	public function test_template_substitutes_returns_ordered_by_family_then_score(): void {
		$items       = array(
			array(
				'page_template_key'    => 't_weak',
				'score'                => 5,
				'fit_classification'   => 'allowed_weak_fit',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'hierarchy_fit'        => '',
				'lpagery_fit'          => '',
				'warning_flags'        => array(),
			),
			array(
				'page_template_key'    => 't_rec_same',
				'score'                => 35,
				'fit_classification'   => 'recommended',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'hierarchy_fit'        => '',
				'lpagery_fit'          => '',
				'warning_flags'        => array(),
			),
			array(
				'page_template_key'    => 't_rec_other',
				'score'                => 40,
				'fit_classification'   => 'recommended',
				'explanation_reasons'  => array(),
				'industry_source_refs' => array(),
				'hierarchy_fit'        => '',
				'lpagery_fit'          => '',
				'warning_flags'        => array(),
			),
		);
		$result      = new Industry_Page_Template_Recommendation_Result( $items );
		$defs        = array(
			array(
				'internal_key'    => 't_weak',
				'template_family' => 'landing',
			),
			array(
				'internal_key'    => 't_rec_same',
				'template_family' => 'landing',
			),
			array(
				'internal_key'    => 't_rec_other',
				'template_family' => 'hub',
			),
		);
		$suggestions = $this->engine->suggest_template_substitutes( 't_weak', 'allowed_weak_fit', $result, $defs, 5 );
		$this->assertCount( 2, $suggestions );
		$this->assertSame( 't_rec_same', $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_SUGGESTED_REPLACEMENT_KEY ] );
		$this->assertSame( Industry_Substitute_Suggestion_Result::REASON_SAME_FAMILY_BETTER_FIT, $suggestions[0][ Industry_Substitute_Suggestion_Result::KEY_SUBSTITUTE_REASON ] );
	}
}
