# ACF Conditional Registration — Rollback Playbook

**Prompt**: 302  
**Contracts**: acf-conditional-registration-contract.md, acf-registration-exception-matrix.md

---

## 1. Purpose

Developer/support playbook to restore safe behavior if the conditional-registration retrofit causes a critical regression. Rollback must remain explicit, internal, and documented; conditional registration stays the default.

---

## 2. When to consider rollback

- Widespread missing ACF groups on page edit that cannot be fixed by assignment/cache invalidation.
- Confirmed full-registration path firing on every request (performance regression) and not fixable by a small patch.
- Policy decision to temporarily revert to unconditional registration for a specific release or environment.

Do **not** rollback for: single-site misconfiguration, stale cache (invalidation should fix), or tooling needing full registration (use existing exception paths).

---

## 3. Rollback options (no user-facing toggle)

- **Code rollback**: Revert the ACF conditional-registration changes (provider, controller, resolvers, cache, diagnostics) to the pre-retrofit behavior where `acf/init` called `register_all()`. Requires code deploy; no runtime toggle.
- **Temporary full registration in bootstrap**: Only if explicitly approved: in `ACF_Registration_Provider`, temporarily call `run_full_registration()` instead of `run_registration()` on `acf/init`. Document the change, ticket, and revert date. Must be reverted after diagnosis or policy expiry.

Neither option may be exposed as a user-facing or public setting. Both are internal/developer-only.

---

## 4. Safe rollback steps (code rollback)

1. Identify the commit range for the ACF performance retrofit (Prompts 281–302).
2. Create a revert branch or patch that restores `acf/init` to calling the registrar’s `register_all()` (or equivalent pre-retrofit path).
3. Run acceptance and regression tests; confirm front-end and admin behavior.
4. Deploy per release process; document rollback in release notes and known-risk register.
5. Schedule re-application of conditional registration with fix or mitigation.

---

## 5. Alignment with exception matrix and release gate

- **Exception matrix**: Rollback does not change the rule that only documented tooling may invoke full registration. A temporary bootstrap full registration is an explicit, one-off exception to be reverted.
- **Release gate**: [acf-registration-performance-release-gate.md](../release/acf-registration-performance-release-gate.md) remains the checklist for re-enabling or re-validating the retrofit after rollback.

---

## 6. Cross-references

- [acf-conditional-registration-support-runbook.md](acf-conditional-registration-support-runbook.md)
- [acf-registration-exception-matrix.md](../contracts/acf-registration-exception-matrix.md)
- [acf-registration-performance-release-gate.md](../release/acf-registration-performance-release-gate.md)
- [known-risk-register.md](../release/known-risk-register.md) (ACF-1)
