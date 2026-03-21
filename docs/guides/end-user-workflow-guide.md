# AIO Page Builder — End-User Workflow Guide

**Audience:** Content editors and users who complete onboarding, review Build Plans, and trigger execution.  
**Spec:** §1.5.4, §0.10.7.  
**Purpose:** Concise guidance for onboarding/profile and Build Plan review.  
**Knowledge base:** [KB index](../kb/index.md#start-here-three-paths) (start-here paths); [FILE_MAP.md](../kb/FILE_MAP.md); [concepts-and-glossary.md](../kb/concepts-and-glossary.md) (terms and default permissions); [master-faq.md](../kb/master-faq.md) (short answers).

**Default WordPress roles:** The **Editor** role receives only `aio_view_build_plans`, `aio_approve_build_plans`, and `aio_view_logs` unless an administrator adds more. If you cannot open **Onboarding & Profile** or **Execute** actions, ask for the appropriate capability (see concepts guide).

---

## 1. Onboarding and profile

- **Where:** **AIO Page Builder → Onboarding & Profile**.

Complete the steps to provide brand and business profile data. This information is used so the AI can produce relevant Build Plans and recommendations. You can **Save draft** and return later. Finish onboarding before relying on AI-generated plans. Operator-level detail (all steps, drafts, **Request AI plan**): [onboarding-and-profile.md](../kb/operator/onboarding-and-profile.md).

---

## 2. Build Plan review

- **Where:** **AIO Page Builder → Build Plans**. Open a plan to see the workspace.

**Full guides:** [build-plan-overview.md](../kb/operator/build-plan-overview.md) — step list, safety before execute, statuses, advisory items. **Existing pages + new pages (approve/deny/bulk):** [build-plan-review-existing-and-new-pages.md](../kb/operator/build-plan-review-existing-and-new-pages.md).

**What you see:** A **context rail** (plan ID, source run, status, summaries) and a **stepper** with numbered steps. The default order starts with **Overview**, then **Existing page changes**, **New pages**, **Hierarchy & flow**, **Navigation**, **Design tokens**, **SEO**, **Confirm**, and **Logs & rollback**—trust the **labels on screen** if your plan differs.

**What you do:** Move through the stepper; on each step, review items and use **Approve** / **Deny** (or **Apply** / **Deny** on navigation), bulk actions where offered, and **Execute** only when your role allows and the item type supports it. Later steps include confirmation and execution history. **SEO** and some notes may be **advisory only** (no execute).

Only approved items are executed. Do not approve items you have not reviewed. The system does not execute without your review and approval (and execution capability where required).

---

## 3. After execution

- **Queue & Logs:** **AIO Page Builder → Queue & Logs** shows queue status and execution logs. Rows can link back to the Build Plan or AI Run.  
- **Rollback:** If something went wrong and the plan supports rollback, use the rollback action on the **Logs & rollback** step (last step in the default stack). Rollback is queued and may not apply in all cases; check the result and verify important content.

---

## 4. Where to get more detail

Operators and admins: see [admin-operator-guide.md](admin-operator-guide.md). Support and diagnostics: see [support-triage-guide.md](support-triage-guide.md). Full workflow index: [FILE_MAP.md](../kb/FILE_MAP.md).
