# Profile snapshots and Profile History

**Audience:** Operators with **`aio_manage_settings`**.  
**Menu:** **Profile History** — **Screen title:** **Profile Snapshot History** (`aio-page-builder-profile-snapshots`).  
**Related:** [onboarding-and-profile.md](onboarding-and-profile.md); [import-export-and-restore.md](import-export-and-restore.md); [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md); [advanced-ai-labs.md](advanced-ai-labs.md) (Prompt Experiments); [concepts-and-glossary.md](../concepts-and-glossary.md) (profile snapshot vs execution snapshot).

---

## What a profile snapshot is

A **profile snapshot** is a **point-in-time record** of the **brand profile** and **business profile** portions of the site profile (`Profile_Store`), stored in the database table **`aio_profile_snapshots`**. Each row includes:

- **`snapshot_id`** — unique id (e.g. `snap_{utc_datetime}_{random}`).
- **`created_at`**, **`profile_schema_version`**, **`scope_type`**, **`scope_id`**, **`source`**.
- **`brand_profile`** and **`business_profile`** payloads (per product profile schema).

Snapshots **do not** store **template preference profile** data in the serialized snapshot payload used for capture/export (`Profile_Snapshot_Helper` only copies brand + business). That matters for **restore semantics** (below).

---

## When snapshots are created (automatic)

**`Profile_Snapshot_Capture_Service`** persists snapshots on a **best-effort** basis (failures are logged, callers are not broken):

| Hook | Source label (UI) |
|------|-------------------|
| `aio_pb_brand_profile_merged` | Brand profile saved |
| `aio_pb_business_profile_merged` | Business profile saved |
| `aio_pb_onboarding_run_completed` | Onboarding AI run |

Each successful capture may emit a **`profile_snapshot_captured`** line to the **PHP error log** (JSON: `snapshot_id`, `source`, `scope_type`, `saved`, etc.). This is **server-side diagnostics**, not the **Queue & Logs** reporting UI.

**Restore-driven snapshots** (see below) add sources **Pre-restore backup** and **Restore applied** via the same factory/repository.

---

## How history works in the admin UI

- The screen lists up to **20** snapshots (**newest first**), with columns: **Snapshot ID**, **Captured**, **Source**, **Changed fields vs current** (diff summary), **Restore**.
- **Diff summary** uses **`Profile_Snapshot_Diff_Service`**: either “No differences” or “*n* / *m* fields differ” compared to the **current** profile store.
- If **no** snapshots exist, the screen explains that snapshots are created when brand/business profile is saved or onboarding AI run completes (same messaging as product copy).

---

## How restore works and what it overwrites

1. **Capability:** **`aio_manage_settings`** (view and restore).
2. **Nonce-protected** POST to **`admin-post.php`** action **`aio_restore_profile_snapshot`**.
3. **Before** overwriting: a **`pre_restore_backup`** snapshot is saved (current brand + business).
4. **Overwrite:** **`Profile_Store::set_full_profile()`** is called with **`brand_profile`** and **`business_profile`** from the chosen snapshot only.
5. **Template preferences:** Because the restore payload does **not** include `template_preference_profile`, **`Profile_Store`** **retains the current template preference profile** when restoring from a snapshot (it is not reset to an empty default in that code path).
6. **After** apply: a **`restore_event`** snapshot is saved to reflect the post-restore state.
7. **Success notice** on redirect; **audit:** **`profile_snapshot_restore`** JSON line to **PHP error log** (actor id, source snapshot id, pre/post snapshot ids, timestamp).

**Confirm dialog** (product copy): warns that restore **overwrites current brand and business profile** and that a **backup snapshot is saved first**.

---

## Export and import interaction

**Export (ZIP, `profiles` category, non-redacted export):** When the export generator is wired with **`profile_snapshot_repository`**, it writes **`profiles/profile_snapshot_history.json`** (array of serialized snapshots). **Support-bundle / redacted** exports skip this file.

