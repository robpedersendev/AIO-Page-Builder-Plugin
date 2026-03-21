# Import, export, and restore

**Audience:** Operators with `aio_export_data` and/or `aio_import_data`.  
**Admin screen:** **AIO Page Builder → Import / Export** (`aio-page-builder-export-restore`).  
**Related:** [admin-operator-guide.md §11](../../guides/admin-operator-guide.md); [export-bundle-structure-contract.md](../../contracts/export-bundle-structure-contract.md); [PORTABILITY_AND_UNINSTALL.md](../../standards/PORTABILITY_AND_UNINSTALL.md); [support-triage-guide.md §3](../../guides/support-triage-guide.md).

This page describes the **real admin workflow**: export creation, package validation (“preview import”), conflict review, restore scope, confirmation, and how that differs from **Build Plan rollback** (snapshots on executed work — see [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md)).

---

## What export contains (user level)

Exports are **ZIP packages** with a root `manifest.json` plus folders such as `settings/`, `styling/`, `profiles/`, `registries/`, `compositions/`, `plans/`, `tokens/`, and optional areas per mode. They capture **plugin-owned configuration and library data**, not a full WordPress site clone.

| Export mode | Typical use | What is included (summary) |
|-------------|-------------|----------------------------|
| **Full operational backup** | Routine backup | Core categories: settings, global styling, profiles (and industry profile fields where exported), section/page template registries, compositions, Build Plans, token sets, uninstall/restore metadata. |
| **Pre-uninstall backup** | Before removing plugin data | Same core set as full operational backup; use when uninstalling from **Privacy, Reporting & Settings**. |
| **Support bundle** | Diagnostics for support | Redacted settings/profile; registries, plans, tokens, and related operational slices; **not** a substitute for a full operational restore. See [support-triage-guide.md §3](../../guides/support-triage-guide.md). |
| **Template only** | Move template library | Registries, compositions, styling (global + entity payloads). Excludes settings, profiles, plans, token sets. |
| **Plan / artifact export** | Move plans and tokens | Plans and token sets; optional normalized AI outputs when the deployment includes them. |
| **Uninstall settings/profile only** | Minimal portability | Settings, profiles, uninstall/restore metadata only. |

**Secrets:** API keys, passwords, and similar categories are **never** placed in export bundles (see contract excluded categories).

**Profile snapshots:** When the deployment exports profile history, it travels under the profiles category (see contract `profile_snapshot_history`). See [profile-snapshots-and-history.md](profile-snapshots-and-history.md) for capture, restore, and import behavior; [onboarding-and-profile.md](onboarding-and-profile.md) for day-to-day profile editing.

After **Create export**, the file appears under **Export history** as `aio-export-*.zip` with a nonce-protected **Download** link.

---

## What restore does

**Restore** reads a validated package and **writes AIO Page Builder plugin data** into the current site in a fixed order (settings-related data, profiles, registries, compositions, token sets, plans, uninstall metadata — see `Restore_Pipeline`).

- **No silent overwrite:** You must pass **Preview import** validation, choose **conflict resolution**, optional **restore scope**, and tick the **confirm restore** checkbox before any write.
- **Not the same as rollback:** Import/restore replaces or merges **bundle** data. Build Plan **rollback** uses execution snapshots for specific actions; see [build-plan-execution-actions.md](build-plan-execution-actions.md) and [build-plan-rollback-and-recovery.md](build-plan-rollback-and-recovery.md).
- **Built pages:** Restore does **not** mean “rebuild every page from the ZIP.” The package restores **definitions and plans**; WordPress posts/pages you already published remain unless you change them elsewhere. The UI states that restore targets **plugin-owned data** and does not clone the whole site.

After **Confirm restore**, the uploaded validation package file is **removed** from the server; the result banner shows success or failure, restored categories, and any **Skipped (not restored)** rows (for example template-library styling paths when the environment cannot apply them).

---

## Restore scope (before Confirm restore)

Two radio choices (only categories **present in the package** apply):

1. **Import settings/profile only** — Restores at most: `settings`, `styling`, `profiles`, `uninstall_restore_metadata`. Everything else in the ZIP is skipped for that run even if the package contains registries or plans.
2. **Import full AIO backup** — Restores all **included** categories from the package subject to conflict resolution.

Use **settings/profile only** when you intentionally want to **avoid** touching template registries, compositions, plans, or token sets (for example after merging templates manually on staging).

---

## Conflict review and decision points

**Preview import** runs validation: manifest, schema rules, allowed ZIP paths, checksum warnings, and a **conflict pre-scan** where repositories are available.

### Conflict table

