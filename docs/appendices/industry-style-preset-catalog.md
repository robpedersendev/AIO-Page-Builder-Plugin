# Industry Style Preset Catalog (Prompt 361)

**Spec:** industry-style-preset-schema.md; industry-style-preset-application-contract.md; styling subsystem (prompts 242–260).  
**Purpose:** Lists built-in style presets loaded by Industry_Style_Preset_Registry. Packs reference these by token_preset_ref (style_preset_key). Presets supply token value bundles and optional component override refs only; no raw CSS.

---

## 1. Loading

- **Source:** `plugin/src/Domain/Industry/Registry/StylePresets/Builtin_Industry_Style_Presets.php` (Builtin_Industry_Style_Presets::get_definitions()).
- **Registry:** Industry_Style_Preset_Registry. Industry_Packs_Module registers the registry under `industry_style_preset_registry` and calls load() with the builtin definitions.
- **Validation:** Invalid definitions skipped at load (invalid token names, prohibited values, unsupported version). Token keys must match `--aio-*` per core spec; values must pass styling sanitization.

---

## 2. Preset keys and industries

| style_preset_key | label | industry_key | description |
|-----------------|-------|--------------|-------------|
| cosmetology_elegant | Elegant Salon | cosmetology_nail | Soft, elegant palette suited to salons and nail studios. |
| realtor_warm | Warm & Trusted | realtor | Warm, trustworthy palette for real estate and listing-focused sites. |
| plumber_trust | Trust & Reliability | plumber | Solid, dependable palette for plumbing and trade services. |
| disaster_recovery_urgency | Urgency & Response | disaster_recovery | Clear, urgent palette for disaster recovery and 24/7 response messaging. |

---

## 3. Pack references (token_preset_ref)

- **cosmetology_nail** (industry-pack-cosmetology-nail.php): token_preset_ref = `cosmetology_elegant`.
- **realtor** (industry-pack-realtor.php): token_preset_ref = `realtor_warm`.
- **plumber** (industry-pack-plumber.php): token_preset_ref = `plumber_trust`.
- **disaster_recovery** (industry-pack-disaster-recovery.php): token_preset_ref = `disaster_recovery_urgency`.

All preset keys above must exist in the registry for pack references to resolve. Presets are optional overlays; application is reversible via the styling subsystem.

---

## 4. Goal style preset overlays (Prompt 512)

Conversion-goal preset overlays refine industry presets by goal. **Registry:** Goal_Style_Preset_Overlay_Registry. **Source:** `plugin/src/Domain/Industry/Registry/StylePresets/GoalOverlays/goal-style-preset-overlay-definitions.php`. **Goals:** calls, bookings, estimates, consultations, valuations, lead_capture. **Target presets:** cosmetology_elegant, realtor_warm, plumber_trust, disaster_recovery_urgency. When conversion_goal_key is set, application may merge goal overlay token_values and component_override_refs for the applied preset. See conversion-goal-style-preset-contract.md and industry-goal-overlay-catalog.md.
