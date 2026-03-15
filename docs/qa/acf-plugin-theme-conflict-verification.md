# ACF Conditional Registration — Plugin/Theme Conflict Verification

**Prompt**: 311  
**Contracts**: acf-conditional-registration-contract.md, acf-third-party-admin-compatibility-matrix.md

---

## 1. Purpose

Records results of testing the conditional-registration retrofit against realistic plugin/theme interference patterns. Confirms registration mode detection and performance hold under common conflicts; documents compatible states and any known unsupported edge cases. Internal only.

---

## 2. Interference scenarios (realistic set)

| Scenario | Description | What we verify |
|----------|-------------|----------------|
| **Admin menu / redirect plugins** | Plugin changes admin menu or redirects post.php to a custom URL. | Resolver still sees post.php + post ID, or fails safe to NON_PAGE_ADMIN; no full registration. |
| **Page builder / editor plugins** | Third-party page builder or editor that loads on page edit (e.g. custom meta boxes, different $pagenow). | If $pagenow remains post.php and post type is page, scoped registration runs; otherwise fail safe. |
| **Theme that alters admin** | Theme that enqueues heavy admin assets or changes admin layout. | Registration mode and timing unchanged; no full registration fallback. |
| **Multiple ACF-dependent plugins** | Other plugins that register ACF groups or hook acf/init. | Our hook at priority 5 runs; we do not depend on being the only consumer of acf/init. |
| **REST / headless admin** | Admin accessed via REST or headless front-end. | Not standard post.php/post-new.php; treated as non-page or skip; zero groups. |
| **Caching / optimization plugins** | Full-page or object cache that might alter request lifecycle. | Single-request registration decision unchanged; cache does not trigger full registration. |

---

## 3. Verification procedure

1. **Baseline**: On clean install (no conflicting plugins), run benchmark scenarios; record registration mode and (optional) query/memory.
2. **Per scenario**: Enable one plugin or theme from the set; repeat same scenarios (front-end, existing-page edit, new-page edit, non-page admin).
3. **Record**: Registration mode correct? Any full registration observed? Resolver fails safe (zero groups) when context ambiguous?
4. **Document**: Outcome (compatible / fail-safe / unsupported) and support guidance in §4.

---

## 4. Outcomes and support guidance

| Scenario | Outcome | Support guidance |
|----------|---------|-------------------|
| Standard admin (no conflict) | Compatible | Primary supported path; see support runbook. |
| Admin menu / redirect (post.php still reachable) | Compatible | If user reaches post.php with valid post ID, behavior as baseline. |
| Admin menu / redirect (post.php replaced or unreachable) | Fail-safe | Resolver may see non-page admin; zero groups. No full registration. Guide user to use standard edit link or document limitation. |
| Page builder overwrites $pagenow | Fail-safe or unsupported | If $pagenow no longer post.php, zero groups. Do not enable full registration; suggest standard editor or document unsupported. |
| Theme alters admin only (no $pagenow change) | Compatible | No change to registration logic. |
| REST/headless admin | Not page-edit context | Zero groups; expected. |
| Caching plugin | Compatible | Registration runs per request; cache may serve cached response; no change to our decision. |

**Unsupported edge cases**: Custom admin UIs that never set $pagenow or post/post_type like WordPress core are not supported for scoped registration; zero groups is the safe outcome. No bespoke integration for specific third-party plugins.

---

## 5. Known risks (update known-risk-register if needed)

- If a specific plugin/theme combination is found to cause incorrect registration (e.g. full registration triggered), add a row to known-risk-register and document mitigation or “avoid X with Y.”
- If all tested scenarios fail safe (zero groups when ambiguous), no full registration; document that in this verification and in third-party matrix.

---

## 6. Cross-references

- [acf-third-party-admin-compatibility-matrix.md](acf-third-party-admin-compatibility-matrix.md)
- [acf-conditional-registration-support-runbook.md](../operations/acf-conditional-registration-support-runbook.md)
- [known-risk-register.md](../release/known-risk-register.md)
