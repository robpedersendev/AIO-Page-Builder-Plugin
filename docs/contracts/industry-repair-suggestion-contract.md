# Industry Repair Suggestion Contract (Prompt 443)

**Spec**: health report contracts; import conflict contracts; pack deprecation and migration contracts; author linting docs.

**Status**: Contract. Defines the advisory repair suggestion engine for missing or invalid industry refs. Suggestions are read-only and never auto-applied.

---

## 1. Purpose

- Provide **likely fix recommendations** for missing or invalid pack refs, bundle refs, overlay refs, subtype refs, or rule refs detected by health check, linting, or import conflict.
- Keep suggestions **advisory only**; no automatic mutation or key rewriting.
- Keep suggestions **bounded and explainable**; no fuzzy matching that silently rewrites keys.
- Support **health reports** and **import conflict previews** by attaching optional suggestion data to issues.

---

## 2. Suggestion result shape

Each suggestion (when present) has the following structure:

| Field | Type | Description |
|-------|------|-------------|
| **broken_ref** | string | The ref that failed validation (e.g. pack key, bundle key, overlay ref). |
| **suggested_ref** | string | Recommended replacement or target (e.g. replacement_ref from deprecation, or a valid alternative key). |
| **suggestion_type** | string | One of: `deprecated_replacement`, `inactive_activate`, `valid_alternative`, `fallback_bundle`. |
| **confidence_summary** | string | Short label: `high`, `medium`, `low`. Explains how confident the engine is in the suggestion. |
| **explanation** | string | Human-readable reason (e.g. "Pack is deprecated; use replacement_ref."). |

When no good suggestion exists (e.g. ambiguity too high), the engine returns `null` or omits the suggestion for that issue.

---

## 3. Suggestion types

| Type | When used | Example |
|------|-----------|--------|
| **deprecated_replacement** | Broken ref is a deprecated/superseded pack or bundle with `replacement_ref` set. | Profile references deprecated pack `realtor_legacy`; pack defines `replacement_ref` => `realtor`. |
| **inactive_activate** | Object exists in registry but is inactive (e.g. pack disabled by toggle). | Pack is present but disabled; suggestion: enable the pack. |
| **valid_alternative** | Ref is missing; engine suggests a valid key from the same registry (e.g. first active bundle for industry). | Pack `starter_bundle_ref` missing; suggest first active bundle for that industry. |
| **fallback_bundle** | Profile or pack references a missing bundle; suggest industry default bundle when available. | `selected_starter_bundle_key` not found; suggest first bundle for primary industry. |

---

## 4. Integration

- **Health check**: Caller may pass each health issue (object_type, key, issue_summary, related_refs) to `Industry_Repair_Suggestion_Engine::suggest_for_issue()`. Attach the returned suggestion to the issue for display (e.g. "Suggested fix: …").
- **Import conflict**: For conflicts that involve missing refs or deprecated keys, caller may request suggestions for the conflict row and display them in the preview table.
- **No auto-apply**: Neither the health report nor the import flow must apply a suggestion without explicit user action. Display only.

---

## 5. Safety

- **No silent rewrite**: The engine never mutates profile, pack definitions, or import payloads.
- **Ambiguity**: When multiple alternatives exist or the failure reason is unclear, the engine must not guess; return no suggestion or mark confidence `low` with a generic explanation.
- **Validation unchanged**: Existing validation and conflict logic remain authoritative; suggestions do not weaken validation rigor.

---

## 6. Files

- **Engine**: `plugin/src/Domain/Industry/Reporting/Industry_Repair_Suggestion_Engine.php`
- **Contract**: This document.
