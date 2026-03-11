# Profile Snapshot Schema (Immutable Run-Time Snapshots)

**Document type:** Authoritative contract for profile data captured at AI-run time (§22.11).  
**Governs:** Snapshot identity, immutability, inclusion rules, and relationship to current profile.  
**Related:** profile-schema.md (current editable state); storage-strategy-matrix.md; ai-input-artifact-schema.md (input artifact profile section may reference profile snapshots).

---

## 1. Purpose

- **Current state** (profile-schema.md): The editable brand and business profile; “current truth” for the site.
- **Snapshot**: An immutable copy of profile data taken at a specific point in time (e.g. when an AI run or onboarding step uses the profile). Used as historical input to that run; must not be silently rewritten when the user edits the profile later.

---

## 2. Snapshot behavior rules (§22.11)

| Rule | Description |
|------|-------------|
| **Snapshot at run time** | AI runs (and any workflow that consumes profile for planning) reference a snapshot of profile data at run time, not the live editable object. |
| **Immutability** | Once a snapshot is stored for a given run/context, it must not be mutated. Rerunning onboarding or editing the current profile does not rewrite an existing snapshot. |
| **Reruns and new snapshots** | A new run (e.g. new AI plan, new onboarding pass) may create a new snapshot from current profile data. Prior run snapshots remain unchanged. |
| **Prefill from prior data** | Rerunning onboarding may prefill from the current profile (which may have been updated). Prefill does not overwrite another run’s snapshot. |
| **Export/import** | Export/import preserves profile structure; snapshot export (if supported) preserves snapshot structure and does not re-bind to current profile. |

---

## 3. Snapshot object identity

Each snapshot is associated with a **scope** (e.g. a specific AI run id, onboarding session id, or plan id). The same scope must always resolve to the same snapshot content for that scope; updates to the current profile do not change that content.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| snapshot_id | string | Yes | Unique identifier for this snapshot (e.g. UUID or run-scoped id). |
| scope_type | string | Yes | Enum: `ai_run` \| `onboarding_session` \| `plan` \| `other`. |
| scope_id | string | Yes | Id of the run, session, or plan this snapshot belongs to. |
| created_at | string | Yes | ISO 8601 timestamp when the snapshot was taken. |
| profile_schema_version | string | Yes | Version tag of the profile schema used (for migration/validation). |
| brand_profile | object | Yes | Immutable copy of brand_profile at snapshot time; shape per profile-schema.md §3. |
| business_profile | object | Yes | Immutable copy of business_profile at snapshot time; shape per profile-schema.md §4–9. |

---

## 4. Snapshot inclusion rules

- **Included:** All fields defined in profile-schema.md for `brand_profile` and `business_profile` (and their child objects) may be included in the snapshot. Inclusion follows the same structure as the current profile; no extra “live” fields are appended after snapshot creation.
- **Excluded:** Secrets, transient UI state, and any data not part of the canonical profile schema are not snapshot. Asset references are snapshot as references only; validity of the referenced file is not guaranteed for historical snapshots.
- **Not applicable:** If a field is omitted in the snapshot, consumers must treat it as absent (use default or N/A per profile-schema.md), not as “latest from current profile.”

---

## 5. Storage and lifecycle

- Snapshots are stored separately from the current profile (e.g. custom table or CPT-backed store keyed by scope_type + scope_id). Storage implementation is out of scope for this contract.
- Current profile lives in options (see storage-strategy-matrix.md). Snapshot storage does not overwrite or replace the current profile.
- Deletion/retention of snapshots is governed by retention policy (e.g. per-run retention); deletion does not affect the current profile.

---

## 6. Valid snapshot example (minimal)

```json
{
  "snapshot_id": "snap_a1b2c3",
  "scope_type": "ai_run",
  "scope_id": "run_xyz",
  "created_at": "2025-07-15T14:00:00Z",
  "profile_schema_version": "1.0",
  "brand_profile": {
    "brand_positioning_summary": "Trusted local accounting partner.",
    "brand_voice_summary": "Professional, approachable, clear.",
    "voice_tone": {
      "formality_level": "neutral",
      "clarity_vs_sophistication": "clarity"
    },
    "asset_references": []
  },
  "business_profile": {
    "business_name": "Acme Accounting LLC",
    "business_type": "Professional services",
    "primary_offers_summary": "Tax, bookkeeping, payroll.",
    "target_audience_summary": "Small business owners.",
    "core_geographic_market": "Metro Denver",
    "personas": [],
    "services_offers": [],
    "competitors": [],
    "geography": []
  }
}
```

---

## 7. Invalid: mutating a snapshot

Snapshot content must not be updated in place. Any process that “updates” a snapshot by copying in current profile values for an existing scope_id is invalid; a new run must create a new snapshot (new snapshot_id / scope_id) instead.

---

## 8. Checklist: snapshot contract coverage

- [ ] Snapshot is identified by snapshot_id and scope (scope_type + scope_id).
- [ ] Snapshot has created_at and profile_schema_version.
- [ ] brand_profile and business_profile in snapshot match profile-schema.md shape (frozen at snapshot time).
- [ ] Profile edits after a run do not rewrite that run’s snapshot.
- [ ] New runs may create new snapshots from current profile; prior snapshots unchanged.
- [ ] No secrets in snapshot; asset references are references only.
- [ ] Export/import of profile preserves structure; snapshot export (if any) preserves snapshot structure.
