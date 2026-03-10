# Composition Validation State Machine

**Document type:** Authoritative contract for custom template composition validation and lifecycle (spec §10.3, §10.10, §10.11, §14.6–14.10).  
**Governs:** Composition statuses, validation result states, validation codes, severity model, state transitions, provenance, snapshot rules, duplication and import revalidation.  
**Related:** object-model-schema.md (§3.3 Custom Template Composition), section-registry-schema.md, page-template-registry-schema.md.

---

## 1. Purpose and scope

Custom template compositions are **user-created page-template configurations** assembled from registered section templates. They sit between section/page template governance and page instantiation. Validation must be **multi-factor and explainable**, not a shallow true/false check. This contract defines:

- **Lifecycle statuses** and **validation result** (sub-)states
- **Allowed state transitions**
- **Validation checks** and **validation codes** with severity (blocking vs warning)
- **Provenance** requirements for duplication
- **Snapshot reference** rules and how they affect validation
- **Export/import revalidation** posture and **one-pager generation viability** as part of eligibility

Validation results are **server-authoritative**. Imported composition payloads are **untrusted until validated**. No hidden client-side-only validity flags.

---

## 2. Lifecycle statuses (object status)

Composition **lifecycle status** is the primary state for the object (spec §10.10, object-model §3.3).

| Status | Meaning | Use |
|--------|---------|-----|
| `draft` | Editable; not yet activated; may or may not pass validation | Initial state; composition under construction. |
| `active` | In use; eligible for page creation (subject to validation_result) | User has activated; validation_result should be `valid` or `warning` for normal use. |
| `archived` | Retained but not selectable for new page creation | Retired composition; preserved for history. |

**Allowed lifecycle transitions:**

| From | To | Condition / note |
|------|-----|------------------|
| draft | draft | No change; validation may run and update validation_result only. |
| draft | active | Allowed only when validation_result is `valid` or `warning` (no blocking failures). |
| active | draft | Allowed (e.g. user reverts to editing). |
| active | archived | Allowed. |
| archived | draft | Allowed (unarchive for edit). |
| archived | active | Allowed after revalidation if valid/warning. |

---

## 3. Validation result (sub-state)

**Validation result** is stored separately from lifecycle status (object-model: `validation_status`). It reflects the outcome of the last validation run and drives eligibility for activation and one-pager generation.

| Result | Meaning | Eligibility for active / one-pager |
|--------|---------|-----------------------------------|
| `pending_validation` | Validation not yet run or composition just created | Not eligible for active; one-pager not generated. |
| `valid` | All checks passed; no warnings | Eligible for active; one-pager generation viable. |
| `warning` | One or more non-blocking warnings; no blocking failures | Eligible for active; one-pager generation viable; user may be notified of warnings. |
| `validation_failed` | One or more blocking failures | Not eligible for active; one-pager generation not viable. |
| `deprecated_context` | Composition references deprecated section(s) with replacement; treat as warning if replacement path exists, else failure | Policy-defined: may be eligible with warning or ineligible. |

**Transitions of validation result:**

- Any → `pending_validation`: Composition created, duplicated, or imported; or explicit “revalidate” before run.
- `pending_validation` → `valid` | `warning` | `validation_failed` | `deprecated_context`: After validation run.
- `validation_failed` / `deprecated_context` → `valid` | `warning`: After user or system fixes and revalidation succeeds.

---

## 4. Validation codes and severity

Each validation check produces zero or more **validation codes**. Each code has a **severity**: **blocking** (failure) or **warning**.

### 4.1 Validation code list

| Code | Severity | Description |
|------|----------|-------------|
| `section_missing` | Blocking | One or more section keys in the composition do not exist in the section registry. |
| `section_deprecated_no_replacement` | Blocking | Referenced section is deprecated and has no replacement recommendation. |
| `section_deprecated_has_replacement` | Warning | Referenced section is deprecated but has a replacement; suggest migration. |
| `ordering_invalid` | Blocking | Section order violates ordering rules (e.g. duplicate positions, gaps, or invalid sequence). |
| `compatibility_adjacency` | Blocking | Two or more sections are adjacent in violation of compatibility rules (avoid_adjacent, etc.). |
| `compatibility_duplicate_purpose` | Warning | Multiple sections with same purpose stacked without clear reason. |
| `variant_conflict` | Blocking | Selected variant(s) conflict with compatibility or template rules. |
| `structural_anchor_missing` | Blocking | Required structural anchor (e.g. opening/closing section expectation) is missing. |
| `helper_generation_failed` | Blocking | Helper content could not be generated for the composition. |
| `field_group_derivation_failed` | Blocking | Field-group assignment cannot be derived for the composition. |
| `one_pager_generation_failed` | Blocking | One-pager generation is not viable (e.g. missing section helpers, invalid order). |
| `snapshot_drift` | Warning | Registry state has drifted from snapshot reference; revalidation recommended. |
| `snapshot_missing` | Warning | No snapshot reference; composition cannot be compared to creation-time registry state. |
| `source_template_unavailable` | Warning | Source page template (if set) is missing or deprecated. |
| `empty_section_list` | Blocking | Composition has no sections. |

