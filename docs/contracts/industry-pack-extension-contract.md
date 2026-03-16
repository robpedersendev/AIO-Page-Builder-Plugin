# Industry Pack Extension Contract

**Spec**: aio-page-builder-master-spec.md (extension layer); rendering-contract; large-scale-acf-lpagery-binding-contract; PORTABILITY_AND_UNINSTALL.

**Status**: Contract definition. Industry Packs are a first-class **extension layer** inside AIO Page Builder. They extend existing registries, onboarding, documentation, AI planning, and LPagery-compatible workflows. They do **not** create separate plugin modes or fork core registries.

---

## 1. Purpose and scope

This contract defines:

- The **subsystem boundary** for the Industry Pack feature set.
- **Terminology**: industry pack, industry affinity, industry overlay, primary/secondary industry, helper overlay, one-pager overlay.
- The rule that industry packs **extend** (overlay) section templates, page templates, docs, onboarding, AI planning, and LPagery workflows—they do not replace them.
- That the plugin remains **one core product**; industry packs are additive and optional.

**Out of scope for this contract**: Industry data storage, registries, onboarding fields, UI filters, AI behavior, or template ranking logic. Those are defined in later prompts and schemas.

---

## 2. Terminology

| Term | Definition |
|------|------------|
| **Industry pack** | A structured, versioned definition that describes an industry vertical (e.g. legal, healthcare, real estate). It references supported page families, preferred/discouraged section keys, CTA patterns, docs, and optional AI/LPagery rules. |
| **Industry affinity** | Metadata on a section or page template indicating that it is a good fit (or poor fit) for one or more industries. Used for filtering, ranking, or guidance—not for removing templates from the registry. |
| **Industry overlay** | A layer of industry-specific behavior applied on top of existing registries and flows. Templates remain in the section/page registries; the overlay adds industry-specific ordering, labels, or guidance. |
| **Primary industry** | The single industry (industry pack) that best matches the site’s or profile’s focus. Used for default recommendations and UI emphasis. |
| **Secondary industry** | Additional industries that may apply; used for broader filtering or fallback guidance. |
| **Industry-specific helper overlay** | References to helper documentation that are relevant when a given industry pack is active. Section templates may reference helpers; industry overlay can add or reorder helper refs. |
| **Industry-specific one-pager overlay** | References to one-pager documentation that are relevant when a given industry pack is active. Page templates may reference one-pagers; industry overlay can add or reorder one-pager refs. |

---

## 3. Subsystem boundary

The Industry Pack subsystem:

- **Extends** the section template registry (e.g. industry affinity metadata, preferred/discouraged keys per pack). It does not create a separate section registry.
- **Extends** the page template registry (e.g. industry affinity, supported page families per pack). It does not create a separate page template registry.
- **Extends** onboarding (e.g. industry selection or primary industry in profile). It does not replace the existing onboarding flow.
- **Extends** documentation (helper refs, one-pager refs) via overlay refs. It does not replace the existing helper/one-pager system.
- **Extends** AI planning (e.g. industry-aware guidance or rules). It does not replace the AI planner.
- **Extends** LPagery-compatible workflows (e.g. token presets or rules per industry). It does not change LPagery token naming or break existing tokens.
- **Extends** export/restore where industry pack definitions and site industry profile are stored; they must be portable and restorable.

The subsystem **does not**:

- Create separate “industry plugin modes” or fork the plugin into multiple products.
- Remove or hide section/page templates from the authoritative registries.
- Change saved page content, ACF values, or LPagery token naming.
- Weaken content survivability, uninstall behavior, or portability (see PORTABILITY_AND_UNINSTALL).

---

## 4. Future object classes and storage (documentation only)

The following are **documented here** for later implementation; no persistent schema is created in this contract:

