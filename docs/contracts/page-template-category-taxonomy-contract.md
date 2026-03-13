# Page Template Category Taxonomy and Hierarchy Contract

**Document type:** Authoritative contract for page-template category taxonomy, hierarchy roles, and directory structure (Prompt 133).  
**Governs:** Four major category classes, subfamilies, hierarchy-role metadata, purpose/CTA-orientation, admin directory/browse grouping, and variation-family rules.  
**Spec refs:** §13 Page Template Registry; §13.11 Compatibility; §13.12 Versioning; §14.3 Allowed Section Ordering; §16.1 One-Pager Purpose; §16.5 Template-Wide Editing Notes; §49.3 Screen Hierarchy; template-library-scale-extension-contract (Prompt 132).

**Enhancement policy:** This contract **enhances** and does **not replace** Prompts 022, 028, 029, 070, 071, 111, 123, or Prompt 132. Page templates remain registry objects with ordered section references, one-pager support, and compatibility metadata. Taxonomy adds **structure and metadata**; it does not replace purpose, archetype, or section-order logic.

---

## 1. Purpose and scope

The expanded page-template library (see template-library-scale-extension-contract) requires a **formal category taxonomy** so that:

- Templates are organized into **hierarchical families** (top-level, hub, nested hub, child/detail).
- **Subfamilies** (Home, About, Services, Locations, etc.) support directory browsing and preview grouping.
- **Hierarchy-role** and **parent/child expectations** are explicit and server-authoritative.
- **Purpose** and **CTA-orientation** are represented at category and template levels for planning and filtering.
- Admin UI can **browse and group** by category class, family, and hierarchy role without treating labels as ad-hoc UI strings.

**Out of scope:** No actual page-template generation, admin UI implementation, rendering changes, or CTA sequencing logic beyond taxonomy references. Category metadata is **validated and server-authoritative**; no change to registry mutation permissions.

---

## 2. Four major category classes

Every page template that participates in the taxonomy **shall** be assigned exactly one **template_category_class**. These are stable registry values used for directory grouping, hierarchy reasoning, and coverage QA.

| Class slug | Description | Typical archetypes (from page-template-registry-schema) | Hierarchy expectation |
|------------|-------------|--------------------------------------------------------|----------------------|
| `top_level` | Entry, home, or primary site-level pages. | `landing_page`, home-specific variants | Often root or near-root; may have children but are not children of another content page. |
| `hub` | Category or topic hub pages that aggregate or link to child content. | `hub_page` | Parent of nested hubs or child pages; has children. |
| `nested_hub` | Sub-hub or sub-category pages under a hub. | `sub_hub_page`, some `hub_page` | Child of hub (or top-level); may have children. |
| `child_detail` | Detail, service, offer, location, event, profile, FAQ, or other leaf/detail pages. | `service_page`, `offer_page`, `location_page`, `event_page`, `profile_page`, `faq_page`, `informational_detail`, etc. | Leaf or detail; typically child of hub or nested hub. |

**Validation:** A template’s `template_category_class` must be one of the four slugs above. Assignment must be consistent with `archetype` and with `hierarchy_role` (see §4). Invalid or missing class for templates that are part of the taxonomy is a validation error.

---

## 3. Subfamilies (template_family)

**Template_family** groups templates by **purpose or site-area** for directory browsing and preview grouping. Each template may have one **primary** `template_family` value. Values are stable slugs.

| Family slug | Description | Typical category class(es) | Example purposes |
|-------------|-------------|----------------------------|------------------|
| `home` | Home or main landing. | top_level | Home, splash. |
| `about` | About, story, team, company. | top_level, hub, child_detail | About us, team, history. |
| `faq` | FAQ and support Q&A. | hub, child_detail | FAQ hub, FAQ category, single FAQ. |
| `contact` | Contact and request. | top_level, child_detail | Contact, request quote. |
| `privacy` | Privacy and legal. | top_level, child_detail | Privacy policy. |
| `terms` | Terms and legal. | top_level, child_detail | Terms of use. |
| `accessibility` | Accessibility and inclusion. | top_level, child_detail | Accessibility statement. |
| `services` | Services or service areas. | hub, nested_hub, child_detail | Service hub, service category, service detail. |
| `locations` | Locations, branches, venues. | hub, nested_hub, child_detail | Location hub, region, location detail. |
| `products` | Products or product lines. | hub, nested_hub, child_detail | Product hub, category, product detail. |
| `directories` | Directory or listing. | hub, child_detail | Directory hub, listing, profile. |
| `offerings` | Offers, pricing, packages. | hub, child_detail | Offer hub, pricing, offer detail. |
| `events` | Events or programs. | hub, child_detail | Events hub, event detail. |
| `profiles` | People, roles, bios. | hub, child_detail | Team hub, profile detail. |
| `comparison` | Comparison or decision. | child_detail | Comparison page. |
| `informational` | General informational. | top_level, hub, child_detail | Info hub, article-style detail. |
| `other` | Other or uncategorized. | any | Fallback only. |