**Severity model:**

- **Blocking:** Any blocking code sets overall result to `validation_failed` (or `deprecated_context` when policy treats deprecated-with-replacement as special). Composition is not eligible for activation until blocking issues are resolved.
- **Warning:** Warnings do not block activation; they are recorded for admin messaging and may set result to `warning` when there are no blocking codes.

---

## 5. State transition table (summary)

| Lifecycle status | Validation result | Can transition to lifecycle | Can transition to validation result |
|------------------|------------------|----------------------------|------------------------------------|
| draft | pending_validation | draft, active (if result becomes valid/warning) | valid, warning, validation_failed, deprecated_context |
| draft | valid | draft, active | valid, warning, validation_failed, deprecated_context (after revalidation) |
| draft | warning | draft, active | same |
| draft | validation_failed | draft only | valid, warning, validation_failed, deprecated_context |
| draft | deprecated_context | draft; active if policy allows | same |
| active | valid | draft, active, archived | same |
| active | warning | draft, active, archived | same |
| active | validation_failed | draft, active, archived | same (e.g. registry changed) |
| archived | * | draft, active (after revalidation) | same |

---

## 6. Provenance fields

Compositions must preserve **provenance** for duplication and traceability (spec §14.9).

| Field | Purpose |
|-------|---------|
| `composition_id` | Unique identifier for this composition; immutable. |
| `source_template_ref` | Optional. Page template internal_key if composition was derived from a page template. |
| `duplicated_from_composition_id` | Optional. Composition id of the source composition if this is a clone. |
| `registry_snapshot_ref_at_creation` | Optional. Reference to the registry (version) snapshot at creation or last validation. |

**Duplication rules:**

- **Clone:** User may clone an existing composition. The clone receives a **new unique composition_id**. The clone must store **duplicated_from_composition_id** = source composition’s id.
- **Rename:** Clone may be renamed (name is editable).
- **Revalidation:** At duplication time, the new composition must be **revalidated** against the current registry. Registry conditions may have changed since the source was created; duplication does not copy “valid” status without re-running validation.
- **Provenance readability:** Provenance fields must remain readable for admin and support; do not lose source ref on clone.

---

## 7. Snapshot reference rules (spec §14.8)

- A composition **shall** retain a **snapshot reference** to the registry state in which it was created or last validated.
- **Snapshot** allows the system to:
  - Understand what the composition meant at build time
  - Compare current registry state to that snapshot (e.g. section definitions, helpers, compatibility rules changed)
- **Validation behavior:**
  - If snapshot is present and current registry differs (e.g. section updated, deprecated, or removed), validation may emit `snapshot_drift` (warning) and/or `section_missing` / `section_deprecated_*` (blocking or warning).
  - If snapshot is missing, validation may emit `snapshot_missing` (warning) and still run all other checks.
- Snapshot reference does not **block** validation; it informs drift and revalidation messaging.

---

## 8. Export / import revalidation (spec §14.10)

- **Export:** Compositions are exportable with metadata, section references, variant choices, validation state, and one-pager reference where relevant.
- **Import:** **Imported compositions must not be assumed valid.** They must be **revalidated** against the **receiving environment** (current section registry, compatibility rules, etc.).
- **Import behavior:**
  - On import, set validation result to **pending_validation** (or run validation immediately).
  - Run full validation; handle **missing referenced sections** and **changed registry state** gracefully (emit `section_missing`, `section_deprecated_*`, `snapshot_drift` as appropriate).
  - Do not reuse imported composition_id if it would conflict with existing ids; **generate new unique composition_id** for the imported composition.
  - Preserve provenance: optional field to record “imported from export package X” for audit.

---

## 9. One-pager and helper viability

