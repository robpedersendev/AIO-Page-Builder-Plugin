# Build Plan Admin IA Contract (Stub)

**Spec**: §49.3 Screen Hierarchy; §49.4 Screen Entry Points; §49.7 Page Templates Screen

**Status**: Stub. Full contract for Build Plan screen hierarchy, workspace steps, and template-selection IA to be expanded.

**Page template directory linkage**: When the user selects a **page template** (e.g. for a new plan or composition), the Build Plan flow may use a template picker that **deep-links** into or from the **page template directory**. The directory IA is defined in **page-template-directory-ia-extension.md**. Build Plan remains the **operational action center** (§49.3); directory browsing is additive and does **not** replace Build Plan or composition workflows. Entry points: Dashboard → Start/Resume Onboarding; Onboarding → Run Crawl / Submit Planning Request; AI Runs → Create Build Plan; **Build Plans → Open Active Plan** (§49.4). Template picker may offer “Browse page templates” linking to directory at appropriate category/family or template detail.

---

## Cross-references

- **page-template-directory-ia-extension.md**: Directory tree, breadcrumbs, list/detail, one-pager/preview links; deep-link from Build Plan template selection.
- **admin-screen-inventory.md**: Build Plans submenu, screen slug, capability.