- **Industry pack** – Definition object; canonical schema in [industry-pack-schema.md](../schemas/industry-pack-schema.md) and Industry_Pack_Schema (required: industry_key, name, summary, status, version_marker; optional: supported_page_families, preferred_section_keys, discouraged_section_keys, default_cta_patterns, seo_guidance_ref, helper_overlay_refs, one_pager_overlay_refs, token_preset_ref, lpagery_rule_ref, ai_rule_ref, metadata). **token_preset_ref** resolves to an Industry Style Preset per [industry-style-preset-schema.md](../schemas/industry-style-preset-schema.md) and Industry_Style_Preset_Registry. **seo_guidance_ref** resolves to an Industry SEO guidance rule per [industry-seo-guidance-schema.md](../schemas/industry-seo-guidance-schema.md) and Industry_SEO_Guidance_Registry. **lpagery_rule_ref** resolves to an Industry LPagery rule per [industry-lpagery-rule-schema.md](../schemas/industry-lpagery-rule-schema.md) and Industry_LPagery_Rule_Registry.
- **Industry site profile** – Site-level or user-level selection of primary/secondary industry (e.g. for onboarding or settings).
- **Section/page industry affinity metadata** – Affinity or tags on section/page templates for filtering/ranking.
- **Industry helper overlay** – Ref(s) to helper docs that apply when an industry pack is active.
- **Industry one-pager overlay** – Ref(s) to one-pager docs that apply when an industry pack is active.
- **Industry starter bundle** – A curated overlay object (see [industry-starter-bundle-schema.md](../schemas/industry-starter-bundle-schema.md)) that describes a recommended starting set for an industry: recommended page families, page/template refs, section emphasis refs, and optional CTA/style/LPagery guidance refs. Bundles are **overlays**: they do not replace section or page template registries. The **Industry_Starter_Bundle_Registry** is read-only; it loads bundle definitions, exposes get by key, get_for_industry, and list_all; invalid definitions are skipped at load. An industry pack may reference a bundle via optional **starter_bundle_ref**. Bundles are not applied or executed in the core Build Plan by default; they are available for onboarding flows or guided entry points when implemented.
- **Industry compliance and caution rules** – Structured advisory rules for claims language, certification wording, local-market sensitivity, testimonial/review cautions, pricing-disclosure, etc. (see [industry-compliance-rule-schema.md](../schemas/industry-compliance-rule-schema.md) and [industry-compliance-rule-contract.md](industry-compliance-rule-contract.md)). The **Industry_Compliance_Rule_Registry** is read-only; it loads rule definitions, exposes get(rule_key), get_for_industry(industry_key), get_all(). Rules are **advisory only**; no legal advice or enforcement. An industry pack may reference rules via optional **compliance_rule_refs**.

Storage targets: industry pack definitions use registry-compatible storage (PHP definitions, option-backed, or DB-backed) per industry-pack-schema and industry-pack-service-map. Export/restore must include industry pack definitions and industry profile when implemented.

---

## 5. Additive and optional

- Industry packs are **additive**. Enabling an industry pack does not remove or replace core templates or flows.
- Industry packs are **optional**. The plugin must run correctly with zero industry packs configured. Safe failure if the industry subsystem is not yet configured or has no active packs.

---

## 6. Bootstrap and registration

- The Industry Pack subsystem is bootstrapped via **Industry_Packs_Module** (Bootstrap) and registered with the plugin container so future prompts have a stable entry point.
- No new public admin actions or routes in the bootstrap; no unsafe exposure.

---

## 7. Cross-references

- **industry-compliance-rule-contract.md**: Compliance/caution rule schema and registry (Prompt 405); advisory only.
- **industry-pack-service-map.md**: Directory structure and service categories (Prompt 319).
- **industry-pack-schema.md**: Industry Pack object schema and persistence (Prompt 320).
- **data-schema-appendix.md**: Schema summary when industry objects are introduced.
- **industry-override-contract.md**: Operator override model for section, page template, and Build Plan item recommendations (Prompt 366). Overrides are explicit and auditable; recommendations remain advisory by default.
- **industry-conflict-resolution-contract.md**: Multi-industry conflict resolution and precedence (primary vs secondary); conflict classes, CTA/page-family/LPagery/style handling; Industry_Conflict_Result schema (Prompt 370).
- **rendering-contract.md**, **large-scale-acf-lpagery-binding-contract.md**: No change to rendering or LPagery naming from industry overlays.
- **PORTABILITY_AND_UNINSTALL**: Built content and portability unchanged; industry data, when added, must follow same preservation/export rules.
