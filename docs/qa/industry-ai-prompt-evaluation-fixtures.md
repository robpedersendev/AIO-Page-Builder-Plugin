# Industry AI Prompt-Pack Evaluation Fixtures (Prompt 416)

**Spec**: industry-prompt-pack-overlay-contract.md; industry-planner-input-contract.md; industry-subtype-extension-contract.md (when subtype-aware).

**Purpose:** Internal evaluation fixtures to test whether AI planner prompt-pack overlays for launch industries (and initial subtype cases) produce structured, on-target recommendations rather than vague generic outputs. Evaluation is internal-only and bounded.

---

## 1. Scope

- **Launch industries:** cosmetology_nail, realtor, plumber, disaster_recovery.
- **Subtype cases (optional):** When overlay service is subtype-aware, include at least one fixture per industry with a valid industry_subtype_key (e.g. realtor_buyer_agent, plumber_residential).
- **Evaluated dimensions:** Page-family selection quality; CTA direction; proof/social-proof expectations; local-page (LPagery) posture.
- **Out of scope:** Exposing raw provider internals; general AI benchmarking product; changing execution behavior.

---

## 2. Overlay output shape (reference)

Per industry-prompt-pack-overlay-contract.md, the overlay produced by **Industry_Prompt_Pack_Overlay_Service::get_overlay_for_artifact()** includes:

| Field | Description | Evaluation focus |
|-------|-------------|------------------|
| schema_version | `1` | Present and valid. |
| active_industry_key | Primary industry when overlay applies | Matches fixture input. |
| required_page_families | Page families to favor | Aligned with pack supported_page_families; non-empty for launch industries with pack. |
| discouraged_weak_fit | Section/page keys to discourage | Pack-defined; optional. |
| cta_priorities | CTA pattern hints | Aligned with pack preferred/default CTA patterns; industry-appropriate (e.g. book_now for cosmetology, valuation for realtor). |
| proof_expectations | Proof/social-proof guidance | Present when pack/overlay provides it; industry-appropriate. |
| local_seo_posture | Local-SEO stance | Emphasize local for local-service industries; neutral or documented otherwise. |
| lpagery_stance | LPagery stance | Prefer local / neutral / defer per pack; industry-appropriate. |
| industry_guidance_text | Flattened guidance for planner | Non-empty when pack has summary/guidance; no secrets. |

---

## 3. Fixture scenarios (representative)

Each scenario is an **input artifact** (or minimal industry_context) plus **expected good-result pattern** for the overlay. Use these to build input_artifact and call `get_overlay_for_artifact()`; then assert or manually compare overlay to expected pattern.

### 3.1 Cosmetology / Nail

- **Input:** industry_context with readiness.state = partial or ready; industry_profile.primary_industry_key = `cosmetology_nail`.
- **Expected good-result pattern:** required_page_families includes home, services, about, contact (or pack-supported subset); cta_priorities favor booking/consultation; industry_guidance_text non-empty; lpagery_stance appropriate for local/salon (e.g. prefer_local or neutral).

### 3.2 Realtor

- **Input:** industry_context ready; primary_industry_key = `realtor`.
- **Expected good-result pattern:** required_page_families includes home, services, about, contact, resource or buyer_guide; cta_priorities favor valuation/consultation; proof_expectations or guidance suitable for trust/credibility; lpagery_stance suitable for local markets.

### 3.3 Realtor subtype (buyer agent)

- **Input:** industry_context ready; primary_industry_key = `realtor`; industry_profile.industry_subtype_key = `realtor_buyer_agent` (when overlay service is subtype-aware).
- **Expected good-result pattern:** Same as realtor base; if subtype overlays add emphasis (e.g. page_family_emphasis), overlay reflects buyer-focused nuance; no generic-only output.

### 3.4 Plumber

- **Input:** industry_context ready; primary_industry_key = `plumber`.
- **Expected good-result pattern:** required_page_families includes home, services, contact (or pack-supported); cta_priorities favor call_now or booking; local_seo_posture emphasize_local or equivalent; lpagery_stance prefer_local or neutral.

### 3.5 Disaster Recovery

- **Input:** industry_context ready; primary_industry_key = `disaster_recovery`.
- **Expected good-result pattern:** required_page_families includes home, services, contact/support; cta_priorities favor emergency/urgency; industry_guidance_text non-empty; lpagery_stance appropriate for 24/7/local service.

### 3.6 No industry (fallback)

- **Input:** industry_context absent or readiness.state = none/minimal; or primary_industry_key empty.
- **Expected good-result pattern:** Overlay contains only schema_version (minimal); no active_industry_key; no required_page_families or cta_priorities from industry. Planner receives no industry-specific constraints.

---

## 4. Evaluation procedure

1. **Build input artifact** for each scenario (industry_context with readiness and industry_profile; or empty for fallback).
2. **Call** `Industry_Prompt_Pack_Overlay_Service::get_overlay_for_artifact( $input_artifact )`.
3. **Assert or review:**  
   - Overlay has schema_version and, when industry is set, active_industry_key matches.  
   - For launch industries with pack: required_page_families non-empty and consistent with pack; cta_priorities non-empty where pack defines CTA patterns.  
   - For no-industry: overlay is minimal (schema_version only).  
4. **Structured comparison:** Export overlay to a comparable structure (e.g. JSON); diff against last known good or checklist. Prefer contract-level assertions over brittle exact string match.
5. **Repeatability:** Same input artifact and registry state yields same overlay; run as part of internal test or QA script. No public-facing surface.

---

## 5. Test integration

- **Unit tests:** Instantiate Industry_Prompt_Pack_Overlay_Service with Industry_Pack_Registry (built-in definitions); for each launch industry build minimal industry_context; call get_overlay_for_artifact(); assert overlay schema_version, active_industry_key, and that required_page_families or cta_priorities are present when pack is present.
- **Fallback test:** Input without industry_context or with minimal readiness; assert overlay has no active_industry_key and no industry-derived arrays.
- **Fixtures:** Fixture data (expected overlay keys or minimal expected shape) can live in this doc or in a test data file; avoid hardcoding full provider output. Prefer “has key and non-empty” over exact content where content may evolve.

---

## 6. Expected good-result checklist (summary)

| Check | Description |
|-------|-------------|
| Schema | overlay.schema_version === '1'. |
| Industry set | When primary set and pack found: active_industry_key === primary; required_page_families or cta_priorities non-empty as per pack. |
| Industry fallback | When no industry or minimal readiness: overlay minimal (no active_industry_key, no industry-derived fields). |
| CTA direction | cta_priorities align with pack (e.g. book_now/valuation/call_now/emergency per vertical). |
| LPagery | lpagery_stance present when pack defines it; value in allowed set (e.g. prefer_local, neutral, defer). |
| No secrets | industry_guidance_text and all overlay fields safe for internal log; no API keys or raw provider internals. |

---

## 7. Cross-references

- **Overlay contract:** [industry-prompt-pack-overlay-contract.md](../contracts/industry-prompt-pack-overlay-contract.md).
- **Planner input:** [industry-planner-input-contract.md](../contracts/industry-planner-input-contract.md).
- **Release gate:** [industry-pack-release-gate.md](../release/industry-pack-release-gate.md).
- **Acceptance report:** [industry-subsystem-acceptance-report.md](industry-subsystem-acceptance-report.md).
