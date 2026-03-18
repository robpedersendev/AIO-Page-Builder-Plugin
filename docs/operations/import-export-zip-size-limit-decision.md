# Import/Export ZIP Pre-Move Size Limit — Decision

**Date:** 2025-03-18  
**Status:** Accepted  
**Sources:** [aio-page-builder-master-spec.md](../specs/aio-page-builder-master-spec.md) §43.11, §43.12; [security-privacy-remediation-ledger.md](security-privacy-remediation-ledger.md); [security-privacy-audit-close-report.md](../qa/security-privacy-audit-close-report.md) §1.3, §3; current implementation: `Import_Export_Screen::handle_validate()`.

---

## 1. Objective

Decide whether the plugin shall enforce a **pre-move** maximum file size for Import/Export ZIP uploads, or rely only on host/PHP limits.

---

## 2. Current state

- **Flow:** `Import_Export_Screen::handle_validate()` — nonce and capability check; then `is_uploaded_file()` and `.zip` extension check; then **move_uploaded_file()** to exports dir; then `Import_Validator::validate( $dest )`.
- **Validation** (structure, manifest, version, etc.) runs **after** the file is moved. There is no check on `$_FILES['aio_ie_package_file']['size']` before move.
- **Limits today:** Only PHP/host limits (e.g. `upload_max_filesize`, `post_max_size`). If those allow a very large file, the plugin moves it and then validation may fail or the server may be stressed.
- **Remediation/closeout:** Ledger and close report describe “no pre-move size limit for zip” as an optional hardening gap.

---

## 3. Where a pre-move cap would be enforced

In `Import_Export_Screen::handle_validate()`:

- **After:** nonce, capability, container/path checks, `is_uploaded_file()`, and `.zip` extension check on the filename.
- **Before:** `move_uploaded_file( $_FILES['aio_ie_package_file']['tmp_name'], $dest )`.

So: read `$_FILES['aio_ie_package_file']['size']`, compare to the chosen maximum; if over, redirect with a dedicated error and do **not** call `move_uploaded_file()`.

---

## 4. Spec and product requirements

- **§43.11 File Upload Security Rules:** “Any file-upload workflow shall validate: … **file size** …”. The spec requires file size to be part of validation; it does not say “rely only on host limits” or “must set an explicit plugin maximum.”
- **§43.12 ZIP Import Security Rules:** ZIP import “shall be tightly controlled”; rules include permission, package structure, manifest validation, version checks, no arbitrary code execution, safe extraction, rejection of malformed packages.
- **Conclusion:** §43.11 requires validating file size. Satisfying that with an **explicit plugin-enforced maximum** before move is consistent with “tightly controlled” ZIP import and avoids moving very large files only to fail later (clearer UX, less disk use, more predictable behavior across hosts).

---

## 5. Decision

**Outcome: B — Approve an explicit plugin-enforced pre-move maximum.**

- **Rationale:** (1) §43.11 requires file size validation; a concrete pre-move cap satisfies that. (2) Aligns with “tightly controlled” ZIP import and with the pattern used for industry bundle uploads (explicit cap before read/move). (3) Avoids move-then-fail for oversized uploads and gives a clear, consistent error.
- **Intended threshold:** **50 MB** (52,428,800 bytes). Full restore ZIPs (templates, plans, profiles, logs) can be larger than industry JSON bundles; 50 MB is sufficient for typical exports while bounding risk. Implement as a named constant so it can be adjusted if product requirements change.
- **UX:** Reject before move with a dedicated error code/message, e.g. “Import package is too large. Maximum size is 50 MB.” (or similar admin-safe wording). Message should state the maximum so users know the limit.

---

## 6. If outcome had been A (host/PHP only)

If the decision had been to **rely only on host/PHP limits** and not add a plugin cap:

- **Documentation:** State that Import/Export ZIP size is limited by server/PHP settings (`upload_max_filesize`, `post_max_size`); the plugin does not enforce an additional maximum. Recommend that operators set these limits appropriately for restore package sizes.
- **Ledger/closeout:** Leave “no pre-move size limit” as optional hardening; no implementation criteria.

This outcome was not chosen; it is recorded here for traceability only.
