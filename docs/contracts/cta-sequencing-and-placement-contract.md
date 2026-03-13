# CTA Section Classification and Page-Level Sequencing Contract

**Document type:** Authoritative contract for CTA-classified section rules and mandatory page-level sequencing (Prompt 135).  
**Governs:** CTA section families, required CTA counts by page-template class, mandatory bottom-of-page CTA, absolute non-adjacency of CTA sections, target non-CTA section-count ranges, and validation error/warning classes.  
**Spec refs:** §14.3 Allowed Section Ordering; §14.4 Invalid Combination Handling; §16.5 Template-Wide Editing Notes; §30.3 Build Plan Generation; §31.5–31.10 step/list/detail consistency; page-template-category-taxonomy-contract (Prompt 133); section-template-category-taxonomy-contract (Prompt 134).

**Enhancement policy:** This contract **enhances** and does **not replace** Prompts 023, 029, 035, 041, 044, 067–076, 123, 133, or 134. Composition remains governed, ordered, and validation-first. CTA sections are **regular registry sections** with classification metadata; they are not special-case hardcoded fragments.

---

## 1. Purpose and scope

Before mass page-template generation, **CTA-specific rules** must be formal composition law. This contract defines:

- What counts as a **CTA-classified section**.
- **Required minimum CTA section counts** per page-template category class.
- **Target non-CTA section count** as a governed range (not a vague “roughly 10”).
- **Mandatory rule:** Every page template must **end** with a CTA-classified section.
- **Absolute rule:** No two CTA-classified sections may **ever** be adjacent.
- **Validation error and warning classes** for violations.
- **Valid and invalid** example page structures.

**Out of scope:** No page-template generation, validator implementation code, admin UI, or CTA copy generation. Validation is **server-authoritative**; no security-model change.

---

## 2. CTA-classified sections and intent metadata

### 2.1 Definition of CTA-classified section

A section is **CTA-classified** if and only if its registry metadata has **cta_classification** (section-template-category-taxonomy-contract §5) set to one of:

- `primary_cta`
- `contact_cta`
- `navigation_cta`

Sections with **cta_classification** = `none` or missing are **not** CTA-classified. Only CTA-classified sections count toward CTA minimums and are subject to the non-adjacency and bottom-of-page rules.

### 2.2 CTA intent families (page level)

Page templates carry **cta_intent_family** (page-template-category-taxonomy-contract §5.2) for taxonomy and filtering. Sequencing rules in this contract do **not** vary by cta_intent_family; the same minimum CTA counts, non-adjacency, and bottom-CTA rules apply to all page-template category classes regardless of intent. Intent metadata remains for planning and reporting only.

### 2.3 Allowed CTA “intensities”

For composition validation, CTA-classified sections are not further subdivided by intensity. A section is either CTA-classified (and counts toward minimums and is subject to adjacency/ending rules) or not. No additional “CTA intensity” field is required by this contract.

---

## 3. Required CTA counts by page-template class

Every page template that participates in the expanded library **shall** satisfy the following **minimum** CTA section counts. Counts are by **template_category_class** (top_level, hub, nested_hub, child_detail).

| template_category_class | Minimum CTA sections | Minimum total sections (CTA + non-CTA) | Notes |
|-------------------------|----------------------|----------------------------------------|-------|
| `top_level`             | 3                    | 3 + target non-CTA (see §4)            | At least 3 CTA-classified sections. |
| `hub`                   | 4                    | 4 + target non-CTA                     | At least 4 CTA-classified sections. |
| `nested_hub`            | 4                    | 4 + target non-CTA                     | Same as hub. |
| `child_detail`          | 5                    | 5 + target non-CTA                     | At least 5 CTA-classified sections. |

**Validation:** A page template whose ordered_sections contain fewer CTA-classified sections than the minimum for its template_category_class **fails** validation with an **error** (see §7). The count is computed by iterating ordered_sections and counting sections that reference a section template with cta_classification in { primary_cta, contact_cta, navigation_cta }.

---

## 4. Target non-CTA section count (governed range)

“Roughly 10 non-CTA sections” is formalized as a **governed target range** so that validators can enforce it mechanically.

| template_category_class | Target min non-CTA sections | Target max non-CTA sections | Notes |
|-------------------------|-----------------------------|-----------------------------|-------|
| `top_level`             | 8                           | 14                          | Non-CTA = sections not CTA-classified. |
| `hub`                   | 8                           | 14                          | Same range. |
| `nested_hub`            | 8                           | 14                          | Same range. |
| `child_detail`          | 8                           | 14                          | Same range. |

**Validation:** A page template whose ordered_sections contain **fewer** non-CTA sections than the target min (8) **fails** with an **error**. A template with **more** non-CTA sections than the target max (14) **fails** with a **warning** (overlong pages; not a hard error unless product policy elevates it). Total sections = CTA-classified count + non-CTA count; both counts are computed from the same ordered_sections.

---

## 5. Mandatory bottom-of-page CTA

**Rule:** Every page template **must** have as its **last** (final) section in ordered_sections a section that is **CTA-classified**.

- The **last** element of ordered_sections (by position) must reference a section template whose cta_classification is primary_cta, contact_cta, or navigation_cta.
- This is **mandatory**. It is not advisory. A page template that does not end with a CTA-classified section **fails** validation with an **error**.

**Validation:** After resolving each section reference to its section template definition, the section at the final position must be CTA-classified. If the final section is not CTA-classified, the validator reports a **bottom_cta_missing** error.

---

## 6. Absolute prohibition on adjacent CTA sections

**Rule:** No two CTA-classified sections may **ever** be adjacent in ordered_sections. Between any two CTA-classified sections there must be **at least one** non-CTA-classified section.

