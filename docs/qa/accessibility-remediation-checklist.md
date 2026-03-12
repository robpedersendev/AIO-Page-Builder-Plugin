# Accessibility Remediation Checklist

**Governs:** Spec §51.5–51.10, §50.1, §56.6; Hardening matrix §59.14, category `accessibility`.  
**Purpose:** Audit and remediation evidence for admin workflows and controlled front-end output.  
**Scope:** Dashboard, Onboarding, AI Runs, Build Plans, Queue & Logs, Privacy/Reporting, crawler screens, confirmation/modals.

---

## 1. Checklist (Spec-Aligned)

### 1.1 Focus Management (§51.5)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Visible focus styling on interactive controls | Pass | Relies on WordPress admin styles; no plugin override. |
| 2 | Focus moved to modals when opened | N/A | No JS modals in current scope; when added, must move focus into dialog. |
| 3 | Focus returned when modals close | N/A | Same as above. |
| 4 | Focus on meaningful success/error context after major actions where helpful | Pass | Full-page render after POST; focus at top of page. |
| 5 | No unexpected focus jumps during async updates | Pass | No client-side async DOM replacement of major content. |

### 1.2 Semantic Heading Rules (§51.6)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Headings reflect content hierarchy | Pass | h1 = page title, h2 = major sections, h3 = subsections. |
| 2 | Heading order logical (no skips) | Pass | Remediated: Build Plan workspace step content has h2 for current step. |
| 3 | Headings not used purely for visual styling | Pass | Screen-reader-text h2 used only where needed for landmark reference. |

### 1.3 Landmark and ARIA Rules (§51.7)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Prefer semantic HTML before ARIA | Pass | main/complementary via role where wrapper is div. |
| 2 | Landmarks where they improve navigation | Pass | role="main", role="complementary" on context rail; nav with aria-label. |
| 3 | ARIA for relationships/patterns only when needed | Pass | aria-labelledby, aria-label on nav/regions; no redundant ARIA. |
| 4 | Stateful controls communicate state accessibly | Pass | aria-disabled on disabled controls; role="status" on badges. |

### 1.4 Color Contrast Rules (§51.8)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Text contrast readable against backgrounds | Pass | Admin uses WP admin palette; badges use status text + class. |
| 2 | Error/warning/success not by color alone | Pass | Status badges include text label; notices include icon/text. |
| 3 | Badges/labels legible across surfaces | Pass | Status_Badge_Component uses role="status" and visible label. |

### 1.5 Form Accessibility Rules (§51.9)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Visible labels | Pass | Crawler comparison: for/id on selects; Settings: label_for. |
| 2 | Association of labels with inputs | Pass | Remediated: step list "Select all" uses id/for. |
| 3 | Required fields indicated | N/A | No required fields in current forms; when added, use aria-required and visible indicator. |
| 4 | Accessible helper text | Pass | aria-describedby where used (e.g. crawler rules). |
| 5 | No placeholder-only labeling | Pass | No critical controls use placeholder as sole label. |

### 1.6 Modal / Popup Accessibility (§51.10)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Keyboard open/close | N/A | No modal dialogs in current scope. |
| 2 | Focus trapped within modal when open | N/A | When modals added: trap focus, return on close. |
| 3 | ESC or approved close behavior | N/A | When modals added: ESC must close. |
| 4 | Screen-reader readable title and content | N/A | Confirmation step is inline content, not a dialog. |

### 1.7 Keyboard Navigation (§51.4)

| # | Criterion | Status | Notes |
|---|-----------|--------|--------|
| 1 | Primary actions reachable by keyboard | Pass | Links and buttons are focusable. |
| 2 | Logical tab order | Pass | Native DOM order; no positive tabindex. |
| 3 | Modal focus trapping/release | N/A | No modals. |
| 4 | No mouse-only critical actions | Pass | All critical actions are links or buttons. |

---

## 2. Issue Register (Hardening Matrix §5.1)

| id | category | severity | title | status | closure_evidence / waiver_id |
|----|----------|----------|-------|--------|-------------------------------|
| *(none open)* | — | — | — | — | — |

*When high-severity a11y issues are found, add rows here. Waivers require a waiver record per hardening matrix §5.2.*

---

## 3. Remediation Summary (Prompt 101)

- **Landmarks:** role="main" and aria-label added to Dashboard, Settings, Queue & Logs, AI Runs, Build Plans, Crawler Sessions, Crawler Comparison, Diagnostics, Build Plan workspace (shell and not-found), AI Run Detail.
- **Build Plan workspace:** .aio-build-plan-main marked as main landmark; context rail as complementary; current step given h2 for semantic step title.
- **Forms:** Step item list "Select all" checkbox associated with its label via id/for.
- **Export plan (disabled):** role="button" added to disabled "Coming soon" control for clarity.
- **Modals:** None in scope; checklist and code comments note future modal requirements (§51.10).

---

## 4. QA Evidence

- Manual: Keyboard tab through Dashboard, Onboarding, AI Runs, Build Plan workspace, Queue & Logs, Crawler screens; confirm focus order and visible focus.
- Manual: Screen reader (NVDA/JAWS/VoiceOver): Landmarks and headings on same screens.
- Manual: Form labels: Crawler comparison selects, step list select-all, Settings checkbox.
- Contrast: Admin and badge styles follow WP admin; token-driven front-end contrast covered in §51.8 and template contracts.

---

## 5. Deferred / Known Limitations

- **Modal behavior:** When confirmation or other modals are implemented, they must implement §51.10 (focus trap, return, ESC, title/content semantics). Tracked in product backlog.
- **Plugin-specific focus styling:** Visible focus currently relies on WordPress admin CSS. If plugin admin CSS is added, include `:focus-visible` styles and do not remove outline.