When conflicts exist, the screen lists **Category**, **Key**, and **Message** (for example “Section key exists,” “Plan exists,” “Token set exists.”). The pre-scan covers **section registry keys**, **plan IDs**, and **token set refs** when those categories are in the package — not every possible overlap is shown as a row; some merges still follow the resolution mode without a separate line.

### Conflict resolution (single choice for the whole restore)

| Mode | Effect |
|------|--------|
| **Overwrite incoming object over current** | Prefer package content for conflicting keys (and generally apply incoming data per pipeline rules). |
| **Keep current and skip import object** | Keep what is already on the site for those conflicts; skip importing the conflicting object. |
| **Import as duplicate where allowed** | Create duplicate keys/IDs where the pipeline supports it. **Not** used for monolithic blobs: for **settings**, **profiles**, and **uninstall_restore_metadata**, “duplicate” is treated as skip — you cannot duplicate those in this flow. |
| **Cancel restore** | Abort; no restore writes. |

**Decision tips**

- **Staging → production:** Often **Overwrite** on staging after a deliberate backup of production, or **Duplicate** for templates/plans when you need both copies.
- **Production has diverged:** **Keep current** protects live keys; pair with **settings/profile only** if you only meant to fix options/profile.
- If you are unsure, **export the current site first**, then run restore on a **staging clone**.

---

## ZIP upload and validation expectations

| Check | Detail |
|-------|--------|
| **Extension** | Must be `.zip`. |
| **MIME** | Server-side check: ZIP content types only (`application/zip`, `application/x-zip-compressed`). Renamed non-ZIP files are rejected. |
| **Size** | Plugin enforces **50 MB maximum** before the package is saved for validation (`52_428_800` bytes). |
| **PHP / host** | `upload_max_filesize` and `post_max_size` can still reject uploads **before** the plugin runs; increase host limits if legitimate packages are blocked below 50 MB. |
| **manifest.json** | Required; must parse and include required manifest keys. |
| **Schema version** | Newer export schema than the running plugin is **blocked**. Same-major and migration rules follow the bundle contract. |
| **Paths** | Only contract-allowed paths; traversal (`..`) or foreign paths fail validation. |
| **Styling** | If `styling` is included, global and entity styling JSON must match expected schema versions. |
| **Checksums** | When the manifest lists checksums, mismatches produce a **warning** (validation can still pass). |
| **ZipArchive** | PHP `ZipArchive` must be available. |

**Session:** Validation is stored **per user** for a limited time (transient, about one hour). If you wait too long, use **Preview import** again.

---

## Deactivation, uninstall, and built-page survivability

**Deactivation:** Nothing is deleted. Built pages, template definitions, settings, and plans remain; only runtime behavior stops until reactivation. (Same messaging as the lifecycle summary on the Import / Export screen.)

**Uninstall (delete plugin):** Only **plugin-owned operational data** is removed when the user proceeds — **not** normal WordPress posts/pages created as built content. **Export before removal** if you need to restore settings, template library, or plans later. Exact uninstall matrices: [PORTABILITY_AND_UNINSTALL.md](../../standards/PORTABILITY_AND_UNINSTALL.md); ACF-specific retention: [acf-uninstall-preservation-operator-guide.md](../../guides/acf-uninstall-preservation-operator-guide.md).

**Built pages** remain on the site after deactivation/uninstall; **template definitions and plans** do not survive uninstall unless you **exported** them and restore after reinstall. Cross-link: [template-system-overview.md](../templates/template-system-overview.md).

The Import / Export screen links to **Privacy, Reporting & Settings** for uninstall choices and shows **template library lifecycle** copy (export before removal, regenerate appendices/previews after restore).

---

## Step-by-step: Exporting

1. Ensure you have **`aio_export_data`**.
2. Open **AIO Page Builder → Import / Export**.
3. Under **Create export**, choose **Export mode** appropriate to your goal (full backup vs template-only vs support bundle, etc.).
4. Click **Create export**. Wait for completion.
5. Use the success **Download** link or **Export history → Download** for `aio-export-*.zip`.
6. Store the ZIP securely (it may contain operational data; treat like any backup).

---

## Step-by-step: Importing (preview and validate)

1. Ensure you have **`aio_import_data`**.
2. Open **Import / Export → Import / Restore**.
3. Choose the `.zip` file (under 50 MB, real ZIP).
4. Click **Preview import**.
5. Read **Validation passed/failed**, **blocking failures**, **warnings**, and **checksum** line.
6. Review **Package contents** (export type, timestamp, plugin/schema version, source site, **included categories**).
7. If validation failed, fix the package or use a compatible plugin version — do not restore.

