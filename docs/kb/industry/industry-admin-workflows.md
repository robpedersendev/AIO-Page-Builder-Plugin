# Industry Pack subsystem — admin workflows

**Audience:** Operators and industry authors.  
**Canonical map:** [FILE_MAP.md](../FILE_MAP.md) §12–§14.  
**Related:** [admin-screen-inventory.md §2.1](../../contracts/admin-screen-inventory.md); [industry-support-training-packet.md](../../operations/industry-support-training-packet.md) (support); [industry-operator-curriculum.md](../../operations/industry-operator-curriculum.md) (training).

---

## Scope (architecture only)

One KB article covers **all Industry-labeled submenus** registered in `Admin_Menu::register()`: profile, overrides, author dashboard, health and drift reports, comparisons, guided repair, bundle import preview, style preset and layer comparison, and embedded assistants on template screens. Prevents parallel “one doc per screen” duplication; subsections will map menu label → purpose → primary actions.

---

## Target outline for full article

- Prerequisites: Industry subsystem available; `aio_manage_settings` (and other caps per screen).
- Industry Profile: primary/secondary industry, starter bundle assistant, pack references, warnings.
- Overrides management: what can be overridden and how conflicts appear.
- Reports and comparisons: when to use health vs drift vs maturity vs readiness reports.
- Guided Repair and Bundle Import: high-level safety and confirmation patterns.
- Style preset vs global styling: link to [global-styling.md](../operator/global-styling.md) and template detail styling.
- Embedded panels (filters, composition assistant, create-page assistant): where they appear.
- Support: when to move to [support-triage-guide.md](../../guides/support-triage-guide.md) and documentation summary export (contract-linked).
