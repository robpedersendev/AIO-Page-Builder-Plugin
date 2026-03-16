# Industry Compliance and Caution Rule Schema (Prompt 405)

**Spec:** industry-pack-extension-contract.md; helper-doc and one-pager contracts; Build Plan and admin warning contracts in master spec.

**Status:** Schema for structured industry compliance, caution, and sensitivity rules. Rules are **advisory** and support vertical-specific warnings and editorial guardrails. The plugin does **not** provide legal advice or enforce jurisdiction-specific law.

---

## 1. Purpose

- Provide a **structured place** for vertical caution guidance (claims language, certification wording, local-market sensitivity, testimonial/review cautions, pricing-disclosure, etc.) without hardcoding vague warnings into helper docs.
- Support **future consumers** in docs, Build Plan review, and admin previews.
- Rules remain **exportable and versioned**; no legal/compliance automation claims beyond editorial caution surfacing.

---

## 2. Rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **rule_key** | string | Yes | Stable, unique key for the rule (pattern `^[a-z0-9_-]+$`; max 64). |
| **industry_key** | string | Yes | Industry pack key (pattern `^[a-z0-9_-]+$`; max 64). |
| **scope** | string | No | Target scope: `global`, `section_family`, `page_family`, or empty (global). Max 32. |
| **target_section_family** | string | No | When scope is section_family: e.g. proof, listing, contact. Max 64. |
| **target_page_family** | string | No | When scope is page_family: e.g. home, contact, services. Max 64. |
| **severity** | string | Yes | `info`, `caution`, or `warning`. Only these values allowed. |
| **caution_summary** | string | Yes | Short summary (max 256 chars) for UI or tooltip. |
| **guidance_text** | string | No | Full guidance or explanation (max 1024 chars). |
| **status** | string | Yes | `active`, `draft`, or `archived`. Only `active` is used at resolution. |
| **version_marker** | string | No | Schema/rule version; max 32 chars. |

- **Invalid rule objects** must fail safely at load (skipped).
- **rule_key** is unique within the registry (first wins on duplicate).

---

## 3. Severity semantics

| Severity | Use |
|----------|-----|
| **info** | General best-practice or reminder; no risk implied. |
| **caution** | Editorial or compliance sensitivity; user should review. |
| **warning** | Higher sensitivity; avoid overclaiming or non-compliant language. |

The system does **not** block or enforce; it surfaces guidance only. No legal certainty is implied.

---

## 4. Registry behavior

- **Industry_Compliance_Rule_Registry**: load(array), get(rule_key), get_for_industry(industry_key), get_all(). Read-only after load.
- Load validates required fields, severity enum, and key patterns; invalid entries skipped. Duplicate rule_key: first wins.
- No public mutation; registry is populated from built-in definitions or optional import only.

---

## 5. Limits of the system

- **Not legal advice.** Rules are editorial guardrails only.
- **No jurisdiction-specific law engines.** Rules are industry/vertical oriented, not legal-domain specific.
- **No hard blocking.** Consumers may display warnings or hints; they do not prevent save or publish.
- **Exportable.** Rules are part of industry subsystem data and may be included in export/restore where applicable.

---

## 6. Implementation reference

- **Industry_Compliance_Rule_Registry**: Domain\Industry\Registry\Industry_Compliance_Rule_Registry.
- **Contract**: industry-compliance-rule-contract.md.
