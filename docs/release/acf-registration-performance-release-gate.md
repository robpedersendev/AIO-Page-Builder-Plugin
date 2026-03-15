# ACF Registration Performance — Release Gate

**Prompts**: 281–292  
**Contracts**: acf-conditional-registration-contract.md, large-scale-acf-lpagery-binding-contract.md §6.2–6.3, acf-page-visibility-contract.md

---

## 1. Gate purpose

Evidence-based release gate for the ACF conditional-registration performance retrofit. Confirms heavy-load regression is resolved without contract drift in field values, LPagery, assignment map, or editor-visible groups.

---

## 2. Evidence

| Item | Artifact |
|------|----------|
| Impact analysis and dependency map | [acf-registration-performance-impact-analysis.md](../qa/acf-registration-performance-impact-analysis.md) |
| Bulk-load elimination | [acf-blueprint-bulk-load-elimination-report.md](../qa/acf-blueprint-bulk-load-elimination-report.md) |
| Acceptance report | [acf-conditional-registration-acceptance-report.md](../qa/acf-conditional-registration-acceptance-report.md) |
| Diagnostics checklist | [acf-conditional-registration-diagnostics-checklist.md](../qa/acf-conditional-registration-diagnostics-checklist.md) |
| Contract | [acf-conditional-registration-contract.md](../contracts/acf-conditional-registration-contract.md) |

---

## 3. Release gate checklist

- [ ] **Front-end no-registration**: Verified (manual or automated) that front-end requests do not run full ACF registration or bulk section load.
- [ ] **Existing-page scoped registration**: Verified that editing an existing page registers only that page's section-owned groups; editor shows same groups as before.
- [ ] **New-page template/composition**: Verified that new-page edit with template/composition chosen registers only those sections; without choice, no groups.
- [ ] **Non-page admin**: Verified that non-page admin (e.g. Dashboard) does not trigger full registration; zero groups.
- [ ] **Field values / LPagery**: No contract drift; field values and LPagery tokens/behavior unchanged.
- [ ] **Assignment map**: Authority and semantics preserved; section-key cache invalidates on assignment change.
- [ ] **Diagnostics**: Bounded; admin/support only; no sensitive data (see diagnostics checklist).

---

## 4. Risks and mitigations

| Risk | Mitigation |
|------|------------|
| Stale section-key cache | Cache invalidated on `aio_acf_assignment_changed`; on miss or invalid data, resolvers fall back to assignment-map/derivation. Correctness over speed. |
| Template/composition cache | Template/composition-keyed cache TTL-bound; invalidation hooks can be added where definitions are saved if needed. |
| Full-registration in tooling | Explicit `run_full_registration()` only for tooling (e.g. export/repair); not called from acf/init. |

---

## 5. Sign-off

- [ ] QA: Acceptance checklist and contract checks passed.
- [ ] Technical: No regressions in field values, LPagery, or assignment map; diagnostics bounded.
- [ ] Product: Heavy-load issue resolved; editor experience unchanged.

---

*Reference this gate in [release-review-packet.md](release-review-packet.md) and [known-risk-register.md](known-risk-register.md) for the release that includes the ACF performance retrofit.*
