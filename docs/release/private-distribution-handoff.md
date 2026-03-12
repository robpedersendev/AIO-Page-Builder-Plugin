# Private Distribution Handoff

**Governs:** Spec §6.1 Private Distribution Method; §6.5 Update Delivery Strategy.  
**Purpose:** Checklist for handing off the release candidate to the chosen private-delivery mode. No public repository; no exposure of credentials or internal endpoints.

---

## 1. Delivery modes (spec §6.1)

The plugin supports:

- **Direct plugin ZIP upload** — Customer or operator uploads the ZIP via WordPress **Plugins → Add New → Upload Plugin**.
- **Manual deployment** — Technical maintainer copies the plugin directory to `wp-content/plugins/` (e.g. via SSH, SFTP, or deployment pipeline).
- **Private update delivery** — Updates delivered via an approved private mechanism (e.g. custom update server, secure link); version detection and migration as per §6.5.
- **Environment-specific deployment** — Support or implementation partner installs a specific build on a target site.

---

## 2. Pre-handoff checklist (all modes)

| # | Item | Pass/Fail |
|---|------|-----------|
| 2.1 | Release candidate ZIP built per [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md). | ☐ |
| 2.2 | [final-approval-runbook.md](final-approval-runbook.md) go/no-go completed; release approved. | ☐ |
| 2.3 | Release notes and changelog version/date set; [release-notes-rc1.md](release-notes-rc1.md) (or current) and [changelog.md](changelog.md) reflect the build. | ☐ |
| 2.4 | No secrets, local paths, or internal-only diagnostics in the ZIP or attached docs. | ☐ |
| 2.5 | Handoff recipient and method documented (who, where, how). | ☐ |

---

## 3. Handoff by mode

### 3.1 Direct ZIP upload (customer / operator)

| # | Step | Pass/Fail |
|---|------|-----------|
| 1 | Provide the production ZIP (single root directory, WordPress-installable). | ☐ |
| 2 | Provide operator-facing release notes (or link) with compatibility, migration, reporting disclosure, and known limitations. | ☐ |
| 3 | Communicate required environment: WordPress 6.6+, PHP 8.1+, ACF Pro 6.2+, GenerateBlocks 2.0+. | ☐ |
| 4 | Do not send credentials, update URLs with embedded keys, or internal runbooks. | ☐ |

### 3.2 Manual deployment (technical maintainer)

| # | Step | Pass/Fail |
|---|------|-----------|
| 1 | Provide the production ZIP or a clean copy of the plugin directory (slug-named root). | ☐ |
| 2 | Document deployment steps: extract to `wp-content/plugins/<plugin-slug>/`; ensure file ownership/permissions appropriate for the server. | ☐ |
| 3 | Provide release notes and any migration/upgrade notes if this is an update. | ☐ |
| 4 | If replacing a previous version: recommend backup or export before overwrite; document rollback (restore from backup or reinstall previous package). | ☐ |

### 3.3 Private update delivery (approved mechanism)

| # | Step | Pass/Fail |
|---|------|-----------|
| 1 | Release package and version metadata available to the update mechanism (e.g. version number, ZIP URL or artifact location). | ☐ |
| 2 | Update delivery does not expose credentials or internal endpoints in client-visible URLs or responses. | ☐ |
| 3 | Changelog or release notes available for the version offered (so users can see what changed). | ☐ |
| 4 | Compatibility and migration notes documented for the release (per §6.5 and release notes). | ☐ |

### 3.4 Environment-specific deployment (support / partner)

| # | Step | Pass/Fail |
|---|------|-----------|
| 1 | Identify target environment (site, staging, etc.) and any site-specific constraints. | ☐ |
| 2 | Use the same production ZIP as other modes; no ad-hoc patches or local-only files. | ☐ |
| 3 | Hand off release notes and known limitations; document any site-specific configuration (e.g. API keys) separately from the plugin package. | ☐ |
| 4 | Do not embed site-specific secrets in the ZIP or in docs shipped with the ZIP. | ☐ |

---

## 4. Post-handoff (optional)

| # | Item | Notes |
|---|------|-------|
| 4.1 | Retain the exact ZIP and version tag in release storage for rollback or audit. | Per product policy. |
| 4.2 | Record handoff date, recipient/mode, and version in internal release log if required. | No sensitive data in log. |
| 4.3 | If issues arise, use [support-triage-guide.md](../guides/support-triage-guide.md) and known-risk register; do not share internal waiver or security detail. | — |

---

## 5. Cross-references

| Doc | Purpose |
|-----|---------|
| [release-candidate-packaging-checklist.md](release-candidate-packaging-checklist.md) | ZIP build and validation. |
| [final-approval-runbook.md](final-approval-runbook.md) | Go/no-go before handoff. |
| [release-notes-rc1.md](release-notes-rc1.md) | Operator-facing release content. |
| Spec §6.1, §6.5 | Private distribution and update strategy. |

---

*Complete the pre-handoff checklist and the section for the chosen delivery mode before handing off. Private distribution does not justify omitting checks or shipping secrets.*
