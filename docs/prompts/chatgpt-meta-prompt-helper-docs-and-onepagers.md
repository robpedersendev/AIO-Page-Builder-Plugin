# ChatGPT Meta-Prompt: Generate Cursor Prompts for Section Helpers and Page One-Pagers

Use the block below as a **ChatGPT prompt**. ChatGPT will output **Cursor prompts** (one per section batch or page batch) that you can copy into Cursor to implement section helper Documentation objects and page-template one-pager Documentation objects. Ask ChatGPT for "the next batch" or "more prompts" to get unlimited prompts.

---

## Paste this into ChatGPT

```
You are a prompt engineer for the AIO Page Builder WordPress plugin. Your job is to output **Cursor prompts** — one prompt per batch — that a developer will run in Cursor to implement helper documentation.

## Context: AIO Page Builder

- **Section templates:** Each has an internal key (e.g. `st_hero_01`, `st_proof_testimonial_01`), `helper_ref` (e.g. `hero_helper`), `section_purpose_family`, `cta_classification`, `name`, `purpose_summary`, and a field blueprint. There are 250+ section templates across batches (Hero, CTA, Proof, Legal/Policy, Process/Timeline/FAQ, Feature/Benefit, Media/Listing/Profile, Gap-Closing, etc.).
- **Page templates:** Each has an internal key (e.g. `pt_home_conversion_01`), `ordered_sections` (list of section keys in order), `template_family`, `template_category_class`, `one_pager` (metadata like `page_purpose_summary`, `page_flow_explanation`, `cta_direction_summary`), and optional one-pager doc link. There are 500+ page templates.
- **Documentation object schema (plugin):**
  - **section_helper:** `documentation_type` = `section_helper`; `source_reference.section_template_key` = section internal_key; `content_body` = helper content (HTML or Markdown). Content must cover: what the section is for, user need, content type, field-by-field guidance, tone, mistakes to avoid, SEO/a11y notes.
  - **page_template_one_pager:** `documentation_type` = `page_template_one_pager`; `source_reference.page_template_key` = page template internal_key; `content_body` = combined one-pager (page purpose, flow, section-by-section guidance in order, page-wide notes). Combines and references all section helpers for that page’s `ordered_sections`.
- **Target audience:** Website users (editors, implementers) who use the templates with **GeneratePress**, **ACF (Advanced Custom Fields)**, **AIOSEO (All in One SEO)**, **FIFU (Featured Image from URL)**, and other common plugins. Docs must be practical: how to fill fields, where things appear in GP, ACF field groups, SEO fields, FIFU for section images, etc.
- **Authority:** Plugin docs/schemas: `plugin/docs/schemas/documentation-object-schema.md`, `plugin/docs/schemas/section-registry-schema.md`. Spec: `docs/specs/aio-page-builder-master-spec.md` §15 (helper docs), §16 (one-pagers).

## Your task

Output **Cursor prompts** that:

1. **Section helper prompts (one prompt per section batch):**
   - Title: e.g. "Section helper Documentation for Hero batch" or "Section helper Documentation for CTA Super Library batch".
   - Instruct Cursor to create **Documentation** objects (or PHP/JSON definitions that conform to the Documentation schema) for every section in that batch.
   - Each section helper must: use `documentation_id` derived from section key (e.g. `doc-helper-st_hero_01`), `documentation_type` = `section_helper`, `source_reference.section_template_key` = that section’s key, `content_body` with real guidance (not placeholders).
   - **Content requirements for each section helper:** What the section is for; which ACF fields it uses and how to fill them; how it fits with GeneratePress (containers, blocks, spacing); any AIOSEO tips for that section (e.g. meta for hero headline); FIFU usage if the section has images; tone and mistakes to avoid; brief SEO and accessibility notes. Language: clear, user-facing, plugin-aware (GeneratePress, ACF, AIOSEO, FIFU, etc.).
   - Specify where output lives: e.g. `plugin/src/Domain/Registries/Docs/SectionHelpers/` or a Documentation registry/store, and that it must be loadable by a future helper-doc resolver.

2. **Page template one-pager prompts (one prompt per page batch):**
   - Title: e.g. "Page template one-pager Documentation for Top-Level Home batch" or "Page template one-pager for Child Detail Services batch".
   - Instruct Cursor to create **Documentation** objects for every page template in that batch with `documentation_type` = `page_template_one_pager`, `source_reference.page_template_key` = page key.
   - **Content for each one-pager:** Page purpose and flow (from the template’s `one_pager` metadata); then **section-by-section** in the order of `ordered_sections`: for each section, a short heading and a summary or inclusion of that section’s helper content (or a pointer to it), so the one-pager is a single document the user can use with GeneratePress, ACF, AIOSEO, FIFU in mind. Page-wide notes: hierarchy, menus, SEO for the whole page, CTA strategy.
   - Specify where output lives: e.g. `plugin/src/Domain/Registries/Docs/PageTemplateOnePagers/` or equivalent, and that page one-pagers must reference the same section helper docs so the system can resolve links later.

3. **Format of each Cursor prompt you output:**
   - Start with a clear **Prompt number and title** (e.g. "Prompt 201 – Section helper Documentation: Hero batch").
   - Include: **Objective**, **Documentation schema to follow** (documentation-object-schema.md), **Section or page batch in scope** (list batch name and how to discover keys: e.g. "Hero batch = definitions in Hero_Intro_Library_Batch_Definitions.php and any Hero-related batches").
   - **Content requirements:** Repeat the user-facing, plugin-aware guidance (GeneratePress, ACF, AIOSEO, FIFU, etc.) and the required content_body structure for section_helper vs page_template_one_pager.
   - **Files to create/update:** Exact paths or pattern; requirement that each doc has documentation_id, documentation_type, source_reference, content_body, status, and optional generated_or_human_edited, version_marker, export_metadata.
   - **Acceptance criteria:** All sections in the batch have a section_helper doc; or all page templates in the batch have a page_template_one_pager that combines section helpers in order; no placeholder content; schema-compliant.
   - End with: "Output format: Return all created/updated files and a short summary. No pseudocode."

## Output rules

- Emit **one full Cursor prompt** per batch (section batch or page batch). Use a consistent numbering scheme (e.g. 201, 202, 203 for section batches; 301, 302, 303 for page batches) so the user can ask for "prompts 201–210" or "next 5 section batches."
- Each Cursor prompt must be **self-contained**: someone can copy-paste it into Cursor and run it without reading this meta-prompt.
- When you don’t know the exact list of section or page keys, say: "Discover all section keys in [path] for the Hero batch" (or equivalent) so Cursor can search the codebase.
- At the end of your reply, add: "To get more prompts, ask: 'Next batch of section helper prompts' or 'Next 10 page one-pager prompts' or 'Cursor prompts for [specific batch name]'."

Start by outputting the first **section helper** batch prompt (e.g. Hero batch) and the first **page template one-pager** batch prompt (e.g. Top-Level Home or first page batch). Then remind the user they can request more batches for unlimited Cursor prompts.
```

---

## How to use

1. Copy the entire block above (from "You are a prompt engineer" through "unlimited Cursor prompts") into ChatGPT.
2. ChatGPT will return at least two full Cursor prompts (one section batch, one page batch). Copy each into Cursor and run.
3. Ask ChatGPT for more: e.g. "Next 5 section helper prompts," "Next page one-pager batch," or "Cursor prompts for CTA Super Library and Proof batches."
4. Run the generated Cursor prompts in order (section helpers before page one-pagers if the one-pagers reference section content).

## Notes

- Section helper content must be **plugin-aware**: GeneratePress, ACF, AIOSEO, FIFU, and other plugins you use. The meta-prompt tells ChatGPT to bake that into every Cursor prompt.
- Page one-pagers **combine** section helpers in `ordered_sections` order so the user has one document per page template.
- The plugin does not yet resolve `helper_ref` or `helper_doc_url`; implementing storage and a resolver is separate. These prompts only create the **content** (Documentation objects or equivalent definitions).
