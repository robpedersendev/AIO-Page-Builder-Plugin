# Industry Subsystem v2 Architectural Guardrails (Prompt 469)

**Spec**: Roadmap contract; master spec constraints; pack extension and subtype extension contracts; authoring and maintenance guides.  
**Purpose**: Define what future industry work **may** and **may not** become so expansion does not silently cross core boundaries: separate products, drag-and-drop branching, unsafe AI authority, or freeform industry forks. Internal-only; no runtime changes in this document.

---

## 1. Principles

- **One core plugin**: Industry layers remain overlays on a single plugin. They do not become a separate product line or fork.
- **Planner/executor separation**: Build Plan generation and approval remain distinct from execution. Industry influences planning and recommendations only; it does not bypass approval or execute without gating.
- **Content survivability and portability**: Built content is native WordPress; industry profile and refs are site preference. Export/restore and uninstall policies apply; no hidden lock-in.
- **Styling, templates, docs, and AI outputs**: Governed by existing contracts. Industry does not introduce new authority boundaries that conflict with master spec or core plugin constraints.

---

## 2. Unacceptable future drift

The following are **out of scope** for industry v2 and must not be introduced without an explicit master-spec or governance decision:

| Pattern | Why out of scope |
|---------|------------------|
| **Separate product or plugin fork** | Industry is an overlay; one codebase, one plugin. No industry-only fork that becomes a different product. |
| **Drag-and-drop or visual builder branching** | Core plugin does not become a drag-and-drop page builder with industry-specific branches. Template and composition model remain; industry adds recommendation and overlay only. |
| **AI as sole authority for plan approval** | AI may propose; human approval gates execution. No "AI auto-approve" or bypass of review for industry-driven plans. |
| **Freeform industry forks (user-defined industries)** | Industries and packs are defined in code/registry and optionally imported via controlled bundle format. No end-user creation of arbitrary "industry" definitions that bypass schema and validation. |
| **Industry-specific execution paths** | Execution remains generic; industry context may be captured in approval snapshot for traceability but does not change execution steps or rollback semantics. |
| **Unbounded subtype or bundle sprawl** | Subtypes and bundles must remain manageable: schema-valid, registry-loaded, and subject to evaluation framework when adding new ones. No hundreds of uncurated subtypes or bundles. |
| **Pack specialization that fragments core** | New packs must use approved seams (roadmap §2). No pack-specific core code paths that cannot be expressed via registry and resolver contracts. |
| **Relaxation of security or approval boundaries** | Capability checks, nonces, and auditability apply to all industry screens and actions. No industry-only bypass of capability or nonce. |
| **Public exposure of internal industry state** | Diagnostics, override audit, and support payloads remain admin/support-only. No public API or front-end exposure of industry internals. |

---

## 3. Acceptable extension seams

Future industry work **must** use the following; no ad hoc code paths:

- **Pack definitions and registries**: New industry_key, pack object, CTA/style/SEO/LPagery refs per industry-pack-schema and roadmap §2.
- **Subtypes**: Subtype schema and resolver; parent fallback; subtype overlays and bundles within existing registries.
- **Starter bundles**: Bundle schema and registry; bundle-to-plan conversion; no change to execution.
- **Overlays**: Section-helper and page-one-pager overlays; allowed regions only; no new core enums.
- **Recommendation resolvers**: Profile and pack drive affinity/discouraged; resolver API changes only with contract update.
- **Diagnostics and support**: Bounded snapshot; optional new fields documented; no secrets.
- **What-if and degraded mode**: Simulation and fail-safe behavior within existing contracts; no new persistence or authority.

---

## 4. Boundaries: subtype count, bundle sprawl, pack specialization

- **Subtype count**: Subtypes are per parent industry; each must have a defined schema, resolver support, and fallback. Adding many subtypes (e.g. >10 per pack) must be justified and documented; evaluation framework applies. No unbounded "subtype explosion."
- **Bundle sprawl**: Starter bundles are registry-loaded and schema-valid. Proliferation of bundles (e.g. dozens per industry without structure) increases maintenance and regression surface; prefer structured bundle families and documented coverage over freeform growth.
- **Pack specialization**: Packs may differ in overlays, CTA, style, and recommendation refs. They must **not** require pack-specific logic in core (e.g. "if realtor then X else Y" in non-industry code). All variation via registry and resolver inputs.

---

## 5. AI authority limits in future industry work

- **Recommendation and scoring**: AI/planner may use industry context to score and suggest; output remains recommendation only. Human review and approval gate execution.
- **Prompt overlays**: Industry and subtype prompt overlays may influence AI inputs; they do not grant AI authority to auto-approve plans or mutate live state.
- **No AI-only industry switch**: Changing primary industry, subtype, or bundle must remain an explicit operator action (profile save, bundle selection). No "AI decided to switch industry" without operator confirmation.
- **Traceability**: Industry context at approval time is captured in approval snapshot for support and rollback; it does not expand AI authority.

---

## 6. Front-end and lock-in

- **No front-end lock-in**: Industry does not require a specific theme or front-end framework beyond what the core plugin already requires. Style presets and tokens are optional; content remains portable.
- **Admin-only industry UI**: Industry Profile, comparison, override management, and diagnostics are admin screens. No public "industry selector" or industry-driven public content unless explicitly designed in product scope with same security and portability rules.

---

## 7. Cross-references

- [industry-subsystem-roadmap-contract.md](industry-subsystem-roadmap-contract.md) — Approved seams and roadmap categories.
- [industry-pack-extension-contract.md](industry-pack-extension-contract.md) — Subsystem boundary.
- [industry-subtype-extension-contract.md](industry-subtype-extension-contract.md) — Subtype fallback and scope.
- [industry-pack-authoring-guide.md](../operations/industry-pack-authoring-guide.md) — Author workflow; must align with these guardrails.
- [industry-phase-two-backlog-map.md](../operations/industry-phase-two-backlog-map.md) — Backlog and maturity; v2 work must stay within guardrails.
