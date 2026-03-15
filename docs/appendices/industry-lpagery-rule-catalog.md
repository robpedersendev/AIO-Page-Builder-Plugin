# Industry LPagery Rule Catalog (Prompt 360)

**Spec:** industry-lpagery-rule-schema.md; industry-lpagery-planning-contract; large-scale-acf-lpagery-binding-contract.  
**Purpose:** Lists built-in LPagery rule definitions loaded by Industry_LPagery_Rule_Registry. Packs reference by lpagery_rule_ref (lpagery_rule_key). Advisory only; no LPagery token or binding mutation.

---

## 1. Loading

- **Source:** `plugin/src/Domain/Industry/LPagery/Rules/lpagery-rule-definitions.php`.
- **Registry:** Industry_LPagery_Rule_Registry::get_builtin_definitions(). Bootstrap (Industry_Packs_Module) registers under CONTAINER_KEY_LPAGERY_RULE_REGISTRY and calls load() with that list.
- **Validation:** lpagery_rule_key, industry_key, version_marker (1), status, lpagery_posture (central/optional/discouraged) required; invalid or duplicate key skipped.

---

## 2. Rule keys and posture

| lpagery_rule_key | industry_key | lpagery_posture |
|------------------|--------------|-----------------|
| cosmetology_nail_01 | cosmetology_nail | optional |
| realtor_01 | realtor | central |
| plumber_01 | plumber | central |
| disaster_recovery_01 | disaster_recovery | central |

---

## 3. Coverage

Per rule: required_token_refs, optional_token_refs, hierarchy_guidance, weak_page_warnings, notes. Token refs align with LPagery binding contract (e.g. {{location_name}}, {{service_title}}). Planning systems (e.g. Industry_LPagery_Planning_Advisor) consume for posture and warnings; no execution-time token naming changes.

---

## 4. Pack references

- **cosmetology_nail:** lpagery_rule_ref => cosmetology_nail_01.
- **realtor:** lpagery_rule_ref => realtor_01.
- **plumber:** lpagery_rule_ref => plumber_01.
- **disaster_recovery:** lpagery_rule_ref => disaster_recovery_01.

All resolve via Industry_LPagery_Rule_Registry::get( key ).