**Directory/browse grouping:** Admin UI should allow filtering or grouping by `template_family` and by `template_category_class`. Grouping is by **registry metadata**, not by display label only. Preview grouping (e.g. “Services”, “Locations”) should use the same family slugs so that previews align with directory structure.

**Variation families:** Category-specific variation (e.g. “Services – compact” vs “Services – full”) is expressed within a family via distinct templates sharing the same `template_family` and differing by purpose_summary, section order, or archetype. No separate “variation family” slug is required; the same `template_family` plus internal_key and purpose distinguish variants.

---

## 4. Hierarchy-role metadata and parent/child expectations

**hierarchy_role** refines how a template behaves in the site hierarchy. Allowed values (stable slugs):

| Role slug | Description | Valid category class(es) | Parent/child expectation |
|-----------|-------------|---------------------------|---------------------------|
| `root` | Root or top-level entry. | top_level | No required parent; may have children. |
| `standalone` | Standalone top-level (e.g. Contact, Privacy). | top_level | No required parent; often no children. |
| `hub` | Aggregator or category hub. | hub | May have parent (top_level or hub); has or expects children. |
| `nested_hub` | Sub-category hub. | nested_hub | Parent is hub or nested_hub; may have children. |
| `leaf` | Leaf or detail page. | child_detail | Parent is hub or nested_hub; no content children. |
| `intermediate` | Generic intermediate (use when more specific role does not fit). | any | Parent/child context-dependent. |

**Validation:** `hierarchy_role` must be one of the above. Combination of `template_category_class` and `hierarchy_role` must be **allowed** per the taxonomy matrix (§7). For example, `child_detail` + `hub` is invalid.

**Parent/child expectations:** Templates may declare **expected parent archetypes or category classes** (e.g. “this service detail template is typically used under a services hub”). This is expressed in existing optional fields such as `hierarchy_hints.common_parent_archetypes` or in compatibility metadata. Taxonomy does not introduce new parent-ID fields on the template itself; parent/child is a **site-structure concern** at build time, not a required field on every template. The taxonomy supports **reasoning** about which templates are suitable as parents or children; it does not mandate a full parent pointer on each template.

---

## 5. Purpose and CTA-orientation metadata

### 5.1 page_purpose_family

**page_purpose_family** groups templates by **primary page intent** for planning and filtering. Allowed values (stable slugs):

| Slug | Description |
|------|-------------|
| `informational` | Primarily inform (about, legal, accessibility). |
| `conversion` | Primarily convert (landing, offer, pricing). |
| `navigation` | Primarily navigate or aggregate (hub, directory). |
| `support` | Primarily support (FAQ, contact, request). |
| `discovery` | Primarily discover (profiles, locations, events). |
| `decision` | Primarily support decision (comparison). |
| `other` | Other or mixed. |

One value per template. Used for AI planning hints, directory filters, and coverage QA (e.g. “ensure conversion and support families are covered”).

### 5.2 cta_intent_family

**cta_intent_family** describes the **primary CTA orientation** of the template (for taxonomy and filtering only; no CTA sequencing logic in this contract). Allowed values:

| Slug | Description |
|------|-------------|
| `primary_conversion` | Primary CTA is conversion (signup, purchase, quote). |
| `contact_request` | Primary CTA is contact or request. |
| `navigation` | Primary CTA is navigation (e.g. to children or related). |
| `none_minimal` | No prominent CTA or minimal. |
| `other` | Other or mixed. |

One value per template. Enables later CTA-ordering or placement rules to reference “templates in primary_conversion family” without encoding sequence in this contract.

---

## 6. Representation in registry and admin directory

### 6.1 Registry metadata fields

The following fields are **taxonomy metadata** and must be represented in the page-template registry when the taxonomy is applied. They are **additive** to the existing page-template schema; see page-template-registry-schema.md for the optional-fields extension.

| Field | Type | Required | Allowed values | Notes |
|-------|------|----------|----------------|-------|
| `template_category_class` | string | For taxonomy participation | `top_level`, `hub`, `nested_hub`, `child_detail` | Major class (§2). |
| `template_family` | string | For taxonomy participation | Slugs from §3 (e.g. `services`, `locations`, `home`) | Subfamily for directory/preview. |
| `hierarchy_role` | string | No (can inherit from class) | Slugs from §4 (`root`, `standalone`, `hub`, `nested_hub`, `leaf`, `intermediate`) | Refines hierarchy; must be consistent with category class. |
| `page_purpose_family` | string | No | Slugs from §5.1 | Page intent. |
| `cta_intent_family` | string | No | Slugs from §5.2 | CTA orientation. |

