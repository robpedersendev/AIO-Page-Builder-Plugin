# ACF Key and Group Key Naming Contract

**Document type:** Implementation-grade contract for ACF group keys, field keys, field names, and related identifiers (spec §20.4, §20.6, §57.5, §59.5).  
**Governs:** Deterministic naming strategy, collision avoidance, revision compatibility, key preservation rules.  
**Related:** acf-field-blueprint-schema.md (field `key`, `name`), section-registry-schema.md (`internal_key`), master spec §20 ACF Architecture.

---

## 1. Purpose and scope

This contract locks the naming strategy for ACF group keys, field keys, field names, and nested/subfield keys **before** any ACF registration code is written. It eliminates guesswork and key drift, preserves compatibility across revisions where possible, and defines collision avoidance.

Keys are internal contracts. They must not be user-controlled in unsafe ways. Generated identifiers must be sanitized and predictable. No request-driven arbitrary key generation.

---

## 2. Canonical patterns

### 2.1 Group key format

| Component | Pattern | Example |
|-----------|---------|---------|
| Prefix | `group_` | Required; ACF convention |
| Namespace | `aio_` | Plugin namespace; reserved |
| Section identity | `{section_key}` | Section `internal_key`; sanitized |
| **Full pattern** | `group_aio_{section_key}` | `group_aio_st01_hero` |

**Rules:**
- Max length: 64 chars (ACF practical limit).
- Section key is already `^[a-z0-9_]+$` per section-registry-schema.
- Group key must be globally unique across all field groups.
- One group per section template; `section_key` ensures uniqueness.

### 2.2 Field key format

| Component | Pattern | Example |
|-----------|---------|---------|
| Prefix | `field_` | Required; ACF convention |
| Section stem | `{section_key}_` | Owning section; sanitized |
| Field name | `{name}` | Short identifier; `^[a-z0-9_]+$` |
| **Full pattern** | `field_{section_key}_{name}` | `field_st01_hero_headline` |

**Rules:**
- Max length: 64 chars.
- `name` from blueprint field definition; never derived from label.
- Unique within the field group (section scope).
- For top-level fields: `field_{section_key}_{name}`.

### 2.3 Field name format

| Component | Pattern | Example |
|-----------|---------|---------|
| **Format** | `^[a-z0-9_]+$` | `headline`, `faq_question` |
| Max length | 64 chars | |
| **Relationship** | Often equals key stem after `field_{section_key}_` | Key `field_st01_headline` → name `headline` |

**Rules:**
- Machine-readable; lowercase; underscores only.
- Labels may change; names must remain stable once in use.
- Do not derive name from user-editable label.

### 2.4 Subfield key format (repeater/group)

| Context | Pattern | Example |
|---------|---------|---------|
| Repeater subfield | `field_{section_key}_{parent_name}_{sub_name}` | `field_st05_faq_items_question` |
| Group subfield | Same | `field_st02_cta_group_btn_label` |

**Rules:**
- Parent `name` + child `name` form the stem.
- Unique within parent; collision with sibling subfields avoided by distinct `sub_name`.
- Max 64 chars; truncate from left if necessary (preserve rightmost `_{sub_name}`).

### 2.5 Repeater row / child naming

ACF repeater rows do not have stable keys; they are indexed by position. The **subfield keys** within each row follow §2.4. No additional row-level key is required for naming. Row identity is position-based for storage; blueprint defines subfield keys only.

---

## 3. Reserved prefixes and namespaces

| Prefix | Reserved for | Prohibited use |
|--------|--------------|----------------|
| `aio_` | Plugin ACF identifiers | Third-party or user-defined groups/fields |
| `group_aio_` | Plugin field groups | Manual ACF groups using this prefix |
| `field_` | All ACF field keys | Any non-field identifier |

**Maximum readability:** Prefer `section_key` in keys for traceability. Avoid abbreviations unless length-constrained.

---

## 4. Collision avoidance rules

1. **Section key uniqueness:** Section `internal_key` is unique in the registry; group key `group_aio_{section_key}` is therefore unique.
2. **Field name uniqueness:** Within a blueprint, each field `name` is unique. Hence `field_{section_key}_{name}` is unique within the group.
3. **Subfield uniqueness:** Within a repeater/group, each subfield `name` is unique. Hence `field_{section_key}_{parent}_{child}` is unique within the parent.
4. **Cross-section:** Different sections have different `section_key`; no cross-section collision.
5. **Prohibited:** Do not reuse `field_` keys across groups for the same logical field without explicit migration.

---

## 5. Prohibited naming patterns

| Pattern | Reason |
|---------|--------|
| Keys derived from user-provided labels | Labels change; keys must stay stable |
| Random hashes as primary identity | Breaks determinism and migration |
| Generic keys: `field_1`, `field_a` | Ambiguous; not traceable |
| Uppercase or mixed case in key/name | Schema requires lowercase |
| Hyphens in key/name | Use underscores only |
| Keys exceeding 64 chars | ACF/database limits |
| User-controlled or request-driven keys | Security; must be server-authoritative |
| Duplicate keys within same group/parent | Collision |

---

## 6. Key preservation decision matrix (revision compatibility)

When a blueprint is revised, keys may be preserved or regenerated:

