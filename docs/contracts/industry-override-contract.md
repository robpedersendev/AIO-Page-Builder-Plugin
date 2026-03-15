# Industry Override Contract

**Spec**: industry-section-recommendation-contract.md; industry-page-template-recommendation-contract.md; industry-build-plan-scoring-contract.md.  
**Status**: Defines the operator override model for industry recommendations (Prompt 366). Recommendations remain advisory; overrides are explicit and auditable.

---

## 1. Purpose

- Allow **admins/reviewers** to intentionally override an industry recommendation (e.g. accept a discouraged section, reject a recommended template, or choose a different option than the resolver suggested).
- **Preserve** default recommendation behavior when no override exists.
- **Keep overrides auditable**: reason capture, optional actor/created markers, and persistent warning visibility so overridden items remain clearly labeled in later views.

---

## 2. Override scopes

| Target type | Description | Target key meaning |
|-------------|-------------|---------------------|
| **section** | Override applies to a section template choice (e.g. "use this section despite discouraged"). | Section template internal_key. |
| **page_template** | Override applies to a page template choice (e.g. "use this template despite weak fit"). | Page template internal_key. |
| **build_plan_item** | Override applies to a single Build Plan item (new_page or existing_page_change). | Plan item_id (within a plan). |

Override identity is scoped by (target_type, target_key) and optionally by plan_id for build_plan_item so the same section/template can have different overrides in different plans.

---

## 3. Override state and reason

- **Override state**: `accepted` (operator accepts the choice despite warning), `rejected` (operator rejects the recommendation), or product-defined equivalents. State determines how the item is labeled (e.g. "Accepted (override)" vs "Rejected").
- **Reason**: Required or strongly encouraged short text (sanitized, bounded length) explaining why the override was applied. Stored for audit and for display in review/compare views.
- **Warnings remain visible**: After override, the original industry warning (e.g. "Discouraged for this industry") remains visible in UI so operators do not lose context. Override does not remove or rewrite industry metadata on the underlying registry definition.

---

## 4. Storage and versioning

- Override objects are stored per product design (e.g. per Build Plan in plan definition, or in a dedicated override store keyed by scope). Schema: Industry_Override_Schema.
- **Versioning**: Override records may include a schema_version or version_marker for future evolution. Created/updated timestamps (or markers) support audit trails where allowed by existing audit conventions.
- **Actor metadata**: Where the plugin already captures actor (e.g. user id) for audit, override may reference it; otherwise override is anonymous or "current user" at write time. No new audit subsystem required in this contract.

---

## 5. Safe defaults

- **Missing override**: When no override exists for (target_type, target_key [and plan_id if applicable]), behavior is **normal recommendation**: show resolver result, no override badge, no reason.
- **Invalid override**: Malformed or invalid override records (e.g. missing target_key, invalid state) must not break recommendation or UI; treat as "no override" and optionally log.

---

## 6. Constraints

- Overrides must be **admin/reviewer-only** (capability check at write).
- Reason must be **sanitized** (e.g. text only, max length per Industry_Override_Schema).
- Overrides **do not** silently rewrite industry_affinity or pack rules on section/page template definitions.
- Planner/executor separation remains; override state may influence display and future "apply" behavior but does not auto-execute.

---

## 7. Files

- **Schema**: plugin/src/Domain/Industry/Overrides/Industry_Override_Schema.php
- **Contract**: docs/contracts/industry-override-contract.md
