# Industry bundle import: preview, conflicts, and apply

**Audience:** Operators who upload industry pack bundles (JSON).  
**Screen:** **Industry Bundle Import Preview** (`aio-page-builder-industry-bundle-import-preview`).  
**Related:** [industry-admin-workflows.md](industry-admin-workflows.md); [import-export-and-restore.md](../operator/import-export-and-restore.md) (full site ZIP restore — different pipeline); [industry-pack-bundle-format-contract.md](../../contracts/industry-pack-bundle-format-contract.md) (format).

This flow is **not** the same as **Import / Export** ZIP restore: it applies a **validated industry pack JSON bundle** (packs, starter bundles, presets, overlays, optional site profile, etc.) with **hash-based conflict detection** and **per-object replace/skip** choices. It does **not** replace WordPress pages or the full plugin backup/restore pipeline.

---

## What an industry bundle is

A **bundle** is a single **JSON** document produced or consumable per **`Industry_Pack_Bundle_Service`**: manifest fields (e.g. `bundle_version`, `schema_version`, `created_at`, `included_categories`) plus **payload arrays** keyed by category, such as:

- `packs`, `starter_bundles`, `style_presets`, `cta_patterns`, `seo_guidance`, `lpagery_rules`
- `section_helper_overlays`, `page_one_pager_overlays`, `question_packs`
- `site_profile` (industry profile / applied preset–shaped data when included)

Bundles are **admin-only**, non-executable structured data. Maximum upload size for preview is **10 MB** (`Industry_Bundle_Upload_Validator::MAX_BYTES`); file must be **`.json`** with allowed MIME detection.

---

## What applying changes (user-facing)

On **Confirm apply** (with valid preview and permissions), **`Industry_Bundle_Apply_Service`**:

1. Re-validates the bundle structure.
2. Recomputes **conflicts** against **effective local hashes** (built-in pack definitions + previously applied bundle payloads in order).
3. If any conflicts exist, requires an explicit **`replace`** or **`skip`** for **each** conflict row.
4. Builds the **payload** to store per **scope** (see below).
5. Persists:
   - Payload under a generated bundle id (option key pattern `aio_pb_industry_bundle_payload_{id}`).
   - Conflict snapshot for that apply (`aio_pb_industry_bundle_conflicts_{id}`).
   - An entry in the **industry bundle registry** (version, scope, user, timestamp, status applied).
   - **Merge state** `apply_order` updated so later applies layer correctly.

**Net effect for operators:** industry pack definitions, starter bundles, presets, overlays, and related artifacts available to the Industry subsystem are **updated according to scope and your decisions**; registry and merge history reflect the apply. This is **additive/overlay-oriented** industry configuration — not a full WordPress content migration.

---

## Preview and conflict review

### Preview (`aio_import_data`)

- Upload JSON → **Preview bundle** posts to `aio_industry_bundle_preview` (nonce + capability).
- Parsed bundle and **conflict list** are stored in a **per-user transient** (~**15 minutes**).
- Screen shows **bundle summary** (version, created time, included categories, item counts per category).

### How conflicts are detected (`Industry_Bundle_Conflict_Scanner`)

- For each object in each **included** category, the scanner derives an **object key** (e.g. industry key for packs, bundle key for starter bundles, or keys derived from `style_preset_key`, `pattern_key`, `industry_key` + section/page keys for overlays).
- **No row** if the key does not exist locally — treated as **new** content (no conflict line).
- **No row** if the key exists and **content hash** matches local — **identical**; nothing to decide.
- **Conflict** when the key exists locally and the **hash differs** — type shown as **`same_key_different_content`**.

Local hashes include **built-in pack definitions** (to avoid silent overwrite of built-in keys) plus **hashes from prior applied bundles** in `apply_order`.

### Apply permissions

- **Preview:** `aio_import_data`.
- **Apply:** `aio_manage_settings` — without it, the screen shows a warning and **Confirm apply** is disabled.

---

## Scopes and decisions

### Scope

| Scope | Meaning |
|-------|---------|
| **Import full site package** | After filtering (below), all included payload categories from the bundle can be represented in the stored payload. |
| **Import settings only** | Only the **`site_profile`** category is kept in the stored payload; other category arrays are removed. |

**Critical UX detail:** Conflict scanning runs on the **entire** uploaded bundle. If the table lists conflicts for `packs` or `starter_bundles` but you choose **Import settings only**, you must still choose **Replace** or **Skip** for **every** conflict row before apply succeeds — even though only `site_profile` will be persisted.

### Per-conflict decisions

- **Replace** — For **full** scope: incoming item is kept in the payload when content differs from local (subject to identical-hash short-circuit below).
- **Skip** — Incoming item for that key is omitted from the stored payload for that apply.