- For every consecutive pair of positions (i, i+1) in ordered_sections, it is **invalid** for both the section at position i and the section at position i+1 to be CTA-classified.
- This is **absolute**. There are no exceptions. A page template that contains two or more CTA-classified sections in adjacent positions **fails** validation with an **error**.

**Validation:** Iterate ordered_sections in order; for each adjacent pair, if both sections are CTA-classified, report an **adjacent_cta_violation** error. Multiple adjacent pairs produce multiple errors (or one error per violating pair, per implementation).

---

## 7. Validation error and warning classes

Validators that enforce this contract **shall** use the following classification for findings. Severity is fixed per rule.

| Code | Severity | Rule | Description |
|------|----------|------|--------------|
| `cta_count_below_minimum` | error | §3 | Page template has fewer CTA-classified sections than the minimum for its template_category_class. |
| `non_cta_count_below_minimum` | error | §4 | Page template has fewer non-CTA sections than target min (8). |
| `bottom_cta_missing` | error | §5 | The last section in ordered_sections is not CTA-classified. |
| `adjacent_cta_violation` | error | §6 | Two CTA-classified sections are adjacent in ordered_sections. |
| `non_cta_count_above_max` | warning | §4 | Page template has more non-CTA sections than target max (14). |

**Error** = validation fails; the page template is **invalid** and must not be accepted for the expanded library until corrected. **Warning** = validation may still pass per product policy; the finding is recorded for review. Product policy may later elevate `non_cta_count_above_max` to an error.

---

## 8. Valid and invalid example structures

### 8.1 Valid: top_level (min 3 CTA, 8–14 non-CTA, last CTA, no adjacent CTA)

Ordered section sequence (conceptual): [non, non, CTA, non, non, non, non, CTA, non, non, CTA].  
- CTA count = 3 (meets minimum for top_level).  
- Non-CTA count = 8 (within 8–14).  
- Last section is CTA (mandatory satisfied).  
- No two CTAs adjacent (non-adjacency satisfied).  
**Result:** Valid.

### 8.2 Valid: child_detail (min 5 CTA, 8–14 non-CTA, last CTA, no adjacent CTA)

Ordered section sequence: [non, non, CTA, non, non, CTA, non, non, CTA, non, CTA, non, CTA].  
- CTA count = 5 (meets minimum for child_detail).  
- Non-CTA count = 8 (within 8–14).  
- Last section is CTA.  
- No adjacent CTAs.  
**Result:** Valid.

### 8.3 Invalid: adjacent CTA sections

Ordered section sequence: [non, CTA, CTA, non, …].  
- The second and third sections are both CTA-classified and adjacent.  
**Result:** Error `adjacent_cta_violation`. Invalid.

### 8.4 Invalid: last section not CTA

Ordered section sequence: [non, CTA, non, CTA, non].  
- Last section is non-CTA.  
**Result:** Error `bottom_cta_missing`. Invalid.

### 8.5 Invalid: top_level with only 2 CTA sections

Ordered section sequence has 2 CTA-classified sections and 10 non-CTA; last section is CTA; no adjacent CTAs.  
- CTA count 2 &lt; minimum 3 for top_level.  
**Result:** Error `cta_count_below_minimum`. Invalid.

### 8.6 Invalid: fewer than 8 non-CTA sections

Ordered section sequence has 5 CTA and 6 non-CTA; last is CTA; no adjacent CTAs.  
- Non-CTA count 6 &lt; target min 8.  
**Result:** Error `non_cta_count_below_minimum`. Invalid.

### 8.7 Warning: more than 14 non-CTA sections

Ordered section sequence has 4 CTA and 15 non-CTA; last is CTA; no adjacent CTAs.  
- Non-CTA count 15 &gt; target max 14.  
**Result:** Warning `non_cta_count_above_max`. Validation may still pass depending on policy.

---

## 9. How validators detect violations

Validators **shall**:

1. **Resolve section references:** For each item in page template ordered_sections, resolve the section_key to the section template definition and read its **cta_classification** (and, if needed, section_purpose_family from section-template-category-taxonomy-contract).
2. **Classify each position:** Mark each position as CTA-classified or non-CTA based on cta_classification (primary_cta, contact_cta, navigation_cta = CTA-classified; none or missing = non-CTA).
3. **Count:** Compute total CTA-classified count and total non-CTA count.
4. **Compare to minimums (§3):** If CTA count &lt; minimum for template_category_class, emit `cta_count_below_minimum`.
5. **Compare to target range (§4):** If non-CTA count &lt; 8, emit `non_cta_count_below_minimum`; if non-CTA count &gt; 14, emit `non_cta_count_above_max` (warning).
6. **Check last section (§5):** If the section at the final position is not CTA-classified, emit `bottom_cta_missing`.
7. **Check adjacency (§6):** For each consecutive pair in order, if both are CTA-classified, emit `adjacent_cta_violation`.

All checks are **deterministic** and **machine-checkable**. No human judgment is required to decide whether a template passes or fails the hard rules.

---

## 10. Links to enhanced prompts and contracts

This contract **enhances** (and does not replace):

| Prompt / contract | Area |
|-------------------|------|
| 023, 029 | Page/section template objects and validation. |
| 035, 041, 044 | Composition, ordering, invalid combination handling. |
| 067–076 | Registry admin; validators may be invoked from admin or CI. |
| 123 | Diagnostics; violation codes can be reported. |
| 133 | Page-template category taxonomy; template_category_class drives count rules. |
| 134 | Section taxonomy; cta_classification defines CTA-classified sections. |

**Cross-references:** Page-template-category-taxonomy-contract and section-template-category-taxonomy-contract are updated to reference this contract for mandatory CTA sequencing and placement rules. Page-template-registry-schema may reference this contract for CTA count and bottom-CTA constraints.