| Change type | Key preserved? | Action |
|-------------|----------------|--------|
| Label-only edit | Yes | No key change. Label is display-only. |
| Instructions edit | Yes | No key change. |
| Default value edit | Yes | No key change. |
| Validation rule change | Yes | No key change. |
| Required toggle | Yes | No key change. |
| LPagery annotation | Yes | No key change. |
| **Field reorder** | Yes | Keys unchanged; order in `fields` array changes. |
| **Field rename (name)** | No | New key; breaking. Requires migration for existing content. |
| **Field add** | N/A | New key; no impact on existing. |
| **Field remove** | N/A | Key orphaned; content may need migration. |
| **Structural move** (e.g. field into group) | No | New key path; breaking. |
| **Nested structure change** (add/remove subfield) | Depends | Adding subfield: new key. Removing: orphan. Reordering subfields: keys preserved. |
| **Section key change** | No | All keys change; breaking. Section key is immutable per spec. |
| **Blueprint ID change** | No | Group key may change if tied to blueprint; verify contract. |

**Compatible revisions:** Label, instructions, validation, required, defaults, LPagery—keys preserved.

**Breaking revisions:** Field `name` change, structural move, section key change—new keys; migration required.

---

## 7. Version-aware revision behavior

- **Same `section_version`:** Blueprint changes are in-place. Apply key-preservation matrix.
- **New `section_version`:** May indicate schema evolution. Document whether keys are preserved or regenerated in migration notes.
- **Stable key retention:** When `version.stable_key_retained` is true on the section, field keys must not change for compatible revisions.
- **Migration:** Breaking changes require explicit migration logic; do not silently reassign keys.

---

## 8. Examples

### 8.1 Simple top-level fields

| Field | Key | Name |
|-------|-----|------|
| Headline | `field_st01_hero_headline` | `headline` |
| Subheadline | `field_st01_hero_subheadline` | `subheadline` |
| CTA Link | `field_st01_hero_cta` | `cta` |

Section: `st01_hero`.

### 8.2 Nested group

| Field | Key | Name |
|-------|-----|------|
| CTA Group | `field_st02_cta_group` | `cta_group` |
| → Button Label | `field_st02_cta_group_btn_label` | `btn_label` |
| → Button URL | `field_st02_cta_group_btn_url` | `btn_url` |

Section: `st02_cta`.

### 8.3 Repeater with subfields

| Field | Key | Name |
|-------|-----|------|
| FAQ Items | `field_st05_faq_items` | `faq_items` |
| → Question | `field_st05_faq_items_question` | `question` |
| → Answer | `field_st05_faq_items_answer` | `answer` |

Section: `st05_faq`.

### 8.4 Group key examples

| Section | Group key |
|---------|-----------|
| st01_hero | `group_aio_st01_hero` |
| st05_faq | `group_aio_st05_faq` |
| st10_legacy_hero | `group_aio_st10_legacy_hero` |

---

## 9. Prohibited examples

| Invalid | Reason |
|---------|--------|
| `field_Headline` | Uppercase |
| `field_st01-headline` | Hyphen |
| `field_1` | Generic; not traceable |
| `headline` (as key) | Missing `field_` prefix |
| `field_st01_hero_Heading Line` | Space; invalid in name |
| `group_st01_hero` | Missing `aio_` namespace |
| Keys derived from `label` | Label may change |

---

## 10. Long key handling

When `field_{section_key}_{parent}_{child}` exceeds 64 chars:

1. Truncate `section_key` from the right first (preserve uniqueness prefix).
2. If still over, abbreviate parent name.
3. Never truncate the final `_{child}` segment; it is the primary identifier.
4. Document truncation in blueprint or generator so it is reproducible.

---

## 11. Relationship to blueprint schema

- Blueprint field `key` must conform to this contract.
- Blueprint field `name` must conform to `^[a-z0-9_]+$`; used in key generation.
- `field_blueprint_ref` in section template should align with `group_aio_{section_key}` semantics (group is per section, not per blueprint id, when section_key is canonical).
- When blueprint_id differs from section_key (e.g. `acf_blueprint_st01` vs `st01_hero`), the **section_key** drives the group key for section ownership. Blueprint_id is a logical reference; group key uses section_key for determinism and collision avoidance.

**Clarification:** Group key = `group_aio_{section_key}` because one section = one group. Blueprint_id is the schema reference; section_key is the owner identity.

---

## 12. Naming examples matrix

| Scope | Component | Pattern | Example |
|-------|-----------|---------|--------|
| Group | Full | `group_aio_{section_key}` | `group_aio_st01_hero` |
| Field | Top-level | `field_{section_key}_{name}` | `field_st01_hero_headline` |
| Field | Repeater | `field_{section_key}_{parent}` | `field_st05_faq_items` |
| Subfield | Repeater child | `field_{section_key}_{parent}_{child}` | `field_st05_faq_items_question` |
| Subfield | Group child | `field_{section_key}_{parent}_{child}` | `field_st02_cta_group_btn_label` |

---

## 13. Security and governance

- Key generation is server-side only. No client-provided keys.
- Sanitize all inputs (section_key, name) before use: `^[a-z0-9_]+$`, max length.
- Do not allow arbitrary request-driven key generation.
- Blueprint definitions are admin-governed; keys come from validated blueprints.
