# Industry Bad-Fit Recommendation Troubleshooting Playbook (Prompt 417)

**Spec**: recommendation contracts; industry-subsystem-diagnostics-checklist; industry-pack-maintenance-checklist; industry-pack-release-gate.

**Purpose:** Support and operator playbook for when users report that industry-driven recommendations are wrong, too generic, too strict, or mismatched to their business. Diagnosis-first; no auto-fix; no user-facing complaint workflow.

**Training:** For shared terminology and subsystem concepts, see [industry-support-training-packet.md](industry-support-training-packet.md) and [industry-operator-curriculum.md](industry-operator-curriculum.md).

---

## 1. Scope and principles

- **In scope:** Diagnosing why section/page/Build Plan recommendations appear wrong, generic, or mismatched (e.g. wrong page families, wrong CTA emphasis, wrong starter bundle).
- **Out of scope:** Changing runtime recommendation logic in this doc; building a user-facing complaint flow; auto-fixing profile or pack state.
- **Principles:** Follow actual subsystem design; favor diagnosis over guesswork; overrides, bundles, and profile completeness are explicit—no hidden or unsafe repair shortcuts.

---

## 2. Common symptom classes

| Symptom class | Examples | Section to use |
|---------------|----------|----------------|
| **Too generic** | Recommendations look the same for every industry; no industry-specific pages or CTAs. | §3.1 Profile completeness; §3.2 Pack activation. |
| **Wrong vertical** | User is a plumber but sees realtor-style templates or CTAs. | §3.1 Primary industry key; §3.2 Pack activation. |
| **Too strict** | Good options are missing or discouraged; user expects more flexibility. | §3.4 Overrides; §3.5 Starter bundle choice. |
| **Subtype mismatch** | User chose “buyer agent” but recommendations feel like generic realtor. | §3.3 Subtype selection. |
| **Missing overlays** | Section or page helper content not industry-specific. | §3.6 Missing overlays. |
| **Weak or wrong metadata** | Token preset, CTA, or LPagery not applied or wrong for industry. | §3.7 Metadata coverage; §3.8 Override state. |
| **No industry applied** | User set industry but behavior is “no industry” (generic). | §3.1, §3.2, §3.9 No-industry fallback. |

---

## 3. Diagnostic steps and likely causes

### 3.1 Industry Profile completeness

- **Check:** Industry Profile (Industry Profile settings screen or Support Triage industry snapshot): `primary_industry_key` set? `schema_version` supported?
- **Likely cause:** Empty or unset primary → resolvers and overlay service treat as no industry; recommendations are generic.
- **Action:** Guide user to set primary industry in Industry Profile. If profile was lost (e.g. after restore), re-select industry and save. Do not inject values programmatically unless authorized; document any manual fix.
- **Evidence:** Industry_Diagnostics_Service snapshot `primary_industry` and `profile_readiness`; Industry_Profile_Repository::get_profile() (admin/support only).

### 3.2 Pack activation

- **Check:** Is the primary industry pack **active** in the environment? (Pack toggle / activation state; built-in packs are active by default unless explicitly disabled.)
- **Likely cause:** Pack disabled or not loaded → overlay and recommendation resolvers have no pack definition; behavior falls back to no-industry or generic.
- **Action:** Confirm pack is enabled for that industry key. If using pack toggle controller, re-enable. Escalate to maintainers if pack is missing from built-in definitions.
- **Evidence:** Industry_Pack_Registry::get( primary_industry_key ); Industry_Health_Check_Service (pack refs, active state).

### 3.3 Subtype selection

- **Check:** If the industry supports subtypes, is `industry_subtype_key` set and **valid** for the primary industry? (Subtype must have same parent_industry_key as primary.)
- **Likely cause:** Subtype not set → parent-only behavior. Subtype set but invalid (wrong parent or unknown key) → resolver falls back to parent only; no subtype-specific emphasis.
- **Action:** Confirm subtype is selected in profile and matches a defined subtype for that industry (see industry-subtype-catalog.md). If user expects subtype-specific behavior, ensure industry_subtype_key is set and valid.
- **Evidence:** Industry_Subtype_Resolver::resolve(); Industry_Profile_Validator with subtype_registry (warnings for unknown or parent-mismatch).

### 3.4 Overrides vs fixing profile/metadata

- **When to use overrides:** User intentionally wants to favor or discourage specific templates/sections beyond what the pack suggests; or one-off exception without changing pack or profile.
- **When to fix profile/metadata:** Recommendation is wrong because profile is incomplete, wrong industry, wrong subtype, or pack/metadata is misconfigured. Fix the source (profile, pack, or overlay) rather than layering overrides everywhere.
- **Action:** Prefer fixing profile and pack state first. Use overrides only when the pack/profile are correct but the user needs a local exception. Document override usage so future pack updates are not surprised.

### 3.5 Starter bundle choice

