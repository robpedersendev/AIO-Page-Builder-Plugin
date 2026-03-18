# Import/Export ZIP Pre-Move Size Limit — Acceptance Criteria

**Decision:** [import-export-zip-size-limit-decision.md](import-export-zip-size-limit-decision.md)  
**Spec note:** [import-export-zip-size-limit-spec-note.md](import-export-zip-size-limit-spec-note.md)

---

## 1. Threshold and constant

- [ ] A named constant (or single configurable value) defines the maximum allowed size for Import/Export ZIP uploads. **Intended value:** 50 MB (52,428,800 bytes).
- [ ] The same value is used for the pre-move check and (if shown) in user-facing messages.

---

## 2. Enforcement point

- [ ] The size check runs in the Import/Export validate flow **after** nonce, capability, `is_uploaded_file()`, and `.zip` extension checks, and **before** `move_uploaded_file()`.
- [ ] If `$_FILES['aio_ie_package_file']['size']` exceeds the maximum (or size is missing/invalid), the handler does **not** call `move_uploaded_file()`; it redirects with a dedicated error.

---

## 3. UX and messaging

- [ ] A dedicated error code/query arg is used for “file too large” (e.g. `error=file_too_large` or equivalent) so the Import/Export screen can show a specific message.
- [ ] The message is admin-safe and states the maximum size (e.g. “Import package is too large. Maximum size is 50 MB.”). No raw server details in the user-facing message.

---

## 4. Security and consistency

- [ ] No change to nonce or capability checks; the size check is an additional validation step.
- [ ] Behavior is consistent regardless of host `upload_max_filesize` / `post_max_size` (plugin rejects over its cap even when PHP would accept the upload).

---

## 5. Tests

- [ ] At least one test: upload (or simulate) a file over the configured maximum → validate flow rejects before move and returns the oversized error (e.g. redirect with the dedicated error code).
- [ ] At least one test: file at or under the limit → validate flow proceeds (move and validation as today) or is covered by existing validation tests.