When there are **no** conflicts, no radio groups are shown; apply proceeds without decision fields.

### Default when no conflict row

For **full site package**, if a key is **not** in the conflict list, the effective decision defaults to **replace** when building payload. Items whose hash **matches** local are **dropped** (no-op — not stored again).

---

## Step-by-step: Previewing a bundle

1. Ensure **`aio_import_data`** and a trustworthy JSON export (plugin-validated structure).
2. Open **Industry Bundle Import Preview** (Industry submenu; exact label per menu registration).
3. Choose the `.json` file (≤ **10 MB**).
4. Click **Preview bundle**.
5. Read **Bundle summary** and category counts; confirm the file is the intended pack/version.
6. If the session expires, re-upload (transient timeout).

---

## Step-by-step: Reviewing conflicts

1. If the table is empty: **no same-key/different-content** clashes with effective local state; new keys apply without a conflict row.
2. For each row, read **Category**, **Object key**, **Conflict type** (`same_key_different_content`).
3. Decide **Replace** if the bundle should win for that key, **Skip** if local (or a prior apply) should win.
4. You must select one option **per row** when any conflicts exist — partial selection fails apply with **“Conflicts require explicit decisions.”**

---

## Step-by-step: Applying safely

1. Complete preview while the transient is valid; resolve all conflicts if present.
2. Choose **Scope** (`Import settings only` vs **Import full site package**) deliberately.
3. Optionally set **Bundle label** (stored as slug/label on the registry record; optional).
4. If you have **`aio_manage_settings`**, click **Confirm apply**.
5. On failure, an error notice appears (redirect query); on success, preview transient is **cleared** and the URL may include an apply-result query parameter — **verify** outcome via Industry Profile, health reports, or registry-related screens; do not assume a prominent success banner exists on this screen.
6. Use **Clear preview** (nonce link) to discard preview without applying.

**Risk-aware practices**

- Export or document current industry state before large **Replace** batches.
- Prefer **staging** for unfamiliar bundles.
- After apply, run **Industry Health** or related reports if your workflow depends on pack coherence.

---

## Edge cases

| Situation | Notes |
|-----------|--------|
| **Overlapping existing state** | Conflicts only when **same object key** and **different hash**; identical content is skipped automatically in full-scope payload build. |
| **Partial or invalid JSON** | Rejected with user-facing invalid JSON / invalid bundle structure messages. |
| **Uncertainty: replace vs skip** | **Skip** preserves effective local content for that key for this apply; **Replace** includes incoming item in stored payload (full scope). There is no three-way merge in UI — binary choice per conflict. |
| **Settings-only with non–site_profile conflicts** | You still must answer **every** conflict row; only `site_profile` is kept in the stored bundle payload. |
| **Built-in packs** | Local hash index includes builtins; changing a built-in key’s content from a bundle shows as conflict. |
| **Layered applies** | `merge_state.apply_order` affects effective hashes for **subsequent** previews — order of operations matters. |
| **After apply** | Registry + merge state updated; invalidate/refresh industry read models may be triggered elsewhere — re-check profile, starter bundles, and health UIs. |

---

## FAQ

**Is this the same as Import / Export restore?**  
No. ZIP restore uses **`Restore_Pipeline`** and plugin export categories. Industry bundle import uses **`Industry_Bundle_Apply_Service`** and JSON bundle validation.

**Who can preview vs apply?**  
Preview: **`aio_import_data`**. Apply: **`aio_manage_settings`**.

**Why do I see conflicts but “settings only”?**  
Scan covers the whole file; scope only limits **what is stored**, not which rows appear.

**What if I leave conflict radios unset?**  
Apply is rejected until every conflict has **`replace`** or **`skip`**.

---

## Troubleshooting

| Symptom | Check |
|---------|--------|
| File too large / wrong type | 10 MB limit; real JSON MIME/extension. |
| Invalid bundle structure | Use bundles from this plugin’s export/contract tooling. |
| Permission denied on apply | Grant **`aio_manage_settings`** or have an administrator apply. |
| No preview after idle | Re-run **Preview bundle** (transient expired). |
| Unexpected overwrite | Review **`apply_order`** and prior applies; use **Skip** or staging. |
| Deeper industry drift | [support-triage-guide.md](../../guides/support-triage-guide.md); Industry health / drift reports per [industry-admin-workflows.md](industry-admin-workflows.md). |

---

## Implementation references (aligning docs with code)

- Screen: `Industry_Bundle_Import_Preview_Screen`
- Handlers: `Admin_Menu::handle_industry_bundle_preview`, `handle_industry_bundle_apply`
- Services: `Industry_Bundle_Upload_Validator`, `Industry_Bundle_Conflict_Scanner`, `Industry_Bundle_Apply_Service`, `Industry_Pack_Bundle_Service`
