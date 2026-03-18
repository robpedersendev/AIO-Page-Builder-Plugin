# Shell / Placeholder Inventory — Backlog

**Purpose:** List of shell/schema-only or placeholder artifacts that still require spec or product definition before full implementation. Not a task list; use for scope and audit traceability.

**Source:** Focused inventory pass over Tokens_Step_UI_Service, Profile_Snapshot_Data, cost_placeholder, History_Rollback_Step_UI_Service, Object_Status_Families, and related areas.

---

## 1. Classified as intentionally deferred (no implementation without spec)

| Artifact | Location | Status | Notes |
|----------|----------|--------|--------|
| **Token application** | Tokens_Step_UI_Service, token_diff_placeholder, placeholder_bulk_states | Deferred | token-application-scope-decision: out of scope; step is recommendation-only. token_diff_placeholder is structural only. |
| **Profile_Snapshot_Data** | Domain/Storage/Profile/Profile_Snapshot_Data.php | Schema-only | No persistence or UI; type/schema per profile-snapshot-schema.md. SPR-010. |
| **cost_placeholder** | AI provider drivers, usage object | Deferred | SPR-010; cost modeling not implemented; reserved for future usage/cost reporting. ai-provider-contract.md §9. |
| **History/Rollback step execution** | History_Rollback_Step_UI_Service | Shell only | Rollback not initiated from this workspace; rollback_eligibility_placeholder and placeholder_0 row are structural. Recovery workflow exists elsewhere; this step is read-only shell. |

---

## 2. Implementation (not placeholders)

| Artifact | Location | Notes |
|----------|----------|--------|
| **Object_Status_Families** | Domain/Storage/Objects/Object_Status_Families.php | Status sets are authoritative for validation and repos. Custom register_post_status not implemented here but class is implementation, not shell. |

---

## 3. Still requiring spec/product definition (backlog)

- **Profile snapshot persistence:** If product later requires storing/restoring profile snapshots, spec must define persistence store, scope, and lifecycle (profile-snapshot-schema.md exists; no persistence contract).
- **Cost/usage reporting:** If product requires token cost or usage-based reporting, spec must define schema, storage, and UI; cost_placeholder remains null until then.
- **Rollback from Build Plan workspace:** If product requires initiating rollback from Step 7 (logs/rollback), spec must define how history rows are populated and how "Request rollback" is wired; currently shell does not initiate rollback.
- **Token diff/apply UI:** If token application is brought in scope (reverse of token-application-scope-decision), spec would define token_diff data source and apply flow; currently intentionally out of scope.

---

## 4. Contract / doc references

- Token application: token-application-scope-decision.md, token-application-descope-criteria.md, execution-action-contract.md (apply_token_set implementation status).
- Profile snapshot: profile-snapshot-schema.md, Profile_Snapshot_Data.php docblock, SPR-010.
- Cost: ai-provider-contract.md §9, SPR-010, security-privacy-remediation-ledger.md.
- History/Rollback step: History_Rollback_Step_UI_Service.php docblock, Build Plan spec §38.
