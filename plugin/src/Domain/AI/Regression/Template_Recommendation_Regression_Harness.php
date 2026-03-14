<?php
/**
 * Regression harness for template-family recommendations (spec §58.3, §60.5, Prompt 211).
 * Compares recommendation payloads against golden cases: class fit, family fit, CTA-law alignment, explanation.
 * Internal QA only; no execution changes. Fixture data synthetic and versioned.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Regression;

defined( 'ABSPATH' ) || exit;

/**
 * Runs template-recommendation regression: load golden fixture, compare recommendation to expected.
 */
final class Template_Recommendation_Regression_Harness {

	/** Fixture key: case identifier. */
	public const FIXTURE_CASE_ID = 'case_id';

	/** Fixture key: scenario (top_level, hub, nested_hub, child_detail). */
	public const FIXTURE_SCENARIO = 'scenario';

	/** Fixture key: fixture schema version. */
	public const FIXTURE_VERSION = 'fixture_version';

	/** Fixture key: recommendation payload (actual or synthetic). */
	public const FIXTURE_RECOMMENDATION = 'recommendation';

	/** Fixture key: expected constraints. */
	public const FIXTURE_EXPECTED = 'expected';

	/** Expected key: template_category_class (string or list of allowed). */
	public const EXPECTED_CATEGORY_CLASS = 'template_category_class';

	/** Expected key: allowed_template_families (optional list). */
	public const EXPECTED_ALLOWED_FAMILIES = 'allowed_template_families';

	/** Expected key: cta_law_aligned (optional bool). */
	public const EXPECTED_CTA_LAW_ALIGNED = 'cta_law_aligned';

	/** Expected key: require_explanation (optional bool). */
	public const EXPECTED_REQUIRE_EXPLANATION = 'require_explanation';

	/** Recommendation may be under proposed_template_summary. */
	public const RECOMMENDATION_TEMPLATE_KEY = 'template_key';
	public const RECOMMENDATION_CATEGORY_CLASS = 'template_category_class';
	public const RECOMMENDATION_TEMPLATE_FAMILY = 'template_family';
	public const RECOMMENDATION_SELECTION_REASON = 'template_selection_reason';

	/** @var string Base path for fixture files. */
	private string $fixtures_base_path;

	public function __construct( string $fixtures_base_path = '' ) {
		$this->fixtures_base_path = rtrim( str_replace( '\\', '/', $fixtures_base_path ), '/' );
	}

	/**
	 * Runs regression for one golden fixture (array or path to JSON).
	 *
	 * @param array<string, mixed>|string $fixture Fixture array or path to JSON.
	 * @return Template_Recommendation_Regression_Result
	 */
	public function run( $fixture ): Template_Recommendation_Regression_Result {
		$data = is_string( $fixture ) ? $this->load_fixture_file( $fixture ) : $fixture;
		if ( ! is_array( $data ) ) {
			return $this->result_invalid( array( 'case_id' => '', 'scenario' => '', 'fixture_version' => '0', 'ran_at' => gmdate( 'Y-m-d\TH:i:s\Z' ) ), 'Fixture must be array or path to JSON.' );
		}
		return $this->run_from_array( $data );
	}

	/**
	 * Runs regression from parsed fixture array.
	 *
	 * @param array<string, mixed> $data
	 * @return Template_Recommendation_Regression_Result
	 */
	public function run_from_array( array $data ): Template_Recommendation_Regression_Result {
		$case_id    = (string) ( $data[ self::FIXTURE_CASE_ID ] ?? 'unknown' );
		$scenario   = (string) ( $data[ self::FIXTURE_SCENARIO ] ?? '' );
		$version    = (string) ( $data[ self::FIXTURE_VERSION ] ?? '0' );
		$recommendation = $data[ self::FIXTURE_RECOMMENDATION ] ?? null;
		$expected   = $data[ self::FIXTURE_EXPECTED ] ?? null;

		$ran_at = gmdate( 'Y-m-d\TH:i:s\Z' );
		$regression_run = array(
			'case_id'         => $case_id,
			'scenario'        => $scenario,
			'fixture_version' => $version,
			'ran_at'          => $ran_at,
		);

		if ( ! is_array( $expected ) ) {
			return $this->result_invalid( $regression_run, 'Fixture expected must be an array.' );
		}
		if ( ! is_array( $recommendation ) ) {
			return $this->result_invalid( $regression_run, 'Fixture recommendation must be an array.' );
		}

		$rec = $this->normalize_recommendation( $recommendation );
		$class_fit   = $this->check_class_fit( $rec, $expected );
		$family_fit  = $this->check_family_fit( $rec, $expected );
		$cta_aligned = $this->check_cta_law_aligned( $rec, $expected, $data );
		$explanation_fit = $this->check_explanation_fit( $rec, $expected );

		$outcome = Template_Recommendation_Regression_Result::OUTCOME_PASS;
		$message = 'Template recommendation regression pass: class, family, and explanation fit.';
		$details = array();

		if ( ! $class_fit ) {
			$outcome = Template_Recommendation_Regression_Result::OUTCOME_REGRESSION;
			$message = 'Class fit regression: template_category_class does not match expected.';
			$details['class_mismatch'] = array(
				'expected' => $expected[ self::EXPECTED_CATEGORY_CLASS ] ?? null,
				'actual'   => $rec[ self::RECOMMENDATION_CATEGORY_CLASS ] ?? null,
			);
		}
		if ( ! $family_fit ) {
			$outcome = Template_Recommendation_Regression_Result::OUTCOME_REGRESSION;
			$message = 'Family fit regression: template_family not in allowed set.';
			$details['family_mismatch'] = array(
				'allowed' => $expected[ self::EXPECTED_ALLOWED_FAMILIES ] ?? array(),
				'actual'  => $rec[ self::RECOMMENDATION_TEMPLATE_FAMILY ] ?? null,
			);
		}
		if ( $cta_aligned === false ) {
			$outcome = Template_Recommendation_Regression_Result::OUTCOME_REGRESSION;
			$message = 'CTA-law alignment regression: expected CTA-compliant recommendation.';
			$details['cta_law'] = array( 'expected_aligned' => true, 'actual' => false );
		}
		if ( ! $explanation_fit ) {
			$outcome = Template_Recommendation_Regression_Result::OUTCOME_REGRESSION;
			$message = 'Explanation fit regression: selection reason or summary missing when required.';
			$details['explanation_missing'] = true;
		}

		return new Template_Recommendation_Regression_Result(
			$outcome,
			$regression_run,
			$class_fit,
			$family_fit,
			$cta_aligned,
			$explanation_fit,
			$message,
			$details
		);
	}

