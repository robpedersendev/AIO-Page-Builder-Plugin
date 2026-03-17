# Industry Pack Version Diff Contract (Prompt 418)

**Spec**: industry-pack-schema; industry-pack-deprecation-contract; health/reporting contracts.

**Status**: Internal, read-only diff and change-summary tool for industry pack versions. No pack content mutation; no public changelog product.

---

## 1. Purpose

- Let developers and reviewers see **what changed** between two pack states (e.g. baseline vs new, or built-in vs imported bundle).
- Produce **readable change summaries**: added/removed packs, changed refs, status changes, warning/deprecation deltas.
- Support **maintenance and release review**; fail safely for invalid comparison targets.

---

## 2. Scope

- **In scope:** Compare two pack definition sets (each: list of pack objects keyed by industry_key). Emit diff result with added, removed, changed packs and ref deltas.
- **Out of scope:** Public changelog; auto-apply migrations; modifying pack content; comparing non-pack assets (overlays/bundles can be summarized separately in future).

---

## 3. Diff result shape

| Field | Type | Description |
|-------|------|-------------|
| **compared_at** | string | ISO 8601 timestamp when diff was run. |
| **left_label** | string | Optional label for baseline (e.g. "built-in"). |
| **right_label** | string | Optional label for new state (e.g. "import bundle"). |
| **added** | list&lt;string&gt; | industry_key present only in right. |
| **removed** | list&lt;string&gt; | industry_key present only in left. |
| **changed** | list&lt;object&gt; | Per-pack change: industry_key, status_change, version_change, refs_added, refs_removed, refs_changed, summary_note. |
| **summary** | object | added_count, removed_count, changed_count; optional impact_level (none/low/medium/high). |
| **notes** | list&lt;string&gt; | Optional human-readable notes (e.g. invalid targets, skipped keys). |

---

## 4. Compared refs and metadata

For each pack present in both left and right, compare:

- **status** (active / draft / deprecated).
- **version_marker**.
- **Refs:** supported_page_families, preferred_section_keys, discouraged_section_keys, default_cta_patterns, preferred_cta_patterns, discouraged_cta_patterns, required_cta_patterns, seo_guidance_ref, token_preset_ref, lpagery_rule_ref, helper_overlay_refs, one_pager_overlay_refs, replacement_ref.
- **Deprecation:** deprecated_at, deprecation_note.
- **Core:** name, summary (for note; not "ref" but often useful in summary).

Array refs: treat as unordered sets for add/remove; refs_changed when value set differs. Scalar refs: changed when value differs.

---

## 5. Service contract

- **Industry_Pack_Diff_Service::diff( array $left_packs, array $right_packs, array $options = [] ): Industry_Pack_Diff_Result**
  - **left_packs** / **right_packs**: List of pack definition arrays (each with industry_key). Normalized to map by industry_key internally; duplicate keys in one side use first occurrence.
  - **options**: left_label, right_label (strings); optional.
  - Returns **Industry_Pack_Diff_Result** (immutable) with added, removed, changed, summary, notes. Safe: invalid or empty input yields empty diff and a note; no throw.

---

## 6. Security and usage

- **Internal/admin-only.** No public route or UI required by this contract; callers enforce capability and context.
- **Read-only.** No modification of pack definitions or registry state.
- **Safe failure:** Invalid comparison targets (e.g. non-array, missing industry_key) are skipped or noted; no exception propagation.

---

## 7. Integration

- **Release review:** Run diff between current built-in pack definitions and proposed bundle or branch to generate change summary.
- **Maintenance checklist:** Reference in industry-pack-maintenance-checklist when changing packs; use diff to verify ref and status deltas.
- **Health/reporting:** Diff result can be consumed by internal reporting; no schema change to health snapshot required.
