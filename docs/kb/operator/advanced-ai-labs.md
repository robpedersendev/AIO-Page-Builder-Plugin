# Profile History and Prompt Experiments

**Audience:** Operators with AI administration access.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §4.  
**Screens:** `aio-page-builder-profile-snapshots`, `aio-page-builder-prompt-experiments`.

---

## Profile History (profile snapshots)

**Canonical guide:** [profile-snapshots-and-history.md](profile-snapshots-and-history.md) — capture hooks, list/diff, restore (brand/business overwrite, template preference behavior), export/import of `profile_snapshot_history`, audit via `error_log`, edge cases vs Build Plan rollback.

**Onboarding and current profile:** [onboarding-and-profile.md](onboarding-and-profile.md) (drafts, prefill, **Request AI plan**). Profile restore does not reset onboarding draft step state by itself.

---

## Prompt Experiments

**Screen:** **Prompt Experiments** (`aio-page-builder-prompt-experiments`).  
**Capability:** `aio_manage_ai_providers` (same gate as **AI Providers**).

Use this area only when your distribution expects **non-production prompt experimentation**. Outputs are not a substitute for reviewing **AI Runs** and **Build Plans** before execution. Detailed prompt-pack contracts live under `docs/contracts/` and the product spec.

**Cross-links:** [admin-operator-guide.md §2–§5](../../guides/admin-operator-guide.md); [ai-runs-and-run-details.md](ai-runs-and-run-details.md); [support-triage-guide.md](../../guides/support-triage-guide.md).

---

## Edge cases

| Situation | Guidance |
|-----------|----------|
| **Experiment vs production behavior** | Treat experiments as **isolated**; confirm which prompt pack a live **AI Run** used before trusting plan content. |
| **No visible experiments** | Missing menu usually means **`aio_manage_ai_providers`** is absent for the user. |
| **Plan quality regressions** | Roll forward with [build-plan-overview.md](build-plan-overview.md) and provider health — not by skipping review. |
