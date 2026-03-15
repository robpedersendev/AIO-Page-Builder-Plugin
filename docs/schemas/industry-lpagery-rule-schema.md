# Industry LPagery Rule Schema

**Spec**: large-scale-acf-lpagery-binding-contract.md; industry-pack-extension-contract.md.

**Status**: Schema for industry LPagery rules. Rules are overlays/advisory constraints; existing LPagery-compatible field keys and token naming remain unchanged. No modification of LPagery injection behavior in this prompt.

---

## 1. Purpose

- Let industry packs define **LPagery posture** (central, optional, discouraged), **required/optional token references**, **local page hierarchy guidance**, and **weak-fit page warnings** in a structured, contract-safe way.
- Rules are **advisory**; they do not rewrite the core LPagery binding contract or alter field/token naming.
- Multi-industry support remains possible; rules are keyed by rule key and scoped by industry_key.

---

## 2. Rule object shape

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| **lpagery_rule_key** | string | Yes | Stable unique key (e.g. `legal_entity_01`, `realtor_listing_01`). Pattern `^[a-z0-9_-]+$`; max 64. |
| **industry_key** | string | Yes | Industry pack key (same pattern; max 64). |
| **version_marker** | string | Yes | Schema version (e.g. `1`). Unsupported versions rejected at load. |
| **status** | string | Yes | `active`, `draft`, or `deprecated`. Only `active` rules are used. |
| **lpagery_posture** | string | Yes | `central`, `optional`, or `discouraged`. Describes how central LPagery is for this vertical. |
| **required_token_refs** | list&lt;string&gt; | No | Token names or refs that are required when LPagery is used (e.g. `{{location_name}}`). Must align with LPagery token contracts. |
| **optional_token_refs** | list&lt;string&gt; | No | Token names or refs that are optional. |
| **hierarchy_guidance** | string | No | Local page hierarchy or generation strategy notes; max 1024. |
| **weak_page_warnings** | string or list&lt;string&gt; | No | Page types or patterns that are weak fit; max 512 per item. |
| **notes** | string | No | General rule notes; max 1024. |
| **metadata** | map | No | Optional metadata (no secrets). |

---

## 3. Validation and safety

- **lpagery_rule_key**, **industry_key**: Non-empty; pattern `^[a-z0-9_-]+$`; max 64. **lpagery_rule_key** unique within registry.
- **lpagery_posture**: One of `central`, `optional`, `discouraged`. Invalid posture → reject at load.
- **version_marker**: Must match supported schema version.
- **required_token_refs**, **optional_token_refs**: Arrays of non-empty strings; token refs must follow existing LPagery token style (e.g. `{{name}}`). Invalid refs may be stripped per implementation; no loosening of field/token contracts.
- Invalid rule objects **fail safely** (skipped at load); no mutation of LPagery runtime behavior.

---

## 4. Relation to LPagery binding contract

- Rules **do not** change existing LPagery-compatible field keys or token naming (large-scale-acf-lpagery-binding-contract).
- Rules **do not** alter LPagery injection or binding logic; they provide vertical-specific posture and guidance for planners and future generation.
- **required_token_refs** / **optional_token_refs** document expected token names; validation at build/generation time remains per existing contract.

---

## 5. Implementation reference

- **Industry_LPagery_Rule_Registry** (plugin/src/Domain/Industry/LPagery/Industry_LPagery_Rule_Registry.php): load(array), get(key), get_all(), list_by_industry(industry_key), list_by_status(status). Invalid entries skipped at load.
