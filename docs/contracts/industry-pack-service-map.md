# Industry Pack Service Map

**Spec**: industry-pack-extension-contract.md; aio-page-builder-master-spec.md.

**Status**: Directory layout and service categories for the Industry Pack subsystem. Services are placeholder or scaffold only until later prompts implement them.

---

## 1. Directory structure

All Industry Pack domain code lives under:

```
plugin/src/Domain/Industry/
├── Registry/       # Pack definitions, schema, validation, load/save
├── Profile/        # Site/user industry profile (primary/secondary industry)
├── Overlays/       # Helper and one-pager overlay resolution
├── AI/             # Industry-aware AI rules and planning hooks
├── LPagery/        # Token presets and LPagery rules per industry
└── Docs/           # Industry-specific doc refs and inventory
```

- **Registry**: Industry pack object schema, validation, persistence, and registry service. Section/page template registries remain authoritative; this registry holds industry pack definitions only.
- **Profile**: Storage and resolution of the site’s (or user’s) primary/secondary industry. Feeds onboarding and template ranking.
- **Overlays**: Resolution of industry-specific helper refs and one-pager refs applied on top of existing section/page doc refs.
- **AI**: Hooks and rules for AI planning when an industry pack is active (e.g. industry_rule_ref from pack). Does not replace the core AI planner.
- **LPagery**: Token presets and LPagery rule references per industry. Must not change LPagery token naming.
- **Docs**: Optional industry-specific documentation inventory or refs; extends existing helper/one-pager system.

---

## 2. Service categories and responsibilities

| Category   | Responsibility |
|-----------|-----------------|
| **Registry** | Define and validate industry pack objects; load/save packs (PHP definitions, option, or DB) per persistence contract; expose pack list and by-key lookup. |
| **Profile**  | Store and retrieve industry site profile (primary industry key, optional secondary keys); integrate with onboarding/settings. |
| **Overlays** | Given active industry, merge or reorder helper refs and one-pager refs for sections/pages. |
| **AI**       | Apply industry AI rules (e.g. ai_rule_ref) in planning context; keep contract with existing AI pillar. |
| **LPagery**  | Resolve token_preset_ref and lpagery_rule_ref for active industry; remain LPagery-compatible. |
| **Docs**     | Industry-specific doc inventory or refs; link to existing docs system. |

---

## 3. Container keys (bootstrap)

The following keys are registered by **Industry_Packs_Module**:

| Key | Purpose | Implementation |
|-----|---------|----------------|
| `industry_packs_loaded` | Dependency flag; industry subsystem is bootstrapped. | `true`. |
| `industry_pack_validator` | Validates single pack or bulk; duplicate-key detection. | Industry_Pack_Validator. |
| `industry_pack_registry` | Registry: load(), get(key), get_all(), list_by_status(status). | Industry_Pack_Registry; loaded with empty list until a pack loader is added. |
| `industry_profile_store` | Site industry profile (primary/secondary, subtype, service/geo model). | Industry_Profile_Repository when `settings` is available; else null. |

Additional keys (e.g. overlay resolver, AI rule applier, LPagery resolver) can be added in later prompts.

---

## 4. Dependency flow (future)

- **Bootstrap**: Industry_Packs_Module registers `industry_packs_loaded` and placeholder keys.
- **Registry** may depend on: section/page template registries (read-only) for affinity or key validation; storage abstraction.
- **Profile** may depend on: options or user meta; onboarding step keys.
- **Overlays** may depend on: industry pack registry, profile (active industry), existing helper/one-pager registries.
- **AI** may depend on: industry pack registry, profile, existing AI prompt/planning services.
- **LPagery** may depend on: industry pack registry, profile, existing LPagery token/rule contracts.
- **Docs** may depend on: industry pack registry, existing docs inventory.

No dependency from core section/page registries, rendering, or execution to Industry Pack is required for baseline behavior; industry is additive.

---

## 5. Alignment with plugin pillars

- **Registries**: Industry pack registry is a separate registry (pack definitions only). Section and page template registries remain authoritative.
- **Onboarding**: Profile integrates with onboarding; industry selection or primary industry can be added as an overlay step or field.
- **Documentation**: Overlays and Docs extend helper/one-pager refs; no replacement of existing docs.
- **AI**: AI category extends planning with industry rules; no replacement of AI provider or prompt pack system.
- **Export/restore**: When implemented, industry pack definitions and industry profile must be included in export and restored; see industry-pack-extension-contract and PORTABILITY_AND_UNINSTALL.
