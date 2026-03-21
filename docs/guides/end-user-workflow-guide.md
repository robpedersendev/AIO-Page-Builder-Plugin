# AIO Page Builder — End-User Workflow Guide

**Audience:** Content editors and users who complete onboarding, review Build Plans, and trigger execution.  
**Spec:** §1.5.4, §0.10.7.  
**Purpose:** Concise guidance for onboarding/profile and Build Plan review.  
**Knowledge base:** [KB index](../kb/index.md) (end-user lane); [FILE_MAP.md](../kb/FILE_MAP.md); [concepts-and-glossary.md](../kb/concepts-and-glossary.md) (terms and default permissions).

**Default WordPress roles:** The **Editor** role receives only `aio_view_build_plans`, `aio_approve_build_plans`, and `aio_view_logs` unless an administrator adds more. If you cannot open **Onboarding & Profile** or **Execute** actions, ask for the appropriate capability (see concepts guide).

---

## 1. Onboarding and profile

- **Where:** **AIO Page Builder → Onboarding & Profile**.

Complete the steps to provide brand and business profile data. This information is used so the AI can produce relevant Build Plans and recommendations. You can **Save draft** and return later. Finish onboarding before relying on AI-generated plans.

---

## 2. Build Plan review

- **Where:** **AIO Page Builder → Build Plans**. Open a plan to see the workspace.

**What you see:** A stepper with steps such as existing page updates, new page creation (build intent), navigation changes, then execution and logs/rollback.

**What you do:**  
- **Step 1 — Existing page updates:** Review each item; **Approve** or **Deny**, or use **Bulk approve** / **Bulk deny** for the step.  
- **Step 2 — Build intent:** Approve items you want built, or use **Build All** / **Build selected**.  
- **Step 3 — Navigation:** Approve or deny menu/navigation items; **Apply All** / **Deny All** available.  
- **Later steps:** Review the approved set, then confirm or start execution as shown in the UI.

Only approved items are executed. Do not approve steps you have not reviewed. The system does not execute without your review and approval.

---

## 3. After execution

- **Queue & Logs:** **AIO Page Builder → Queue & Logs** shows queue status and execution logs. Rows can link back to the Build Plan or AI Run.  
- **Rollback:** If something went wrong and the plan supports rollback, use the rollback action in the Build Plan workspace (Step 7). Rollback is queued and may not apply in all cases; check the result and verify important content.

---

## 4. Where to get more detail

Operators and admins: see [admin-operator-guide.md](admin-operator-guide.md). Support and diagnostics: see [support-triage-guide.md](support-triage-guide.md). Full workflow index: [FILE_MAP.md](../kb/FILE_MAP.md).
