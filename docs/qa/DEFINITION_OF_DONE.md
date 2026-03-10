# Definition of Done

A feature or change is not complete until all of the following are satisfied. This document is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

## Code Quality

- [ ] Code passes linting (e.g. `npm run lint:php` / phpcs).
- [ ] Static analysis passes where configured.
- [ ] Relevant tests exist and pass.
- [ ] Plugin Check findings are reviewed and addressed (even for private distribution). Treat warnings as required review items.

## Feature Completeness

- [ ] Every feature includes failure handling.
- [ ] Every feature includes appropriate logging for operational visibility.
- [ ] Uninstall/cleanup reasoning is documented or implemented where data is created.

## Security

- [ ] Capability checks applied to all privileged actions.
- [ ] Nonces used for all state-changing admin requests.
- [ ] Input validated first; sanitized when needed; escaped on output.
- [ ] No secrets exposed client-side or in logs.
- [ ] REST routes register explicit permission callbacks.
- [ ] AJAX handlers verify intent, capability, and payload shape.

## Performance and UX

- [ ] Long-running work is queued, chunked, or scheduled where applicable.
- [ ] Admin UI feels native to WordPress.
- [ ] Errors are structured and actionable; no sensitive data leaked.

## Transparency

- [ ] No hidden behavior.
- [ ] External behavior (e.g. outbound reporting) is disclosed where required.