**Import / restore pipeline:** If the ZIP contains that file and the restore pipeline has a snapshot repository, records whose **`profile_schema_version`** matches the running plugin’s **`Versions::PROFILE_SCHEMA_VERSION`** are **re-saved** into the snapshot table; incompatible versions are **skipped** with a warning log entry — they are **not** silently upgraded.

Full ZIP restore semantics: [import-export-and-restore.md](import-export-and-restore.md).

---

## Step-by-step: Reviewing snapshot history

1. Open **AIO Page Builder → Profile History**.
2. Read **source** labels to see whether a row came from onboarding, a save, restore, or pre-restore backup.
3. Use **Changed fields vs current** to see whether that snapshot still differs from today’s profile.
4. Remember the list is **capped at 20** rows; older rows are not shown on this screen (they may still exist in the table until pruned by product policy — there is no operator “purge” on this screen in current code).

---

## Step-by-step: Restoring a snapshot safely

1. Confirm you have **`aio_manage_settings`**.
2. Prefer **exporting** a current full backup from **Import / Export** before large reversions.
3. Identify the target **Snapshot ID** and read the diff column.
4. Click **Restore** and accept the confirmation — understand **brand + business** are overwritten from the snapshot; **template preference profile** follows **`Profile_Store`** behavior above.
5. After success, re-open **Onboarding & Profile** if you rely on stepper state — restore does **not** reset onboarding draft/step state by itself ([onboarding-and-profile.md](onboarding-and-profile.md)).
6. Optionally verify a new **Pre-restore backup** row appeared for rollback of the rollback.

---

## Edge cases

| Situation | Notes |
|-----------|--------|
| **No snapshots** | Normal on new sites until first brand/business save or onboarding completion hook fires. |
| **Restore changed more than expected** | **Template preference** may stay as-is; diff column only summarizes brand/business vs snapshot. Check **Onboarding & Profile** for other state. |
| **Snapshot from older imported ZIP** | Restore import **skips** snapshot rows with **mismatched** `profile_schema_version`; history may be partial after cross-version restore. |
| **vs Build Plan rollback** | Profile snapshots affect **profile store** only. **Build Plan rollback** uses **execution snapshots** for queued actions — different system ([build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md)). |
| **Capture failed** | Check server **`error_log`** for `profile_snapshot_capture_failed`; UI will not list a snapshot that was never saved. |
| **Many snapshots** | UI shows **20** newest; export may still serialize **all** via `get_all()` without limit in the export path — large sites may produce a large JSON file. |

---

## FAQ

**Is restore logged in Queue & Logs?**  
No. Capture/restore auditing in current code is **PHP `error_log`**, not the reporting log tabs.

**Does restore undo industry profile options?**  
Snapshot payload is **brand + business** in **`Profile_Store`**; industry options are separate settings — not overwritten by this restore path.

**Can editors restore?**  
Restore requires **`aio_manage_settings`**, same as viewing this screen.

---

## Troubleshooting

| Symptom | Check |
|---------|--------|
| Restore failed / service unavailable | Container services `profile_snapshot_repository`, `profile_store`, `profile_snapshot_factory` must exist; retry after deployment issues. |
| Snapshot not found | Id may be stale; list refreshes after applies. |
| Diff shows N/A | Diff service or profile store missing from container. |
| History empty after migration | Hooks may not have fired; save profile once or complete onboarding to generate captures. |

---

## Implementation references

- UI: `Profile_Snapshot_History_Panel`
- Persistence: `Profile_Snapshot_Repository` / `aio_profile_snapshots`
- Capture: `Profile_Snapshot_Capture_Service`, `Profile_Snapshot_Factory`, `Profile_Snapshot_Helper`
- Export/import: `Export_Generator` ( `profile_snapshot_history` ), `Restore_Pipeline` (restore branch)