All values are **server-authoritative** and **validated**. Category labels are not mere UI strings; they drive directory browsing, preview grouping, and coverage QA.

### 6.2 Admin browsing semantics

- **Directory grouping:** Admin screens that list or browse page templates **should** support grouping or filtering by `template_category_class` and by `template_family`. Default sort or group may be by class, then by family.
- **Preview grouping:** Preview or template-picker UIs **should** group by `template_family` (and optionally by class) so that “Services”, “Locations”, etc. map to the same slugs as in the registry.
- **Screen hierarchy (§49.3):** Where admin screen hierarchy or navigation exposes “Page templates” or “Registry”, the taxonomy provides the structure for sub-views (e.g. “By family”, “By class”) without defining specific screen components in this contract.

### 6.3 Compatibility and section ordering

Existing **compatibility** (§13.11) and **allowed section ordering** (§14.3) rules remain in force. Taxonomy does not replace them. Templates in the same `template_family` may share similar section-ordering expectations (e.g. “services detail” often has hero + trust + CTA); such expectations are documented in purpose_summary, one_pager, or compatibility metadata, not only in the family slug.

---

## 7. Taxonomy matrix: valid and invalid assignments

### 7.1 Valid examples (matrix rows)

| template_category_class | template_family | hierarchy_role | Example archetype | Notes |
|--------------------------|-----------------|----------------|-------------------|-------|
| top_level | home | root | landing_page | Home page. |
| top_level | about | standalone | landing_page | About us. |
| top_level | contact | standalone | request_page | Contact. |
| top_level | privacy | standalone | informational_detail | Privacy policy. |
| hub | services | hub | hub_page | Services hub. |
| hub | locations | hub | hub_page | Locations hub. |
| nested_hub | services | nested_hub | sub_hub_page | Service category. |
| nested_hub | products | nested_hub | sub_hub_page | Product category. |
| child_detail | services | leaf | service_page | Service detail. |
| child_detail | locations | leaf | location_page | Location detail. |
| child_detail | events | leaf | event_page | Event detail. |
| child_detail | faq | leaf | faq_page | FAQ page. |
| child_detail | offerings | leaf | offer_page | Offer detail. |
| child_detail | comparison | leaf | comparison_page | Comparison. |

### 7.2 Invalid category assignments (must be rejected)

The following combinations or usages are **invalid** and must be rejected by validation:

| Condition | Reason |
|-----------|--------|
| `template_category_class` = `child_detail` and `hierarchy_role` = `hub` | Child/detail templates are not hubs. |
| `template_category_class` = `top_level` and `hierarchy_role` = `leaf` | Top-level is not a leaf. |
| `template_category_class` = `hub` and `hierarchy_role` = `leaf` | Hub class implies aggregator role. |
| `template_category_class` not in { top_level, hub, nested_hub, child_detail } | Only four classes allowed. |
| `template_family` not in the defined family slug set (§3) when taxonomy is applied | Unknown family breaks directory grouping. |
| `hierarchy_role` not in the defined role set (§4) when provided | Unknown role breaks hierarchy reasoning. |
| `page_purpose_family` or `cta_intent_family` not in defined sets (§5) when provided | Unknown purpose/CTA breaks filtering and QA. |

Validation must be **server-side** and **authoritative**. Admin UI must not allow saving a template with an invalid combination.

---

## 8. Category-specific variation families

Within a **template_family**, multiple templates may coexist as **variants** (e.g. “Services – short”, “Services – long”). Rules:

- **Same family, distinct purpose/sections:** Variants share `template_family` (and usually `template_category_class` and `hierarchy_role`) but differ by `internal_key`, `purpose_summary`, `ordered_sections`, or `archetype` where appropriate.
- **No duplicate keys:** Each template has a unique `internal_key`. Variation is not expressed by duplicating the same key with a different “variant” flag.
- **Coverage QA:** When verifying coverage (template-library-scale-extension-contract §9), families should be checked for spread (e.g. no single family dominates) and for meaningful variation within family (no thin clones).

---

## 9. Links to enhanced prompts

This contract **enhances** (and does not replace) the following:

| Prompt | Area |
|--------|------|
| 022 | Page template object and registry model. |
| 028, 029 | Registry storage, validation. |
| 070, 071 | Registry admin screens; taxonomy supplies grouping semantics. |
| 111 | Build Plan / template selection; taxonomy can drive filters. |
| 123 | Diagnostics/reporting; taxonomy can drive coverage reporting. |
| 132 | Scale extension; taxonomy implements “template-family” and “category coverage” structure. |

Page templates remain **registry objects** with ordered section references, one-pager support (§16.1, §16.5), and compatibility metadata (§13.11, §13.12). This contract adds **category taxonomy and hierarchy metadata** and **directory/browse semantics** on top of that foundation.
