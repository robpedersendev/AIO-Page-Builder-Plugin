# Demo Fixture Guide

**Document type:** QA guide for the generic demo fixture and seed-data generator (spec §56.4, §60.7; Prompt 130).  
**Purpose:** Deterministic synthetic data for registries, profile, crawl summary, AI runs, Build Plans, logs, export, and (optionally) the template showcase pack.

---

## 1. Scope

- **Generator:** `AIOPageBuilder\Domain\Fixtures\Demo_Fixture_Generator`
- **Result type:** `Demo_Fixture_Result`
- **Output:** All payloads are tagged synthetic (`_synthetic`). No real customer data, no secrets, no external calls.

---

## 2. Domains

| Option | Domain | Content |
|--------|--------|--------|
| `include_registries` | Registry bundle | Sections, page templates, compositions, documentation, snapshots (from Registry_Fixture_Builder). |
| `include_profile` | Brand/business profile | Profile_Schema-shaped fixture. |
| `include_crawl` | Crawl summary | Sample page records (crawl_run_id, url, title_snapshot, page_classification, etc.). |
| `include_ai_runs` | AI run | Run metadata and artifact placeholders (no real provider/keys). |
| `include_build_plans` | Build Plans | One or more Build Plan definitions conforming to Build_Plan_Schema. |
| `include_logs` | Logs | Sample log entries (structure only). |
| `include_export` | Export | Export_Result-shaped example. |
| `include_template_showcase` | Template showcase | Full template showcase pack (see [template-showcase-fixture-guide.md](template-showcase-fixture-guide.md)). |

By default only `include_template_showcase` is false; all other domains are included when their flag is true.

---

## 3. Usage

```php
$gen   = new \AIOPageBuilder\Domain\Fixtures\Demo_Fixture_Generator();
$result = $gen->generate(); // or $gen->generate( array( 'include_build_plans' => false, 'include_template_showcase' => true ) )
$result->is_success();
$result->get_counts();
$result->get_summary();
$result->to_payload();
```

---

## 4. Template showcase (Prompt 201)

To include the template-focused demo pack (representative section/page templates, compositions, compare sets, Build Plan template recommendation items), pass:

```php
$result = $gen->generate( array( 'include_template_showcase' => true ) );
```

The summary will contain `template_showcase` with manifest, sections, page_templates, compositions, build_plan_recommendation_items, and compare_sets. See **template-showcase-fixture-guide.md** for schema, application steps, and example manifest.

---

## 5. Verification

- All summary payloads must carry the synthetic marker.
- No secret-like keys or live API keys in output.
- Deterministic: repeated calls with same options yield same counts and structure (excluding any non-deterministic data outside the generator’s control).