	/**
	 * Loads fixture from JSON file. Path may be relative to fixtures_base_path or absolute.
	 *
	 * @param string $path
	 * @return array<string, mixed>|null
	 */
	public function load_fixture_file( string $path ): ?array {
		$path = str_replace( '\\', '/', $path );
		if ( $this->fixtures_base_path !== '' && $path[0] !== '/' && preg_match( '#^[A-Za-z]:#', $path ) === 0 ) {
			$path = $this->fixtures_base_path . '/' . $path;
		}
		if ( ! is_readable( $path ) ) {
			return null;
		}
		$raw = file_get_contents( $path );
		if ( $raw === false ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Normalizes recommendation: may be raw item with proposed_template_summary or flat keys.
	 *
	 * @param array<string, mixed> $recommendation
	 * @return array{template_key: string, template_category_class: string, template_family: string, template_selection_reason: string}
	 */
	private function normalize_recommendation( array $recommendation ): array {
		$summary = $recommendation['proposed_template_summary'] ?? $recommendation;
		$summary = is_array( $summary ) ? $summary : array();
		return array(
			self::RECOMMENDATION_TEMPLATE_KEY       => (string) ( $summary['template_key'] ?? $recommendation['template_key'] ?? '' ),
			self::RECOMMENDATION_CATEGORY_CLASS      => (string) ( $summary['template_category_class'] ?? $recommendation['template_category_class'] ?? '' ),
			self::RECOMMENDATION_TEMPLATE_FAMILY     => (string) ( $summary['template_family'] ?? $recommendation['template_family'] ?? '' ),
			self::RECOMMENDATION_SELECTION_REASON   => (string) ( $recommendation['template_selection_reason'] ?? $summary['template_selection_reason'] ?? '' ),
		);
	}

	private function check_class_fit( array $rec, array $expected ): bool {
		$expected_class = $expected[ self::EXPECTED_CATEGORY_CLASS ] ?? null;
		if ( $expected_class === null || $expected_class === '' ) {
			return true;
		}
		$actual = $rec[ self::RECOMMENDATION_CATEGORY_CLASS ] ?? '';
		if ( is_array( $expected_class ) ) {
			return in_array( $actual, $expected_class, true );
		}
		return $actual === (string) $expected_class;
	}

	private function check_family_fit( array $rec, array $expected ): bool {
		$allowed = $expected[ self::EXPECTED_ALLOWED_FAMILIES ] ?? null;
		if ( $allowed === null || ! is_array( $allowed ) || empty( $allowed ) ) {
			return true;
		}
		$actual = $rec[ self::RECOMMENDATION_TEMPLATE_FAMILY ] ?? '';
		return in_array( $actual, $allowed, true );
	}

	/**
	 * CTA-law: when expected.cta_law_aligned is true, fixture may provide template_metadata to verify; else we trust fixture.
	 *
	 * @param array $rec
	 * @param array $expected
	 * @param array $data Full fixture (for template_metadata).
	 * @return bool|null True aligned, false not, null not checked.
	 */
	private function check_cta_law_aligned( array $rec, array $expected, array $data ): ?bool {
		if ( ! array_key_exists( self::EXPECTED_CTA_LAW_ALIGNED, $expected ) ) {
			return null;
		}
		$expected_aligned = (bool) $expected[ self::EXPECTED_CTA_LAW_ALIGNED ];
		$metadata = $data['template_metadata'] ?? null;
		if ( is_array( $metadata ) && isset( $metadata['min_cta'], $metadata['last_section_cta'] ) ) {
			$min = (int) $metadata['min_cta'];
			$last_cta = (bool) $metadata['last_section_cta'];
			$aligned = $min >= 3 && $last_cta;
			return $aligned === $expected_aligned;
		}
		return null;
	}

	private function check_explanation_fit( array $rec, array $expected ): bool {
		if ( empty( $expected[ self::EXPECTED_REQUIRE_EXPLANATION ] ) ) {
			return true;
		}
		$reason = trim( $rec[ self::RECOMMENDATION_SELECTION_REASON ] ?? '' );
		return $reason !== '';
	}

	private function result_invalid( array $regression_run, string $message ): Template_Recommendation_Regression_Result {
		return new Template_Recommendation_Regression_Result(
			Template_Recommendation_Regression_Result::OUTCOME_FAIL,
			$regression_run,
			false,
			false,
			null,
			false,
			$message,
			array( 'fixture_invalid' => true )
		);
	}
}
