# Demo Fixture and Seed Data Toolkit (Prompt 130)

Internal-only guide for using the deterministic demo fixture generator for demos, QA, and review sessions. Spec refs: §56.4, §60.7, §60.6, §59.15.

## Purpose

- Populate a **safe demo environment** with representative synthetic data.
- Support **repeatable demos**, QA runs, and onboarding of internal reviewers.
- **No production customer data**, no secrets, no real URLs or API keys.

## Scope

The toolkit provides:

- **Registries** – Section, page template, composition, documentation, and snapshot fixtures (via `Registry_Fixture_Builder`).
- **Profile** – Brand and business profile fixture (schema-compliant, synthetic text).
- **Crawl summary** – Sample crawl session page records (demo URLs only).
- **AI runs** – Run metadata and artifact placeholders (no real provider/keys).
- **Build Plans** – One or more plan definitions (schema-valid, synthetic).
- **Logs** – Example log entry structures.
- **Export example** – Export-result-shaped payload for import/export demos.

All output is **tagged synthetic** (`_synthetic: true`) and must not be used for production logic or reporting.

## Access and Permissions

- Fixture generation is **internal-only** and must be **permission-gated** (e.g. admin/support/QA capability).
- No public fixture download; no changes to runtime authorization boundaries.
- Seed data must **not** trigger real provider reporting or external calls when used in demo mode.

## Usage

1. **Generate fixtures (in-memory)**  
   Use `Demo_Fixture_Generator::generate( $options )` to obtain a `Demo_Fixture_Result`. Options can include/exclude domains (e.g. `include_registries`, `include_build_plans`). Default is all domains included.

2. **Result shape**  
   `Demo_Fixture_Result` provides:
   - `is_success()`, `get_message()`
   - `get_counts()` – counts per domain (registries, profile, crawl_summary, ai_runs, build_plans, logs, export_example)
   - `get_summary()` – full payload summary (redacted; no secrets)
   - `is_synthetic()` – always `true`
   - `to_payload()` – stable structure for logging or API

3. **Persistence (if applicable)**  
   The generator returns data structures only. Any code that **persists** demo data (e.g. into options, CPTs, or tables) must be a separate internal seeder or script that:
   - Uses the generator output.
   - Respects the same schemas and validators as real data.
   - Does not enable real reporting or external calls in demo context.

## Reset / Reseed

For internal demo environments:

- **Reset**: Remove or truncate demo data using the same mechanisms as for real data (e.g. delete demo CPTs, clear demo options, truncate demo table rows), ensuring only data tagged or scoped as demo is affected.
- **Reseed**: Run the fixture generator again (and, if used, the same seeder) to repopulate. Fixtures are **deterministic** (stable keys and shapes) for repeatable demos.

## Constraints

- **Observational/support only** – Fixtures support demos and QA; they do not drive execution, approval, or reporting logic.
- **Schema fidelity** – Generated structures conform to existing object schemas and validators.
- **No vanity metrics** – Fixture data is minimal and representative, not high-volume test data.
- **Security** – No real credentials, no customer PII, no raw secrets in summaries or payloads.

**Preview and dummy data:** For **template previews** in the admin directory (section and page templates), synthetic data and preview fidelity are defined in **template-preview-and-dummy-data-contract.md** (docs/contracts). That contract governs realistic dummy ACF data, preview-safe omission and animation, and required detail-screen metadata. Fixture data and preview dummy data share the same “no production, no secrets” policy; the preview contract adds preview-specific rules (real renderer, category-aware realism, reduced-motion).

## Files

- `src/Domain/Fixtures/Demo_Fixture_Generator.php` – Generator.
- `src/Domain/Fixtures/Demo_Fixture_Result.php` – Result value object.
- Registry fixtures: `src/Domain/Registries/Fixtures/Registry_Fixture_Builder.php`.

## Tests

Unit tests cover:

- Successful deterministic seeding.
- Schema-valid generated objects (e.g. Build Plan, profile, export shape).
- Synthetic markers present and no secret-like keys in output.
- No external-call leakage when using the generator in isolation (generator does not perform HTTP or reporting).
