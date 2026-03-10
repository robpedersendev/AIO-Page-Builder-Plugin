# Engineering Standard

This document defines the engineering baseline for this plugin and any plugin built on this base. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

## Applicability

- All code in this plugin.
- Any plugin forked, extended, or built on this base.
- Third-party integrations that ship with the plugin.

## WordPress Baseline

- **WordPress Coding Standards (WCS):** All PHP must pass phpcs with WordPress, WordPress-Extra, and WordPress-Docs rulesets. Treat WCS warnings as design feedback, not optional decoration.
- **Plugin Check:** Run Plugin Check and treat findings as required review items even for private distribution.
- **Native APIs:** Prefer native WordPress storage and APIs over custom abstractions unless justified.
- **Admin UI:** Admin pages must feel native to WordPress. Use core components, patterns, and styling conventions.
- **REST and AJAX:** REST routes and AJAX handlers must follow WordPress conventions. See [SECURITY_STANDARD.md](SECURITY_STANDARD.md).

## Architecture

### Layered Separation

Code must be organized into distinct layers:

- **Bootstrap:** Plugin activation, deactivation, and initial registration.
- **Admin UI:** Menus, screens, and admin-facing JavaScript.
- **REST / AJAX:** HTTP endpoints and AJAX handlers.
- **Domain Services:** Business logic independent of WordPress internals.
- **Persistence:** Data storage and retrieval.
- **Reporting:** Outbound operational reporting (see [REPORTING_EXCEPTION.md](REPORTING_EXCEPTION.md)).
- **Diagnostics:** Logging, debugging, and health checks.

Keep planner logic separate from executor logic. Do not couple generated content survival to plugin activation unless explicitly approved.

### Code Organization

- Use namespaces and autoloading for new code.
- Avoid giant god classes and procedural sprawl.
- Prefer stable contracts, deterministic behavior, and maintainable code over cleverness.
- Do not introduce hidden behavior.

## Planning

Before major implementation:

1. Produce a concise plan covering:
   - Files changed
   - Risks
   - Data model impact
   - Security impact
   - Rollback / uninstall impact
   - Test strategy

2. For destructive or irreversible behavior, produce a preflight checklist before coding.

3. Explain architectural tradeoffs before implementing major changes.

## Quality

- No code is complete until it passes linting, static analysis, and relevant tests.
- Every feature must include failure handling, logging, and uninstall/cleanup reasoning.
- Long-running work must be queued, chunked, or scheduled.