- **Check:** Is `selected_starter_bundle_key` set? Does that bundle exist and belong to the primary industry?
- **Likely cause:** Wrong or missing starter bundle → Build Plan or recommendation flow may suggest pages/sections that don’t match user intent. Empty selection → system may use pack default or first available bundle.
- **Action:** Confirm user’s intended starting set; set selected_starter_bundle_key to a bundle that matches their industry (and subtype if applicable). See industry-starter-bundle-catalog.md.
- **Evidence:** Industry_Profile_Repository get_profile(); Industry_Starter_Bundle_Registry::get_for_industry( primary ).

### 3.6 Missing overlays

- **Check:** For the primary industry, do section helper and page one-pager overlays exist and load? (Overlay counts in diagnostics snapshot.)
- **Likely cause:** No overlays for that industry → helper/one-pager content is base-only; recommendations may still use pack (page families, CTA) but copy/guidance not industry-tuned.
- **Action:** Confirm overlay definitions exist for that industry (SectionHelperOverlays, PageOnePagerOverlays). If missing, escalate to pack/overlay authors; do not invent overlay content in support.
- **Evidence:** Industry_Diagnostics_Service section_overlay_count, page_overlay_count; overlay registries get_for_industry().

### 3.7 Weak metadata coverage

- **Check:** Industry Health Report (Industry_Health_Check_Service): any errors or warnings for token_preset_ref, seo_guidance_ref, lpagery_rule_ref, CTA refs, starter bundle refs?
- **Likely cause:** Pack definition has missing or broken refs → style, SEO, LPagery, or CTA guidance not applied; recommendations may look generic or inconsistent.
- **Action:** Use Health Report to list gaps. Escalate to pack maintainers to add or fix refs in pack definition. No secrets or unsafe refs in examples.
- **Evidence:** industry-subsystem-diagnostics-checklist.md §4; Industry_Health_Check_Service::run().

### 3.8 Override state

- **Check:** Are there any site-level or template-level overrides that force certain templates/sections or suppress industry recommendations?
- **Likely cause:** Overrides (e.g. discouraged list, forced template) can make good recommendations disappear or reorder.
- **Action:** Review override state (per contract); if override is intentional, document. If override was applied by mistake, guide user to clear or adjust.
- **Evidence:** Recommendation resolver and Build Plan context; override storage per product design.

### 3.9 No-industry fallback

- **Check:** When profile is empty or primary is unset, system must behave as “no industry” (neutral recommendations, no industry-specific overlays). Confirm no errors or crashes.
- **Likely cause:** User expects industry behavior but profile is empty or not loaded (e.g. different site, restore failed, option not saved).
- **Action:** Confirm profile is saved and loaded (Industry Profile screen; diagnostics snapshot). If fallback is correct but user expected industry, see §3.1.
- **Evidence:** industry-recommendation-regression-guard.md §3.3; resolver tests for empty/unknown industry.

---

## 4. Escalation paths

| Situation | Escalate to | What to provide |
|-----------|-------------|------------------|
| Pack definition wrong or missing (e.g. industry_key, refs) | Pack authors / maintainers | Industry key, Health Report snippet, expected vs actual. |
| Overlay missing or wrong for industry | Overlay/pack maintainers | Industry key, section/page key, overlay catalog ref. |
| Subtype definition missing or incorrect | Subtype/pack maintainers | industry_subtype_key, parent_industry_key, industry-subtype-catalog. |
| Resolver or scoring behavior bug | Engineering | Scenario (profile + pack state), expected vs actual recommendation list, no secrets. |
| Product decision (e.g. change fallback, add override type) | Product owner | Use case, impact, contract ref. |

---

## 5. Launch-industry examples (brief)

- **Cosmetology / Nail:** “Recommendations look like generic business.” → Check primary_industry_key = cosmetology_nail; pack active; overlays loaded; CTA (e.g. book_now) and token preset (e.g. cosmetology_elegant) present in pack and applied.
- **Realtor:** “I’m a buyer’s agent but see seller-focused templates.” → Check industry_subtype_key = realtor_buyer_agent if subtype-aware; else confirm primary = realtor and starter bundle / page families favor buyer_guide or resource.
- **Plumber:** “No local/emergency emphasis.” → Check primary = plumber; pack CTA (call_now) and LPagery/local refs; local_seo_posture and lpagery_stance in overlay.
- **Disaster recovery:** “Suggestions don’t feel urgent.” → Check primary = disaster_recovery; CTA and overlay emphasize 24/7, emergency, claim assistance.

---

## 6. Cross-references

- **Diagnostics:** [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md); Industry_Diagnostics_Service; Support Triage industry snapshot.
- **Health report:** [industry-subsystem-diagnostics-checklist.md](../qa/industry-subsystem-diagnostics-checklist.md) §4; Industry_Health_Check_Service.
- **Maintenance:** [industry-pack-maintenance-checklist.md](industry-pack-maintenance-checklist.md).
- **Subtypes:** [industry-subtype-catalog.md](../appendices/industry-subtype-catalog.md); [industry-subtype-extension-contract.md](../contracts/industry-subtype-extension-contract.md).
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md).
- **No secrets:** Do not paste profile, API keys, or user data into tickets or logs; use industry_key, refs, and error codes only.
