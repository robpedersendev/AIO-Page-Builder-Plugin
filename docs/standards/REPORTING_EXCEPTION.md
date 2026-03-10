# Reporting Exception

This document describes the narrow, documented exception to wordpress.org-style behavior for private-distribution operational reporting. It is designed to be explicit, reviewable, and reusable for future unrelated WordPress plugins.

## Context

This plugin is privately distributed. The only standing exception to wordpress.org-style behavior is approved private-distribution operational reporting. Outbound install notifications, heartbeat messages, and diagnostics are allowed only because this product is privately distributed.

## Permitted Scope

The following outbound reporting is permitted:

- **Install notifications:** Signals that the plugin has been activated or installed.
- **Heartbeat messages:** Periodic status or health indicators.
- **Diagnostics:** Non-sensitive operational data to support troubleshooting and monitoring.

Nothing beyond these categories is permitted without a documented decision. Do not broaden this exception without explicit approval and an entry in [DECISION_LOG.md](../decisions/DECISION_LOG.md).

## Isolation

- Reporting code must be isolated in its own domain (separate namespace and/or module).
- Reporting logic must not be coupled to core plugin behavior. Core features must function independently of reporting success or failure.

## Payload Requirements

All reporting payloads require:

- **Schema definitions:** Document each field, its type, and purpose.
- **Redaction rules:** Define what must never be included (e.g. passwords, tokens, user-identifying data beyond what is necessary).
- **Retry rules:** Define retry behavior on failure (backoff, max attempts).
- **Timeout rules:** Define timeouts for outbound requests.
- **Audit logs:** Log delivery outcomes (success, failure, reason) for operational visibility.

Minimize payloads. Document fields. Log delivery outcomes.

## Failure Isolation

Reporting failure must never take down core plugin behavior. If reporting fails (network error, timeout, server error), the plugin must continue to function normally. Report failures gracefully and optionally log them for debugging.

## Disclosure

Reporting must be disclosed in:

- Admin-facing documentation
- Settings and help content
- Any place where users can learn what external communication the plugin performs

Users must be able to understand that the plugin sends outbound data and what categories of data are involved.
