# Extension Pack Compatibility Evidence

**Governs:** Spec §54, §56.3, §56.10, §59.14; Prompt 127.  
**Purpose:** Record test execution and outcomes for extension-pack environments only. Baseline (GeneratePress + GenerateBlocks + ACF Pro) is documented in [compatibility-matrix.md](compatibility-matrix.md); this file is for **additional-tested** themes and plugins.

**Rules:** Do not claim compatibility for an environment without a corresponding test record. Add a compatibility shim only when a verified issue requires it; document the shim and the issue here.

---

## 1. Evidence Table

| Target | Type | Test date | Result | Shims required | Shim file / notes |
|--------|------|-----------|--------|----------------|-------------------|
| Astra | Theme | TBD | Pending | No | — |
| Kadence | Theme | TBD | Pending | No | — |
| Yoast SEO | Plugin | TBD | Pending | No | — |

- **Result:** Pass / Fail / Pending. Use Pending until tests are run and recorded.
- **Shims required:** Yes only if a code shim was added to resolve a verified conflict; document file path and purpose in the last column.

---

## 2. How to Add or Verify an Extension-Pack Target

1. **Select target:** Choose a theme or plugin that is a candidate for the extension pack (block-capable theme or coexistence plugin per spec §6.10, §54).
2. **Run compatibility test:** On a test install with WP 6.6+, PHP 8.1+, ACF Pro 6.2+, GenerateBlocks 2.0+, activate the target theme or plugin. Execute: activation, core admin flows, Build Plan and execution path (or current scope). Record any failure or degradation.
3. **Record evidence:** Update the table above with Test date, Result (Pass/Fail), and Shims required (Yes/No). If a shim was added, add the shim file path and a short note.
4. **Update matrix:** Ensure [compatibility-matrix.md](compatibility-matrix.md) Section 6 lists the target and points here. If result is Fail, either document as unsupported for that combination or add a shim and re-test.
5. **No overclaiming:** Do not add targets without running tests. Do not claim "supports all themes" or "supports all SEO plugins."

---

## 3. Shim Log

When a compatibility shim is added (e.g. under `plugin/src/` or `plugin/src/Domain/.../Compatibility/`), record it here so the extension pack remains traceable.

| Shim | Target | Issue addressed | Verified |
|------|--------|------------------|----------|
| *(none yet)* | — | — | — |

---

## 4. Summary

- **Baseline:** GeneratePress + GenerateBlocks + ACF Pro — required and validated per compatibility-matrix.md; does not depend on this file.
- **Extension pack:** Astra, Kadence, Yoast SEO — additive only; test status and shims are recorded above. Unsupported environments remain unsupported.
