# Spec Revision Note: Industry Bundle JSON Apply In Scope

**Date:** 2025-03-18  
**Decision:** [industry-bundle-apply-decision.md](industry-bundle-apply-decision.md)  
**Master spec:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md)

---

## Purpose

This note formally brings **industry bundle JSON import apply** into product scope. The master spec does not currently mention it; this revision adds it and ties it to the existing contract definitions.

---

## Addition to master spec

The following content should be treated as part of the master specification. Preferred placement: in or immediately after **§4.14 Import / Export Domain**, or in the industry pack extension / lifecycle section if one explicitly enumerates industry features.

**Suggested insertion (e.g. after the bullet list under §4.14 “It includes:”):**

- **Industry pack bundle import (apply):** The plugin SHALL support applying an uploaded industry pack bundle (JSON) to the site’s industry registries and overlays, in addition to the existing preview. Apply semantics—what content is written, where, overwrite/merge/conflict behavior, validation, and safe failure—are defined by the contracts **industry-pack-bundle-format-contract** and **industry-pack-import-conflict-contract**. Apply is admin-only, follows conflict resolution (operator choice or default policy), and MUST NOT bypass full export/restore validation when used in a restore context. Full site backup and restore remain via the Import / Export (ZIP) flow.

**Alternative (short):** In §0.4 Scope or in the industry pack extension layer description, add:

- **Industry pack bundle apply:** Applying an uploaded industry pack bundle (JSON) to industry registries/overlays is in scope; semantics are defined in docs/contracts/industry-pack-bundle-format-contract.md and docs/contracts/industry-pack-import-conflict-contract.md.

---

## Contract references (authoritative)

| Topic | Document |
|-------|----------|
| Bundle format, categories, validation, relationship to full export/restore | docs/contracts/industry-pack-bundle-format-contract.md |
| Conflict types, resolution policies, validation-before-apply, safe failure, auditability | docs/contracts/industry-pack-import-conflict-contract.md |

---

## Effect on existing remediation

- **SPR-007:** Once this spec note is adopted and apply is implemented per the contracts, SPR-007 is satisfied by implementation. Ledger and closeout report can be updated to “Fixed” with a reference to this decision and the apply implementation.