- **One-pager generation viability** is a validation concern (spec §14.6, §14.7). If one-pager cannot be generated (e.g. missing section helpers, invalid order), validation must emit **one_pager_generation_failed** (blocking).
- **Helper generation success** and **field-group assignment derivability** are explicit validation checks; failure yields blocking codes **helper_generation_failed** and **field_group_derivation_failed**.
- A composition is **eligible for normal page creation use** only when validation result is `valid` or `warning` (and policy allows `deprecated_context` where applicable) and one-pager generation is viable where required.

---

## 10. Scenario matrix

| Scenario | Expected validation result | Blocking codes | Warning codes | Notes |
|----------|----------------------------|----------------|---------------|--------|
| **Valid new composition** | valid | none | none | All sections exist, order and compatibility ok, one-pager and field derivation succeed. |
| **Warning-only composition** | warning | none | e.g. snapshot_drift, section_deprecated_has_replacement | Can still activate; user notified. |
| **Invalid: missing section** | validation_failed | section_missing | — | One or more section keys not in registry. |
| **Invalid: deprecated section, no replacement** | validation_failed | section_deprecated_no_replacement | — | Referenced section deprecated without replacement. |
| **Deprecated section with valid replacement note** | warning or deprecated_context | none | section_deprecated_has_replacement | Replacement suggested; policy may allow activation with warning. |
| **Incompatible adjacency** | validation_failed | compatibility_adjacency | — | Two sections must not be adjacent per compatibility rules. |
| **Missing structural anchor** | validation_failed | structural_anchor_missing | — | Required opening/closing or anchor section missing. |
| **One-pager generation failure** | validation_failed | one_pager_generation_failed | — | One-pager cannot be generated. |
| **Field derivation failure** | validation_failed | field_group_derivation_failed | — | Field-group assignment cannot be derived. |
| **Duplicate from source with registry drift** | validation_failed or warning | Depends on drift (e.g. section_missing if section removed) | snapshot_drift, section_deprecated_has_replacement | After duplication, revalidation runs; drift may produce failures or warnings. |
| **Imported composition, missing section** | validation_failed | section_missing | possibly snapshot_missing | Import not trusted; revalidate; graceful handling of missing refs. |

---

## 11. Example scenarios (narrative)

### 11.1 Valid new composition

- User creates composition with sections [st01_hero, st02_faq, st05_cta]; all exist and are active; order and compatibility valid; one-pager and field derivation succeed.
- **Result:** validation_result = `valid`. User can set lifecycle to `active`.

### 11.2 Warning-only composition

- Composition references st10_legacy_hero (deprecated, replacement st01_hero). No other issues.
- **Result:** validation_result = `warning`; code `section_deprecated_has_replacement`. User can still activate; admin sees warning and replacement suggestion.

### 11.3 Invalid: deprecated section with no replacement

- Composition references a section that is deprecated and has no replacement_section_key.
- **Result:** validation_result = `validation_failed`; code `section_deprecated_no_replacement`. Cannot activate until section is replaced or composition edited.

### 11.4 Invalid: missing section

- Composition references section key "st99_nonexistent"; not in registry.
- **Result:** validation_result = `validation_failed`; code `section_missing`. Cannot activate.

### 11.5 Duplicated composition requiring revalidation

- User duplicates composition A (which was valid at creation). Since then, one of A’s sections was removed from the registry.
- **Result:** New composition B has new composition_id, duplicated_from_composition_id = A’s id. Revalidation runs: B gets validation_result = `validation_failed`; code `section_missing`. User must fix B or remove the missing section reference.

### 11.6 Imported composition

- User imports a composition from an export package. Receiving site does not have section "st_custom_from_export".
- **Result:** Import creates composition with new id; validation_result = `pending_validation` then run validation → `validation_failed`; code `section_missing`. Graceful handling: composition is stored but not eligible until user updates section list or registry is updated.

---

## 12. Implementation notes

- **Server-authoritative:** All validation outcomes and state transitions are determined server-side. No client-only validity flags.
- **Explainable:** Validation must produce clear reasons (codes + severity) for failure or warning; do not collapse all failures into a single “invalid” reason.
- **Snapshot state:** Do not ignore snapshot reference; use it for drift detection and messaging.
- **Provenance:** Do not lose duplicated_from_composition_id or source_template_ref on clone/import.
- **Import:** Do not assume import equals valid; always revalidate.
- **Future:** Mutation actions (create, update, duplicate, import, archive) remain **capability-gated** by callers; this contract does not implement permission checks.