---

## Step-by-step: Reviewing conflicts

1. After a successful preview, open **Conflicts (review before restore)** if the table is present.
2. For each row, note **category** and **key** — these are the collision points with the **current** site.
3. Decide which **Conflict resolution** mode matches your intent (overwrite vs keep vs duplicate vs cancel).
4. If the table is empty, conflicts were not detected by the pre-scan; you still choose a resolution mode for consistency before confirm.

---

## Step-by-step: Applying restore safely

1. Complete **Preview import** with **Validation passed.**
2. Optionally take a **fresh export** of the current site (full backup) so you can revert mentally operationally.
3. Choose **Restore scope** (full vs settings/profile only).
4. Choose **Conflict resolution**.
5. Tick **I understand this will write AIO Page Builder plugin data…**
6. Click **Confirm restore**.
7. Read the **Restore completed / failed** banner; note **Restored categories** and **Skipped (not restored)**.
8. Re-check critical screens: **Build Plans** ([build-plan-overview.md](build-plan-overview.md)), **Templates**, **Settings**, **Queue & Logs** if plans drive execution.

---

## Edge cases

| Situation | What happens / what to do |
|-----------|---------------------------|
| **File too large** | Over 50 MB: redirect error “Maximum size is 50 MB.” Split data (e.g. template-only export) or raise host limits only if the package is legitimately under 50 MB but PHP blocks earlier. |
| **Invalid package** | Wrong MIME, corrupt ZIP, missing `manifest.json`, bad JSON, blocked schema version, prohibited paths, styling version mismatch → **Validation failed** with blocking messages. |
| **Partial conflict scenarios** | Table lists some keys (sections/plans/tokens); other categories may still restore under the chosen mode without a row. **Duplicate** does not apply to settings/profile blobs. |
| **Restoring into a site with existing state** | Expect conflicts on matching keys; use **Overwrite** vs **Keep current** deliberately. Prefer staging. |
| **Losing built pages** | Uninstall does not delete built pages; you lose **plugin library/plan** data unless exported. Restoring a bundle does not delete posts by itself. |
| **Support bundle** | Treat as diagnostic export; do not assume full restore parity with **Full operational backup**. |
| **Validation expired** | Re-run **Preview import** if confirm says no validation result. |
| **Checksum warnings** | Investigate tampering or incomplete copy; prefer a clean re-export from source. |

---

## FAQ

**Who can export vs import?**  
Separate capabilities: `aio_export_data` for create/download; `aio_import_data` for preview and restore. See [concepts-and-glossary.md](../concepts-and-glossary.md).

**Does restore import WordPress users or media?**  
No. Only AIO Page Builder bundle categories.

**Will restore delete my pages?**  
Not as a default effect of the restore pipeline; it updates plugin-owned records. Always test on staging before production.

**Pre-uninstall vs full backup?**  
Functionally the same core category set from the generator; pre-uninstall is the operator-intent label for uninstall workflows.

**Where is uninstall explained?**  
**Privacy, Reporting & Settings** — see [admin-operator-guide.md §10](../../guides/admin-operator-guide.md).

---

## Troubleshooting

| Symptom | Check |
|---------|--------|
| Upload fails immediately | PHP `upload_max_filesize` / `post_max_size`; web server body limit. |
| “Invalid file type” | File must be a real ZIP (not `.rar` renamed). |
| Schema / migration errors | Plugin version on target must be **≥** export tooling that produced the package; same-major rules per contract. |
| Restore failed / skipped styling | Read **Skipped (not restored)**; environment may lack normalizers/sanitizers for styling restore. |
| No conflicts but unexpected overwrites | Pre-scan is limited; **Overwrite** applies broadly — use **Keep current** or scope **settings/profile only** when unsure. |
| Need help correlating failures | [support-triage-guide.md](../../guides/support-triage-guide.md); **Queue & Logs** → Import/Export Logs where enabled. |

---

## Related documentation

- **Profile and onboarding:** [onboarding-and-profile.md](onboarding-and-profile.md)  
- **Templates and library:** [template-system-overview.md](../templates/template-system-overview.md)  
- **Build Plans:** [build-plan-overview.md](build-plan-overview.md)  
- **Support triage & support bundle:** [support-triage-guide.md](../../guides/support-triage-guide.md)  
- **Bundle structure (implementers):** [export-bundle-structure-contract.md](../../contracts/export-bundle-structure-contract.md)  
- **Uninstall policy:** [PORTABILITY_AND_UNINSTALL.md](../../standards/PORTABILITY_AND_UNINSTALL.md)
