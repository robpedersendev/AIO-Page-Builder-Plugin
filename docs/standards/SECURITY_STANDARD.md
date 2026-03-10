# Security Standard

This document defines the security baseline for this plugin. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

## Capability Checks

Every privileged action requires a capability check.

- Before performing any operation that modifies data, changes settings, or accesses sensitive information, verify the current user has the appropriate capability (e.g. `manage_options`, `edit_posts`).
- Use `current_user_can()` or equivalent. Never assume the request originates from an authorized context without verification.

## Nonces for State-Changing Actions

Use nonces for state-changing admin requests.

- All forms, AJAX calls, and REST requests that create, update, or delete data must include a valid nonce.
- Verify the nonce before processing. Reject requests with invalid or missing nonces.
- Use `wp_create_nonce()` and `wp_verify_nonce()` with action names that identify the intent.

## Input and Output

- **Validate first:** Check that input meets expected shape, type, and constraints before use.
- **Sanitize when needed:** Apply sanitization when storing or transmitting data (e.g. `sanitize_text_field()`, `sanitize_textarea_field()`).
- **Escape on output:** Escape all dynamic content when rendering (e.g. `esc_html()`, `esc_attr()`, `esc_url()`). Use the appropriate escaping function for the output context.

## Secrets and Sensitive Data

- Never expose secrets client-side (API keys, tokens, passwords, connection strings).
- Never log or transmit secrets, tokens, passwords, or raw API keys.
- Store secrets in secure configuration (e.g. constants, environment variables, options with appropriate access control). Do not commit secrets to version control.
- Use placeholders in code and document how to supply real values.

## Personal Data

Any personal data handling must consider:

- GDPR / privacy export and erase integration where applicable.
- Privacy disclosure in admin-facing documentation and settings.

## REST and AJAX

- **REST routes:** Register explicit permission callbacks. Do not rely on default behavior.
- **AJAX handlers:** Verify intent (nonce), capability, and payload shape before processing.
- **Errors:** Return structured errors and actionable messages. Avoid leaking sensitive data in error responses.
