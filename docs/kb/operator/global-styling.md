# Global Style Tokens and Global Component Overrides

**Audience:** Operators configuring site-wide styling defaults.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §13.  
**Screens:** `aio-page-builder-global-style-tokens`, `aio-page-builder-global-component-overrides`.

---

## Prerequisites

- **Capability:** `aio_manage_settings` for both **Global Style Tokens** (`aio-page-builder-global-style-tokens`) and **Global Component Overrides** (`aio-page-builder-global-component-overrides`).
- **Relationship:** Site-wide defaults here interact with **per-template styling** on Section/Page Template Detail (`Entity_Style_UI_State_Builder`). **Industry Style Preset** is a separate Industry menu — [industry-admin-workflows.md](../industry/industry-admin-workflows.md).

Full token catalogs and validation rules live in styling specs/contracts; this page orients operators only.

---

## Workflow (high level)

1. Open **AIO Page Builder → Global Style Tokens** or **Global Component Overrides**.
2. Adjust values as your distribution allows; save when the screen provides save actions.
3. Validate rendering on a few section/page templates and on the front end; heavy changes may warrant a staging pass.

**Deeper template library context:** [template-library-operator-guide.md](../../guides/template-library-operator-guide.md).

---

## Edge cases

| Situation | Guidance |
|-----------|----------|
| **Per-template styling disagrees with global** | Detail-screen overrides typically win for that template; confirm which layer applied in the editor preview. |
| **Industry pack active** | Compare with **Industry Style Preset** and Industry reports — [industry-admin-workflows.md](../industry/industry-admin-workflows.md). |
| **Uninstall / portability** | [styling-portability-and-uninstall.md](../../guides/styling-portability-and-uninstall.md); [PORTABILITY_AND_UNINSTALL.md](../../standards/PORTABILITY_AND_UNINSTALL.md). |

---

## Related

- [styling-release-gate.md](../../release/styling-release-gate.md) — release and QA context for styling changes.
