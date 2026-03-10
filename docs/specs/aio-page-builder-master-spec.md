# **AIO Page Builder Plugin**

## **Formal Specification Guide, Build Plan, and Roadmap**

### **Master Document Index / Section Outline**

## **0. Document Control**

### **0.1 Document Title**

### **AIO Page Builder Plugin — Formal Specification Guide, Build Plan, and Roadmap**

### **Document Short Name:\
AIO Page Builder Master Specification**

### **Document Type:\
Formal Product and Technical Specification**

### **Document Classification:\
Internal Master Planning Document**

### **Primary Use:\
This document is the authoritative source of truth for product planning, technical architecture, implementation sequencing, compliance posture, operational behavior, and long-term roadmap definition for the AIO Page Builder plugin.**

### 

### **0.2 Document Purpose**

### **This document exists to define, in one formal and durable specification, the full intended behavior, architecture, boundaries, requirements, workflows, and implementation roadmap for the AIO Page Builder plugin.**

### **The purpose of this document is to:**

### **establish a stable product definition before development begins**

### **provide a detailed shared understanding of what the plugin must do, how it must do it, and what it must never do**

### **separate required functionality from optional enhancements, future ideas, and non-goals**

### **serve as the planning foundation for product design, technical architecture, development execution, quality assurance, release readiness, and long-term maintenance**

### **reduce ambiguity by documenting terminology, assumptions, constraints, responsibilities, dependencies, and decision authority**

### **create a durable reference that can be used across planning sessions, development cycles, review meetings, bug analysis, and scope management**

### **This document is intended to be detailed enough that future implementation work can be traced back to clearly written requirements rather than memory, assumption, or informal conversation.**

### 

### **0.3 Intended Audience**

### **This document is written for the following audiences:**

### **Primary Audience**

### **Product owner**

### **Lead architect**

### **Plugin developer(s)**

### **Technical implementer(s)**

### **QA reviewer(s)**

### **Secondary Audience**

### **UX/UI planner(s)**

### **Content system designer(s)**

### **AI prompt and schema designer(s)**

### **Security reviewer(s)**

### **Support and diagnostics reviewer(s)**

### **Tertiary Audience**

### **Future collaborators who need to understand product intent**

### **Any technical contractor or development partner brought into the project later**

### **Internal stakeholders reviewing scope, feasibility, sequencing, or release readiness**

### **This document assumes that readers may vary in technical depth. For that reason, it is written in a formal, explicit, and structured way so it can support both strategic review and detailed implementation planning.**

### 

### **0.4 Scope of This Specification**

### **This specification covers the full planned product definition for the AIO Page Builder plugin as a privately distributed WordPress plugin engineered to WordPress-style standards, with specific private-distribution exceptions related to mandatory outbound operational reporting.**

### **This specification includes, but is not limited to:**

### **product definition and product boundaries**

### **architectural model**

### **plugin modules and responsibilities**

### **section template registry**

### **page template registry**

### **custom template composition system**

### **helper paragraph and one-pager documentation system**

### **ACF field architecture and assignment rules**

### **LPagery token compatibility rules**

### **native block and GenerateBlocks rendering model**

### **CSS, ID, class, and attribute contract requirements**

### **brand profile and business profile intake**

### **guided onboarding flow**

### **public-site crawl and analysis rules**

### **AI provider abstraction layer**

### **AI prompt pack structure**

### **AI input and output artifact handling**

### **structured schema validation rules for AI output**

### **build plan UI and workflow**

### **existing page update flow**

### **new page build flow**

### **menu and navigation update flow**

### **design token update flow**

### **SEO, metadata, and media review flow**

### **execution engine**

### **diff, snapshot, and rollback model**

### **queue and job handling for long-running operations**

### **security model**

### **permissions and capability model**

### **logging, diagnostics, and reporting**

### **privacy and data handling**

### **import, export, uninstall, restore, and survivability behavior**

### **implementation sequencing, milestones, and roadmap**

### **This specification defines the full target product, even where implementation may later be phased.**

### 

### **0.5 Out-of-Scope Items**

### **The following items are explicitly out of scope for this specification unless later added by formal revision:**

### **Visual Builder Parity**

### **Full drag-and-drop page builder functionality comparable to Elementor**

### **Freeform visual design tooling for arbitrary layouts**

### **Front-end visual editing of every design property**

### **Theme Replacement**

### **Replacement of GeneratePress, WordPress core themes, or the block editor**

### **Creation of a full standalone theme framework**

### **Building a proprietary theme dependency that is required for content survivability**

### **AI Autonomy Without Review**

### **Unrestricted AI execution without user review or approval**

### **Direct destructive site mutations performed by the AI planner**

### **Silent replacement of content, structure, or menus without a tracked execution record**

### **Repository Distribution Requirements**

### **WordPress.org submission readiness as a product requirement**

### **Adherence to repository rules that conflict with the approved private-distribution exception model for mandatory reporting**

### **General-Purpose Site Management**

### **Replacing SEO plugins entirely**

### **Replacing media library management plugins entirely**

### **Replacing WordPress user management or role editor plugins entirely**

### **Replacing security plugins or backup plugins entirely**

### **Non-Public Crawl Targets**

### **Crawling logged-in, private, member-only, or admin-only site areas as part of the default system**

### **Automated competitor crawling beyond user-provided or user-approved inputs**

### **Scraping behavior that exceeds respectful public-site analysis rules**

### **Custom Hosting or SaaS Platform**

### **Turning the plugin into a hosted SaaS product within this specification**

### **Central account licensing or seat-based access control**

### **Dependency on a proprietary external platform for core page survivability**

### **Unbounded Content Generation**

### **Acting as a general AI writing assistant for arbitrary non-plugin tasks**

### **Serving as a generic chatbot UI inside WordPress**

### **Out-of-scope items may be revisited in future revisions, but they are not part of the baseline specification.**

### 

### **0.6 Definitions and Terminology**

### **For the purposes of this document, the following terms shall have the meanings defined below.**

### **AIO Page Builder Plugin\
The private-distribution WordPress plugin specified by this document.**

### **Section Template\
A pre-defined, reusable section-level content and layout unit with a fixed internal structural contract, fixed CSS contract, fixed field blueprint, and associated helper documentation.**

### **Page Template\
An ordered composition of section templates intended for a specific page purpose, archetype, or site role.**

### **Custom Template Composition\
A user-created page template assembled from existing registered section templates.**

### **Registry\
The canonical internal index of section templates, page templates, compositions, prompt packs, or other controlled system definitions.**

### **Helper Paragraph\
Structured instructional copy associated with a section template that explains how a user should properly fill out or update the section’s fields and content.**

### **One-Pager\
A compiled reference document for a page template or custom composition made by combining all relevant helper paragraphs and page-level editing guidance into a single user-facing document.**

### **Rendering Contract\
The fixed structural rules governing how a template is translated into saved native block content and front-end HTML.**

### **CSS Contract\
The fixed naming and application rules for CSS classes, IDs, and data attributes. This contract is system-defined and may not be renamed by AI.**

### **Design Tokens\
Controlled stylistic values such as colors, typography assignments, spacing scales, surface treatments, radii, shadows, and related design variables that may change without changing the underlying markup contract.**

### **Brand Profile\
Structured business and branding data provided by the user for AI planning, documentation, and styling guidance.**

### **Business Profile\
Structured information about the user’s business, services, audience, locations, competitors, and operational context.**

### **Onboarding\
The guided intake workflow that collects user data, reviews settings, triggers crawl analysis, and prepares the input package for the AI provider.**

### **Crawl Snapshot\
A stored record of public, indexable, meaningful site pages and their analyzed structure and content at a specific point in time.**

### **AI Provider\
A third-party model provider selected by the user for structured planning outputs.**

### **Prompt Pack\
The controlled, versioned prompt structure used to communicate with an AI provider.**

### **AI Run\
A single recorded submission of inputs to a selected AI provider together with the resulting outputs, artifacts, validations, and metadata.**

### **AI Artifact\
Any saved input, file, prompt, raw response, normalized response, validation record, or related file associated with an AI run.**

### **Build Plan\
The structured, step-based plan generated from validated AI output and local system logic that the user reviews and executes.**

### **Planner\
The logic layer responsible for producing structured recommendations and proposed changes. The planner does not directly mutate the site.**

### **Executor\
The logic layer responsible for performing approved site changes based on validated build plan actions.**

### **Snapshot\
A stored pre-change or post-change record used for history, diffing, or rollback.**

### **Rollback\
A controlled recovery action that attempts to reverse a previously executed change using stored snapshots and operation records.**

### **Private Distribution\
Plugin delivery outside of the WordPress.org repository through direct ZIP distribution or similar controlled private installation methods.**

### **Mandatory Reporting\
Required outbound notifications and diagnostics defined by this product, including install notification, heartbeat, and approved developer error reporting.**

### **WordPress-Style Standards\
Engineering practices aligned with WordPress plugin-handbook expectations for code structure, security, lifecycle handling, permissions, sanitization, escaping, portability, and admin behavior.**

### 

### **0.7 Acronyms and Abbreviations**

### **ACF — Advanced Custom Fields\
AI — Artificial Intelligence\
API — Application Programming Interface\
CPT — Custom Post Type\
CSS — Cascading Style Sheets\
DX — Developer Experience\
HTML — HyperText Markup Language\
HTTP — Hypertext Transfer Protocol\
IA — Information Architecture\
ID — Identifier\
JSON — JavaScript Object Notation\
LPagery — Landing Page or location page generation tool referenced in this project context\
Meta — WordPress metadata associated with posts, users, terms, or other objects\
PHP — Hypertext Preprocessor\
QA — Quality Assurance\
REST — Representational State Transfer\
SEO — Search Engine Optimization\
UI — User Interface\
URL — Uniform Resource Locator\
UX — User Experience\
WP — WordPress\
WP-Cron — WordPress scheduled task system\
ZIP — Compressed archive file format**

### 

### **0.8 Assumptions**

### **This specification is written with the following assumptions in place:**

### **The plugin will be distributed privately and will not be submitted to the WordPress.org plugin repository.**

### **The plugin should still be engineered to WordPress-style best practices for security, portability, lifecycle handling, and admin behavior, except where private-distribution reporting requirements intentionally differ.**

### **The plugin is intended to operate on standard WordPress installations using a conventional theme setup, with a strong preference toward compatibility with native blocks and GenerateBlocks-oriented content assembly.**

### **The plugin is not intended to recreate full visual builder behavior and is instead focused on structured templating, planning, guided execution, and safe content generation.**

### **The plugin will use fixed internal naming contracts for classes, IDs, attributes, field keys, and system handles. These contracts are owned by the plugin and are not subject to AI renaming.**

### **AI outputs are considered advisory and structured, not inherently trusted for direct destructive execution.**

### **Users of the plugin may have varying levels of technical skill; therefore, the system must guide them clearly and defensibly.**

### **Built pages must remain usable and should not depend on continued plugin activation for their front-end rendering or structural survival.**

### **The system will support ACF-driven structured fields and LPagery-compatible tokenization where relevant and feasible.**

### **AI provider usage will rely on user-supplied API credentials handled securely by the plugin.**

### **The plugin may be installed on websites not controlled or supported directly by the product owner, so logging, diagnostics, survivability, and support visibility are important.**

### **The specification is being created before final implementation details are locked, so some technical decisions may later be refined without changing the product’s core direction.**

### 

### **0.9 Constraints**

### **The product defined by this specification is subject to the following constraints:**

### **Platform Constraints**

### **Must operate within WordPress plugin architecture**

### **Must not rely on replacing WordPress core editing paradigms**

### **Must preserve front-end content independence from ongoing plugin activation wherever reasonably possible**

### **Distribution Constraints**

### **Plugin will be privately distributed**

### **Plugin will include mandatory outbound operational reporting as defined by product requirements**

### **Plugin is not required to meet repository submission rules where those rules conflict with private reporting requirements**

### **Architectural Constraints**

### **AI may not rename or redefine the plugin’s internal structural contracts**

### **Planner and executor must remain logically separated**

### **Native block content is the primary page output model**

### **Render callbacks are allowed only where clearly justified**

### **Security Constraints**

### **All privileged actions must be capability-restricted**

### **Sensitive credentials must not be exposed client-side**

### **External communications must be secured and logged appropriately**

### **Secrets, passwords, and authentication materials must never be included in reports or exports except where explicitly and safely designed**

### **Usability Constraints**

### **The system must remain understandable to non-developer site operators**

### **The build plan must be reviewable step by step**

### **Destructive or high-impact actions must be visible, logged, and recoverable where possible**

### **Performance Constraints**

### **Long-running work must be queued or otherwise managed safely**

### **Large sites, large template libraries, and repeated AI runs must be supportable without unacceptable admin degradation**

### **Data Constraints**

### **Plugin-created operational data must be exportable for backup and restore**

### **Uninstall must not destroy already-built pages**

### **Plugin-specific operational data must be separable from content survivability**

### **Compliance Constraints**

### **The plugin should follow WordPress-style engineering standards except for the private-distribution reporting exception model**

### **Privacy and transparency expectations still apply within the private distribution model**

### 

### **0.10 Guiding Product Principles**

### **The following principles govern all product, architectural, and implementation decisions for this plugin.**

#### **0.10.1 Structure Over Chaos**

### **The plugin exists to impose structured, repeatable, scalable page-building logic. It should reduce ambiguity, not introduce it.**

#### **0.10.2 Fixed Internal Contracts**

### **Internal classes, IDs, attributes, template keys, and field keys are system contracts. They must remain stable and predictable.**

#### **0.10.3 Native Content First**

### **Pages created by the plugin should result in native WordPress content structures wherever possible so that the site remains durable and usable over time.**

#### **0.10.4 AI as Planner, Not Unchecked Operator**

### **AI provides structured recommendations, analysis, and proposed content and styling values. AI does not receive unilateral authority to destructively mutate the website without an approved execution path.**

#### **0.10.5 Aggressive, but Recoverable**

### **The product may support aggressive workflows for rebuilding or replacing site content, but those workflows must still be logged, reviewable, and recoverable.**

#### **0.10.6 Portability Matters**

### **The plugin must not trap the user in fragile output that collapses when the plugin is removed. Built pages should survive.**

#### **0.10.7 Documentation Is Part of the Product**

### **Helper paragraphs, one-pagers, and plan visibility are not optional niceties. They are part of the core product promise.**

#### **0.10.8 Private Distribution Does Not Mean Sloppy Standards**

### **Even though the plugin is privately distributed, it should still follow disciplined engineering standards and WordPress-compatible patterns wherever practical.**

#### **0.10.9 Security Is Mandatory**

### **Security is not a future enhancement. Capability checks, validation, sanitization, escaping, and secure handling of API integrations are baseline requirements.**

#### **0.10.10 User Clarity Over Hidden Magic**

### **The system should explain what it is doing, what it plans to do, and what happened after it acted. Hidden behavior should be minimized.**

#### **0.10.11 Operational Visibility Is Required**

### **Because the plugin will exist on many independently run sites, diagnostics, heartbeat visibility, and install and error awareness are part of the operational model.**

#### **0.10.12 Extensibility Without Fragility**

### **The system should be modular enough to grow, but not so loose that future expansion breaks core contracts.**

### 

### **0.11 Distribution Model**

### **The AIO Page Builder plugin is defined as a privately distributed WordPress plugin.**

### **This means:**

### **the plugin will be distributed outside the WordPress.org plugin repository**

### **installation is expected to occur through direct ZIP upload or equivalent private installation methods**

### **product update distribution may also occur privately**

### **repository approval is not a product requirement**

### **private operational reporting requirements are permitted by product policy even where they would not be allowed in repository distribution**

### **The distribution model is intentionally selected in order to support:**

### **mandatory installation notification**

### **mandatory recurring heartbeat reporting**

### **mandatory or policy-driven developer diagnostics reporting**

### **private control over packaging, release cadence, and deployment behavior**

### **Despite the private distribution model, the plugin should still be designed so that it installs and behaves like a standard WordPress plugin from the perspective of ordinary site administrators.**

### 

### **0.12 Compliance Standard**

### **This plugin shall be engineered to a WordPress-style private-distribution compliance standard.**

### **That means:**

### **the plugin should follow WordPress plugin best practices for code organization, capability checks, input sanitization, output escaping, lifecycle management, portability, and admin architecture**

### **the plugin should respect WordPress conventions for activation, deactivation, uninstall, options handling, post meta handling, and CPT registration**

### **the plugin should support a privacy-conscious data handling model, including transparency and exportability where appropriate**

### **the plugin should use WordPress-compatible implementation patterns rather than bypassing them without clear reason**

### **This compliance standard explicitly includes one approved exception category:**

### **Private-Distribution Reporting Exception\
The plugin may perform mandatory outbound operational notifications and diagnostics, including installation notification, heartbeat messages, and developer error reporting, even though those behaviors would not be permissible under WordPress.org repository distribution rules without explicit user opt-in.**

### **This exception is not to be interpreted broadly. It does not waive the need for:**

### **secure engineering**

### **data minimization where possible**

### **clear admin disclosure**

### **proper secret handling**

### **role and capability enforcement**

### **safe lifecycle behavior**

### **The compliance target is therefore:**

### **WordPress-style standards everywhere practical, with explicit private-distribution exceptions only where formally approved by product policy.**

### 

### **0.13 Revision History**

### **This section shall track formal revisions to the specification so that decisions, scope changes, and architectural changes can be audited over time.**

### **Revision Record Format**

### **Revision Number**

### **Date**

### **Author**

### **Summary of Changes**

### **Approval Status**

### **Initial Entries**

### **Revision 0.1\
Date: TBD\
Author: Product Owner / Spec Author\
Summary of Changes: Initial draft of Document Control section created\
Approval Status: Draft**

### **Revision 0.2\
Date: TBD\
Author: Product Owner / Spec Author\
Summary of Changes: Expanded formal specification structure and master outline\
Approval Status: Draft**

### **Revision 1.0\
Date: TBD\
Author: Product Owner\
Summary of Changes: First approved baseline specification release\
Approval Status: Pending**

### **Revision Rules**

### **Any change affecting scope, architecture, security, compliance posture, AI execution behavior, data retention, or reporting behavior must be logged as a formal revision.**

### **Minor wording clarifications that do not change behavior may be grouped into editorial revisions.**

### **Implementation discoveries that materially change prior assumptions must be documented in revision history rather than silently folded into later sections.**

### **Deprecated decisions should remain traceable and not be deleted without record.**

### 

### **0.14 Change Approval Process**

### **Because this document is intended to function as the authoritative master specification, changes to it must follow a clear approval model.**

#### **0.14.1 Change Categories**

### **Changes shall be classified into one of the following categories:**

### **Editorial Change\
A wording, formatting, grammar, or clarity change that does not alter product behavior, system requirements, architectural intent, or scope.**

### **Clarification Change\
A change that makes a requirement more explicit without materially changing the expected product behavior.**

### **Functional Change\
A change that adds, removes, or modifies required product behavior, workflows, modules, or UX expectations.**

### **Architectural Change\
A change that affects system structure, data model, rendering model, execution model, integration model, or lifecycle model.**

### **Security or Compliance Change\
A change that affects permissions, reporting, privacy, outbound communication, secret handling, data retention, or any compliance-related requirement.**

### **Scope Change\
A change that redefines what is in scope, out of scope, deferred, or required for the target product.**

#### **0.14.2 Approval Authority**

### **Unless later delegated in writing, the Product Owner is the final approval authority for this specification.**

### **The Product Owner may also designate review input from:**

### **lead developer**

### **technical architect**

### **security reviewer**

### **QA lead**

### **implementation partner**

### **Advisory review does not equal approval unless explicitly stated.**

#### **0.14.3 Approval Requirements by Change Type**

### **Editorial Change\
Requires Review: Optional\
Requires Formal Approval: No\
Requires Revision Log Entry: Optional**

### **Clarification Change\
Requires Review: Recommended\
Requires Formal Approval: Usually\
Requires Revision Log Entry: Yes**

### **Functional Change\
Requires Review: Yes\
Requires Formal Approval: Yes\
Requires Revision Log Entry: Yes**

### **Architectural Change\
Requires Review: Yes\
Requires Formal Approval: Yes\
Requires Revision Log Entry: Yes**

### **Security or Compliance Change\
Requires Review: Yes\
Requires Formal Approval: Yes\
Requires Revision Log Entry: Yes**

### **Scope Change\
Requires Review: Yes\
Requires Formal Approval: Yes\
Requires Revision Log Entry: Yes**

#### **0.14.4 Change Workflow**

### **A proposed change is identified.**

### **The change is classified by type.**

### **The affected sections are identified.**

### **The rationale for the change is documented.**

### **Any downstream impact on architecture, roadmap, data model, or UI is reviewed.**

### **The Product Owner approves, rejects, or defers the change.**

### **If approved, the specification is updated and the revision is recorded.**

### **If implementation has already begun, related build tasks must also be updated to match the approved specification change.**

#### **0.14.5 Decision Integrity Rule**

### **No implementation shortcut, developer assumption, or convenience-based workaround may silently override the approved specification. If implementation reality requires a change, the specification must be updated through the approval process before the new behavior becomes authoritative.**

#### **0.14.6 Conflict Resolution Rule**

### **If two sections of this specification appear to conflict, the following precedence order shall apply until the conflict is formally resolved:**

### **Security and compliance requirements**

### **Explicit product principles**

### **Architectural constraints**

### **Functional requirements**

### **UX preferences**

### **Editorial wording**

### **Any unresolved conflict must be added to the decision log for formal resolution.**

### 

### **0.15 Related Documents and Dependencies**

### **This section identifies the documents, systems, tools, plugins, standards, and reference materials that are related to the AIO Page Builder plugin and that may influence its planning, implementation, operation, maintenance, or future expansion.**

### **The purpose of this section is to:**

### **define what external and internal materials this specification depends on**

### **identify the systems the plugin is expected to integrate with or account for**

### **distinguish required dependencies from optional dependencies**

### **identify which reference materials are authoritative versus advisory**

### **reduce ambiguity during development by documenting upstream inputs and downstream dependencies**

### **This section should be maintained as the dependency landscape evolves.**

#### **0.15.1 Dependency Classification Model**

### **Dependencies and related documents shall be classified into the following categories:**

### **Authoritative Internal Documents\
Documents created specifically for this project that define required behavior, architecture, or implementation intent.**

### **Supporting Internal Assets\
Internal working materials, inventories, spreadsheets, CSS references, style guides, strategy documents, and historical planning artifacts that inform implementation but do not automatically override the formal specification unless explicitly adopted into it.**

### **Platform Dependencies\
Software platforms or systems required for the plugin to function as designed.**

### **Functional Plugin Dependencies\
WordPress plugins or plugin classes that are required or strongly expected for major functionality.**

### **Optional Integration Dependencies\
Third-party plugins, systems, or services that are not required for core functionality but may enhance compatibility, workflow, or usefulness.**

### **External Provider Dependencies\
Third-party AI services or similar external systems intentionally used by the plugin.**

### **Standards and Conventions References\
Engineering, security, accessibility, and WordPress-oriented conventions that should guide implementation.**

#### **0.15.2 Authoritative Internal Documents**

### **The following internal documents shall be treated as authoritative inputs to the extent explicitly adopted by this specification or later referenced sections:**

### **AIO Page Builder Master Specification\
This document. It is the primary governing specification for the product unless superseded by an approved revision.**

### **Formal Build Plan and Roadmap Sections\
Any approved continuation documents or subordinate specifications derived from this master document and formally incorporated into it.**

### **Approved Decision Log\
The running record of formally approved architectural, product, scope, security, and compliance decisions.**

### **Approved Data Schema Definitions\
Any approved schema documents for AI input, AI output, export manifests, registry structures, custom tables, or operational object structures.**

### **Approved Capability Matrix\
Any approved permissions matrix governing access control and execution authority within the plugin.**

### **Where conflicts arise between internal project notes and the formal specification, the formal specification shall take precedence unless a formal revision states otherwise.**

#### **0.15.3 Supporting Internal Assets**

### **The following supporting materials may inform planning and implementation but are not automatically authoritative unless explicitly adopted into the specification:**

### **section template inventories**

### **page template inventories**

### **build order spreadsheets**

### **page and section naming systems**

### **CSS planning documents**

### **brand strategy documents**

### **brand style guides**

### **historical Elementor-based section planning documents**

### **historical LPagery field mapping plans**

### **prior site architecture notes**

### **prior workflow conversations and planning outputs**

### **helper text drafts**

### **one-pager drafts**

### **content strategy notes**

### **endpoint inventories**

### **roadmap planning notes**

### **These assets are important because they capture prior system thinking, naming logic, and template structure assumptions, but they must be normalized into the formal specification before they are treated as binding requirements.**

#### **0.15.4 Core Platform Dependencies**

### **The following are core platform assumptions for the plugin:**

### **WordPress Core\
The plugin is built specifically for WordPress and assumes operation within a standard WordPress environment.**

### **PHP Runtime\
The plugin depends on a supported PHP environment appropriate for the chosen WordPress support range.**

### **MySQL or MariaDB-Compatible Database Layer\
The plugin assumes a standard WordPress-compatible database environment for options, post meta, CPTs, and any custom tables.**

### **WordPress Admin Environment\
The plugin assumes access to standard WordPress admin screens, hooks, actions, filters, REST capabilities, and plugin lifecycle events.**

### **These are foundational dependencies. If they are missing or materially incompatible, the plugin cannot operate as intended.**

#### **0.15.5 Required Functional Plugin Dependencies**

### **The following dependencies are expected to be required or effectively required for the product to deliver its intended core functionality:**

### **Advanced Custom Fields (ACF)\
ACF is required for the section-level field architecture, field-group generation model, and controlled field visibility strategy adopted by this product.**

### **GenerateBlocks or Compatible Native Block Strategy\
Because page output is intended to rely on native blocks and a GenerateBlocks-friendly structure, the plugin assumes access to the necessary block capabilities or a compatible implementation pathway.**

### **If the final implementation requires a specific version or edition of any dependency, that requirement shall be stated explicitly in later technical sections.**

#### **0.15.6 Conditional or Contextual Functional Dependencies**

### **The following dependencies are not universally required for every installation, but they are strongly associated with major intended workflows:**

### **LPagery\
Required where users intend to use LPagery-driven tokenized or bulk-generated page workflows. Not every site may need LPagery, but LPagery compatibility is a planned product requirement.**

### **AI Provider Account Access\
Required for onboarding-based AI analysis, structured site planning, and AI-generated recommendations. The plugin should still retain meaningful non-AI local utility where possible, but the AI planning system depends on provider availability and valid credentials.**

### **Outbound Email Capability\
Required for installation notification, heartbeat reporting, and developer error reporting. If the site cannot send mail, reporting behavior must fail gracefully and log the failure.**

#### **0.15.7 Optional Integration Dependencies**

### **The following integrations are optional unless later elevated to required status:**

### **SEO plugins for metadata synchronization or interoperability**

### **featured image or media-related plugins**

### **role or capability management plugins**

### **caching plugins**

### **security plugins**

### **backup or export plugins**

### **logging or monitoring plugins**

### **multisite-specific administrative tooling**

### **connector layers to other content or data systems**

### **The plugin should be built to coexist with common plugins where practical, but it is not required to natively replicate their full functionality.**

#### **0.15.8 External Provider Dependencies**

### **The following external dependencies are intentional parts of the product architecture:**

### **AI Provider APIs\
The plugin is designed to communicate with one or more AI providers selected by the user for structured planning output. These providers are external dependencies and may differ in features, limits, and structured output capabilities.**

### **Remote Reporting Destination\
The plugin’s mandatory private-distribution operational reporting model assumes a valid destination for installation notifications, heartbeat messages, and developer diagnostics reporting.**

### **Optional External Services Introduced Later\
If future revisions introduce other external services, such as remote update endpoints, remote prompt libraries, remote diagnostics dashboards, or cloud asset storage, those dependencies must be added by formal revision and explicitly classified.**

#### **0.15.9 Internal System Dependencies**

### **The following internal subsystems depend on one another and must be planned as related components rather than isolated features:**

### **section template registry depends on the CSS contract, field architecture, and helper paragraph system**

### **page template registry depends on section templates, one-pager assembly rules, and compatibility logic**

### **custom template compositions depend on the section registry and validation rules**

### **onboarding depends on the brand profile system, business profile system, crawl engine, and AI provider setup**

### **AI runs depend on prompt packs, input artifact preparation, registry snapshots, and crawl snapshots**

### **build plans depend on validated AI outputs and local normalization logic**

### **execution depends on build plans, capability enforcement, snapshot creation, and status logging**

### **rollback depends on snapshot storage and operation history**

### **uninstall and restore depend on export packaging and import validation**

### **telemetry and reporting depend on installation lifecycle events, diagnostics, and mail or transport capability**

### **Because of these interdependencies, build sequencing must be intentional and modular.**

#### **0.15.10 Standards and Conventions References**

### **The plugin should be designed in alignment with the following categories of standards and conventions:**

### **WordPress Plugin Architecture Conventions\
Used to guide plugin structure, admin behavior, capability checks, lifecycle handling, uninstall behavior, storage choices, and compatible implementation patterns.**

### **WordPress Security Conventions\
Used to guide sanitization, escaping, nonce usage, capability enforcement, secure request handling, and secret protection.**

### **WordPress Privacy and Data Handling Conventions\
Used to guide exporter and eraser integration, retention decisions, transparency, and safe external communication practices.**

### **WordPress Coding Standards\
Used to guide PHP, JavaScript, CSS, and naming consistency.**

### **Accessibility Standards and Best Practices\
Used to guide admin UI and front-end output expectations for semantic structure, focus handling, keyboard access, and clarity.**

### **Structured Output and Schema Validation Best Practices\
Used to guide AI input and output reliability and prevent unstructured or ambiguous build instructions from becoming the operational source of truth.**

### **These references guide implementation quality, even when the plugin is privately distributed.**

#### **0.15.11 Dependency Ownership and Responsibility**

### **Each dependency category shall have an implied ownership responsibility:**

### **Product Owner**

### **approves dependency adoption**

### **approves dependency removals or major replacements**

### **approves changes affecting scope or compatibility posture**

### **Technical Architect or Lead Developer**

### **evaluates dependency feasibility**

### **determines implementation boundaries**

### **defines safe integration patterns**

### **maintains compatibility logic**

### **Implementation Team**

### **builds against approved dependencies**

### **reports dependency conflicts or constraints**

### **avoids introducing undeclared critical dependencies without approval**

### **No dependency should become functionally critical without being explicitly documented in this section or a later approved revision.**

#### **0.15.12 Dependency Volatility and Change Risk**

### **Not all dependencies carry the same risk. For planning purposes, dependencies shall be treated according to the following risk classes:**

### **Low Volatility Dependencies**

### **WordPress core concepts**

### **standard plugin lifecycle patterns**

### **internal registries**

### **internal documentation systems**

### **Moderate Volatility Dependencies**

### **ACF implementation assumptions**

### **GenerateBlocks-oriented output structure**

### **LPagery compatibility expectations**

### **common plugin interoperability expectations**

### **High Volatility Dependencies**

### **AI provider features**

### **model behavior**

### **model pricing or limits**

### **external structured output reliability**

### **reporting transport assumptions**

### **any future remote services**

### **Higher-volatility dependencies should be isolated behind abstraction layers where possible.**

#### **0.15.13 Dependency Failure Philosophy**

### **The system shall be designed so that dependency failures are handled intentionally.**

### **Examples:**

### **if AI provider access fails, the plugin should not corrupt existing content**

### **if email or reporting fails, the plugin should log failure and continue safe local operation where possible**

### **if optional plugin integrations are absent, the core content system should remain usable**

### **if LPagery is absent, LPagery-specific workflows may be disabled without breaking unrelated plugin functions**

### **if the plugin is removed, already-built native pages must remain usable**

### **A missing or failed dependency must not silently create destructive behavior.**

#### **0.15.14 Future Dependency Additions**

### **Any future dependency that materially affects the product must be added through the formal change approval process if it does any of the following:**

### **introduces a new required plugin**

### **introduces a new external service**

### **changes the output model of built pages**

### **changes privacy or reporting behavior**

### **changes the AI planning or execution architecture**

### **changes uninstall, export, or restore behavior**

### **changes the portability guarantees of built content**

### **This rule exists to prevent uncontrolled architectural drift.**

#### **0.15.15 Dependency Summary Statement**

### **In summary, this specification depends on:**

### **WordPress as the operating platform**

### **a structured template registry system defined by this project**

### **ACF as the adopted field architecture basis**

### **native block and GenerateBlocks-compatible page assembly**

### **LPagery compatibility where applicable**

### **user-selected AI provider access for AI planning functions**

### **controlled operational reporting infrastructure for private-distribution support visibility**

### **internal documentation assets and future formal schema documents as governing references**

### **All dependencies must support the core product promise:**

### **Structured page and section templating, guided AI-assisted planning, safe execution, portable built content, and durable operational visibility across privately distributed WordPress installs.**

### **If you want, I’ll format Section 1. Executive Product Overview in this same Google Docs-ready style next.**

### 

### 

## **1. Executive Product Overview**

### **1.1 Product Summary**

### **AIO Page Builder is a privately distributed WordPress plugin designed to help users plan, structure, generate, rebuild, and manage websites through a controlled system of reusable section templates, reusable page templates, structured field architecture, AI-assisted planning, and guided execution workflows.**

### **The plugin is intended to solve a specific class of website-building problems: situations where users need more structure, consistency, speed, and operational guidance than standard WordPress editing alone provides, but do not want or need a full visual builder ecosystem. Instead of acting as a drag-and-drop design tool, the plugin acts as a template orchestration, content planning, and execution system.**

### **At its core, the plugin provides:**

### **a registry of section templates**

### **a registry of page templates built from those section templates**

### **a system for creating custom page-template compositions from registered sections**

### **a section-level ACF field model with programmatic visibility assignment**

### **helper documentation for each section template**

### **one-pager documentation for each page template or custom composition**

### **a guided onboarding system to collect business and brand context**

### **a public-site crawl and analysis engine**

### **a structured AI planning system using user-selected provider APIs**

### **a build-plan interface for reviewing, approving, denying, and executing recommended site changes**

### **a safe execution engine for creating pages, rebuilding pages, updating hierarchies, and applying structured changes**

### **logging, rollback, export, restore, and operational reporting capabilities**

### **The plugin is designed to create native WordPress content outcomes, not fragile proprietary page representations. Its primary output should remain usable even if the plugin is later deactivated or removed.**

### 

### **1.2 Core Product Vision**

### **The core vision of AIO Page Builder is to make website planning and page construction significantly more structured, scalable, repeatable, and explainable for WordPress users who need more than manual editing but less than a full visual-builder dependency.**

### **The long-term vision is not to compete with every page builder or replace the WordPress editor. The long-term vision is to provide a formal system that can:**

### **define reusable, high-quality page-building patterns**

### **document how those patterns should be used**

### **understand a business and its website context**

### **analyze an existing public site**

### **generate a structured recommendation for what should be built, rebuilt, or reorganized**

### **guide the user through that plan step by step**

### **safely execute approved changes**

### **preserve built content in a durable, portable way**

### **The ideal end state is a plugin that behaves like a website planning and structured build operator. It should help users move from vague business information and messy site structure to a more coherent site architecture and page system without depending on improvisation at every step.**

### **The plugin should make the process of site planning and page production feel:**

### **deliberate rather than chaotic**

### **systematized rather than improvised**

### **guided rather than opaque**

### **aggressive in capability, but controlled in execution**

### **reusable rather than one-off**

### **documentation-backed rather than memory-based**

### **This product vision assumes that structure and repeatability are strategic advantages, and that a website-building system becomes more valuable when it can explain itself, preserve consistency, and reduce operator uncertainty.**

### 

### **1.3 Primary Use Cases**

### **The plugin is intended to support a focused set of primary use cases.**

#### **1.3.1 Structured New Site Planning**

### **A user wants to define a website structure from scratch using a guided system rather than manually creating every page without strategy. The plugin collects business and brand information, analyzes any relevant existing site context, generates a recommended site structure, and proposes what pages should be created and which page templates should be used.**

#### **1.3.2 Existing Site Rebuild or Reorganization**

### **A user has an existing public website and wants structured guidance on what should be changed. The plugin crawls the public site, analyzes meaningful indexable pages, and produces a structured plan for:**

### **pages to keep**

### **pages to rebuild**

### **pages to replace**

### **pages to newly create**

### **changes to hierarchy**

### **changes to menus and navigation**

### **tokenized visual system recommendations**

#### **1.3.3 Reusable Template-Based Page Production**

### **A user wants to build pages from pre-defined page templates composed of section templates instead of manually designing each page. The plugin provides the template selection, field structure, helper documentation, and execution pathway needed to do this consistently.**

#### **1.3.4 Custom Template Composition**

### **A user wants to build a new page template from registered sections while still staying inside the system’s structural rules. The plugin allows them to compose a valid custom page template and automatically generate the corresponding helper one-pager.**

#### **1.3.5 ACF-Driven Content Management at Scale**

### **A user wants content fields to stay organized and relevant to the page being edited. The plugin creates and assigns only the relevant section-based ACF field groups to built pages so the editing experience remains structured and scalable.**

#### **1.3.6 LPagery-Compatible Bulk Content Workflows**

### **A user wants to create pages in a token-aware system that can support location or landing-page style generation where LPagery tokens are relevant. The plugin ensures section-level field structures are compatible where possible.**

#### **1.3.7 AI-Assisted Site Planning with Human Approval**

### **A user wants AI to help determine:**

### **site purpose and flow**

### **suggested pages**

### **suggested hierarchy**

### **suggested menus**

### **suggested page-template usage**

### **suggested section-level content direction**

### **suggested design-token values**

### **The plugin allows AI to assist in planning, but routes all execution through a user-reviewable build plan.**

#### **1.3.8 Guided Page Replacement and Update Workflows**

### **A user wants to update an existing page using an aggressive but controlled replacement model. The plugin can archive or privatize the original page, create a new version using a template, populate the page using the structured plan, and log the change.**

#### **1.3.9 Site Operations Visibility Across Many Installations**

### **The product owner wants the plugin to provide visibility into installation status, heartbeat status, and errors across many private installs through built-in operational reporting.**

### 

### **1.4 Core User Problems Solved**

### **This plugin is intended to solve several recurring user problems that appear in manual WordPress workflows.**

#### **1.4.1 Lack of Structure in Site Planning**

### **Many users know they need pages, offers, or hierarchy, but do not have a clear system for deciding:**

### **what pages should exist**

### **how those pages relate to one another**

### **which page pattern should be used for each page**

### **how navigation should be organized**

### **The plugin solves this by creating a structured planning model backed by templates, crawl analysis, and AI-assisted recommendations.**

#### **1.4.2 Inconsistent Page Construction**

### **Without a formal template system, pages often become inconsistent in:**

### **section order**

### **field usage**

### **content hierarchy**

### **styling decisions**

### **SEO structure**

### **user flow**

### **The plugin solves this by defining fixed section and page templates with known behavior and known editing guidance.**

#### **1.4.3 Poor Documentation and Editor Confusion**

### **Users often struggle to understand what belongs in each section, how to write for it, what kind of image should be used, how much copy is appropriate, or how fields should be filled out.**

### **The plugin solves this by attaching helper paragraphs to section templates and generating one-pagers for page templates and custom compositions.**

#### **1.4.4 Overwhelming Field Interfaces**

### **When too many ACF fields or unrelated field groups appear on a page, the editing experience becomes cluttered and hard to manage.**

### **The plugin solves this by generating section-level field groups and assigning them programmatically so only relevant fields appear on the relevant pages.**

#### **1.4.5 Existing Site Rebuild Uncertainty**

### **Users often know a site needs improvement but do not know what to change first, what should be rebuilt, what can stay, and what structure should replace the current mess.**

### **The plugin solves this through public-site analysis, AI-assisted recommendation, and a structured build plan interface.**

#### **1.4.6 Risky or Opaque Rebuild Workflows**

### **Aggressive rebuilds are often scary because users do not know what will happen, what changed, or how to recover if something goes wrong.**

### **The plugin solves this by separating planning from execution, recording actions, creating snapshots, and supporting rollback-oriented workflows.**

#### **1.4.7 Fragile Builder Lock-In**

### **Many page-building workflows create dependency on a specific builder’s rendering model, making future edits, migration, or plugin removal painful.**

### **The plugin solves this by targeting native WordPress content output and aiming for built-page survivability beyond the plugin lifecycle.**

#### **1.4.8 Disconnected Branding and Content Decisions**

### **Brand information, audience data, service structure, and competitor context are often stored nowhere or scattered across informal notes, making content planning inconsistent.**

### **The plugin solves this by centralizing business and brand profile data and using it to inform planning, documentation, and AI-assisted recommendations.**

### 

### **1.5 Product Value Proposition**

### **The value proposition of AIO Page Builder is that it provides a more structured, intelligent, and durable way to plan and build WordPress websites without requiring users to rely on a fully open-ended visual builder.**

### **Its value is not based on creative freedom alone. Its value is based on controlled power.**

### **The plugin provides value by combining:**

### **reusable section-level systems**

### **reusable page-level systems**

### **structured field architecture**

### **embedded editing guidance**

### **AI-assisted planning**

### **reviewable build execution**

### **rollback-aware operations**

### **portable built outputs**

### **multi-site operational visibility**

### **In practical terms, the plugin promises the following value:**

#### **1.5.1 Faster Structured Build Workflows**

### **Users can move faster because they are not inventing every page from scratch.**

#### **1.5.2 Better Consistency**

### **Pages created within the system follow defined structures and editing rules.**

#### **1.5.3 Better Planning Quality**

### **The AI-assisted planning layer can review site context and recommend a more coherent structure than ad hoc manual decisions alone.**

#### **1.5.4 Better Editor Usability**

### **Relevant field groups and helper documentation reduce confusion during content entry.**

#### **1.5.5 Better Safety**

### **Aggressive actions are executed through logged, structured workflows rather than silent one-off changes.**

#### **1.5.6 Better Portability**

### **Built pages are intended to remain usable even if the plugin is later disabled or removed.**

#### **1.5.7 Better Operational Oversight**

### **Because the plugin is privately distributed, it includes built-in reporting and diagnostics visibility that support deployment across many independent sites.**

### **In short, the product value proposition is:**

### **AIO Page Builder helps users turn website planning and page construction into a structured, documented, AI-assisted, execution-safe system rather than a collection of disconnected manual actions.**

### 

### **1.6 Product Boundaries**

### **The product is intentionally powerful, but it still has boundaries.**

### **The plugin is bounded by the following design intent:**

### **it is a template orchestration and execution system**

### **it is not a fully general visual builder**

### **it is a structured planning tool**

### **it is not an unrestricted AI autonomy engine**

### **it is a WordPress plugin**

### **it is not a custom CMS replacement**

### **it is intended to generate and manage durable page outputs**

### **it is not intended to trap the user in proprietary front-end dependency**

### **it is designed for structured website planning, rebuilding, and expansion**

### **it is not intended to replace every plugin category that touches content, SEO, media, roles, or security**

### **The plugin should have enough scope to meaningfully plan, generate, and manage pages, but not so much scope that it becomes an undefined all-purpose site operating system.**

### 

### **1.7 What the Plugin Is**

### **The plugin is:**

### **a WordPress plugin for structured site planning and page production**

### **a registry-driven system for reusable section templates**

### **a registry-driven system for reusable page templates**

### **a custom composition system for assembling valid page-template variants from existing sections**

### **a field architecture system built around section-level ACF field groups**

### **a documentation system with helper paragraphs and one-pagers**

### **a guided onboarding system for business, brand, and website context intake**

### **a public-site crawl and analysis system**

### **a multi-provider AI planning integration layer**

### **a schema-validated AI artifact system**

### **a step-based build-plan interface**

### **a review and execution system for updating existing pages and creating new ones**

### **a menu and hierarchy planning tool**

### **a design-token recommendation and application system**

### **a logging, snapshot, and rollback-aware operational tool**

### **a privately distributed plugin with built-in operational reporting**

### **Most importantly, it is a system of structure and orchestration, not just a convenience plugin.**

### 

### **1.8 What the Plugin Is Not**

### **The plugin is not:**

### **a full drag-and-drop visual page builder**

### **a replacement for Elementor in the broad sense of reproducing its entire editing paradigm**

### **a general-purpose front-end design canvas**

### **a theme replacement**

### **a proprietary front-end rendering framework that pages cannot survive without**

### **an unrestricted AI agent with authority to mutate the site without user approval**

### **a generic AI chatbot for arbitrary site questions**

### **a complete replacement for all SEO plugins**

### **a complete replacement for all media plugins**

### **a replacement for WordPress role and user management systems**

### **a backup plugin**

### **a hosting platform**

### **a SaaS dashboard product in the scope of this specification**

### **a crawler for private, member-only, or admin-only content by default**

### **an excuse to bypass WordPress security, lifecycle, portability, or admin conventions**

### **The product gains clarity and strength by being explicit about what it does not attempt to be.**

### 

### **1.9 High-Level Functional Pillars**

### **The plugin is organized around several high-level functional pillars.**

#### **1.9.1 Template Registry Pillar**

### **This pillar defines, stores, validates, and manages:**

### **section templates**

### **page templates**

### **custom template compositions**

### **template compatibility rules**

### **helper documentation links**

### **This pillar is the structural backbone of the plugin.**

#### **1.9.2 Field and Content Architecture Pillar**

### **This pillar governs:**

### **ACF field-group generation**

### **page assignment rules**

### **LPagery compatibility where applicable**

### **field naming**

### **field visibility**

### **structured editing experiences**

### **This pillar ensures content input remains organized and relevant.**

#### **1.9.3 Rendering and Portability Pillar**

### **This pillar governs:**

### **native block output**

### **GenerateBlocks-friendly composition**

### **render callback boundaries**

### **post content assembly**

### **built-page survivability**

### **front-end structural consistency**

### **This pillar ensures that output remains durable and usable.**

#### **1.9.4 Documentation and Guidance Pillar**

### **This pillar governs:**

### **helper paragraphs**

### **one-pager generation**

### **section usage guidance**

### **page-level editing guidance**

### **content clarity standards**

### **This pillar makes the system understandable rather than opaque.**

#### **1.9.5 Brand, Business, and Onboarding Pillar**

### **This pillar governs:**

### **brand intake**

### **business intake**

### **audience and persona intake**

### **service and market intake**

### **competitor intake**

### **onboarding persistence**

### **rerun and prefill logic**

### **This pillar gives the planning system real context.**

#### **1.9.6 Crawl and Analysis Pillar**

### **This pillar governs:**

### **public-page discovery**

### **indexable-page filtering**

### **meaningful-page classification**

### **crawl snapshots**

### **structural analysis**

### **page inventory and relationship analysis**

### **This pillar gives the plugin situational awareness of the current site.**

#### **1.9.7 AI Planning Pillar**

### **This pillar governs:**

### **provider abstraction**

### **prompt pack assembly**

### **input packaging**

### **schema-validated structured outputs**

### **run artifacts**

### **planning recommendations**

### **This pillar allows AI to assist in a controlled and auditable way.**

#### **1.9.8 Build Plan and Review Pillar**

### **This pillar governs:**

### **build-plan generation**

### **step-based review UI**

### **page update proposals**

### **page creation proposals**

### **menu proposals**

### **token proposals**

### **review, acceptance, and denial flows**

### **This pillar converts AI output into a human-operable action system.**

#### **1.9.9 Execution, Snapshot, and Rollback Pillar**

### **This pillar governs:**

### **approved action execution**

### **page creation**

### **page replacement**

### **menu updates**

### **status tracking**

### **snapshot creation**

### **rollback preparation**

### **failure recovery**

### **This pillar makes aggressive action operationally responsible.**

#### **1.9.10 Diagnostics, Reporting, and Lifecycle Pillar**

### **This pillar governs:**

### **logs**

### **errors**

### **developer reporting**

### **install notification**

### **heartbeat visibility**

### **export/import**

### **deactivation and uninstall behavior**

### **This pillar supports long-term maintainability across many sites.**

### 

### **1.10 Private Distribution Positioning**

### **The plugin is intentionally positioned as a privately distributed product, not a repository-bound WordPress.org plugin.**

### **This positioning is strategic, not incidental.**

### **Private distribution allows the product to:**

### **include mandatory install notification**

### **include mandatory heartbeat reporting**

### **include mandatory or policy-driven developer diagnostics reporting**

### **maintain tighter control over release packaging and update behavior**

### **serve a specialized operational model without being constrained by repository policy where that policy conflicts with the product’s support model**

### **Private distribution does not mean the plugin should behave carelessly or non-standardly. It simply means the distribution channel and operational-reporting model are intentionally outside WordPress.org repository constraints.**

### **The product should still feel normal to install and use from the perspective of a WordPress admin:**

### **installable by standard plugin ZIP upload**

### **managed through ordinary admin screens**

### **governed by capability checks**

### **structured like a disciplined plugin**

### **removable without destroying built pages**

### **The positioning can therefore be stated as:**

### **A privately distributed professional WordPress plugin built for structured planning, templated page production, and controlled multi-site operational visibility.**

### 

### **1.11 WordPress-Style Engineering Standards**

### **Although the plugin is privately distributed, it is explicitly intended to follow WordPress-style engineering standards wherever practical.**

### **This includes standards around:**

### **plugin architecture**

### **activation, deactivation, and uninstall behavior**

### **capability-based access control**

### **nonce usage and request validation**

### **input sanitization**

### **output escaping**

### **use of standard WordPress storage patterns**

### **use of CPTs, post meta, options, and custom tables appropriately**

### **admin screen conventions**

### **REST and AJAX permission controls**

### **portability and survivability of built content**

### **privacy-aware data handling**

### **plugin compatibility discipline**

### **predictable and maintainable code organization**

### **This standard exists for several reasons:**

#### **1.11.1 Operational Reliability**

### **Following WordPress-style patterns improves predictability across different installs.**

#### **1.11.2 Lower Maintenance Cost**

### **Conventional engineering reduces the amount of custom logic that future developers must mentally reverse-engineer.**

#### **1.11.3 Better Compatibility**

### **WordPress-style behavior helps the plugin coexist better with themes, plugins, and hosting environments.**

#### **1.11.4 Better Security Posture**

### **Security best practices are easier to enforce when the plugin stays within known WordPress architectural patterns.**

#### **1.11.5 Better Portability**

### **A plugin that respects core patterns is less likely to create content that becomes brittle later.**

### **The product standard is therefore not “repository-compliant at all costs.”\
It is “WordPress-style engineered at all times except where the approved private-distribution reporting model intentionally differs.”**

### 

### **1.12 Mandatory Reporting Exception Model**

### **One of the defining differences between this product and a repository-bound plugin is the mandatory reporting model.**

### **Because the plugin is privately distributed, it is permitted by product policy to include outbound operational reporting that would not be acceptable for WordPress.org distribution without explicit opt-in.**

### **This exception model covers three core reporting behaviors:**

#### **1.12.1 Installation Notification**

### **Upon successful plugin installation or activation, the plugin may send an operational notification indicating that the plugin has been installed on a website.**

#### **1.12.2 Recurring Heartbeat Reporting**

### **On a defined recurring schedule, the plugin may send a heartbeat message confirming operational status and site presence.**

#### **1.12.3 Error and Diagnostics Reporting**

### **When qualifying failures or error conditions occur, the plugin may send structured diagnostics information to the designated reporting destination.**

### **This exception model exists to support:**

### **operational visibility**

### **support awareness**

### **failure monitoring**

### **deployment footprint awareness across many uncontrolled installs**

### **However, this exception model does not eliminate the need for disciplined handling of outbound data. The reporting system must still follow internal product rules for:**

### **secure transmission**

### **data minimization where possible**

### **secret exclusion**

### **role-safe behavior**

### **log traceability**

### **admin transparency**

### **failure logging when reporting itself fails**

### **The reporting model is therefore best understood as:**

### **a deliberately approved private-distribution operational exception, not a general license to ignore privacy, safety, or engineering discipline.**

### 

### **1.13 Long-Term Product Direction**

### **The long-term direction of AIO Page Builder is to become a highly structured WordPress site planning and page-orchestration system that can scale across many different website use cases while remaining disciplined in architecture and durable in output.**

### **Its future direction should emphasize:**

#### **1.13.1 Deeper Template System Maturity**

### **The section and page template registries should become more robust over time, with clearer compatibility rules, stronger documentation, richer variants, and more refined build patterns.**

#### **1.13.2 Better Planning Intelligence**

### **The AI planning layer should improve in quality through better prompt packs, better schema design, better artifact handling, and better normalization of recommendations into actionable build plans.**

#### **1.13.3 Safer and Smarter Execution**

### **The execution engine should become more capable while preserving the planner/executor boundary and maintaining recoverability, auditability, and transparency.**

#### **1.13.4 Better Multi-Site Operability**

### **Because the plugin is intended for wide private distribution, long-term maturity should include improved diagnostics, better operational insight, and stronger support tooling.**

#### **1.13.5 Better Documentation as a Product Feature**

### **Helper paragraphs, one-pagers, field guidance, and plan explainability should continue to improve rather than being treated as secondary implementation details.**

#### **1.13.6 Better Portability and Reduced Fragility**

### **The product should continue to favor durable content output and lower long-term lock-in, even as functionality becomes more advanced.**

#### **1.13.7 Better Extensibility**

### **Future growth should happen through clear modules, versioned registries, schema discipline, and controlled expansion rather than ad hoc feature accumulation.**

#### **1.13.8 Better Strategic Positioning**

### **The plugin should increasingly occupy a clear category:\
not just “page building,” but structured site planning, templated content production, and controlled execution for WordPress.**

### **The long-term direction is not to become everything.\
The long-term direction is to become extremely strong at a specific kind of structured website work:**

### **understanding what a site should be, defining how its pages should be built, guiding users through those decisions, and safely turning those decisions into durable WordPress content.**

### 

## **2. Product Goals, Non-Goals, and Success Criteria**

### **2.1 Primary Goals**

### **The primary goals of the AIO Page Builder plugin are the central product outcomes that must be achieved for the product to be considered successful in its intended category.**

#### **2.1.1 Establish a Structured Website-Building System**

### **The plugin must provide a formal and repeatable system for creating, organizing, and applying section templates and page templates in WordPress. It must reduce improvisation and replace scattered manual decision-making with a durable framework.**

#### **2.1.2 Enable Guided Site Planning**

### **The plugin must help users move from business information and existing site context to a clear, structured plan for what pages should exist, how they should be organized, what templates should be used, and what updates should be made.**

#### **2.1.3 Support Reusable Page Production**

### **The plugin must make it practical to build pages repeatedly from known template systems rather than requiring users to rebuild structure and logic from scratch every time.**

#### **2.1.4 Preserve Native Content Survivability**

### **The plugin must generate outputs that remain durable and usable as native WordPress content to the greatest extent reasonably possible. Built pages must not become structurally dependent on the plugin for basic front-end survival.**

#### **2.1.5 Provide AI-Assisted Planning Without Uncontrolled Execution**

### **The plugin must allow AI to contribute useful planning, hierarchy, structure, and content recommendations while preserving clear approval boundaries before high-impact site changes occur.**

#### **2.1.6 Improve Editing Clarity and Usability**

### **The plugin must reduce editor confusion by providing relevant field groups, helper documentation, template guidance, and build-plan visibility.**

#### **2.1.7 Support Aggressive but Controlled Rebuild Workflows**

### **The plugin must support meaningful site change workflows, including structured replacement of existing pages, while preserving logs, status reporting, and recoverability.**

#### **2.1.8 Maintain Multi-Site Operational Visibility**

### **Because the plugin is intended for broad private distribution, it must support installation visibility, heartbeat visibility, and actionable error reporting in a structured operational model.**

### 

### **2.2 Secondary Goals**

### **Secondary goals are important product benefits that materially improve usefulness but are subordinate to the primary goals.**

#### **2.2.1 Reduce Time-to-Build for Structured Sites**

### **The plugin should allow qualified users to move faster from planning to implementation by reusing section and page patterns.**

#### **2.2.2 Improve Content Consistency Across Pages**

### **The plugin should promote consistent hierarchy, content flow, section behavior, and field usage across multiple pages and multiple sites.**

#### **2.2.3 Centralize Business and Brand Context**

### **The plugin should provide a clear place to store and reuse business, audience, brand, and service information that would otherwise be scattered or lost.**

#### **2.2.4 Improve Team Handoff and Collaboration**

### **The plugin should help future editors, collaborators, or implementers understand what a page is, why it exists, and how it should be edited.**

#### **2.2.5 Support Scalable Template Growth**

### **The system should be able to grow from a smaller template library to a larger one without collapsing under inconsistent naming, unclear field mapping, or ad hoc logic.**

#### **2.2.6 Provide Better Planning Transparency**

### **The plugin should make AI planning outputs and build recommendations inspectable, downloadable, and understandable.**

#### **2.2.7 Encourage Safer Site Operations**

### **The plugin should nudge users toward safer workflows through step-based review, status visibility, snapshots, and logs.**

### 

### **2.3 Non-Goals**

### **Non-goals are outcomes the plugin is explicitly not attempting to achieve, even if they may be adjacent to the product’s domain.**

#### **2.3.1 Full Visual Builder Replacement**

### **The plugin is not intended to replicate the full feature set, editing paradigm, or freeform design capabilities of Elementor or similar visual page builders.**

#### **2.3.2 Arbitrary Layout Freedom as a Primary Feature**

### **The plugin is not primarily designed to let users visually invent any layout at any time with no structural boundaries.**

#### **2.3.3 WordPress Theme Replacement**

### **The plugin is not intended to function as a theme framework or replace WordPress themes as the primary rendering layer.**

#### **2.3.4 Autonomous Site Mutation by AI**

### **The plugin is not intended to allow AI to silently or independently perform destructive or high-impact site changes without user review and approved execution.**

#### **2.3.5 Full Replacement of Specialized Plugins**

### **The plugin is not intended to fully replace mature plugin categories such as backup tools, security suites, media-library managers, role-management tools, or full SEO suites.**

#### **2.3.6 Private-Content Crawler by Default**

### **The plugin is not intended to crawl, parse, or analyze private site areas, logged-in content, or administrative content by default.**

#### **2.3.7 Generic Chatbot or Writing Assistant**

### **The plugin is not intended to operate as an open-ended AI assistant for arbitrary questions unrelated to structured site planning and page production.**

#### **2.3.8 Proprietary Lock-In as a Business Model**

### **The plugin is not intended to make built pages fragile or unusable if the plugin is later removed.**

### 

### **2.4 Design Principles**

### **Design principles define how product decisions should be made when multiple valid implementation options exist.**

#### **2.4.1 Structure Before Flexibility**

### **The plugin should prefer structured systems with known rules over excessive flexibility that undermines consistency and scale.**

#### **2.4.2 Explainability Before Cleverness**

### **Users should be able to understand what the system proposes, what it built, and how it made those decisions.**

#### **2.4.3 Reusability Before One-Off Convenience**

### **When possible, the system should solve recurring patterns with reusable template logic instead of relying on one-time exceptions.**

#### **2.4.4 Stable Contracts Before Dynamic Reinvention**

### **Markup contracts, field keys, template handles, and system identifiers should remain stable.**

#### **2.4.5 Durable Content Before Proprietary Dependency**

### **Output should remain functional and editable as WordPress content even if the plugin is deactivated or removed.**

#### **2.4.6 Guided Choice Before Empty Canvases**

### **The system should provide choices, pathways, and validated options rather than repeatedly forcing users into open-ended blank-state decision making.**

### 

### **2.5 Reliability Principles**

### **Reliability principles define the operational quality expected from the system.**

#### **2.5.1 Deterministic Execution**

### **The same approved action should behave the same way under the same conditions.**

#### **2.5.2 Explicit Status Visibility**

### **Long-running or multi-step operations should expose status, progress, and failure information rather than leaving the user uncertain.**

#### **2.5.3 Recovery Awareness**

### **High-impact operations should be performed in a way that preserves the ability to inspect history and, where supported, recover from failure.**

#### **2.5.4 Clear Separation of Planning and Doing**

### **Planning outputs should not be treated as execution success. Proposed changes and performed changes must remain distinct.**

#### **2.5.5 Failure Should Be Noisy Internally, Not Destructive Externally**

### **Operational failure should produce logs and diagnostics rather than silent corruption.**

### 

### **2.6 Safety Principles**

### **Safety principles define how the product should behave when performing site-impacting actions.**

#### **2.6.1 No Silent Destructive Changes**

### **The plugin should not hide destructive or high-impact actions behind vague controls or invisible processes.**

#### **2.6.2 Snapshot Before Mutation**

### **When possible and appropriate, meaningful state should be captured before executing impactful changes.**

#### **2.6.3 Permissions Must Gate Action**

### **No user should be able to execute actions they are not explicitly authorized to perform.**

#### **2.6.4 External Services Must Not Be Trusted Blindly**

### **AI output, provider availability, remote transport, and diagnostics pipelines must all be treated as fallible.**

#### **2.6.5 Sensitive Data Must Be Protected**

### **Credentials, secrets, and prohibited data types must be excluded from logs, outbound reports, and artifacts except where explicitly required and safely handled.**

### 

### **2.7 User Experience Principles**

### **The plugin’s user experience should be guided by the following principles.**

#### **2.7.1 Clarity Over Mystery**

### **Users should know what screen they are on, what action is available, what the system recommends, and what the consequences are.**

#### **2.7.2 Step-Based Complexity**

### **Complex workflows should be broken into meaningful stages instead of overwhelming users with everything at once.**

#### **2.7.3 Context Should Stay Visible**

### **Important context such as site purpose, plan rationale, or current status should not disappear while the user moves through the workflow.**

#### **2.7.4 Documentation Is Embedded, Not Separate**

### **Guidance should live close to where users need it.**

#### **2.7.5 Pretty Does Not Replace Useful**

### **Polished UI is valuable, but clear hierarchy, explicit actions, and reliable feedback matter more than decoration.**

### 

### **2.8 Content Portability Principles**

### **Because the plugin works inside WordPress, content portability is a core strategic concern.**

#### **2.8.1 Built Pages Must Survive Plugin Removal**

### **The plugin must be designed so that created pages remain useful after deactivation or uninstall.**

#### **2.8.2 Post Content Should Hold Meaningful Structure**

### **Important page output should exist in standard WordPress content storage where possible.**

#### **2.8.3 Plugin Data and Page Data Must Be Distinguishable**

### **Operational plugin data should be separable from the site content it helped generate.**

#### **2.8.4 Export and Restore Must Be Possible**

### **Users should have a pathway to export plugin-specific settings and restore them later without losing built page survivability.**

### 

### **2.9 Maintainability Principles**

### **Maintainability matters because this plugin is intended to grow.**

#### **2.9.1 Module Boundaries Must Be Clear**

### **Template registry logic, AI logic, execution logic, and reporting logic should not collapse into a single undifferentiated codebase.**

#### **2.9.2 Naming Must Stay Predictable**

### **Handles, classes, IDs, field keys, object types, and schema names must be consistently defined and documented.**

#### **2.9.3 Configuration Must Be Traceable**

### **Behavior driven by settings, prompt packs, schema versions, and template registries must be versionable and reviewable.**

#### **2.9.4 New Features Must Respect Existing Contracts**

### **Expansion should not casually break template assumptions, data structures, or content survivability guarantees.**

#### **2.9.5 Operational Data Must Remain Understandable**

### **Logs, snapshots, artifacts, and execution records should be stored in a way that supports inspection and debugging.**

### 

### **2.10 Success Metrics**

### **Success metrics define how product fitness should be evaluated at a strategic level.**

#### **2.10.1 Structural Success**

### **The plugin successfully supports:**

### **section template creation and management**

### **page template creation and management**

### **valid custom compositions**

### **reusable build workflows**

#### **2.10.2 Planning Success**

### **The plugin successfully:**

### **captures brand and business inputs**

### **crawls public site structure**

### **generates structured AI-backed recommendations**

### **turns those recommendations into actionable build plans**

#### **2.10.3 Execution Success**

### **The plugin successfully:**

### **creates new pages from templates**

### **applies approved page updates**

### **updates hierarchy and menus where approved**

### **reports status clearly**

### **records meaningful history**

#### **2.10.4 Usability Success**

### **Users can:**

### **understand the system**

### **identify what fields belong to a page**

### **follow helper documentation**

### **review plan recommendations**

### **approve or deny changes with confidence**

#### **2.10.5 Durability Success**

### **Pages built by the plugin remain functionally usable after plugin deactivation or uninstall.**

#### **2.10.6 Operational Success**

### **The product owner receives install, heartbeat, and error visibility as intended under the private-distribution model.**

### 

### **2.11 Failure Conditions**

### **The product should be considered to have failed its intent if any of the following become normal or recurring outcomes.**

#### **2.11.1 Pages Become Dependent on the Plugin for Basic Survival**

### **If created pages meaningfully break when the plugin is removed, the product has failed a core portability principle.**

#### **2.11.2 The System Becomes Structurally Inconsistent**

### **If templates, fields, naming, or rendering contracts become unpredictable, the product loses its core advantage.**

#### **2.11.3 AI Outputs Become the Unreviewed Source of Destructive Action**

### **If planning and execution cease to be meaningfully separated, safety and trust break down.**

#### **2.11.4 The Editing Experience Becomes More Confusing, Not Less**

### **If field groups, template guidance, and plan UI overwhelm users rather than helping them, the product is not solving the right problem.**

#### **2.11.5 Logs and Recovery Become Insufficient**

### **If impactful changes cannot be understood, traced, or reasonably recovered from, operational reliability is inadequate.**

#### **2.11.6 The Plugin Becomes a Grab Bag of Unrelated Features**

### **If scope expands without preserving system coherence, the product loses category clarity.**

### 

### **2.12 Launch Readiness Criteria**

### **For any meaningful release, the following readiness criteria should be met for the included feature set.**

#### **2.12.1 Functional Readiness**

### **The released feature set performs its declared workflows end to end without known critical blockers.**

#### **2.12.2 Safety Readiness**

### **Permissions, validation, status visibility, and logging are in place for all released execution actions.**

#### **2.12.3 Documentation Readiness**

### **User-facing guidance and internal implementation guidance exist for the released feature set.**

#### **2.12.4 Data Readiness**

### **Schema, storage, and export considerations for the released feature set are understood and implemented.**

#### **2.12.5 Operational Readiness**

### **Reporting, diagnostics, and core lifecycle handling behave as specified for the released feature set.**

#### **2.12.6 Scope Integrity**

### **No release should imply support for workflows the system does not actually yet handle in a dependable way.**

### 

## **3. Stakeholders, Roles, and User Types**

### **3.1 Product Owner**

### **The Product Owner is the ultimate business and scope authority for the plugin.**

### **The Product Owner is responsible for:**

### **approving product direction**

### **approving scope and non-goals**

### **approving major architectural changes**

### **approving private-distribution operational policy**

### **approving roadmap priorities**

### **approving compliance posture**

### **defining success from a business and product standpoint**

### **The Product Owner may also act as the primary interpreter of ambiguous product intent where implementation questions arise.**

### 

### **3.2 Plugin Administrator**

### **The Plugin Administrator is the highest-authority operational user on an installed site.**

### **This role is typically responsible for:**

### **installing and activating the plugin**

### **configuring core settings**

### **managing dependencies**

### **setting AI provider credentials**

### **managing brand and business profile data**

### **starting onboarding**

### **reviewing build plans**

### **executing high-impact changes**

### **viewing logs and diagnostics**

### **managing export and uninstall decisions**

### **The Plugin Administrator must be treated as a privileged role whose actions can significantly alter site structure.**

### 

### **3.3 Site Builder / Operator**

### **The Site Builder or Operator is a user responsible for using the plugin to create, update, and manage website pages within the system’s structure.**

### **This role may:**

### **work with templates**

### **create page compositions**

### **review helper guidance**

### **initiate or continue structured build workflows**

### **populate fields**

### **build new pages**

### **participate in page update workflows**

### **review recommended navigation changes**

### **This role may or may not have full access to global settings, AI providers, or operational diagnostics, depending on capability assignments.**

### 

### **3.4 Content Editor**

### **The Content Editor is primarily concerned with entering, refining, and maintaining page content rather than controlling site architecture or plugin internals.**

### **This role typically needs:**

### **access to page-specific field groups**

### **access to helper paragraphs and one-pagers**

### **clarity about what belongs in each field**

### **the ability to revise content without being overwhelmed by unrelated system controls**

### **The plugin should support a content-editor-friendly experience that does not expose unnecessary technical complexity.**

### 

### **3.5 Marketing User**

### **The Marketing User is concerned with site messaging, page purpose, hierarchy quality, SEO direction, and content strategy.**

### **This role may care about:**

### **page purpose and flow**

### **audience alignment**

### **offer structure**

### **navigation strategy**

### **SEO recommendations**

### **internal linking opportunities**

### **brand voice consistency**

### **page template suitability**

### **The plugin should support marketing review without forcing marketing users to understand the entire technical architecture.**

### 

### **3.6 Technical Maintainer**

### **The Technical Maintainer is responsible for plugin upkeep, compatibility, troubleshooting, and implementation integrity.**

### **This role may include:**

### **a developer**

### **a technical contractor**

### **a support engineer**

### **a site maintenance partner**

### **Responsibilities may include:**

### **investigating failed jobs**

### **validating dependency issues**

### **reviewing logs**

### **resolving import/export issues**

### **checking compatibility with themes or plugins**

### **maintaining reporting integrity**

### **The plugin should expose enough operational detail for this role to act effectively.**

### 

### **3.7 Support / Diagnostics Recipient**

### **The Support or Diagnostics Recipient is the party intended to receive install notifications, heartbeat messages, and error reporting under the private-distribution model.**

### **This role is responsible for:**

### **monitoring plugin health signals**

### **identifying troubled installs**

### **reviewing operational issues**

### **understanding deployment spread**

### **using error visibility to inform support decisions**

### **This role may be internal to the product owner’s business and may not exist within the customer site itself.**

### 

### **3.8 AI Provider Account Owner**

### **The AI Provider Account Owner is the party whose external AI API credentials are configured for use by the plugin.**

### **This role is responsible for:**

### **providing provider credentials**

### **understanding provider cost exposure**

### **selecting provider or model preferences where applicable**

### **authorizing the use of external AI services for planning functions**

### **This role may be the same as the Plugin Administrator or Product Owner, but should be conceptually distinct because provider usage has cost and privacy implications.**

### 

### **3.9 Multi-User Collaboration Considerations**

### **The plugin is expected to operate in environments where multiple users may interact with the same site and the same plan data.**

### **Therefore, the system should account for:**

### **different users having different capability levels**

### **one user initiating a workflow that another user continues**

### **the need for persistent plan status and history**

### **visibility into who approved or denied actions**

### **visibility into who executed specific changes**

### **shared access to documentation and helper guidance**

### **prevention of accidental overlap on high-impact operations where practical**

### **The plugin should treat multi-user behavior as normal rather than exceptional.**

### 

### **3.10 User Permission Boundaries**

### **The plugin must recognize that not every user should be allowed to:**

### **view AI credentials**

### **initiate onboarding**

### **run crawls**

### **trigger AI planning**

### **approve build plans**

### **execute page replacements**

### **apply navigation changes**

### **view raw AI artifacts**

### **download exports**

### **view diagnostics and logs**

### **manage reporting settings**

### **Permission boundaries must therefore be explicit, capability-based, and action-specific.**

### **The system should distinguish between:**

### **users who can view information**

### **users who can edit planning data**

### **users who can approve changes**

### **users who can execute changes**

### **users who can manage operational settings**

### **users who can access sensitive artifacts and diagnostics**

### **The plugin must not assume that broad site-editing access equals authority to perform all plugin operations.**

### 

## **4. System Scope and Functional Domains**

### **4.1 Template Registry Domain**

### **This domain is responsible for defining, storing, organizing, validating, and exposing the system’s reusable structural units.**

### **It includes:**

### **section template records**

### **page template records**

### **custom page-template compositions**

### **template metadata**

### **compatibility declarations**

### **version references**

### **helper documentation associations**

### **This domain is foundational because the rest of the system depends on stable reusable building blocks.**

### 

### **4.2 Rendering Domain**

### **This domain is responsible for turning approved template structures into actual WordPress page content.**

### **It includes:**

### **native block assembly**

### **GenerateBlocks-friendly composition**

### **section instance output rules**

### **page instantiation rules**

### **dynamic rendering only where justified**

### **output portability requirements**

### **This domain must enforce the plugin’s commitment to durable content rather than proprietary lock-in.**

### 

### **4.3 ACF Field Management Domain**

### **This domain is responsible for the section-level structured field system.**

### **It includes:**

### **field-group blueprints**

### **field-key naming conventions**

### **field visibility logic**

### **page assignment logic**

### **field validation rules**

### **field dependency declarations**

### **cleanup or de-registration logic where applicable**

### **This domain exists to keep content input structured, relevant, and scalable.**

### 

### **4.4 LPagery Token Mapping Domain**

### **This domain is responsible for aligning template fields with LPagery-compatible token workflows where relevant.**

### **It includes:**

### **token mapping rules**

### **supported field-type mappings**

### **token naming logic**

### **partial compatibility rules**

### **validation behavior**

### **bulk-generation assumptions**

### **This domain is contextual rather than universal, but it is still a planned system requirement.**

### 

### **4.5 Brand and Business Profile Domain**

### **This domain is responsible for collecting, storing, updating, and exposing the user’s business and brand context.**

### **It includes:**

### **business details**

### **services and offers**

### **locations and markets**

### **audiences and personas**

### **brand voice and tone**

### **competitors**

### **logos and assets**

### **editable long-term profile data**

### **snapshot data for AI runs**

### **This domain provides the context needed for planning and consistency.**

### 

### **4.6 Site Crawl and Analysis Domain**

### **This domain is responsible for public-site discovery and structural interpretation.**

### **It includes:**

### **public URL discovery**

### **meaningful-page filtering**

### **indexability logic**

### **page inventory**

### **structural classification**

### **hierarchy analysis**

### **navigation participation analysis**

### **crawl snapshot storage**

### **change comparison between crawl runs**

### **This domain gives the system awareness of the existing site.**

### 

### **4.7 AI Provider Integration Domain**

### **This domain is responsible for controlled interaction with external AI services.**

### **It includes:**

### **provider driver abstraction**

### **authentication handling**

### **model capability awareness**

### **request assembly**

### **retry and timeout behavior**

### **response normalization**

### **provider-specific differences hidden behind a common interface**

### **This domain allows the plugin to use AI without hard-coding a single provider path.**

### 

### **4.8 AI Artifact and Prompt Domain**

### **This domain is responsible for the preparation, storage, retrieval, and governance of AI-related inputs and outputs.**

### **It includes:**

### **prompt packs**

### **input snapshots**

### **file manifests**

### **raw responses**

### **normalized structured outputs**

### **validation results**

### **downloadable artifacts**

### **run metadata**

### **cost and usage metadata where available**

### **This domain supports auditability and trust.**

### 

### **4.9 Build Plan Domain**

### **This domain is responsible for converting validated planning outputs into a structured action interface for the user.**

### **It includes:**

### **build-plan objects**

### **step-based organization**

### **page update recommendations**

### **new page recommendations**

### **navigation recommendations**

### **design-token recommendations**

### **state tracking**

### **reopen and resume behavior**

### **remaining-change filtering**

### **This domain is where planning becomes operationally usable.**

### 

### **4.10 Execution Engine Domain**

### **This domain is responsible for performing approved actions.**

### **It includes:**

### **page creation**

### **page replacement**

### **hierarchy updates**

### **menu creation and reassignment**

### **token application**

### **state transitions**

### **execution logging**

### **retry behavior**

### **partial-failure handling**

### **This domain must remain tightly permissioned and observable.**

### 

### **4.11 Diff and Rollback Domain**

### **This domain is responsible for before-and-after comparison and recovery-aware behavior.**

### **It includes:**

### **snapshots**

### **diff objects**

### **page-level change comparisons**

### **structure comparisons**

### **menu comparisons**

### **token comparisons**

### **rollback eligibility rules**

### **rollback execution support**

### **This domain exists to make aggressive workflows safer and more reviewable.**

### 

### **4.12 Navigation and Menu Domain**

### **This domain is responsible for understanding and applying structural navigation recommendations.**

### **It includes:**

### **current menu inspection**

### **proposed menu structures**

### **menu item mapping**

### **location assignment**

### **rename logic**

### **comparison logic**

### **approval and execution rules**

### **This domain ensures that site structure changes are reflected in navigational systems.**

### 

### **4.13 Styling and Design Token Domain**

### **This domain is responsible for the system of visual values that can vary without changing internal markup contracts.**

### **It includes:**

### **colors**

### **typography assignments**

### **spacing scales**

### **radius values**

### **shadow values**

### **surface logic**

### **component variants**

### **preview logic**

### **accept and reject flow**

### **This domain is where AI and user overrides can influence visual output safely.**

### 

### **4.14 Import / Export Domain**

### **This domain is responsible for packaging, restoring, and preserving plugin-specific data.**

### **It includes:**

### **export package creation**

### **manifest generation**

### **ZIP structure**

### **import validation**

### **restore sequencing**

### **conflict handling**

### **uninstall export options**

### **selective inclusion of artifacts and logs**

### **This domain is critical for plugin survivability and reinstall continuity.**

### 

### **4.15 Logging and Diagnostics Domain**

### **This domain is responsible for capturing, structuring, surfacing, and exporting operational information.**

### **It includes:**

### **execution logs**

### **job logs**

### **AI run logs**

### **error logs**

### **reporting logs**

### **diagnostics visibility**

### **severity classification**

### **filtering and export**

### **This domain supports supportability and operational trust.**

### 

### **4.16 Telemetry and Reporting Domain**

### **This domain is responsible for the private-distribution reporting behaviors approved by product policy.**

### **It includes:**

### **install notification**

### **recurring heartbeat**

### **structured error reporting**

### **reporting payload rules**

### **reporting destination handling**

### **reporting failure logging**

### **transparency surfaces in the admin UI**

### **This domain is part of the product’s private operational support model.**

### 

### **4.17 Security and Permissions Domain**

### **This domain is responsible for access control, request safety, data protection, and secure behavior across the plugin.**

### **It includes:**

### **capabilities**

### **permission checks**

### **nonce validation**

### **route permissions**

### **input sanitization**

### **output escaping**

### **secret handling**

### **redaction rules**

### **upload validation**

### **import safety**

### **This domain must influence every other domain rather than exist in isolation.**

### 

### **4.18 Deactivation / Uninstall / Restore Domain**

### **This domain is responsible for plugin lifecycle behavior after installation.**

### **It includes:**

### **activation behavior**

### **deactivation behavior**

### **uninstall behavior**

### **export-before-removal flow**

### **cleanup choices**

### **job unscheduling**

### **restore behavior**

### **built-page survival guarantees**

### **This domain exists to ensure that plugin removal is operationally responsible and does not destroy generated content.**

### 

## **5. Product Architecture Overview**

### **5.1 Architectural Philosophy**

### **The architecture of AIO Page Builder must be driven by structure, separation of concerns, durability, and controlled extensibility.**

### **The system should not be built as a monolithic bundle of ad hoc features. It should instead be built as a set of cooperating domains with clear responsibilities and clear boundaries.**

### **The architecture must reflect the product’s defining promises:**

### **reusable structure**

### **native content durability**

### **AI-assisted planning without unchecked autonomy**

### **safe execution**

### **explainability**

### **operational visibility**

### **Whenever implementation choices are available, the architectural philosophy should favor:**

### **explicit over implicit**

### **stable contracts over dynamic reinvention**

### **modular responsibility over feature sprawl**

### **durable outputs over clever shortcuts**

### **reviewable operations over hidden automation**

### 

### **5.2 Core Architectural Layers**

### **The product should be understood as a layered system.**

#### **5.2.1 Definition Layer**

### **This layer contains the reusable system definitions that shape everything else.**

### **It includes:**

### **section template definitions**

### **page template definitions**

### **composition rules**

### **field blueprints**

### **helper documentation definitions**

### **prompt pack definitions**

### **schema definitions**

### **compatibility declarations**

### **This layer answers the question: What structures exist?**

#### **5.2.2 Context Layer**

### **This layer contains the business and website context used for planning.**

### **It includes:**

### **brand profile**

### **business profile**

### **crawl snapshots**

### **existing site inventory**

### **template registry snapshots**

### **provider settings**

### **historical plan references**

### **This layer answers the question: What situation are we planning for?**

#### **5.2.3 Planning Layer**

### **This layer converts definitions and context into structured recommendations.**

### **It includes:**

### **prompt assembly**

### **AI run orchestration**

### **structured output validation**

### **normalized recommendation generation**

### **build-plan generation**

### **This layer answers the question: What should happen?**

#### **5.2.4 Execution Layer**

### **This layer performs approved actions.**

### **It includes:**

### **page creation**

### **page replacement**

### **hierarchy changes**

### **menu updates**

### **token application**

### **status updates**

### **result logging**

### **This layer answers the question: What was actually done?**

#### **5.2.5 Observability Layer**

### **This layer records and surfaces operational awareness.**

### **It includes:**

### **logs**

### **artifacts**

### **snapshots**

### **diffs**

### **rollback references**

### **telemetry**

### **diagnostics**

### **export data**

### **This layer answers the question: What happened, how do we inspect it, and how do we recover if needed?**

### 

### **5.3 Separation of Concerns**

### **Separation of concerns is a core architectural requirement, not a stylistic preference.**

### **The plugin must clearly distinguish between:**

### **template definitions and page instances**

### **stored content and generated guidance**

### **AI planning and local execution**

### **design-token values and CSS contract naming**

### **operational logs and user-facing content**

### **plugin settings and page-level content data**

### **system-managed structures and user-authored content**

### **This separation is necessary to:**

### **preserve predictability**

### **reduce implementation confusion**

### **support maintainability**

### **prevent unsafe coupling**

### **preserve data portability**

### **No single subsystem should silently take ownership of concerns that belong to another subsystem.**

### 

### **5.4 Planner vs Executor Boundary**

### **The boundary between planner and executor is one of the most important architectural rules in the system.**

#### **5.4.1 Planner Responsibilities**

### **The planner is responsible for:**

### **collecting context**

### **packaging context for AI runs**

### **receiving AI output**

### **validating output**

### **normalizing recommendations**

### **generating structured build-plan data**

### **explaining proposed site structure and changes**

### **The planner proposes.\
It does not perform high-impact site mutation directly.**

#### **5.4.2 Executor Responsibilities**

### **The executor is responsible for:**

### **performing approved actions**

### **creating new pages**

### **replacing or rebuilding existing pages**

### **assigning hierarchy**

### **applying approved menu changes**

### **applying token changes**

### **recording outcomes**

### **exposing status and failures**

### **The executor acts only on approved instructions and validated system inputs.**

#### **5.4.3 Why This Boundary Exists**

### **This boundary exists to:**

### **reduce risk**

### **preserve user approval**

### **improve traceability**

### **prevent AI outputs from being mistaken for execution success**

### **support better rollback logic**

### **keep the system conceptually understandable**

### **This boundary must remain intact across future versions.**

### 

### **5.5 Content Layer vs Orchestration Layer**

### **The product must distinguish clearly between content that belongs to the WordPress page and orchestration data that belongs to the plugin.**

#### **5.5.1 Content Layer**

### **The content layer consists of:**

### **post content**

### **native blocks**

### **section content rendered into page structure**

### **user-editable page output**

### **front-end meaningful content**

### **This is the layer that should survive plugin removal.**

#### **5.5.2 Orchestration Layer**

### **The orchestration layer consists of:**

### **template assignment metadata**

### **section instance mapping metadata**

### **build-plan status data**

### **AI provenance references**

### **execution state**

### **snapshot references**

### **rollback references**

### **internal workflow state**

### **This is plugin-owned operational data.**

#### **5.5.3 Architectural Rule**

### **The orchestration layer may guide and explain the content layer, but it must not make the content layer functionally meaningless without the plugin.**

### 

### **5.6 Static Registry vs Runtime Data**

### **The system must distinguish between controlled definitions and live operational records.**

#### **5.6.1 Static Registry Data**

### **Static registry data includes:**

### **section templates**

### **page templates**

### **composition rules**

### **field blueprints**

### **helper templates**

### **prompt packs**

### **schema definitions**

### **This data changes intentionally and relatively infrequently.**

#### **5.6.2 Runtime Data**

### **Runtime data includes:**

### **brand profile entries**

### **crawl snapshots**

### **AI runs**

### **build plans**

### **page instances**

### **logs**

### **snapshots**

### **diffs**

### **job queue records**

### **reporting records**

### **This data changes often and reflects real-world usage.**

#### **5.6.3 Architectural Importance**

### **Mixing registry definitions with runtime state leads to confusion, poor versioning, and brittle logic. These data classes must be intentionally separated.**

### 

### **5.7 Human-Managed Data vs System-Managed Data**

### **The plugin must clearly distinguish what humans intentionally edit from what the system maintains automatically.**

#### **5.7.1 Human-Managed Data**

### **Examples include:**

### **brand profile fields**

### **business profile fields**

### **section helper content where editable**

### **chosen template compositions**

### **manual plan approvals or denials**

### **page content edits**

### **token overrides**

#### **5.7.2 System-Managed Data**

### **Examples include:**

### **generated IDs and handles**

### **build-plan state transitions**

### **snapshots**

### **raw AI responses**

### **artifact manifests**

### **job state**

### **execution logs**

### **report delivery records**

#### **5.7.3 Architectural Rule**

### **Human-managed data should be visible and editable where appropriate. System-managed data should be inspectable where useful, but not casually user-editable unless explicitly intended.**

### 

### **5.8 Local Site Data vs External Provider Data**

### **The architecture must recognize a boundary between local system truth and external service responses.**

#### **5.8.1 Local Site Data**

### **Local site data includes:**

### **WordPress content**

### **plugin settings**

### **profile data**

### **template registries**

### **field assignments**

### **plan state**

### **logs**

### **snapshots**

#### **5.8.2 External Provider Data**

### **External provider data includes:**

### **provider capabilities**

### **model responses**

### **usage metadata**

### **remote API status**

### **remote errors**

#### **5.8.3 Architectural Rule**

### **External provider responses may inform the local system, but the local system remains the authoritative operational environment. External services are inputs, not the source of final local truth.**

### 

### **5.9 Safe Execution Model**

### **The system’s execution model must be intentionally safe, even where aggressive workflows are supported.**

### **The safe execution model should include:**

### **validated inputs**

### **permission enforcement**

### **explicit approvals**

### **predictable action sequencing**

### **snapshotting where appropriate**

### **result logging**

### **failure reporting**

### **retry rules where valid**

### **partial-failure awareness**

### **rollback awareness**

### **The plugin may support fast and high-impact workflows, but it must not support careless ones.**

### 

### **5.10 Plugin Lifecycle Architecture**

### **Lifecycle architecture defines how the system behaves across installation, operation, deactivation, uninstall, and restore.**

### **The architecture must account for:**

### **first-time setup**

### **settings persistence**

### **dependency validation**

### **recurring operational tasks**

### **deactivation cleanup of scheduled tasks**

### **uninstall decision points**

### **export before removal**

### **restoration after reinstall**

### **continued survival of built pages**

### **Lifecycle handling is not a secondary implementation detail. It is part of the product contract.**

### 

### **5.11 Failure Isolation Strategy**

### **Because the plugin combines templates, AI services, execution logic, and reporting logic, it must prevent one subsystem failure from automatically destroying others.**

#### **5.11.1 AI Failure Must Not Break Existing Content**

### **If AI planning fails, the existing site and built pages should remain intact.**

#### **5.11.2 Reporting Failure Must Not Break Core Site Functions**

### **If install, heartbeat, or error reporting fails, local plugin operation should continue safely unless the failed process itself is the action being evaluated.**

#### **5.11.3 Optional Integration Failure Must Degrade Gracefully**

### **If optional plugin integrations are unavailable, related features may degrade or disable, but core site-building logic should remain usable where possible.**

#### **5.11.4 Queue Failure Must Not Hide Itself**

### **If scheduled or queued work fails, the system should record and surface that failure rather than silently stalling.**

#### **5.11.5 Export or Import Failure Must Not Corrupt Existing Content**

### **Packaging and restore workflows must fail safely.**

### **This failure isolation strategy is necessary for a plugin intended to operate across many varied site environments.**

### 

### **5.12 Extensibility Strategy**

### **The architecture must be designed to allow future growth without undermining stability.**

### **Extensibility should occur through:**

### **modular service boundaries**

### **versioned registries**

### **versioned schemas**

### **provider abstraction**

### **controlled integration points**

### **documented compatibility rules**

### **stable contracts for classes, IDs, field keys, and object handles**

### **Future extensibility may include:**

### **more templates**

### **more providers**

### **more plan workflows**

### **more export options**

### **more diagnostics tooling**

### **more compatibility layers**

### **However, extensibility must not become a reason to weaken current clarity. The system should expand through controlled additions, not through unbounded flexibility that erodes the architecture.**

### **The architectural objective is not merely to “allow future features.”\
It is to allow future features without breaking the product’s identity, safety model, or structural integrity.**

## **6. Plugin Packaging and Distribution Model**

### **6.1 Private Distribution Method**

### **AIO Page Builder shall be distributed as a privately delivered WordPress plugin and shall not depend on WordPress.org repository listing, repository update delivery, or repository policy enforcement as a condition of use.**

### **The private distribution method shall support the following delivery modes:**

### **direct plugin ZIP upload through the standard WordPress plugin installation interface**

### **manual deployment to the WordPress plugins directory by a technical maintainer**

### **private update delivery methods approved by product policy**

### **environment-specific deployment by support or implementation partners**

### **The private distribution method is intentionally selected to support the product’s operational model, including mandatory installation notification, mandatory heartbeat reporting, and mandatory diagnostics reporting.**

### **Private distribution shall not be used as a justification for careless packaging, non-standard plugin structure, or bypassing standard WordPress lifecycle expectations. The plugin should still install and behave like a conventional WordPress plugin from the perspective of a site administrator.**

### 

### **6.2 Installation Package Format**

### **The installation package shall be distributed as a standard WordPress-compatible ZIP archive containing a single plugin root directory and all files required for installation and activation.**

### **The installation package shall:**

### **be uploadable through the standard WordPress plugin installer**

### **contain a valid main plugin file with required header metadata**

### **include all required PHP, CSS, JavaScript, asset, schema, and template registry files needed for the packaged release**

### **exclude development-only files unless intentionally included for support or debugging**

### **include versioned metadata so the installed system can identify its own release state**

### **support deterministic extraction into the WordPress plugins directory**

### **The ZIP package should be production-clean and should not include unnecessary local development artifacts, machine-specific files, or unused dependencies.**

### 

### **6.3 Plugin File Structure Standards**

### **The plugin shall use a structured, predictable, maintainable file organization that separates responsibilities by domain rather than collapsing logic into a small number of large files.**

### **The file structure should support clear grouping for:**

### **bootstrap and loader files**

### **activation, deactivation, and uninstall logic**

### **service container or module registration logic**

### **admin screen logic**

### **REST and AJAX handlers**

### **CPT and taxonomy registration**

### **registry definitions**

### **ACF field definitions**

### **build-plan services**

### **AI provider services**

### **prompt-pack services**

### **crawl services**

### **execution services**

### **logging and reporting services**

### **migration and table creation logic**

### **asset bundles**

### **schema definitions**

### **import and export services**

### **helper documentation templates**

### **compatibility layers**

### **The file structure should be stable enough that future contributors can locate major responsibilities quickly without needing to reverse-engineer the project.**

### 

### **6.4 Activation Requirements**

### **The plugin shall define a controlled activation process that validates whether the minimum environment and dependency requirements are met before the plugin attempts to operate normally.**

### **Activation requirements shall include, at minimum:**

### **a supported WordPress version**

### **a supported PHP version**

### **required plugin dependencies where those dependencies are mandatory for declared functionality**

### **availability of required database capabilities**

### **ability to create or upgrade plugin-owned options and data structures**

### **ability to register required hooks and scheduled tasks**

### **ability to initialize plugin settings and baseline configuration**

### **Activation shall also trigger the product’s approved private-distribution operational behavior, including installation reporting, subject to the product’s internal rules for timing, retries, and failure logging.**

### **Activation must fail safely if a hard requirement is not met. The plugin must not enter a half-configured state that creates undefined behavior.**

### 

### **6.5 Update Delivery Strategy**

### **The plugin shall support private update delivery outside of WordPress.org and shall be designed so that updates can be delivered without compromising structural integrity, data continuity, or built-page survivability.**

### **The update strategy shall account for:**

### **version detection**

### **migration execution**

### **registry evolution**

### **schema evolution**

### **custom table upgrades**

### **asset versioning**

### **backward compatibility handling**

### **recovery behavior if an update does not fully complete**

### **The update mechanism may later be implemented through one or more private channels, but the product architecture must not assume WordPress.org-hosted update infrastructure.**

### **All updates shall preserve core content survivability and should avoid creating conditions where previously built pages become invalid solely because the plugin version changed.**

### 

### **6.6 Backward Compatibility Strategy**

### **Backward compatibility shall be treated as a deliberate product responsibility rather than an afterthought.**

### **The plugin shall maintain backward compatibility in the following areas wherever reasonably possible:**

### **built page output**

### **registry handle stability**

### **object type stability**

### **field key stability**

### **post meta key stability**

### **export format interpretability**

### **migration-aware table changes**

### **plan and artifact readability across nearby versions**

### **Where backward compatibility cannot be preserved, the system shall:**

### **document the break**

### **version the affected contract**

### **provide migration logic if feasible**

### **avoid silent destructive change**

### **preserve user visibility into what changed**

### **The plugin may evolve aggressively, but it must not evolve carelessly.**

### 

## **6.7 Supported WordPress Versions**

### **6.7 Supported WordPress Versions**

### **The plugin shall support WordPress 6.6 and newer.**

### **The plugin shall be considered:**

### **minimum supported on WordPress 6.6\**

### **fully validated target on the current major WordPress release at the time each plugin release is cut\**

### **unsupported below WordPress 6.6\**

### **Rules:**

### **Installation or activation on WordPress versions below 6.6 shall be blocked with an admin-visible requirement notice.\**

### **The plugin shall not claim support for versions that have not passed the compatibility test matrix defined in Section 56.\**

### **Every production release shall record the tested WordPress version range in release notes and internal QA artifacts.\**

### **If a future plugin release raises the minimum WordPress version, that change shall be treated as a breaking compatibility decision and documented under Section 58.\**

### 

## **6.8 Supported PHP Versions**

### **6.8 Supported PHP Versions**

### **The plugin shall support PHP 8.1 and newer.**

### **The plugin shall be considered:**

### **minimum supported on PHP 8.1\**

### **preferred and routinely validated on PHP 8.1, 8.2, and 8.3\**

### **unsupported below PHP 8.1\**

### **Rules:**

### **Installation or activation below PHP 8.1 shall be blocked.\**

### **The codebase may use PHP 8.1 language features and typing patterns, but shall avoid introducing version-specific behavior that narrows support further unless formally approved.\**

### **Any future increase to the PHP minimum version shall be treated as a documented compatibility change.\**

### **Runtime environment checks shall confirm PHP compatibility before activation completes.\**

### 

## **6.9 Supported Theme Assumptions**

### **6.9 Supported Theme Assumptions**

### **The plugin shall be designed for standards-compliant WordPress themes that support the block editor and normal page rendering behavior, with GeneratePress designated as the preferred tested theme environment.**

### **The supported theme posture shall be:**

### **preferred environment: GeneratePress\**

### **preferred block companion: GenerateBlocks\**

### **general compatibility target: standards-compliant block-capable themes\**

### **unsupported assumption: themes that fundamentally break block rendering, page content output, or standard menu/location behavior\**

### **Hard rules:**

### **The plugin shall not require a custom proprietary theme for built-page survival.\**

### **The plugin shall not assume theme-specific PHP templates for core content generation.\**

### **Theme-specific enhancements may be added later, but the baseline plugin contract must remain theme-independent.\**

### **If a theme lacks required menu locations, layout features, or block support, the plugin shall warn, degrade gracefully, or block only the affected workflow rather than failing silently.\**

### 

## **6.10 Supported Plugin Dependencies**

### **6.10 Supported Plugin Dependencies**

### **The plugin shall define dependency support in three classes:**

### **Class A — Required for core product operation**

### **Advanced Custom Fields Pro\**

### **GenerateBlocks\**

### **Class B — Optional but supported for planned workflows**

### **LPagery\**

### **supported SEO plugins\**

### **common caching plugins\**

### **common security plugins\**

### **Class C — Coexistence only**

### **media helper plugins\**

### **role-management plugins\**

### **site-monitoring plugins\**

### **backup/export plugins\**

### **Dependency rules:**

### **No undeclared plugin may become required for core product operation.\**

### **Core product promises must remain valid using only the Class A stack.\**

### **If a Class B integration is missing, only the related workflow may degrade; the core template and planning system must remain usable.\**

### **Any future Class A dependency addition requires formal change approval.\**

### 

## **6.11 Required Plugins**

### **6.11 Required Plugins**

### **The following plugins are required for the intended production feature set:**

#### **6.11.1 Advanced Custom Fields Pro**

### **Required minimum version: 6.2\
Purpose: section-level field architecture, field-group generation, options-driven settings support, validation, and structured edit UI.**

#### **6.11.2 GenerateBlocks**

### **Required minimum version: 2.0\
Purpose: preferred block-layer composition and predictable container/grid output for the plugin’s native block build model.**

### **Rules:**

### **If ACF Pro is missing, activation shall be blocked.\**

### **If GenerateBlocks is missing, activation shall be blocked for the full production feature set.\**

### **The plugin shall present a dependency notice that identifies the missing plugin, minimum version, and reason it is required.\**

### **Dependency version checks shall occur on activation and again within admin diagnostics.\**

### 

## **6.12 Optional Plugins**

### **6.12 Optional Plugins**

### **The following plugins are optional and shall not be required for core operation unless the user intends to use a dependent workflow:**

#### **6.12.1 LPagery**

### **Used for token-driven or bulk generated page workflows.**

#### **6.12.2 SEO Plugins**

### **Supported integration target classes may include:**

### **major title/meta plugins\**

### **schema-oriented SEO plugins\**

#### **6.12.3 Featured Image / Media Helper Plugins**

### **Used only where the site already relies on an existing featured-image/media workflow.**

#### **6.12.4 Role / Capability Plugins**

### **Supported as coexistence tools for organizations that manage permissions externally.**

### **Rules:**

### **Missing optional plugins shall not block activation.\**

### **Optional integrations shall be hidden or disabled when the relevant plugin is not present.\**

### **The plugin shall not fake compatibility with an optional plugin unless that specific integration has been tested and documented.\**

### 

## **6.13 Environment Validation Rules**

### **6.13 Environment Validation Rules**

### **The plugin shall perform environment validation at three points:**

### **activation\**

### **admin diagnostics\**

### **before high-impact workflow execution\**

### **Validation categories shall include:**

### **WordPress version\**

### **PHP version\**

### **required dependency presence and minimum version\**

### **database readiness\**

### **REST/admin capability assumptions\**

### **scheduler readiness\**

### **mail/reporting transport availability\**

### **uploads directory availability for export workflows\**

### **provider configuration readiness for AI workflows\**

### **Validation outcomes shall be classified as:**

### **blocking failure\**

### **non-blocking warning\**

### **informational notice\**

### **Blocking failures must stop the affected workflow.\
Warnings must be visible and logged.\
Informational notices may be displayed without blocking action.**

### **The plugin shall maintain a structured diagnostics report showing current environment readiness.**

### 

## **7. Core Technical Stack and Dependencies**

### **7.1 WordPress Core Dependencies**

### **The plugin depends fundamentally on WordPress core as the runtime platform, storage framework, admin framework, plugin lifecycle environment, and content-management foundation.**

### **Core WordPress dependencies include:**

### **plugin bootstrap loading**

### **activation, deactivation, and uninstall hooks**

### **admin menu and screen APIs**

### **REST infrastructure**

### **post types and taxonomies**

### **post meta and options storage**

### **scheduled task infrastructure**

### **user and capability systems**

### **uploads and filesystem interfaces where appropriate**

### **native block storage and rendering behavior**

### **standard email and HTTP transport layers where used**

### **The plugin must align with WordPress core architectural expectations rather than attempting to work around them without necessity.**

### 

### **7.2 GeneratePress / GenerateBlocks Assumptions**

### **The plugin is intended to operate well in environments that use GeneratePress and block-based composition patterns compatible with GenerateBlocks.**

### **The architectural assumption is not that GeneratePress replaces plugin logic, but that:**

### **the theme environment remains lightweight and standards-oriented**

### **block-based content is a primary rendering target**

### **GenerateBlocks-style composition is a preferred implementation path for reusable page structures where appropriate**

### **The plugin shall not depend on legacy builder concepts from unrelated systems. Instead, it shall treat GeneratePress and GenerateBlocks as compatible ecosystem assumptions for:**

### **page layout cleanliness**

### **block-based output strategy**

### **long-term portability**

### **These assumptions should enhance the product but not redefine it.**

### 

### **7.3 ACF Dependency Model**

### **ACF is a foundational dependency for the plugin’s structured field model.**

### **The ACF dependency model shall include:**

### **one section template corresponding to a field-group blueprint**

### **programmatic field registration**

### **deterministic field-key naming**

### **field-type standardization**

### **validation rules**

### **page-level visibility assignment based on template usage**

### **support for later field-group refinement without breaking existing content relationships where possible**

### **The plugin’s architecture assumes ACF as the basis for structured input and field governance. The product is not intended to treat ACF as a casual optional add-on for its primary workflows.**

### 

### **7.4 LPagery Dependency Model**

### **LPagery is a contextual dependency, not a universal one.**

### **The LPagery dependency model shall include:**

### **token compatibility where applicable**

### **token mapping rules for supported fields**

### **explicit identification of supported and unsupported field patterns**

### **conditional workflow behavior when LPagery is absent**

### **preservation of core plugin functionality even when LPagery-dependent workflows are not active**

### **The plugin shall support LPagery-oriented workflows where relevant, but it shall not require LPagery for all users or all use cases.**

### 

### **7.5 Native Blocks Strategy**

### **The plugin shall treat native WordPress blocks as the primary content output model.**

### **The native blocks strategy exists to support:**

### **portability**

### **survivability of built pages**

### **compatibility with modern WordPress editing patterns**

### **lower long-term fragility**

### **human-readability of content structures relative to opaque proprietary storage patterns**

### **The plugin may use structured block composition, reusable patterns, and GenerateBlocks-compatible constructs, but its central architectural commitment is that generated page output should exist in forms that remain meaningful in WordPress.**

### 

### **7.6 Render Callback Usage Strategy**

### **Render callbacks shall be allowed only where they are clearly justified by a specific functional need.**

### **Appropriate reasons to use render callbacks may include:**

### **dynamic assembly that cannot reasonably be stored entirely as static post content**

### **controlled output of system-driven operational data**

### **context-aware display elements that are explicitly designed to remain plugin-dependent**

### **specialized rendering requirements that improve maintainability without harming core content survivability**

### **Render callbacks shall not be used casually for content that should instead exist as durable page output. The plugin should favor saving meaningful structure into content rather than making the front end depend unnecessarily on runtime plugin logic.**

### 

### **7.7 CSS and Asset Delivery Strategy**

### **The plugin shall define a stable CSS and asset delivery strategy aligned with its fixed markup contract.**

### **This strategy shall include:**

### **stable class naming rules**

### **stable ID naming rules**

### **stable data attribute rules**

### **scoped asset loading where practical**

### **versioned CSS and JavaScript assets**

### **front-end assets only where needed**

### **admin assets only on relevant screens**

### **support for design-token-driven value changes without selector renaming**

### **The CSS strategy must distinguish clearly between:**

### **fixed structural selectors**

### **variable visual values**

### **admin-facing assets**

### **front-end assets**

### **core assets versus optional assets**

### **The asset system should support maintainability, controlled caching, and minimal accidental coupling.**

### 

### **7.8 JavaScript Admin App Strategy**

### **The plugin shall support a JavaScript-enhanced admin experience where necessary for complex workflows, but shall do so in a way that remains grounded in WordPress admin conventions.**

### **The JavaScript admin strategy may include:**

### **interactive onboarding flows**

### **step-based build-plan interfaces**

### **comparison views**

### **progress indicators**

### **structured modals and confirmation dialogs**

### **plan filtering and bulk action controls**

### **queue status updates**

### **preview interactions**

### **The admin JavaScript layer must not become an uncontrolled parallel application detached from WordPress permission and lifecycle expectations. Server-side validation and capability enforcement remain authoritative even where the UI is highly interactive.**

### 

### **7.9 Queue / Background Task Strategy**

### **The plugin shall include a task execution strategy suitable for crawl operations, AI runs, bulk builds, reporting, exports, and other potentially long-running work.**

### **The queue or background task strategy shall support:**

### **scheduled job dispatch**

### **retry logic**

### **progress state**

### **failure state**

### **resumability where appropriate**

### **visibility into pending and completed work**

### **isolation between jobs**

### **protection against accidental duplicate execution**

### **The plugin may use WordPress-native scheduling behavior as part of this system, but the product architecture must acknowledge that some tasks are too heavy or too operationally important to treat as instantaneous admin actions.**

### 

### **7.10 External API Dependency Strategy**

### **The plugin depends on external APIs for AI planning and may also depend on external transport for reporting.**

### **The external API strategy shall include:**

### **provider abstraction rather than hard-coded single-provider assumptions**

### **server-side request handling**

### **secret protection**

### **retry and timeout logic**

### **request logging with redaction**

### **response normalization**

### **provider-specific error mapping**

### **graceful failure handling**

### **External API usage must never become a hidden point of silent corruption. When external services fail, the plugin must preserve local safety and expose the failure clearly.**

### 

## **8. Data Model Overview**

### **8.1 Data Architecture Principles**

### **The data model shall be designed around clarity, separation of concerns, traceability, portability, and controlled growth.**

### **The core principles are:**

### **different kinds of data must have distinct ownership and storage strategies**

### **durable site content must be distinguishable from plugin operational data**

### **reusable definitions must be distinguishable from runtime records**

### **logs and artifacts must be inspectable**

### **large operational datasets should not be forced into unsuitable storage models**

### **changes in data structures must be version-aware**

### **The system’s data model should help future maintainers understand what a record is, who owns it, how long it matters, and whether it survives plugin removal.**

### 

### **8.2 Data Ownership Model**

### **Every major class of data in the plugin shall have a clear ownership model.**

### **Data ownership categories shall include:**

### **user-authored content**

### **user-configured settings**

### **plugin-defined registries**

### **runtime operational records**

### **external-provider artifacts**

### **generated documentation**

### **reporting and diagnostics records**

### **Ownership matters because it affects:**

### **editability**

### **export expectations**

### **deletion rules**

### **survivability rules**

### **permission rules**

### **migration handling**

### **No significant data class should exist in an ambiguous ownership state.**

### 

### **8.3 Human-Created Data**

### **Human-created data includes any data intentionally entered, edited, approved, denied, or curated by users.**

### **Examples include:**

### **brand profile fields**

### **business profile fields**

### **template composition choices**

### **manual overrides**

### **page content edits**

### **section field entries**

### **token overrides**

### **approval decisions**

### **denial decisions**

### **naming decisions**

### **optional documentation edits where supported**

### **Human-created data should generally be:**

### **understandable**

### **reviewable**

### **exportable where appropriate**

### **protected from accidental overwrite**

### **clearly distinguished from machine-generated records**

### 

### **8.4 System-Generated Data**

### **System-generated data includes any data created automatically by the plugin or by controlled provider-assisted workflows.**

### **Examples include:**

### **generated handles and identifiers**

### **crawl snapshots**

### **AI input bundles**

### **AI output artifacts**

### **normalized recommendations**

### **build-plan status records**

### **queue records**

### **execution logs**

### **rollback references**

### **reporting delivery records**

### **generated helper one-pagers**

### **generated manifests**

### **System-generated data should be managed according to clear retention, visibility, and cleanup rules.**

### 

### **8.5 Temporary vs Persistent Data**

### **The plugin shall distinguish between temporary data and persistent data.**

### **Temporary data includes:**

### **transient queue state caches**

### **intermediate packaging states**

### **temporary validation caches**

### **temporary comparison outputs**

### **temporary admin workflow state that does not need long-term retention**

### **Persistent data includes:**

### **templates**

### **field assignments**

### **profile data**

### **crawl snapshots**

### **AI runs**

### **build plans**

### **logs**

### **exports**

### **snapshots**

### **rollback records**

### **reporting records where retained**

### **This distinction must influence storage choice, cleanup behavior, and performance strategy.**

### 

### **8.6 Data Retention Categories**

### **The plugin shall classify data into retention categories.**

### **Suggested categories include:**

### **permanent until user deletion**

### **long-lived operational**

### **medium-lived operational**

### **short-lived operational**

### **ephemeral cache**

### **export package only**

### **uninstall-removable**

### **uninstall-preserved by choice**

### **Retention categories shall be defined later in more detail, but the system must be built so data does not accumulate without policy.**

### 

### **8.7 Data Sensitivity Categories**

### **The plugin shall classify data by sensitivity so it can handle security, reporting, and export correctly.**

### **Suggested sensitivity categories include:**

### **public content**

### **internal operational**

### **admin-visible restricted**

### **privileged restricted**

### **secret**

### **prohibited-from-reporting**

### **prohibited-from-export-without-explicit-intent**

### **Examples:**

### **page content may be public or public-derived**

### **build logs may be internal operational**

### **API credentials are secret**

### **passwords and tokens are prohibited from ordinary logging and reporting**

### **This classification should guide redaction and access-control decisions.**

### 

### **8.8 Data Exportability Rules**

### **The plugin shall define which data classes are exportable, under what permissions, and in what formats.**

### **Exportable data may include:**

### **settings**

### **profile data**

### **registries**

### **template compositions**

### **build plans**

### **AI artifacts**

### **logs**

### **snapshots**

### **design-token sets**

### **helper documentation**

### **manifests**

### **Exportability shall be permissioned and should not imply that every stored record is exportable to every user.**

### **The export system must support the plugin’s uninstall and restore philosophy.**

### 

### **8.9 Data Cleanup Rules**

### **Data cleanup rules shall ensure that plugin-owned data can be removed responsibly without destroying built page content.**

### **Cleanup behavior shall distinguish between:**

### **routine cleanup of temporary data**

### **retention-managed cleanup of stale operational data**

### **deactivation behavior**

### **uninstall cleanup**

### **user-selected preservation behavior**

### **exported-versus-non-exported data**

### **Cleanup must be intentional. The system must not leave behind uncontrolled clutter, but must also not delete valuable records unexpectedly.**

### 

### **8.10 Data Migration Strategy**

### **Because the plugin is expected to evolve, its data model must support migration.**

### **The data migration strategy shall cover:**

### **option structure changes**

### **custom table schema changes**

### **CPT meta shape changes**

### **registry schema changes**

### **export format versioning**

### **AI schema versioning**

### **backward compatibility with prior nearby releases where feasible**

### **Migration must be:**

### **version-aware**

### **logged**

### **safe to retry where possible**

### **recoverable when it fails**

### **explicit about what changed**

### 

## **9. WordPress Content and Storage Model**

### **9.1 Use of Custom Post Types**

### **Custom Post Types shall be used for human-meaningful, inspectable, persistent plugin objects that benefit from WordPress-native management patterns.**

### **Candidate use cases include:**

### **section templates**

### **page templates**

### **custom compositions**

### **build plans**

### **AI runs**

### **prompt packs**

### **documentation objects**

### **version snapshots where appropriate**

### **CPT usage is appropriate where:**

### **the object has meaningful identity**

### **the object may benefit from admin visibility**

### **the object may have status states**

### **the object may need revision-like handling or relationships**

### **the object may need standard WordPress querying behavior**

### **CPTs shall not be used merely because they are available; they should be used where they fit the conceptual model.**

### 

### **9.2 Use of Taxonomies**

### **Taxonomies may be used where controlled categorization or labeling improves organization, filtering, or compatibility management.**

### **Possible taxonomy uses include:**

### **section categories**

### **page template categories**

### **template purpose tags**

### **compatibility labels**

### **documentation categories**

### **Taxonomies should only be used where the categorization is genuinely useful and consistent. They should not become a dumping ground for metadata better handled elsewhere.**

### 

### **9.3 Use of Post Meta**

### **Post meta shall be used for object-level structured data attached to CPT records or built pages where the data belongs specifically to that object.**

### **Examples include:**

### **template metadata**

### **composition metadata**

### **field assignment references**

### **page orchestration metadata**

### **execution provenance references**

### **AI-run references tied to a plan or page**

### **status markers associated with a specific object**

### **Post meta is appropriate when the data belongs to one object and does not require a high-volume relational operational model.**

### 

### **9.4 Use of Options**

### **WordPress options shall be used for global plugin configuration and installation-level settings.**

### **Examples include:**

### **plugin configuration settings**

### **reporting settings**

### **version markers**

### **migration markers**

### **provider configuration metadata**

### **global token defaults**

### **global admin preferences**

### **uninstall behavior preferences**

### **Options shall not be used for high-volume runtime records or for object-specific data that belongs with pages, plans, or logs.**

### 

### **9.5 Use of Custom Database Tables**

### **Custom tables shall be used where the volume, structure, or operational nature of the data makes CPTs, options, or post meta inappropriate.**

### **Likely use cases include:**

### **crawl snapshots**

### **AI artifacts**

### **queue records**

### **execution logs**

### **rollback records**

### **token sets**

### **assignment maps**

### **reporting delivery records**

### **Custom tables shall be used when they materially improve:**

### **query efficiency**

### **relationship clarity**

### **retention management**

### **log volume handling**

### **operational observability**

### **Custom tables must be versioned, upgradeable, and documented.**

### 

### **9.6 Use of User Meta**

### **User meta may be used for user-specific plugin preferences, workflow state, or personal interface settings where those settings do not belong globally.**

### **Examples may include:**

### **screen preferences**

### **dismissed notices**

### **per-user view settings**

### **saved filters for plan interfaces**

### **optional user-specific workflow preferences**

### **User meta shall not be used for shared operational state that belongs to the site or plugin as a whole.**

### 

### **9.7 Use of Transients / Caches**

### **Transients or other controlled cache mechanisms may be used for temporary performance optimization, but not as the authoritative storage of essential business logic.**

### **Appropriate uses may include:**

### **temporary crawl summaries**

### **temporary provider capability cache**

### **temporary comparison results**

### **non-critical admin screen acceleration**

### **temporary validation cache**

### **Critical state must not depend solely on cache survival.**

### 

### **9.8 Use of Uploads Directory**

### **The uploads directory may be used for generated export packages, downloadable artifacts, temporary package preparation, or other file-based outputs where filesystem persistence is appropriate.**

### **Uploads-directory usage shall be governed by:**

### **permission checks**

### **cleanup rules**

### **naming rules**

### **collision avoidance**

### **temporary-versus-persistent distinctions**

### **secret-handling restrictions**

### **Sensitive files must not be written casually or left accessible without appropriate protection.**

### 

### **9.9 Use of ZIP Archives**

### **ZIP archives shall be used for:**

### **settings exports**

### **uninstall-before-removal exports**

### **artifact bundles**

### **documentation bundles where needed**

### **restore packages**

### **ZIP usage must include:**

### **manifest structure**

### **version marking**

### **validation rules**

### **import safety checks**

### **selective inclusion rules**

### **permission-gated download**

### **ZIP archives are a product-level portability tool, not just a convenience feature.**

### 

### **9.10 Content Portability Rules**

### **Content portability is a core requirement.**

### **The plugin shall generate output in forms that remain meaningful to WordPress even when plugin-specific orchestration data is absent.**

### **Portability rules include:**

### **meaningful structure should exist in post content where possible**

### **generated page content should not depend on hidden proprietary builder state for ordinary front-end usefulness**

### **plugin-owned workflow metadata must be distinguishable from content**

### **helper documentation and planning data may be plugin-owned, but built pages must still survive**

### **The system should optimize for content survivability, not plugin lock-in.**

### 

### **9.11 Built Page Persistence Rules**

### **Built pages shall persist as WordPress pages with content, hierarchy, metadata, and associated settings appropriate to the execution pathway used.**

### **Built page persistence includes:**

### **preservation of created pages across plugin deactivation**

### **preservation of meaningful front-end rendering through standard WordPress mechanisms**

### **preservation of page titles, slugs, parent assignments, and content unless explicitly changed by later approved actions**

### **Built pages must be treated as first-class site content, not temporary plugin projections.**

### 

### **9.12 Plugin Removal Survivability Rules**

### **Plugin removal survivability means the site should not collapse simply because the plugin is deactivated or uninstalled.**

### **The plugin shall therefore be designed so that:**

### **built pages remain in the database as normal pages**

### **page content remains meaningful**

### **plugin operational data can be exported before removal**

### **plugin cleanup can occur without deleting site pages unless explicitly and intentionally designed otherwise**

### **uninstall does not function as a destructive content wipe**

### **This survivability rule is central to the product’s long-term credibility.**

### 

## **10. Custom Post Types and Content Objects**

### **10.1 Section Template Object**

### **The Section Template object represents a reusable section-level pattern defined by the plugin.**

### **It shall contain, directly or through attached metadata:**

### **stable internal key**

### **human-readable name**

### **purpose description**

### **category or classification**

### **structural blueprint reference**

### **field-group blueprint reference**

### **CSS contract reference**

### **helper paragraph reference**

### **compatibility metadata**

### **version metadata**

### **active or deprecated status**

### **The Section Template object is a foundational reusable unit and should be treated as a controlled system definition.**

### 

### **10.2 Page Template Object**

### **The Page Template object represents an ordered composition of section templates intended for a recognizable page purpose.**

### **It shall contain:**

### **stable internal key**

### **human-readable name**

### **intended use or page archetype**

### **ordered list of section template references**

### **optional versus required section metadata**

### **compatibility and purpose tags**

### **one-pager generation metadata**

### **SEO default reference data where applicable**

### **version metadata**

### **active or deprecated status**

### **The Page Template object is a reusable page-level pattern definition.**

### 

### **10.3 Custom Template Composition Object**

### **The Custom Template Composition object represents a user-created page-template configuration assembled from registered section templates.**

### **It shall contain:**

### **unique composition identifier**

### **human-readable composition name**

### **ordered section list**

### **validation status**

### **compatibility state**

### **source references if derived from an existing template**

### **helper one-pager reference**

### **snapshot reference to the registry state at creation time**

### **status and version markers**

### **This object allows user-created flexibility without abandoning structural governance.**

### 

### **10.4 Build Plan Object**

### **The Build Plan object represents the structured action plan created from validated AI output and local normalization logic.**

### **It shall contain:**

### **plan identifier**

### **associated AI run reference**

### **associated site/profile context references**

### **generated site-purpose summary**

### **step-based recommended changes**

### **current state of approvals and denials**

### **remaining work status**

### **execution status markers**

### **timestamps**

### **authorship and actor references**

### **The Build Plan object is operational, reviewable, and stateful.**

### 

### **10.5 AI Run Object**

### **The AI Run object represents a single provider interaction context together with its governance metadata.**

### **It shall contain:**

### **run identifier**

### **provider identifier**

### **model identifier**

### **prompt-pack version reference**

### **input snapshot references**

### **registry snapshot references**

### **crawl snapshot references**

### **raw response references**

### **normalized output references**

### **validation status**

### **usage metadata where available**

### **timing metadata**

### **actor references**

### **The AI Run object is an auditable record of the planning interaction.**

### 

### **10.6 Prompt Pack Object**

### **The Prompt Pack object represents a versioned system of instructions used to generate AI planning requests.**

### **It shall contain:**

### **prompt-pack identifier**

### **human-readable name**

### **version**

### **active/inactive status**

### **system prompt components**

### **contextual insertion rules**

### **schema expectations**

### **provider notes or compatibility metadata**

### **changelog references**

### **This object allows prompt logic to evolve in a controlled and traceable way.**

### 

### **10.7 Documentation Object**

### **The Documentation object represents helper paragraphs, one-pager materials, or other structured guidance artifacts associated with templates or compositions.**

### **It may contain:**

### **documentation identifier**

### **documentation type**

### **source template or composition reference**

### **generated or human-edited status**

### **content body**

### **version marker**

### **export metadata**

### **active or archived status**

### **Documentation is a product feature and therefore deserves explicit representation rather than being treated as throwaway text.**

### 

### **10.8 Version Snapshot Object**

### **The Version Snapshot object represents a preserved record of a relevant system state at a moment in time.**

### **It may be used for:**

### **template registry snapshots**

### **prompt-pack snapshots**

### **schema snapshots**

### **build-context snapshots**

### **compatibility snapshots**

### **The snapshot object exists to support traceability, reproducibility, and migration-aware reasoning.**

### 

### **10.9 Object Relationships**

### **The plugin’s content objects shall be explicitly related rather than loosely implied.**

### **Key relationships include:**

### **section templates belong to template registries**

### **page templates reference section templates**

### **custom compositions reference section templates**

### **documentation objects reference templates or compositions**

### **build plans reference AI runs**

### **AI runs reference prompt packs and snapshots**

### **build plans may reference created or affected pages**

### **snapshots may reference the objects they preserve**

### **Relationships must be stored in a consistent and queryable way.**

### 

### **10.10 Object Statuses**

### **Each major object type shall support a controlled set of statuses appropriate to its role.**

### **Examples may include:**

### **draft**

### **active**

### **inactive**

### **archived**

### **deprecated**

### **failed validation**

### **pending generation**

### **pending review**

### **completed**

### **superseded**

### **Statuses must not be arbitrary. They should have defined meanings and transition rules.**

### 

### **10.11 Object Lifecycle Rules**

### **Each object class shall have a lifecycle from creation through change and possible retirement.**

### **Lifecycle rules should define:**

### **how the object is created**

### **what constitutes a valid editable state**

### **what status changes are allowed**

### **how the object is versioned**

### **whether it can be archived or deprecated**

### **whether it can be exported**

### **whether it can be deleted and under what conditions**

### **Lifecycle clarity is required for long-term maintainability and supportability.**

### 

## **11. Custom Tables and Operational Data**

### **11.1 Crawl Snapshot Table(s)**

### **Crawl snapshot tables shall store structured records representing public-site discovery and analysis results.**

### **These tables may include data such as:**

### **crawl run identifier**

### **URL**

### **canonical URL**

### **title snapshot**

### **meta snapshot**

### **indexability flags**

### **page classification**

### **hierarchy clues**

### **navigation participation flags**

### **summary data**

### **content hash or change markers**

### **crawl timestamps**

### **crawl status and error state**

### **Crawl data is operational and potentially high-volume, making custom tables more appropriate than generic post storage.**

### 

### **11.2 AI Artifact Table(s)**

### **AI artifact tables shall store structured records for input and output artifacts associated with AI runs.**

### **These tables may include:**

### **artifact identifier**

### **AI run reference**

### **artifact type**

### **file reference or storage location**

### **raw prompt reference**

### **raw response reference**

### **normalized output reference**

### **validation status**

### **redaction status**

### **usage metadata**

### **timestamps**

### **These tables support artifact governance, debugging, and export workflows.**

### 

### **11.3 Job Queue Table(s)**

### **Job queue tables shall store all queued, running, retrying, completed, failed, or cancelled operational tasks.**

### **These tables may include:**

### **job identifier**

### **job type**

### **queue status**

### **priority**

### **payload reference**

### **actor reference**

### **created timestamp**

### **start timestamp**

### **completion timestamp**

### **retry count**

### **lock state**

### **failure reason**

### **related object references**

### **The queue table is central to the safe handling of long-running or asynchronous-like plugin operations.**

### 

### **11.4 Execution Log Table(s)**

### **Execution log tables shall store structured records of site-impacting actions and meaningful operational steps.**

### **These tables may include:**

### **log entry identifier**

### **action type**

### **related job reference**

### **affected object references**

### **actor reference**

### **pre-change snapshot reference**

### **result summary**

### **status**

### **warning flags**

### **error details reference**

### **timestamp**

### **Execution logs must be queryable, filterable, and useful for both support and rollback reasoning.**

### 

### **11.5 Diff / Rollback Table(s)**

### **Diff and rollback tables shall store before-and-after references and structured change data needed for inspection or reversal logic.**

### **These tables may include:**

### **diff identifier**

### **rollback identifier**

### **related execution reference**

### **snapshot references**

### **object scope**

### **diff type**

### **rollback eligibility flag**

### **rollback status**

### **failure notes**

### **created timestamp**

### **These tables support both explainability and recovery.**

### 

### **11.6 Token Set Table(s)**

### **Token set tables shall store structured collections of design-token values and their states.**

### **These tables may include:**

### **token set identifier**

### **source type**

### **current or proposed state**

### **associated plan reference**

### **associated site or settings scope**

### **value payload**

### **accepted/rejected status**

### **created timestamp**

### **applied timestamp**

### **This allows token systems to be versioned, compared, approved, and restored.**

### 

### **11.7 Assignment Map Table(s)**

### **Assignment map tables shall store mappings that are operationally useful and potentially too relational or dynamic for clean post-meta handling.**

### **These mappings may include:**

### **page-to-field-group assignments**

### **page-to-template assignments**

### **plan-to-object assignments**

### **template-to-dependency assignments**

### **composition-to-section mappings in normalized form**

### **Assignment tables should exist where they make queries, validation, and cleanup substantially clearer.**

### 

### **11.8 Reporting / Telemetry Table(s)**

### **Reporting and telemetry tables shall store operational communication records for the private-distribution reporting model.**

### **These tables may include:**

### **report identifier**

### **report type**

### **destination category**

### **status**

### **payload summary**

### **redaction state**

### **send attempt count**

### **response summary**

### **failure reason**

### **created timestamp**

### **sent timestamp**

### **These tables are necessary for observability and support, especially when outbound communication fails or must be audited.**

### 

### **11.9 Table Creation and Upgrade Rules**

### **All custom tables shall be created and upgraded through controlled schema management.**

### **Table creation and upgrade rules shall include:**

### **deterministic table names**

### **version-aware schema management**

### **upgrade path logic**

### **safe handling of missing tables**

### **safe handling of partially upgraded installations**

### **logging of schema changes**

### **avoidance of destructive schema assumptions without migration planning**

### **Custom table creation must be reliable across fresh installs and upgrades.**

### 

### **11.10 Table Indexing Strategy**

### **Custom tables shall include indexing appropriate to their operational query patterns.**

### **Indexing should be considered for:**

### **identifiers**

### **foreign-reference columns**

### **status columns**

### **timestamp columns**

### **frequently filtered relationship fields**

### **job status and queue processing fields**

### **report status fields**

### **artifact type fields**

### **Indexing should improve expected operational behavior without creating unnecessary schema complexity.**

### 

### **11.11 Table Cleanup Strategy**

### **Custom tables shall have explicit cleanup rules tied to retention policy, deactivation behavior, uninstall behavior, and export/restore strategy.**

### **Cleanup strategy shall distinguish between:**

### **ephemeral data cleanup**

### **stale operational cleanup**

### **user-requested cleanup**

### **uninstall cleanup**

### **retained historical data**

### **export-protected data**

### **The plugin must not allow custom tables to grow forever without policy, and must not destroy meaningful operational history without intentional rules.**

### 

## **12. Section Template Registry Specification**

### **12.1 Purpose of Section Templates**

### **Section templates are the foundational reusable building blocks of the plugin. Each section template represents a controlled, repeatable content-and-layout unit intended to solve a specific communication or structural purpose on a page.**

### **The purpose of section templates is to:**

### **create a stable library of reusable page sections**

### **reduce one-off design and content improvisation**

### **standardize markup, styling hooks, field structure, and editing expectations**

### **support repeatable page-template construction**

### **support user-created compositions without allowing structural chaos**

### **provide a reliable base for helper documentation, ACF generation, LPagery mapping, and AI planning**

### **A section template is not merely a visual layout. It is a complete system definition that includes structure, fields, usage guidance, compatibility assumptions, and fixed internal contracts.**

### 

### **12.2 Section Template Required Fields**

### **Every section template shall include a minimum required definition set so it can be understood, rendered, documented, validated, and reused consistently.**

### **Required fields shall include:**

### **stable internal section key**

### **human-readable section name**

### **purpose summary**

### **section category**

### **blueprint definition reference**

### **field-group blueprint reference**

### **helper paragraph reference**

### **CSS contract manifest reference**

### **default variant or baseline configuration**

### **compatibility metadata**

### **version marker**

### **active/deprecated status**

### **render mode classification**

### **asset dependency declaration, including “none” where applicable**

### **A section template without these required fields shall be considered incomplete and shall not be eligible for normal use in page templates or custom compositions.**

### 

### **12.3 Section Template Optional Fields**

### **Section templates may also include optional metadata where that metadata improves planning, usability, compatibility, or future extensibility.**

### **Optional fields may include:**

### **short label or display alias**

### **preview description**

### **preview image or preview markup reference**

### **suggested use cases**

### **prohibited use cases**

### **notes for AI planning**

### **hierarchy role hints**

### **SEO relevance notes**

### **token-affinity notes**

### **LPagery mapping notes**

### **accessibility warnings or enhancements**

### **migration notes**

### **deprecation notes**

### **replacement section suggestions**

### **dependencies on other sections or layout contexts**

### **Optional fields should add clarity, not noise. Optional fields must not become a dumping ground for undocumented behavior.**

### 

### **12.4 Registry Key Rules**

### **Each section template must have a stable, system-owned registry key.**

### **Registry key rules shall include:**

### **keys must be unique across the section registry**

### **keys must be deterministic and non-human-fragile**

### **keys must not change casually once released**

### **keys must not be AI-generated at runtime**

### **keys should use a controlled naming pattern, such as st01, st16, or an equivalent future standardized format**

### **keys must be safe for use in code references, metadata, manifests, export files, and internal relationships**

### **A section template key is a contract identifier, not a marketing label. Once published into operational use, it should be treated as durable.**

### 

### **12.5 Naming Conventions**

### **Each section template shall have a clear human-readable name in addition to its stable internal key.**

### **Naming conventions shall:**

### **use concise but meaningful names**

### **reflect the section’s function rather than decorative appearance alone**

### **avoid ambiguity where possible**

### **remain understandable to both technical and non-technical users**

### **distinguish similar sections through purpose-based naming rather than arbitrary numbering alone**

### **Examples of good naming logic include names that indicate role, such as:**

### **Hero**

### **FAQ**

### **Testimonials**

### **Related Cards**

### **Conversion Band**

### **Pricing Grid**

### **Feature Comparison**

### **Human-readable names may evolve for clarity, but internal keys should remain stable unless formally migrated.**

### 

### **12.6 Section Categories**

### **Section templates shall be grouped into controlled categories to improve filtering, planning, composition, and documentation.**

### **Categories may include, for example:**

### **hero / intro**

### **trust / proof**

### **feature / benefit**

### **process / steps**

### **pricing / packages**

### **FAQ**

### **media / gallery**

### **comparison**

### **CTA / conversion**

### **directory / listing**

### **profile / bio**

### **stats / highlights**

### **timeline**

### **navigation / jump links**

### **related / recommended content**

### **legal / disclaimer support**

### **utility / structural support**

### **Categories shall be used for organization and compatibility reasoning, but category alone must not replace actual compatibility logic.**

### 

### **12.7 Section Variants**

### **A section template may support one or more controlled variants.**

### **Variants are permitted when:**

### **the structural family is the same**

### **the core purpose remains the same**

### **the field model remains meaningfully related**

### **the CSS contract can remain stable through controlled modifiers or token differences**

### **Variants may include differences such as:**

### **media left / media right**

### **compact / standard / expanded**

### **light / dark / neutral surface treatment**

### **centered / split layout**

### **icon-led / image-led emphasis**

### **single-column / two-column presentation**

### **Variants must be defined explicitly. Variants shall not be created ad hoc in a way that undermines the fixed system contract.**

### 

### **12.8 Section Blueprint Structure**

### **Each section template must have a blueprint that defines how the section exists as a renderable, documentable, field-mapped object.**

### **The section blueprint shall define:**

### **structural wrapper rules**

### **inner container rules**

### **major child regions**

### **allowed element types**

### **content slot definitions**

### **field-to-slot mapping**

### **variant logic**

### **default content expectations**

### **optional element behavior**

### **render mode**

### **CSS hook points**

### **accessibility responsibilities**

### **asset requirements**

### **The blueprint is the system-level representation of how the section works. It must be explicit enough to support rendering, validation, helper generation, ACF generation, and plan reasoning.**

### 

### **12.9 Section Helper Paragraph Structure**

### **Each section template shall be accompanied by a helper paragraph or helper block set that explains how the section should be used.**

### **The helper paragraph structure shall include:**

### **what the section is for**

### **what kind of content belongs in it**

### **what tone or depth is appropriate**

### **what each important field should contain**

### **how media should be chosen if applicable**

### **what common mistakes to avoid**

### **how the section supports user flow or page purpose**

### **any relevant SEO or accessibility guidance**

### **any variant-specific notes where needed**

### **The helper structure should make the section operable for editors, marketers, or site builders without requiring them to decode the underlying technical blueprint.**

### 

### **12.10 Section Asset Dependencies**

### **Each section template must declare its asset needs.**

### **Asset dependency declarations shall identify:**

### **whether the section needs front-end CSS**

### **whether the section needs admin-only CSS**

### **whether the section needs front-end JavaScript**

### **whether the section needs admin-only JavaScript**

### **whether the section depends on icon assets, media patterns, or other shared resources**

### **whether the section has no special assets beyond global system assets**

### **Assets must be declared explicitly so the plugin can manage loading and avoid unnecessary bloat.**

### 

### **12.11 Section CSS Contract Manifest**

### **Each section template shall have a CSS contract manifest that defines the selector structure and styling hooks the system expects.**

### **The manifest shall include:**

### **base section class**

### **section ID strategy if applicable**

### **inner wrapper classes**

### **major structural child classes**

### **modifier classes**

### **variant class rules**

### **state classes where relevant**

### **approved data attributes**

### **token hook points**

### **prohibited selector patterns if needed**

### **This manifest is a structural contract. It must remain stable enough to support portability, documentation, and deterministic rendering.**

### 

### **12.12 Section Accessibility Contract**

### **Each section template must define its baseline accessibility responsibilities.**

### **The section accessibility contract shall include:**

### **heading expectations**

### **structural landmark expectations if relevant**

### **image alt-text expectations**

### **button/link clarity expectations**

### **keyboard interaction expectations where interactive elements exist**

### **list semantics where applicable**

### **details/accordion behavior requirements where applicable**

### **contrast-aware design-token considerations**

### **avoidance of inaccessible content patterns**

### **Accessibility requirements must be treated as part of the section definition, not an optional afterthought.**

### 

### **12.13 Section Compatibility Rules**

### **Section templates shall declare compatibility assumptions to support safe page-template construction and custom composition.**

### **Compatibility rules may include:**

### **sections that may precede or follow the section comfortably**

### **sections that should not appear adjacent to the section**

### **sections that duplicate the same purpose and should not be stacked without reason**

### **variant conflicts**

### **dependency on page context**

### **dependency on token or surface assumptions**

### **dependency on content availability, such as media-heavy layouts needing appropriate assets**

### **Compatibility rules should help the system and the user avoid structurally weak or redundant combinations.**

### 

### **12.14 Section Versioning Rules**

### **Each section template shall be version-aware.**

### **Versioning rules shall include:**

### **a version marker per template definition**

### **changelog awareness for structural changes**

### **distinction between non-breaking and breaking changes**

### **migration expectations where applicable**

### **preservation of older rendered pages where possible**

### **stable internal key retention across compatible revisions**

### **Section versioning must allow the registry to evolve without making prior page outputs unintelligible.**

### 

### **12.15 Section Deprecation Rules**

### **A section template may be deprecated when it is no longer recommended for new use.**

### **Deprecation rules shall include:**

### **deprecation status marker**

### **reason for deprecation**

### **replacement recommendation where applicable**

### **retention of old references for existing pages**

### **exclusion from normal new-template selection where appropriate**

### **preservation of existing rendered pages unless a formal migration path is applied**

### **Deprecated sections should remain understandable and traceable. Deprecation must not equal silent erasure.**

### 

## **13. Page Template Registry Specification**

### **13.1 Purpose of Page Templates**

### **Page templates are reusable, page-level structural definitions composed of ordered section templates. They exist to express repeatable page types that serve known website purposes.**

### **The purpose of page templates is to:**

### **define known page patterns**

### **reduce page-level improvisation**

### **improve consistency of site structure**

### **accelerate build workflows**

### **standardize page flow**

### **support AI planning with known template options**

### **support helper one-pager generation**

### **provide a bridge between abstract business need and actual page assembly**

### **A page template is a structured plan for how a page should be built, not just a label.**

### 

### **13.2 Page Template Required Fields**

### **Each page template shall include a minimum required definition set.**

### **Required fields shall include:**

### **stable internal page-template key**

### **human-readable page-template name**

### **page purpose summary**

### **ordered section list**

### **required versus optional section designations**

### **compatibility metadata**

### **one-pager generation metadata**

### **version marker**

### **active/deprecated status**

### **category or template archetype**

### **default structural assumptions**

### **endpoint or usage notes where applicable**

### **A page template without these fields shall not be considered complete.**

### 

### **13.3 Page Template Optional Fields**

### **Optional page-template fields may include:**

### **display description**

### **recommended industries or business types**

### **recommended audience types**

### **suggested page title patterns**

### **suggested slug patterns**

### **hierarchy hints**

### **internal linking hints**

### **default token-affinity notes**

### **AI planning notes**

### **SEO notes**

### **documentation notes**

### **page-template preview metadata**

### **migration notes**

### **replacement template references**

### **Optional fields must support clarity and planning, not obscure the core definition.**

### 

### **13.4 Ordered Section Composition Rules**

### **Each page template must define an ordered section composition.**

### **Composition rules shall include:**

### **canonical section order**

### **optional insertion points**

### **whether repeated use of a section type is allowed**

### **adjacency expectations**

### **opening section expectations**

### **closing section expectations**

### **rules for optional sections that can be omitted without breaking flow**

### **rules for sections that are conditional on content availability or page type**

### **Page templates are ordered on purpose. Order should reflect user flow, not arbitrary placement.**

### 

### **13.5 Required vs Optional Sections**

### **Page templates shall distinguish between required sections and optional sections.**

### **Required sections are sections that define the page template’s essential identity or flow.**

### **Optional sections are sections that can improve or extend the page but are not necessary for the page template to remain valid.**

### **This distinction is important for:**

### **template validation**

### **custom composition guidance**

### **one-pager generation**

### **AI planning recommendations**

### **build-plan logic**

### **Optionality must be explicitly defined, not guessed at runtime.**

### 

### **13.6 Template Purpose and Use Cases**

### **Each page template shall state the kind of page it is meant to serve.**

### **Purpose and use-case declarations may include:**

### **service page**

### **offer page**

### **pricing page**

### **FAQ page**

### **hub page**

### **sub-hub page**

### **landing page**

### **location page**

### **event page**

### **request page**

### **profile page**

### **directory page**

### **comparison page**

### **informational detail page**

### **This declaration helps users and AI systems choose the right template for the right context.**

### 

### **13.7 Endpoint Pattern Associations**

### **A page template may declare common endpoint patterns it is intended to support.**

### **Examples may include:**

### **top-level service pages**

### **child detail pages**

### **FAQ branches**

### **location pages**

### **directory leaf pages**

### **campaign landing pages**

### **Endpoint pattern associations are planning hints, not rigid URL generators. They help the system reason about where a template belongs in the site structure.**

### 

### **13.8 Hierarchy Hints**

### **Page templates may include hierarchy hints that suggest where the template commonly sits in relation to other pages.**

### **Hierarchy hints may indicate:**

### **likely top-level usage**

### **likely child-page usage**

### **common parent page types**

### **common sibling relationships**

### **whether the page tends to act as a hub, leaf, or intermediate level**

### **Hierarchy hints inform AI planning and build-plan review, but they do not override explicit user-approved structure.**

### 

### **13.9 SEO Defaults**

### **A page template may include default SEO guidance.**

### **SEO defaults may include:**

### **title pattern suggestions**

### **meta description direction**

### **heading expectations**

### **internal link expectations**

### **schema-type suggestions**

### **page-intent classification**

### **common keyword-targeting notes**

### **warnings against thin or duplicated content**

### **SEO defaults are guidance scaffolding and should remain adaptable to actual page purpose and content.**

### 

### **13.10 One-Pager Assembly Rules**

### **Each page template shall define how its one-pager is assembled from section helper inputs and page-level notes.**

### **Assembly rules shall include:**

### **section helper order**

### **page-purpose summary**

### **cross-section strategy notes**

### **optional section handling**

### **global editing notes**

### **page-flow explanation**

### **token or visual-system notes if relevant**

### **This ensures that page-template documentation is structured and reproducible rather than manually improvised every time.**

### 

### **13.11 Page Template Compatibility Rules**

### **Page templates shall declare compatibility expectations.**

### **Compatibility rules may address:**

### **site contexts where the template is appropriate**

### **site contexts where the template is inappropriate**

### **required supporting content assumptions**

### **incompatibility with certain section variants**

### **hierarchy assumptions**

### **token or layout dependencies**

### **conflicts with other page purposes**

### **These rules help users and AI systems avoid using a template for a page it does not fit.**

### 

### **13.12 Page Template Versioning**

### **Each page template shall be version-aware.**

### **Versioning rules shall include:**

### **version marker per page-template definition**

### **changelog awareness**

### **compatibility tracking with underlying section versions where relevant**

### **migration notes when section order or meaning changes materially**

### **stable template key preservation whenever feasible**

### **Page-template versioning must account for the fact that a page template depends on multiple section definitions.**

### 

### **13.13 Page Template Deprecation Rules**

### **A page template may be deprecated when it is no longer recommended for new page creation.**

### **Deprecation rules shall include:**

### **deprecated status marker**

### **reason for deprecation**

### **recommended replacement template where applicable**

### **continued interpretability of old plans and pages that used the template**

### **exclusion from standard new-build selection where appropriate**

### **Deprecation must preserve traceability and historical understanding.**

### 

## **14. Custom Page Template Composition System**

### **14.1 Purpose of User-Created Compositions**

### **The custom composition system exists to give users controlled flexibility without abandoning the registry-based structure of the plugin.**

### **Its purpose is to:**

### **allow users to build a custom page-template arrangement from approved section templates**

### **preserve structural discipline while enabling custom workflows**

### **support use cases not fully covered by built-in page templates**

### **automatically generate corresponding documentation and field logic**

### **keep custom builds inside the plugin’s validation, compatibility, and portability rules**

### **Custom composition is intended to extend the system, not bypass it.**

### 

### **14.2 Composition Builder Rules**

### **The composition builder shall allow users to assemble a page template from registered sections within defined constraints.**

### **Builder rules shall include:**

### **only registered section templates may be used**

### **section order must be explicit**

### **invalid or deprecated sections should be prevented or warned against**

### **optional variant choices must respect section rules**

### **composition metadata must be captured at save time**

### **validation must occur before the composition is marked usable**

### **The builder should feel flexible, but its flexibility must always operate inside the system contract.**

### 

### **14.3 Allowed Section Ordering Logic**

### **Section ordering in a custom composition shall be governed by compatibility and flow logic.**

### **Ordering logic may enforce or warn about:**

### **required opening section types**

### **required closing section types**

### **disallowed adjacency pairs**

### **excessive repetition of similar-purpose sections**

### **contradictory sequence flow**

### **hierarchy-dependent sequence expectations**

### **CTA placement logic**

### **FAQ placement logic**

### **proof/trust placement logic**

### **The goal is not to prohibit every unusual composition, but to prevent structurally poor arrangements from being treated as normal.**

### 

### **14.4 Invalid Combination Handling**

### **The system must identify and handle invalid or weak section combinations.**

### **Invalid combination handling may include:**

### **blocking known impossible combinations**

### **warning about risky but technically allowable combinations**

### **identifying redundant purpose overlap**

### **flagging missing required structural anchors**

### **flagging unsupported variant pairings**

### **flagging dependency issues, such as sections that need content types not present in the composition’s intended use**

### **The system should favor explicit validation over silent acceptance.**

### 

### **14.5 Composition Metadata**

### **Each custom composition must store sufficient metadata to remain understandable, exportable, and supportable.**

### **Composition metadata shall include:**

### **composition identifier**

### **human-readable name**

### **ordered section list**

### **variant selections**

### **creation timestamp**

### **creator reference**

### **validation status**

### **compatibility notes**

### **source template reference if derived from a built-in template**

### **registry snapshot reference**

### **one-pager reference**

### **active/archived/deprecated status**

### **This metadata turns a composition into a real managed object rather than a temporary UI state.**

### 

### **14.6 Composition Helper One-Pager Generation**

### **Each valid custom composition shall trigger generation of a composition-specific one-pager.**

### **The one-pager shall be generated from:**

### **included section helper paragraphs**

### **composition order**

### **optional composition-level notes**

### **cross-section editing guidance**

### **composition purpose summary where available**

### **The generated documentation should be treated as part of the composition’s usability and maintainability value.**

### 

### **14.7 Composition Validation Rules**

### **A composition must pass validation before it is eligible for normal page creation use.**

### **Validation rules shall include:**

### **section existence validation**

### **deprecation awareness**

### **ordering validation**

### **compatibility validation**

### **variant validation**

### **required structural-anchor validation**

### **helper generation success**

### **field-group assignment derivability**

### **one-pager generation viability**

### **Validation should produce clear reasons for failure or warning, not merely binary outcomes.**

### 

### **14.8 Composition Snapshot Rules**

### **A composition shall retain a snapshot reference to the registry state in which it was created or last validated.**

### **Snapshot rules are necessary because:**

### **section definitions can change**

### **helper content can evolve**

### **field logic can evolve**

### **compatibility rules can evolve**

### **The snapshot allows the system to understand what the composition meant at the time it was built and to compare against current registry state later.**

### 

### **14.9 Reuse and Duplication Rules**

### **Users may wish to reuse or duplicate compositions.**

### **Reuse and duplication rules shall include:**

### **ability to clone an existing composition**

### **ability to rename the clone**

### **preservation of provenance reference to the source composition**

### **generation of a new unique composition identifier**

### **revalidation at the time of duplication if registry conditions changed**

### **Reuse should support productivity without making composition provenance unreadable.**

### 

### **14.10 Export / Import Rules for Compositions**

### **Compositions shall be exportable and importable as part of plugin portability and uninstall/restore workflows.**

### **Composition export/import rules shall include:**

### **inclusion of composition metadata**

### **inclusion of section references**

### **inclusion of variant choices**

### **inclusion of validation state where relevant**

### **inclusion of one-pager reference or generated output**

### **compatibility checking during import**

### **graceful handling of missing referenced sections or changed registry state**

### **Imported compositions must not be assumed valid blindly; they must be checked against the receiving environment.**

### 

## **15. Section Helper Paragraph System**

### **15.1 Helper Paragraph Purpose**

### **The helper paragraph system exists to explain how each section should be used by a human editor, builder, or reviewer.**

### **Its purpose is to:**

### **reduce confusion**

### **improve content quality**

### **standardize editing expectations**

### **bridge technical structure and human decision-making**

### **reduce reliance on memory or off-platform notes**

### **improve consistency across multiple users and multiple sites**

### **Helper paragraphs are part of the product itself, not an optional documentation extra.**

### 

### **15.2 Helper Paragraph Structure**

### **Each section helper paragraph or helper block shall follow a clear structure.**

### **The structure should include:**

### **what the section is for**

### **what user need or page goal it supports**

### **what type of content belongs here**

### **what level of detail is appropriate**

### **how the section should feel in tone and emphasis**

### **what each major field or content area should contain**

### **what supporting media is appropriate if applicable**

### **what mistakes to avoid**

### **any SEO or accessibility notes that materially affect section quality**

### **The structure should be consistent enough that users learn how to read helper guidance quickly.**

### 

### **15.3 Field-by-Field Instruction Requirements**

### **Helper guidance must explain the meaningful editable fields for the section.**

### **Field-by-field instructions should cover:**

### **what belongs in the field**

### **recommended length or depth where useful**

### **tone or style cues**

### **whether the field is optional or essential**

### **what not to put there**

### **whether the field supports tokenization**

### **whether the field should align with page-level messaging**

### **Field instructions should be practical and concrete, not vague.**

### 

### **15.4 Voice and Clarity Standards**

### **Helper guidance shall be written in a voice that is clear, direct, helpful, and non-technical where possible.**

### **Voice and clarity standards include:**

### **explain the section in plain language first**

### **avoid needless jargon**

### **be specific enough to guide actual editing**

### **avoid vague advice like “make it compelling” without context**

### **distinguish between required content and best-practice suggestions**

### **support both marketers and operators, not just developers**

### **The helper system should lower the barrier to using the section correctly.**

### 

### **15.5 Image Guidance Standards**

### **Where a section includes image fields, the helper system must explain what kind of images should be used.**

### **Image guidance may include:**

### **recommended subject matter**

### **recommended orientation or crop style**

### **quality expectations**

### **whether image authenticity matters**

### **whether text-heavy images should be avoided**

### **whether the image should reinforce trust, demonstrate product use, or convey context**

### **alt-text expectations**

### **Image guidance should help the user choose strategically relevant media, not just “any image.”**

### 

### **15.6 Heading Guidance Standards**

### **Where headings exist, the helper system must explain:**

### **what the main heading should accomplish**

### **whether clarity or emotional pull should dominate**

### **expected length or complexity**

### **relationship between eyebrow, headline, subheading, or supporting text**

### **avoidance of overly generic headings**

### **heading tone alignment with page purpose**

### **Headings should be treated as strategic content, not just empty placeholders.**

### 

### **15.7 Bullets / Pills / Cards Guidance Standards**

### **Where a section uses bullets, pills, cards, highlights, or repeated micro-content units, the helper system must explain:**

### **what each unit should represent**

### **expected number or density where useful**

### **whether items should be benefit-driven, fact-driven, proof-driven, or action-driven**

### **how repetitive phrasing should be avoided**

### **how scannability should be preserved**

### **This helps repeated content structures remain strong instead of sloppy or redundant.**

### 

### **15.8 Gallery / Media Guidance Standards**

### **Where a section includes galleries or multiple media units, helper guidance should explain:**

### **what the set as a whole should communicate**

### **whether variety or consistency is more important**

### **whether captions matter**

### **whether the media should be chronological, thematic, proof-oriented, or aesthetic**

### **when a gallery is unnecessary or excessive**

### **Media systems should support page purpose, not overwhelm it.**

### 

### **15.9 SEO-Relevant Guidance Rules**

### **Where section behavior materially affects SEO, the helper system should note that.**

### **SEO-relevant notes may include:**

### **heading clarity**

### **duplicate-content risk**

### **thin-content risk**

### **keyword stuffing avoidance**

### **internal linking opportunities**

### **strategic use of supporting text**

### **whether the section supports page-intent reinforcement**

### **The helper system should not become a full SEO manual, but it should acknowledge section-level SEO impact where it matters.**

### 

### **15.10 Accessibility Guidance Rules**

### **Where section editing choices materially affect accessibility, helper guidance must mention them.**

### **Accessibility guidance may include:**

### **alt-text responsibility**

### **heading hierarchy responsibility**

### **avoiding image-only information**

### **link text clarity**

### **button label clarity**

### **avoiding confusing repeated headings**

### **concise and understandable copy for interactive elements**

### **Accessibility guidance should be practical and embedded, not abstract.**

### 

### **15.11 Reusability Rules**

### **Helper paragraphs must be written so they remain reusable across different page contexts where the section is valid.**

### **Reusability rules include:**

### **avoid overfitting helper text to one specific site unless the section is intentionally site-specific**

### **explain role and strategy, not only one example**

### **allow the helper to support both AI reasoning and human use where appropriate**

### **make updates version-aware when section logic changes**

### **The helper system should scale with the registry, not become brittle and over-customized.**

### 

## **16. Page Template One-Pager System**

### **16.1 Purpose of the One-Pager**

### **The one-pager exists to give the user a consolidated, page-level editing and strategy reference for a specific page template or custom composition.**

### **Its purpose is to:**

### **summarize what the page is for**

### **explain how the page should flow**

### **combine all relevant section helper guidance**

### **make editing and review easier**

### **support content handoff**

### **support operational clarity**

### **reduce the need to jump between many section-level references**

### **The one-pager is a practical planning and editing tool, not just a documentation artifact.**

### 

### **16.2 Source Inputs for One-Pager Generation**

### **One-pager generation shall be based on controlled source inputs.**

### **These inputs may include:**

### **page template definition**

### **ordered section list**

### **section helper paragraphs**

### **optional page-template notes**

### **optional composition-level notes**

### **page-purpose summary**

### **hierarchy context hints**

### **SEO default notes where relevant**

### **token or visual-system notes where relevant**

### **The one-pager should be reproducible from system inputs, not dependent on informal memory.**

### 

### **16.3 Assembly Rules**

### **The one-pager must be assembled according to explicit rules.**

### **Assembly rules shall include:**

### **page-purpose opening summary**

### **ordered section guidance in the same order as the page**

### **distinction between required and optional sections**

### **page-wide best-practice notes**

### **cross-section strategy notes**

### **closing notes if needed for review or QA**

### **Assembly should feel coherent and page-oriented, not like a random stack of unrelated text blocks.**

### 

### **16.4 Section Order Reflection**

### **The one-pager must reflect the actual section order of the page template or composition it documents.**

### **Section-order reflection is important because:**

### **editing logic follows flow**

### **readers need to understand sequence**

### **documentation must match the actual page build**

### **optional sections must appear where they belong in the flow**

### **The one-pager should never describe the page in a sequence different from the actual composition.**

### 

### **16.5 Template-Wide Editing Notes**

### **The one-pager may include notes that apply to the page as a whole rather than any single section.**

### **Template-wide notes may cover:**

### **overall tone**

### **level of detail**

### **page intent**

### **audience expectations**

### **CTA strategy**

### **page-length expectations**

### **proof or trust expectations**

### **hierarchy role**

### **internal-link strategy**

### **These notes help the page behave like a coherent page, not merely a stack of valid sections.**

### 

### **16.6 Cross-Section Strategy Notes**

### **The one-pager should include guidance on how sections relate to one another.**

### **Cross-section strategy notes may cover:**

### **how the hero sets up later proof**

### **how pricing relates to FAQ**

### **how trust sections should reinforce offer sections**

### **how media should support the main promise**

### **how CTA placement should align with user readiness**

### **how repetition should be avoided across sections**

### **These notes are especially valuable because weak pages often fail not inside one section, but between sections.**

### 

### **16.7 AI-Generated vs System-Generated Documentation**

### **The system shall distinguish between documentation generated directly from stable template logic and documentation enhanced or influenced by AI.**

### **System-generated documentation should be based on registry definitions and helper rules.**

### **AI-enhanced documentation may provide context-specific refinements, but it must not overwrite the baseline meaning of the template without traceability.**

### **Users should be able to tell whether a one-pager is:**

### **purely system-generated**

### **system-generated with AI enhancement**

### **manually edited after generation**

### 

### **16.8 User Download Formats**

### **The one-pager shall be available in user-friendly forms suitable for reading, sharing, or archiving.**

### **Supported forms may include:**

### **in-admin view**

### **downloadable text or structured document form**

### **inclusion in export package**

### **composition-specific reference output**

### **The exact document format may evolve, but readability and portability are required.**

### 

### **16.9 Update Rules When Templates Change**

### **When a page template changes, the one-pager generation system must understand whether the existing one-pager should be:**

### **regenerated automatically**

### **marked outdated**

### **left unchanged but flagged**

### **reapproved by the user**

### **regenerated only for future uses, not past artifacts**

### **This rule is important because documentation drift can create confusion.**

### 

### **16.10 Custom Composition One-Pager Rules**

### **Custom compositions shall receive one-pagers generated from the same assembly logic, adapted to the custom section order and content.**

### **Composition one-pager rules shall include:**

### **inclusion of all included section helper material**

### **preservation of section order**

### **inclusion of composition metadata where useful**

### **custom composition naming**

### **regeneration when the composition changes**

### **compatibility warnings if any included section is deprecated or changed materially**

### **Custom pages deserve the same documentation discipline as built-in templates.**

### 

## **17. Rendering and Page Build Model**

### **17.1 Native Block Rendering Strategy**

### **The plugin shall use native WordPress block content as the primary rendering target for built pages.**

### **This strategy exists to support:**

### **content durability**

### **editor familiarity**

### **portability**

### **reduced lock-in**

### **survivability after plugin removal**

### **compatibility with modern WordPress workflows**

### **The plugin may construct block content programmatically, but the result should live meaningfully in post_content where possible.**

### 

### **17.2 GenerateBlocks Integration Strategy**

### **Where practical, the plugin shall support a GenerateBlocks-friendly composition model.**

### **This strategy may include:**

### **block structures consistent with GenerateBlocks patterns**

### **reusable container logic**

### **block-compatible class assignment**

### **content output that remains understandable in block-editor contexts**

### **GenerateBlocks support should strengthen the product’s practical utility without turning the plugin into a thin wrapper around another plugin’s full interface.**

### 

### **17.3 Static Markup vs Dynamic Rendering**

### **The plugin shall distinguish between static saved content and dynamic runtime output.**

### **Static rendering shall be preferred when:**

### **the content is page-owned**

### **the structure is durable**

### **the page should survive plugin removal**

### **no meaningful context-dependent runtime logic is needed**

### **Dynamic rendering may be used when:**

### **the output is inherently contextual**

### **the output is utility-oriented rather than core page content**

### **plugin-dependent runtime logic adds clear value and is intentional**

### **Static should be the default. Dynamic should be justified.**

### 

### **17.4 When Render Callbacks Are Allowed**

### **Render callbacks are allowed only under controlled circumstances.**

### **They may be allowed for:**

### **utility blocks that expose system state**

### **dynamic lists or references that are intentionally plugin-driven**

### **narrowly scoped runtime behavior that cannot reasonably be stored statically**

### **admin-supportive or plan-supportive display components**

### **Render callbacks shall not be used as a lazy substitute for building durable page content.**

### 

### **17.5 Post Content Assembly Rules**

### **When a page is built from a template or composition, the plugin must assemble its post_content according to deterministic rules.**

### **Assembly rules shall include:**

### **page-level wrapper logic where applicable**

### **ordered section insertion**

### **section markup construction**

### **field value injection**

### **class and attribute application**

### **block serialization rules**

### **preservation of human-editable content structure where intended**

### **Post content should be assembled consistently so the same template produces the same structural outcome under the same inputs.**

### 

### **17.6 Section Instance Rendering Rules**

### **Each instance of a section on a page must be rendered according to the section’s blueprint and page context.**

### **Section instance rendering shall include:**

### **wrapper output**

### **variant handling**

### **field-to-markup mapping**

### **optional field omission logic**

### **required field handling**

### **asset hook application**

### **accessibility handling**

### **token hook application**

### **class and ID assignment**

### **A section instance is a realization of a template definition, not a freeform hand-built block.**

### 

### **17.7 Template-to-Page Instantiation Rules**

### **When a page template or composition is used to create a page, the system shall perform a controlled instantiation process.**

### **Instantiation rules shall include:**

### **page creation or replacement target determination**

### **template selection recording**

### **section sequence realization**

### **default field initialization where appropriate**

### **ACF assignment derivation**

### **orchestration metadata recording**

### **one-pager association**

### **plan or AI provenance association where relevant**

### **Instantiation must produce both a usable page and a traceable operational record.**

### 

### **17.8 Template Rebuild Rules**

### **The system shall support rebuild or replacement workflows for existing pages under controlled rules.**

### **Rebuild rules may include:**

### **snapshot before mutation**

### **rename/slug handling for old page when replacing**

### **creation of a new page from the selected template**

### **injection of approved content and metadata**

### **hierarchy reassignment**

### **reporting of success, warnings, or failure**

### **retention of rollback references where supported**

### **Rebuild must be powerful, but never opaque.**

### 

### **17.9 Content Editability After Build**

### **Built content must remain editable by authorized users after page creation.**

### **Content editability rules shall include:**

### **users may edit page content normally unless explicitly restricted**

### **plugin-generated structure should remain understandable**

### **orchestration metadata should not prevent ordinary content maintenance**

### **helper and one-pager documentation should remain available as support aids**

### **The goal is not to freeze the page forever, but to provide a strong starting structure.**

### 

### **17.10 Rendered Content Independence from Plugin**

### **The rendered content model shall be designed so that meaningful page output does not require continuous plugin execution for basic front-end survival.**

### **This means:**

### **important content should exist in standard WordPress storage**

### **core page structures should not vanish if the plugin is deactivated**

### **plugin-owned enhancements may disappear, but page meaning should remain**

### **This is one of the product’s most important trust-building requirements.**

### 

### **17.11 Front-End Asset Loading Rules**

### **Front-end assets shall be loaded intentionally and efficiently.**

### **Asset loading rules shall include:**

### **only load assets needed by enabled section usage where practical**

### **version assets for cache management**

### **separate admin assets from front-end assets**

### **avoid globally bloating every page unnecessarily**

### **preserve section rendering integrity**

### **ensure design-token-driven styles are available where required**

### **Asset delivery should support both performance and reliability.**

### 

## **18. CSS, ID, Class, and Attribute Contract**

### **18.1 Global CSS Contract Principles**

### **The CSS contract is a system-level structural agreement that defines how markup is identified and styled.**

### **Global principles include:**

### **selector structures must be stable**

### **styling hooks must be predictable**

### **AI may influence values, not base naming**

### **contracts must support portability and maintainability**

### **contracts must support both front-end consistency and development clarity**

### **The CSS contract is an architectural asset, not just a styling detail.**

### 

### **18.2 Fixed Naming Policy**

### **The plugin shall use a fixed naming policy for classes, IDs, handles, and supporting selector-related structures.**

### **This policy exists to ensure:**

### **consistency**

### **traceability**

### **maintainability**

### **deterministic rendering**

### **easier debugging**

### **easier documentation**

### **safer extensibility**

### **Naming may evolve by formal versioned change, but must never be treated as an AI-mutable layer.**

### 

### **18.3 ID Assignment Rules**

### **IDs shall be used intentionally and sparingly.**

### **ID assignment rules shall include:**

### **IDs must be unique within the rendered page**

### **IDs should be used where necessary for anchors, ARIA relationships, JS targets, or uniquely identified wrappers**

### **IDs must follow the plugin’s naming conventions**

### **IDs must not be generated in fragile or random ways without traceable logic**

### **IDs must not be used where classes or data attributes are more appropriate**

### **IDs are part of the structural contract and must remain predictable.**

### 

### **18.4 Class Assignment Rules**

### **Classes are the primary styling and structural hook system.**

### **Class assignment rules shall include:**

### **every major rendered element should have meaningful classes**

### **classes should reflect role, scope, or contract purpose**

### **class layering should support global base styles, section scope, and element role**

### **variant classes must be explicit and controlled**

### **utility classes, if used, must align with the product’s naming policy**

### **Classes are the primary long-term maintainability layer and must be designed accordingly.**

### 

### **18.5 Data Attribute Assignment Rules**

### **Data attributes may be used to enrich the structural contract where semantic classes or IDs alone are insufficient.**

### **Appropriate uses may include:**

### **section identity**

### **role markers**

### **mode markers**

### **state markers**

### **variant markers**

### **execution or registry references where safe and appropriate**

### **Data attributes must not expose sensitive information. They should support tooling, clarity, and deterministic behavior.**

### 

### **18.6 HTML Coverage Requirement**

### **The plugin shall ensure that rendered HTML coverage is sufficiently complete for styling, scripting, inspection, and support.**

### **This means:**

### **wrappers must have defined hooks**

### **key child elements must have defined hooks**

### **interactive elements must have clear hooks**

### **section identity must be visible in the markup structure**

### **there should be no large “anonymous” structural regions that cannot be addressed reliably**

### **This requirement supports styling consistency, debugging, and future extensibility.**

### 

### **18.7 Section-Level CSS Contract**

### **Each section shall have a section-level CSS scope and associated structural hooks.**

### **The section-level contract shall include:**

### **a base section class**

### **section wrapper structure**

### **section inner container structure**

### **section role markers**

### **section variant markers**

### **token application points**

### **optional section-specific modifier classes**

### **This creates a predictable styling and support boundary around every section instance.**

### 

### **18.8 Page-Level CSS Contract**

### **Page templates and built pages may also have page-level structural hooks.**

### **The page-level contract may include:**

### **page template identifier class**

### **composition identifier class where relevant**

### **page wrapper markers**

### **template-specific modifier classes**

### **hierarchy-aware classes where appropriate**

### **Page-level styling hooks should support coordinated page behavior without undermining the independence of section-level contracts.**

### 

### **18.9 Element-Level CSS Contract**

### **Within sections, meaningful child elements shall have stable element-level hooks.**

### **Element-level contract examples may include:**

### **eyebrow**

### **headline**

### **intro**

### **media wrapper**

### **media element**

### **cards grid**

### **CTA group**

### **note**

### **disclosure**

### **list item**

### **badge**

### **caption**

### **These hooks should be consistent enough to support reusable styling and documentation.**

### 

### **18.10 AI Value Influence Boundaries**

### **AI may influence values, but not structure-defining identifiers.**

### **AI influence may include:**

### **token values**

### **variant selection recommendations**

### **content choices**

### **spacing preferences if mapped to token systems**

### **visual emphasis recommendations**

### **AI may not:**

### **rename classes**

### **rename IDs**

### **rename data attribute keys**

### **redefine selector contracts**

### **invent runtime naming conventions outside the system rules**

### **This boundary protects system integrity.**

### 

### **18.11 Tokenized Styling Model**

### **The styling model shall use tokens to separate stable selectors from variable visual values.**

### **Tokens may govern:**

### **colors**

### **typography assignments**

### **spacing scales**

### **radii**

### **shadows**

### **surface treatment**

### **component styles**

### **Tokenization allows brand customization and AI-influenced visual changes without destabilizing the underlying markup contract.**

### 

### **18.12 Collision Avoidance Strategy**

### **The plugin shall use a consistent namespacing strategy to reduce collision risk with themes, plugins, and user CSS.**

### **Collision avoidance shall include:**

### **plugin-prefixed class and ID conventions**

### **structured section and page-template scoping**

### **avoidance of overly generic class names**

### **predictable data attribute namespacing**

### **intentional global selector discipline**

### **The product should assume it will coexist with many unrelated plugins and themes.**

### 

### **18.13 Developer Extension Rules**

### **Developers may extend the system, but extension must respect the fixed contract model.**

### **Extension rules shall include:**

### **do not alter core naming policy casually**

### **use approved extension hooks or overrides where available**

### **preserve compatibility with existing section and page-template definitions**

### **document any custom extension that becomes operationally important**

### **avoid creating hidden local conventions that bypass the registry model**

### **Extensions should build on the system, not fork it into unpredictability.**

### 

## **19. Design Token and Brand Styling Engine**

### **19.1 Purpose of the Token Engine**

### **The token engine exists to allow visual customization and brand alignment without altering the plugin’s structural markup contract.**

### **Its purpose is to:**

### **map brand choices into controlled visual variables**

### **support AI-recommended visual values safely**

### **allow user overrides**

### **keep styles consistent across templates and pages**

### **support export, restore, and comparison of design states**

### **The token engine is the bridge between stable structure and adaptable presentation.**

### 

### **19.2 Token Categories**

### **The token engine shall support controlled token categories.**

### **Categories may include:**

### **primary color**

### **secondary color**

### **accent color**

### **neutral palette**

### **text colors**

### **background and surface colors**

### **typography families**

### **typography scale**

### **spacing scale**

### **border radius scale**

### **shadow scale**

### **border treatments**

### **button styles**

### **card styles**

### **form styles**

### **emphasis styles**

### **Categories must be explicit and versionable.**

### 

### **19.3 Brand Color Model**

### **The token engine shall support a structured color model rather than arbitrary ad hoc values.**

### **The color model may include:**

### **primary brand color**

### **secondary brand color**

### **accent color**

### **supporting neutrals**

### **text hierarchy colors**

### **surface colors**

### **interactive state colors**

### **success/warning/error colors where needed**

### **The system should support both user-defined values and AI-recommended values, while preserving clarity around what role each color serves.**

### 

### **19.4 Typography Model**

### **The token engine shall support a typography model that distinguishes structural roles rather than only raw font values.**

### **The typography model may include:**

### **heading family**

### **body family**

### **optional accent/display family**

### **size scale**

### **weight conventions**

### **line-height conventions**

### **tracking where relevant**

### **style role mapping such as eyebrow, headline, body, note, button text, caption**

### **Typography should be role-based and systematic.**

### 

### **19.5 Spacing Scale Model**

### **The token engine shall support a spacing scale used across sections and elements.**

### **The spacing scale may define:**

### **micro spacing**

### **intra-component spacing**

### **section inner spacing**

### **section outer spacing**

### **grid gap scales**

### **CTA grouping spacing**

### **responsive adjustments**

### **Spacing must be tokenized to allow coordinated page rhythm rather than random manual margins.**

### 

### **19.6 Radius / Shadow / Surface Model**

### **The engine shall support visual-system tokens for shape and depth.**

### **This model may include:**

### **radius scale**

### **shadow scale**

### **surface contrast rules**

### **panel treatments**

### **border styles**

### **overlay behavior where applicable**

### **These values should support consistent visual identity across many section types.**

### 

### **19.7 Button / Form / Card Variant Model**

### **The token engine may govern reusable component style families.**

### **Component variant modeling may include:**

### **primary button**

### **secondary button**

### **ghost or text button**

### **form field styling**

### **card styling**

### **panel styling**

### **badge styling**

### **pill styling**

### **These should behave as controlled variants, not arbitrary isolated settings.**

### 

### **19.8 Light / Dark / Neutral Surface Logic**

### **The styling engine shall support surface logic so sections can present correctly across different visual treatments.**

### **Surface logic may include:**

### **light surface**

### **dark surface**

### **neutral surface**

### **elevated panel on surface**

### **inverse text expectations**

### **contrast-aware token assignment**

### **Surface logic is important for page-level cohesion and section-level usability.**

### 

### **19.9 AI Token Recommendation Inputs**

### **AI token recommendations may be based on structured inputs such as:**

### **brand profile data**

### **uploaded logos or brand colors**

### **business type**

### **audience**

### **positioning**

### **tone**

### **existing site analysis**

### **visual consistency findings**

### **competitor landscape notes where provided**

### **AI recommendations should be grounded in inputs, but final local application remains within the token engine and user approval model.**

### 

### **19.10 User Override Rules**

### **Users must be able to override AI-recommended token values within authorized workflows.**

### **Override rules shall include:**

### **user-set values take precedence over unapplied AI suggestions**

### **manual changes must be recorded as user actions**

### **overrides should remain exportable and restorable**

### **the system should distinguish between current active tokens and proposed tokens**

### **The product should empower human control over branding outcomes.**

### 

### **19.11 Generated Stylesheet Rules**

### **The token engine shall be capable of generating styles or style variables from accepted token sets.**

### **Generated stylesheet rules shall include:**

### **version awareness**

### **deterministic output**

### **use of fixed selectors and variable values**

### **scope control**

### **safe regeneration when tokens change**

### **cache-busting or asset versioning support where needed**

### **Generated stylesheets must preserve the distinction between fixed structure and variable design values.**

### 

### **19.12 Preview / Accept / Reject Flow**

### **Token proposals shall support a review workflow.**

### **The flow may include:**

### **current vs proposed comparison**

### **preview of token impact**

### **accept all**

### **accept selected**

### **reject selected**

### **revert to prior token set**

### **record of who accepted or rejected the proposal**

### **This supports brand control and reduces accidental visual drift.**

### 

### **19.13 Token Export / Import Rules**

### **Accepted token sets and relevant proposal states shall be exportable and importable.**

### **Token export/import rules shall include:**

### **token values**

### **token roles**

### **version marker**

### **source metadata**

### **accepted state**

### **compatibility checking during import**

### **ability to restore prior styling state**

### **Design-token portability is part of the plugin’s broader restore model.**

### 

## **20. ACF Architecture and Field Group Generation**

### **20.1 ACF Dependency Philosophy**

### **ACF is treated as a foundational structured-content dependency for the plugin.**

### **The ACF philosophy is:**

### **fields should be attached to meaning, not random implementation convenience**

### **section templates should own their field logic**

### **field visibility should remain relevant to the page being edited**

### **programmatic generation is preferable to chaotic manual field administration for the core system**

### **fields should support scale and predictability**

### **ACF is not merely a helper tool in this product. It is part of the formal content architecture.**

### 

### **20.2 Section-Level Field Group Model**

### **Each section template shall correspond to a section-level field group blueprint.**

### **This model means:**

### **fields are defined around section purpose**

### **a section carries its own editable content model**

### **field groups remain modular**

### **page-level visibility can be derived from section usage**

### **custom compositions can assemble page-relevant field visibility by combining section definitions**

### **This is preferred over exposing every field group on every page.**

### 

### **20.3 Programmatic Field Registration Rules**

### **Field groups and related logic shall be registered programmatically for the core system definitions.**

### **Programmatic registration rules shall include:**

### **deterministic field keys**

### **deterministic group keys**

### **version-aware definitions**

### **compatibility with export/import logic where needed**

### **controlled field updates through code or governed registry changes**

### **avoidance of uncontrolled drift between environments**

### **Programmatic registration supports portability, reproducibility, and maintainability.**

### 

### **20.4 Field Key Naming Convention**

### **Each field key shall follow a controlled naming convention.**

### **Field key rules shall include:**

### **namespacing to the plugin system**

### **relationship to section identity where appropriate**

### **avoidance of ambiguous or generic keys**

### **stability once used in live content**

### **distinction between field label and field key**

### **Field keys are part of the product’s structural contract and must not be casually renamed.**

### 

### **20.5 Field Type Standards**

### **The plugin shall standardize how field types are used across section definitions.**

### **Field type standards may include guidance for:**

### **text**

### **textarea**

### **WYSIWYG**

### **image**

### **gallery**

### **link**

### **repeater**

### **select**

### **true/false**

### **relationship**

### **number**

### **URL**

### **color or token-selector fields where appropriate**

### **Field types should be chosen based on editing clarity, validation needs, and portability, not because they are merely available.**

### 

### **20.6 Field Requirement Rules**

### **Fields shall be marked required or optional intentionally.**

### **Requirement rules shall include:**

### **required fields for section validity**

### **optional fields that enhance but do not define the section**

### **conditional fields where applicable**

### **warnings for underfilled sections even when technically valid**

### **page-template-level awareness where a field may be critical in one context and less important in another**

### **Required-field policy should support quality without creating unnecessary friction.**

### 

### **20.7 Field Defaults and Placeholders**

### **Fields may include defaults or placeholders where that improves usability.**

### **Defaults and placeholders shall:**

### **guide expected content shape**

### **avoid encouraging lazy production content**

### **remain clearly distinguishable from user-entered content**

### **support onboarding and build efficiency**

### **avoid becoming accidental live copy unless intentionally accepted**

### **Defaults are aids, not substitutes for actual page strategy.**

### 

### **20.8 Repeater and Flexible Field Usage Rules**

### **Repeaters may be used where repeated structured content is necessary.**

### **Rules shall include:**

### **use repeaters when repeated items share a stable schema**

### **avoid unnecessary complexity**

### **enforce item-level clarity through good subfield design**

### **support cards, bullets, stats, FAQs, or similar patterns where appropriate**

### **Flexible or overly open-ended field structures should be used cautiously, because the product favors controlled structure over freeform complexity.**

### 

### **20.9 Image and Gallery Field Rules**

### **Image and gallery fields must follow clear standards.**

### **Rules shall include:**

### **define expected media purpose**

### **support alt-text responsibility where appropriate**

### **distinguish single-image sections from multi-image sections**

### **avoid unnecessary gallery complexity when a single image is more appropriate**

### **support compatibility with helper guidance and accessibility expectations**

### **Media fields should support strategy, not just asset storage.**

### 

### **20.10 WYSIWYG Usage Rules**

### **WYSIWYG fields may be used where controlled rich text is appropriate.**

### **WYSIWYG rules shall include:**

### **use only where richer formatting is genuinely useful**

### **avoid using WYSIWYG where plain structured fields would be clearer**

### **document expected output patterns**

### **avoid turning field structures into mini page builders inside a section**

### **maintain consistency with helper guidance and SEO expectations**

### **The product should not recreate unstructured content chaos inside section fields.**

### 

### **20.11 Relationship and Select Field Rules**

### **Relationship and select fields may be used where the user must choose from defined options or linked objects.**

### **Rules shall include:**

### **use select fields for controlled variants and finite choices**

### **use relationship fields when linking to known entities is part of the section behavior**

### **preserve meaningful labels for editors**

### **validate selected values against available options**

### **avoid using relationship fields as a workaround for poor architecture**

### **These field types should increase clarity, not complexity.**

### 

### **20.12 Validation Rules**

### **Field validation shall support both technical correctness and editorial usability.**

### **Validation may include:**

### **required field enforcement**

### **URL validation**

### **number validation**

### **selection validation**

### **image presence validation**

### **repeater minimum or maximum item validation where appropriate**

### **conditional validation based on section variant or mode**

### **warning-tier validation for best-practice issues that are not technically blocking**

### **Validation should explain problems clearly and should not be cryptic.**

### 

### **20.13 Field Group Visibility Rules**

### **Field groups shall be shown only where relevant.**

### **Visibility rules shall include:**

### **field groups appear on pages that use the associated section template or derived composition**

### **unrelated field groups should not clutter unrelated pages**

### **deprecated field groups should be handled with care on legacy pages**

### **field visibility should remain consistent with page-template assignment and page lifecycle state**

### **Field group visibility is one of the key usability advantages of the system.**

### 

### **20.14 Page Assignment Logic**

### **When a page is created from a template or composition, the plugin shall derive and apply relevant field-group visibility rules.**

### **Page assignment logic shall include:**

### **identify included sections**

### **map sections to field-group blueprints**

### **assign relevant groups to the new page**

### **avoid attaching unrelated groups**

### **update assignments when a page’s structure changes in governed workflows**

### **preserve legacy access where necessary for existing content support**

### **This logic must be deterministic and traceable.**

### 

### **20.15 Field Group Cleanup Rules**

### **Field-group cleanup must be handled carefully so content is not orphaned or editing experiences are not broken.**

### **Cleanup rules shall include:**

### **deactivating or deprecating field groups only through controlled workflows**

### **preserving interpretability for pages built under older structures**

### **avoiding sudden disappearance of needed fields without migration support**

### **supporting uninstall/export logic appropriately**

### **keeping registry-derived definitions in sync with the code-defined system**

### **Field cleanup should favor responsible lifecycle handling, not aggressive deletion.**

## **21. LPagery Token Compatibility Model**

### **21.1 Purpose of LPagery Support**

### **The LPagery token compatibility model exists to ensure that the plugin’s section templates and page templates can support token-driven page generation workflows where that is relevant to the user’s site-building strategy.**

### **The purpose of LPagery support is to:**

### **enable structured bulk page generation**

### **allow section fields to accept tokenized values where appropriate**

### **reduce friction between template architecture and location or landing-page generation workflows**

### **preserve consistency across many generated pages**

### **keep token support governed rather than improvised**

### **LPagery support is a compatibility feature within the broader system. It is important, but it does not redefine the plugin’s architecture.**

### 

### **21.2 Token Mapping Strategy**

### **The plugin shall define a formal token mapping strategy for section-level fields that may accept LPagery values.**

### **The token mapping strategy shall include:**

### **a mapping between field types and token compatibility**

### **a definition of where tokenized values may be injected**

### **a clear distinction between token-friendly and non-token-friendly fields**

### **support for field-level notes explaining token expectations**

### **a token map per section template where applicable**

### **a composition-aware token map when multiple sections are assembled into a page template**

### **Token mapping must be explicit. The system shall not assume that every field can safely receive tokenized content.**

### 

### **21.3 Supported Field Types for Tokenization**

### **Only field types that can safely and meaningfully accept tokenized values should be treated as supported.**

### **Supported field types may include:**

### **single-line text fields**

### **textarea fields**

### **WYSIWYG fields where tokenized insertion is structurally safe**

### **URL fields where tokenized URLs are valid**

### **button label and button URL fields**

### **simple select-field defaults where tokenized value mapping is formally supported**

### **image URL fields, if the system explicitly supports such a pattern**

### **alt-text or caption fields when token use is meaningful**

### **Support should be conservative by default. Token support should be earned through clear utility and validation, not assumed automatically.**

### 

### **21.4 Unsupported / Partially Supported Cases**

### **The system shall explicitly identify unsupported or only partially supported tokenization cases.**

### **Unsupported or partially supported cases may include:**

### **highly nested repeater structures without clear token-map logic**

### **complex relationship fields**

### **media-library attachment selection fields that require local object references**

### **variant selectors that are intended to remain controlled by template logic**

### **fields whose token injection could destabilize markup or validation**

### **rich content areas where unrestricted token substitution creates malformed output**

### **highly conditional fields that depend on runtime context outside LPagery’s normal token pattern**

### **The system should fail clearly or warn explicitly when a user attempts tokenization outside supported patterns.**

### 

### **21.5 Token Naming Rules**

### **Token names and token references used in the plugin’s compatibility layer must follow clear, deterministic naming rules.**

### **Token naming rules shall include:**

### **consistency with LPagery’s expected token reference style**

### **field-to-token mappings that are human-understandable**

### **avoidance of ambiguous aliases**

### **documentation of the token expected by each token-compatible field**

### **stability of token-reference naming where a mapping has already been used operationally**

### **The plugin should never create a situation where token behavior depends on undocumented naming guesswork.**

### 

### **21.6 Token Injection Rules**

### **Token injection rules define how and where tokenized values are introduced into page content.**

### **Injection rules shall include:**

### **tokenized values should enter through supported fields, not through uncontrolled string replacement across whole page output**

### **field-level token handling should preserve structural integrity**

### **token values should be treated as content inputs, not as markup instructions**

### **tokenized values must not be allowed to break class, ID, attribute, or wrapper contracts**

### **injection behavior should preserve escaping and validation expectations appropriate to the field type**

### **Token injection should happen through governed input pathways, not by mutating final output blindly.**

### 

### **21.7 Bulk Page Generation Considerations**

### **The LPagery model shall account for the fact that tokenized workflows are often used at scale.**

### **Bulk generation considerations include:**

### **page-template suitability for repeated generation**

### **field-level token availability**

### **consistency of output across many generated pages**

### **prevention of structurally invalid pages when token data is incomplete**

### **generation-time validation of required token-fed fields**

### **awareness of page slug/title generation dependencies**

### **scalable handling of page-level metadata during bulk generation**

### **The system should support scale without sacrificing correctness.**

### 

### **21.8 Dynamic Content Constraints**

### **LPagery compatibility must operate within the plugin’s broader rendering and portability rules.**

### **Dynamic content constraints include:**

### **tokenization should not create hidden runtime dependencies that break the page if LPagery is later removed from a specific workflow**

### **tokenized content should resolve into durable page content where the workflow intends final page generation**

### **tokenization should not bypass the plugin’s safety and validation model**

### **tokenization should not be used to alter system-owned structural contracts**

### **Tokens are content inputs, not permission to turn the system into an uncontrolled dynamic rendering layer.**

### 

### **21.9 Validation and Fallback Rules**

### **The system shall validate token compatibility and define fallback behavior when token data is missing or invalid.**

### **Validation and fallback rules shall include:**

### **validation of whether a section supports tokenization**

### **validation of whether a specific field supports tokenization**

### **validation of required token availability where applicable**

### **warnings when token usage is technically possible but strategically weak**

### **clear failure states where required token data is absent**

### **optional fallback defaults only where explicitly supported and safe**

### **no silent production of broken or misleading page content**

### **Fallback behavior must be intentional and documented.**

### 

### **21.10 Token Map Maintenance Rules**

### **Token maps must be maintained as first-class compatibility artifacts.**

### **Maintenance rules shall include:**

### **version-awareness of token maps**

### **section-template-specific token-map ownership**

### **update review when field structures change**

### **deprecation handling when fields or sections change materially**

### **exportability of token-map metadata where relevant**

### **import compatibility checks when bringing templates into another environment**

### **Token-map maintenance is required to prevent compatibility drift.**

### 

## **22. Brand and Business Profile Module**

### **22.1 Purpose of Brand Profile Data**

### **The brand profile exists to capture the visual, tonal, and strategic identity of the user’s brand in a structured way that can inform planning, styling, documentation, and AI-assisted recommendations.**

### **Its purpose is to:**

### **centralize brand identity inputs**

### **reduce inconsistency across site planning and page creation**

### **support token recommendation logic**

### **support AI understanding of tone and presentation**

### **preserve brand context across onboarding reruns and future planning cycles**

### **The brand profile is not merely cosmetic. It is strategic input.**

### 

### **22.2 Purpose of Business Profile Data**

### **The business profile exists to capture the operational, commercial, audience, and market context of the business the website serves.**

### **Its purpose is to:**

### **explain what the business does**

### **explain who the business serves**

### **explain where the business operates**

### **explain what offers, products, or services the site must support**

### **provide context for site hierarchy, page planning, navigation, and content strategy**

### **improve AI-assisted planning quality**

### **The business profile is the plugin’s structured understanding of the site’s real-world purpose.**

### 

### **22.3 Required Data Categories**

### **The onboarding and profile module shall support required categories sufficient to generate meaningful planning outcomes.**

### **Required data categories may include:**

### **business name**

### **business type**

### **primary offers, services, or products**

### **target audience or customer type**

### **core geographic market**

### **brand positioning summary**

### **brand voice summary**

### **current site URL where applicable**

### **preferred contact or conversion goals**

### **basic brand asset references**

### **preferred AI provider configuration for planning workflows**

### **Required categories should gather enough context to support actual planning without leaving major gaps.**

### 

### **22.4 Optional / Advanced Data Categories**

### **The profile system may also support deeper optional or advanced inputs.**

### **Optional categories may include:**

### **secondary offers**

### **seasonality**

### **strategic priorities**

### **major differentiators**

### **service-area complexity**

### **compliance or legal sensitivity notes**

### **preferred content emphasis**

### **existing marketing language**

### **value proposition notes**

### **additional brand rules**

### **content restrictions**

### **internal sales process notes**

### **visual inspiration references**

### **Advanced fields should improve the quality of planning without burdening users who do not need that depth.**

### 

### **22.5 Competitor Information Structure**

### **The module may store competitor information in a structured format.**

### **Competitor records may include:**

### **competitor name**

### **competitor URL**

### **market relevance**

### **notes on competitive positioning**

### **differentiation observations**

### **competitor strengths and weaknesses as perceived by the user**

### **Competitor input should inform planning, not drive copying. It exists to improve strategic awareness.**

### 

### **22.6 Audience / Persona Structure**

### **Audience and persona data shall be stored in a structured way.**

### **Persona records may include:**

### **persona name or role**

### **demographic or market description**

### **goals**

### **pain points**

### **buying motivations**

### **objections**

### **service relevance**

### **conversion expectations**

### **tone sensitivity or messaging preference**

### **Audience structure should help the plugin reason about page purpose, messaging, and flow.**

### 

### **22.7 Service / Product Structure**

### **Services and products should be modeled structurally rather than stored as unstructured notes only.**

### **This structure may include:**

### **name**

### **category**

### **short description**

### **strategic importance**

### **target audience**

### **geographic applicability**

### **offer relationships**

### **hierarchy hints**

### **whether dedicated pages are likely needed**

### **This supports AI page planning and template selection.**

### 

### **22.8 Geographic Market Structure**

### **The plugin shall support structured market or geography inputs where relevant.**

### **Geographic structure may include:**

### **primary location**

### **secondary locations**

### **service area**

### **shipping area**

### **region type**

### **in-person versus remote applicability**

### **location-specific offer differences**

### **Geographic data is especially important when site structure or LPagery workflows involve local or regional differentiation.**

### 

### **22.9 Brand Voice / Tone Structure**

### **Voice and tone inputs shall be structured enough to support planning and documentation.**

### **Voice/tone structure may include:**

### **core tone descriptors**

### **prohibited tone descriptors**

### **level of formality**

### **emotional positioning**

### **clarity versus sophistication preference**

### **audience-facing style notes**

### **copy restrictions or preferences**

### **preferred CTA style**

### **Voice must be captured in a way the system can actually use.**

### 

### **22.10 Asset Intake Rules**

### **The module may accept brand-related assets where useful.**

### **Asset intake rules shall include:**

### **support for logos or visual identity assets where needed**

### **support for color references**

### **support for typography references if provided**

### **validation of file types where files are accepted**

### **distinction between required and optional assets**

### **safe handling of stored asset references**

### **Assets should support planning and token recommendations, not create unnecessary file complexity.**

### 

### **22.11 Edit / Reuse / Snapshot Rules**

### **Profile data must support both ongoing editability and historical traceability.**

### **Rules shall include:**

### **users may update current profile data**

### **AI runs should reference snapshots of profile data at run time**

### **rerunning onboarding should prefill prior data**

### **profile edits after a completed AI run must not silently rewrite the historical input state of that run**

### **export/import should preserve profile structure where possible**

### **The system must support both “current truth” and “historical run context.”**

### 

### **22.12 Validation Standards**

### **Profile validation shall ensure the data is useful, not merely present.**

### **Validation may include:**

### **required-field validation**

### **URL validation**

### **file validation**

### **minimum content sufficiency checks for important strategy fields**

### **warnings for weak or placeholder-like entries**

### **controlled allowed values where useful**

### **support for “unknown” or “not applicable” only where product logic permits it**

### **Validation should improve planning quality, not just satisfy form requirements mechanically.**

### 

## **23. Guided Onboarding Experience**

### **23.1 Onboarding Goals**

### **The onboarding flow exists to move the user from an unconfigured plugin state to a planning-ready state.**

### **Its goals are to:**

### **collect business and brand context**

### **configure provider access**

### **gather existing site context**

### **trigger public-site analysis where relevant**

### **produce a clean submission package for AI planning**

### **reduce user confusion through step-based guidance**

### **Onboarding is the structured intake process that makes the rest of the plugin meaningful.**

### 

### **23.2 First-Run Experience**

### **The first-run experience shall introduce the user to the plugin in a controlled and confidence-building way.**

### **The first-run experience should:**

### **explain what the plugin does**

### **explain what information will be collected**

### **explain the role of AI planning**

### **explain mandatory reporting behavior at an appropriate level of clarity**

### **direct the user into onboarding rather than leaving them in an unstructured blank state**

### **First-run should orient, not overwhelm.**

### 

### **23.3 Onboarding Step Breakdown**

### **The onboarding flow shall be broken into meaningful steps.**

### **Step groupings may include:**

### **welcome / orientation**

### **business profile**

### **brand profile**

### **audience and offers**

### **geography and competitors**

### **asset intake**

### **existing site information**

### **provider setup**

### **crawl preferences and initiation**

### **review and confirmation**

### **submission**

### **The exact UI sequencing may evolve, but the process must remain structured and legible.**

### 

### **23.4 Required Fields Policy**

### **The plugin may require most or all onboarding fields needed for useful planning, but required-field policy must be realistic.**

### **The required-fields policy shall:**

### **distinguish truly required planning inputs from optional enhancement inputs**

### **support clear error handling**

### **allow intentional values like “not applicable” where appropriate**

### **prevent meaningless placeholders from passing without warning where practical**

### **Required fields should improve plan quality rather than force noise into the system.**

### 

### **23.5 Prefill / Return User Logic**

### **The onboarding flow shall support return users and repeat planning cycles.**

### **Prefill logic shall include:**

### **repopulating previously saved fields**

### **preserving prior user choices where still valid**

### **showing current values clearly**

### **allowing users to modify prior inputs**

### **preserving historical AI-run snapshots separately from the current editable profile**

### **Return users should not have to restart from zero.**

### 

### **23.6 Saved Draft Logic**

### **The onboarding process should support intermediate save behavior.**

### **Saved draft logic may include:**

### **saving in-progress onboarding data**

### **allowing the user to leave and return later**

### **preserving partially completed steps**

### **validating step-by-step without requiring final submission**

### **clear display of draft versus submitted state**

### **This is important for complex setups and multi-step intake.**

### 

### **23.7 User Warning and Confirmation Logic**

### **Onboarding should include appropriate warnings and confirmations before major actions.**

### **Examples include:**

### **warning before starting a crawl**

### **warning before using external AI services**

### **warning when rerunning planning without meaningful changes**

### **warning when required dependencies are missing**

### **final confirmation before submission**

### **Warnings should be informative and action-oriented, not alarmist.**

### 

### **23.8 Existing Site Analysis Triggering**

### **Onboarding shall support triggering analysis of the existing public site where the user provides a live site context.**

### **This trigger should be explicit, not hidden.**

### **The system should:**

### **collect the relevant site URL**

### **apply crawl rules**

### **store crawl results as a snapshot**

### **include the crawl as part of planning context**

### **surface crawl status clearly**

### **This step is important because the plugin is not only for greenfield planning.**

### 

### **23.9 AI Provider Setup During Onboarding**

### **The onboarding flow may include provider setup or provider confirmation.**

### **This may include:**

### **selecting a provider**

### **entering credentials**

### **verifying minimum connectivity where appropriate**

### **selecting or confirming model preferences where the product exposes them**

### **explaining that provider usage may incur cost to the user**

### **Provider setup must be secure and must not expose secrets in the UI unnecessarily.**

### 

### **23.10 Review and Submission Step**

### **Before final submission, the onboarding flow shall provide a review step.**

### **The review step should:**

### **summarize user inputs**

### **summarize crawl readiness or crawl status**

### **summarize provider setup state**

### **highlight missing or weak information**

### **confirm that the user is ready to submit for planning**

### **This step improves confidence and reduces accidental low-quality submissions.**

### 

### **23.11 Re-Run Onboarding Flow**

### **Users shall be able to rerun onboarding at a later time.**

### **Rerun behavior shall include:**

### **prefilled prior data**

### **editable fields**

### **visibility into what changed since last planning run where practical**

### **clear association between a rerun and a new AI-run context**

### **no silent rewriting of older run history**

### **Reruns are a core planned behavior, not an edge case.**

### 

### **23.12 Change Detection Before New AI Run**

### **Before launching a new AI planning run, the system should evaluate whether materially relevant inputs have changed.**

### **Change detection may consider:**

### **brand profile changes**

### **business profile changes**

### **asset changes**

### **crawl snapshot changes**

### **template registry changes**

### **prompt-pack changes**

### **provider/model changes**

### **If no meaningful changes are detected, the system should warn the user that they may be repeating a materially similar run with additional provider cost.**

### 

## **24. Public Site Crawl and Analysis Engine**

### **24.1 Crawl Purpose**

### **The crawl engine exists to create a bounded, planning-grade representation of the current public website so the plugin can recommend site structure, page changes, and navigation changes based on actual current state rather than guesswork.**

### **The crawl is not intended to be a general search spider, a penetration tool, or an unrestricted archival crawler.**

### 

### **24.2 Crawl Scope Rules**

### **The crawl shall be restricted to:**

### **the site’s current canonical host\**

### **public HTML pages\**

### **same-host URLs only\**

### **pages reachable from approved discovery sources\**

### **a maximum of 500 HTML pages per crawl run\**

### **a maximum crawl depth of 4 hops from approved seed URLs\**

### **Subdomains shall be excluded by default. External domains shall always be excluded.**

### 

### **24.3 Public-Only Constraints**

### **The crawler shall only analyze content that is publicly accessible without authentication.**

### **It shall not intentionally request or analyze:**

### **/wp-admin/\**

### **/wp-login.php\**

### **authenticated/member pages\**

### **pages requiring cookies or logged-in state\**

### **drafts, pending, or private post previews\**

### **admin-ajax or REST endpoints not intended as public HTML pages\**

### **If a public URL unexpectedly returns a login-gated or protected response, the crawler shall mark it as skipped and record the reason.**

### 

### **24.4 Indexable-Only Constraints**

### **The crawler shall only treat a page as planning-eligible if all of the following are true:**

### **the response is HTTP 200\**

### **the content type is HTML\**

### **the page is not marked noindex through HTML meta or X-Robots-Tag\**

### **the page is not disallowed by robots.txt for crawl expansion\**

### **the page is not classified as ignored utility/system content under 24.6\**

### **A page may still be recorded as discovered but excluded for audit purposes if it fails indexability criteria.**

### 

### **24.5 Meaningful-Page Classification Rules**

### **A page shall be classified as meaningful if it is indexable and meets at least one of the following conditions:**

### **appears in primary or footer navigation\**

### **contains a visible H1 and at least 150 words of content\**

### **is a likely service, product, location, hub, FAQ, about, contact, event, pricing, or request page\**

### **is linked repeatedly from meaningful internal pages\**

### **clearly functions as a site-structure page rather than a utility endpoint\**

### **A page shall be classified as non-meaningful if it is primarily transactional, ephemeral, system-generated, pagination-only, duplicate, or utility-oriented.**

### 

### **24.6 Ignored Page Types**

### **The crawler shall exclude the following patterns by default:**

### **cart\**

### **checkout\**

### **account\**

### **login\**

### **register\**

### **search results\**

### **feeds\**

### **attachment endpoints with no meaningful page content\**

### **thank-you pages\**

### **order status pages\**

### **preview URLs\**

### **tag/date/author archives unless specifically whitelisted later\**

### **paginated archive URLs beyond page 1\**

### **obvious faceted-filter URLs\**

### **UTM or tracking-parameter variants\**

### **Ignored pages shall be marked as excluded with reason codes where recorded.**

### 

### **24.7 Legal / Respectful Crawl Behavior**

### **The crawler shall behave conservatively and respectfully.**

### **Rules:**

### **It shall use a single worker by default.\**

### **It shall wait 250 milliseconds between HTTP requests.\**

### **It shall use a request timeout of 8 seconds.\**

### **It shall stop after 25 consecutive hard failures or when the page limit is reached.\**

### **It shall identify requests with the user-agent string:\
AIOPageBuilderCrawler/{plugin_version} ({site_url})\**

### **It shall not attempt to bypass robots, auth, rate limits, or access controls.\**

### 

### **24.8 Rate Limits and Request Patterns**

### **The crawler shall:**

### **request one page at a time by default\**

### **use GET requests for HTML page retrieval\**

### **avoid fetching the same normalized URL more than once per crawl run\**

### **stop expansion on non-HTML content\**

### **record redirects but not continue endless redirect chains\**

### **cap redirect following at 3 hops\**

### 

### **24.9 URL Discovery Rules**

### **The crawler shall use this discovery order:**

### **homepage URL\**

### **sitemap.xml and sitemap index files, if publicly available\**

### **primary navigation links discovered on the homepage\**

### **footer navigation links discovered on the homepage\**

### **internal links on already accepted meaningful pages\**

### **URL normalization shall remove fragments and tracking parameters before deduplication.**

### 

### **24.10 Canonicalization Rules**

### **For each crawled page, the crawler shall capture:**

### **requested URL\**

### **final URL after redirects\**

### **canonical URL if present\**

### **normalized URL key used internally\**

### **The crawler shall treat the canonical URL as the preferred identity for clustering duplicate pages when the canonical target is same-host and valid.**

### 

### **24.11 Duplicate Detection Rules**

### **A page shall be considered a likely duplicate if one or more of the following are true:**

### **same canonical URL as another discovered page\**

### **identical normalized title + H1 + major content hash\**

### **redirecting to an already accepted meaningful page\**

### **same content hash and same normalized structure as another page\**

### **Duplicates shall not become separate planning pages unless explicitly justified by unique navigational role.**

### 

### **24.12 Navigation Detection Rules**

### **The crawler shall attempt to classify links as navigation links when they appear in:**

### **\<nav\> landmarks\**

### **repeated header containers\**

### **repeated footer containers\**

### **repeated off-canvas/mobile-menu containers\**

### **breadcrumb structures\**

### **Navigation detection shall record:**

### **menu context guess\**

### **label\**

### **target URL\**

### **nesting depth if inferable\**

### 

### **24.13 Content Summary Rules**

### **For each meaningful page, the crawler shall store:**

### **title tag\**

### **meta description\**

### **H1\**

### **H2 outline up to first 10 H2s\**

### **word-count estimate\**

### **conversion-intent classification\**

### **page-type classification\**

### **concise page summary limited to 500 words of extracted text\**

### **nav participation summary\**

### **internal-link count estimate\**

### **The crawler shall not store unlimited raw page text in the snapshot table.**

### 

### **24.14 Structural Analysis Rules**

### **For each meaningful page, the crawler shall infer:**

### **likely page role: hub / branch / leaf / utility\**

### **likely business function: service / product / location / support / trust / conversion / informational\**

### **hierarchy hints from breadcrumbs, URL path, nav position, and link graph\**

### **whether the page is underbuilt, duplicate, or structurally redundant\**

### **whether the page appears to need replacement, consolidation, or preservation\**

### **These classifications are advisory inputs to later AI planning.**

### 

### **24.15 Crawl Snapshot Storage Rules**

### **Each crawl run shall create a single crawl snapshot record and associated child page records.**

### **The snapshot shall store:**

### **crawl ID\**

### **site host\**

### **crawl start and end timestamps\**

### **crawl settings used\**

### **total discovered URLs\**

### **accepted meaningful pages\**

### **excluded pages\**

### **failed requests\**

### **final snapshot status\**

### **Each page record shall store all normalized fields needed for later AI input and Build Plan generation.**

### 

### **24.16 Crawl Failure Handling**

### **Crawl failure states shall include:**

### **provider/transport failure\**

### **excessive redirects\**

### **timeout failure\**

### **malformed response\**

### **robots exclusion\**

### **unsupported content type\**

### **page limit reached\**

### **fatal crawl abort due to repeated failure\**

### **If a crawl partially succeeds, the snapshot shall be marked partial rather than failed completely. Partial snapshots may still be used for planning if they meet minimum data thresholds.**

### 

### **24.17 Re-Crawl and Comparison Rules**

### **The plugin shall support comparison of a new crawl to the prior completed crawl for the same site.**

### **Comparison output shall include:**

### **newly discovered pages\**

### **removed pages\**

### **changed titles/H1s\**

### **changed canonical targets\**

### **changed nav participation\**

### **changed page classifications\**

### **changed meaningful-page counts\**

### **A re-crawl shall not overwrite the prior snapshot. It shall create a new snapshot linked to the previous one.**

### 

## **25. AI Provider Abstraction Layer**

### **25.1 Supported Provider Model**

### **The plugin shall support a provider abstraction model rather than hard-coding a single AI service as the only planning pathway.**

### **The supported provider model shall allow:**

### **one or more current providers**

### **consistent internal handling across providers**

### **future provider expansion**

### **provider-specific capability checks**

### **normalized output expectations despite provider differences**

### **The abstraction model is important for resilience and flexibility.**

### 

### **25.2 Provider Driver Architecture**

### **Each AI provider shall be represented through a driver or service layer that conforms to the plugin’s internal provider contract.**

### **The driver architecture should support:**

### **authentication handling**

### **model capability retrieval where needed**

### **request formatting**

### **file attachment handling**

### **structured output requests**

### **retry and timeout behavior**

### **response normalization**

### **provider-specific error handling**

### **The rest of the system should interact with a normalized provider interface rather than provider-specific logic scattered everywhere.**

### 

### **25.3 Authentication Model**

### **Provider authentication shall be handled server-side and securely.**

### **Authentication rules shall include:**

### **credentials must not be exposed in front-end code**

### **credentials must not be exposed in ordinary logs**

### **credentials must be stored and retrieved using a secure internal pattern**

### **provider requests must be made from trusted server-side execution paths**

### **invalid credentials must be detected and surfaced clearly**

### **Authentication failure must be understood as an operational state, not an invisible background issue.**

### 

### **25.4 API Key Storage Rules**

### **API keys and similar secrets shall be treated as sensitive data.**

### **Storage rules shall include:**

### **restricted access to provider credentials**

### **avoidance of exposing raw secrets in admin views unnecessarily**

### **no inclusion of secrets in exports unless explicitly and safely designed**

### **no inclusion of secrets in heartbeat, install, or diagnostics reporting**

### **support for key update and replacement workflows**

### **Secrets handling is a security-critical requirement.**

### 

### **25.5 Provider Capability Matrix**

### **The plugin shall maintain awareness of provider capability differences.**

### **The capability matrix may include:**

### **structured output support**

### **file attachment support**

### **context size considerations**

### **model selection availability**

### **error-format differences**

### **retry implications**

### **output reliability notes**

### **This matrix allows the plugin to adapt behavior without weakening the unified planning experience.**

### 

### **25.6 Model Selection Rules**

### **The provider layer may support model selection where the product exposes it.**

### **Model selection rules shall include:**

### **use of a default recommended model where appropriate**

### **support for user-selected models if permitted by the product UI**

### **compatibility checks between model and required features**

### **warning when a selected model may not support required schema behavior**

### **storing model choice in AI-run metadata**

### **Model choice should be visible, intentional, and recorded.**

### 

### **25.7 Request Assembly Rules**

### **Provider requests shall be assembled through a controlled process.**

### **Request assembly shall include:**

### **system prompt injection**

### **user/context data injection**

### **registry data injection**

### **crawl data injection**

### **schema request structure**

### **file attachment inclusion where supported**

### **token/cost awareness where practical**

### **redaction pass before submission**

### **Request assembly must be deterministic and auditable.**

### 

### **25.8 File Attachment Rules**

### **Where providers support file-based input, the plugin may attach structured files.**

### **File attachment rules shall include:**

### **only include approved file types or data packages**

### **maintain a file manifest**

### **redact sensitive data before attachment**

### **avoid attaching unnecessary raw data when summaries are sufficient**

### **record attachment use in the AI-run artifact record**

### **Attachments must be intentional and reviewable.**

### 

### **25.9 Structured Output Rules**

### **The provider layer shall require or strongly prefer structured outputs aligned with the plugin’s schema expectations.**

### **Structured output rules shall include:**

### **schema-based output targeting**

### **validation-ready formatting**

### **explicit rejection or retry handling when structured output is not returned**

### **separation between raw provider response and normalized internal representation**

### **Structured outputs are a core requirement because the build plan cannot depend on vague prose alone.**

### 

### **25.10 Retry / Backoff / Timeout Rules**

### **Provider interactions must use reasonable operational controls.**

### **These controls shall include:**

### **timeout handling**

### **retry handling where failure appears transient**

### **backoff logic where appropriate**

### **upper bounds on retries**

### **failure logging**

### **non-destructive handling when all retries fail**

### **The plugin must not hang indefinitely or behave unpredictably on network or provider failure.**

### 

### **25.11 Error Normalization Rules**

### **Provider errors shall be normalized into a consistent internal representation.**

### **Normalization shall support:**

### **user-facing error clarity**

### **internal debugging clarity**

### **provider-specific raw detail retention where useful**

### **consistent categorization such as auth failure, rate limit, malformed response, timeout, unsupported feature, or validation failure**

### **Error normalization is necessary for a coherent support and diagnostics system.**

### 

### **25.12 Provider-Specific Override Rules**

### **The abstraction layer may allow provider-specific overrides where necessary.**

### **Overrides may address:**

### **attachment handling**

### **schema formatting differences**

### **model compatibility differences**

### **timeout tuning**

### **response parsing differences**

### **Overrides must remain contained in the provider layer and must not leak provider-specific branching throughout the rest of the system.**

### 

### **25.13 Future Provider Expansion Rules**

### **The architecture must support future provider additions.**

### **Expansion rules shall include:**

### **conformance to the provider contract**

### **capability declaration**

### **schema compatibility review**

### **authentication support**

### **logging compatibility**

### **error normalization support**

### **artifact storage compatibility**

### **Future provider addition should be additive, not destabilizing.**

### 

## **26. AI Prompt Pack System**

### **26.1 Prompt Pack Purpose**

### **Prompt packs exist to define the controlled instruction sets used for AI planning runs.**

### **Their purpose is to:**

### **keep AI behavior consistent**

### **version planning logic**

### **support repeatability**

### **improve output quality**

### **allow controlled prompt evolution over time**

### **preserve historical run traceability**

### **Prompt packs are system assets, not casual text snippets.**

### 

### **26.2 Prompt Pack Components**

### **A prompt pack may include:**

### **system prompt base**

### **role framing**

### **planning instructions**

### **schema requirements**

### **site-analysis instructions**

### **template-registry interpretation rules**

### **build-plan expectations**

### **provider-specific notes where needed**

### **safety instructions**

### **normalization expectations**

### **Prompt packs should be modular enough to evolve without becoming unreadable.**

### 

### **26.3 System Prompt Structure**

### **The system prompt should define the highest-level planning contract.**

### **It should communicate:**

### **what the AI is being asked to do**

### **what inputs it is receiving**

### **what outputs it must produce**

### **what constraints it must obey**

### **how it should use template registries**

### **how it should treat existing-site analysis**

### **how it should structure recommendations**

### **The system prompt should be clear, formal, and optimized for reliable structured planning behavior.**

### 

### **26.4 User Data Injection Rules**

### **User-provided profile data must be inserted into the prompt pack through controlled pathways.**

### **User data injection rules shall include:**

### **structured profile insertion**

### **preservation of meaning without uncontrolled verbosity**

### **consistent field labeling**

### **omission or summarization of empty/irrelevant fields**

### **traceability of what was injected**

### **The system should avoid messy prompt assembly where user data is shoved in without structure.**

### 

### **26.5 Registry Data Injection Rules**

### **The page-template and section-template registries shall be introduced to the AI through controlled representations.**

### **Registry data injection shall include:**

### **template keys**

### **names**

### **purpose summaries**

### **section order for page templates**

### **compatibility notes where important**

### **omission of unnecessary implementation detail such as raw helper text if not needed for the planning step**

### **Registry injection must give the AI enough structural context to plan effectively without bloating the request unnecessarily.**

### 

### **26.6 Crawl Data Injection Rules**

### **Crawl data must be injected in a structured and planning-relevant form.**

### **This may include:**

### **page inventory summary**

### **meaningful-page summaries**

### **hierarchy observations**

### **navigation observations**

### **content-purpose summaries**

### **duplicate-content concerns**

### **key existing-site weaknesses or strengths**

### **The crawl injection should support planning, not drown the model in raw page dumps unless a specific use case justifies it.**

### 

### **26.7 Prompt Compression / Summarization Rules**

### **Prompt packs must support compression and summarization where payloads would otherwise become too large.**

### **Compression rules may include:**

### **summarizing long pages**

### **summarizing repetitive crawl findings**

### **prioritizing high-value registry detail**

### **excluding unnecessary low-value records**

### **converting verbose data into structured summaries**

### **Compression must preserve planning usefulness, not merely shrink tokens blindly.**

### 

### **26.8 Prompt Versioning Rules**

### **Prompt packs shall be versioned.**

### **Versioning rules shall include:**

### **version identifier**

### **change notes**

### **historical run association**

### **compatibility tracking where needed**

### **ability to understand which prompt pack produced which AI run**

### **Prompt versioning is essential for debugging and improving planning outcomes over time.**

### 

### **26.9 Prompt Testing Rules**

### **Prompt packs should be testable before or during release workflows.**

### **Prompt testing may include:**

### **schema conformance testing**

### **comparative run testing**

### **input-coverage testing**

### **provider-compatibility testing**

### **regression testing when prompt changes occur**

### **Prompt changes should not be shipped blindly if they materially affect planning quality.**

### 

### **26.10 Prompt Safety Rules**

### **Prompt packs must preserve product safety requirements.**

### **Safety rules shall include:**

### **no instruction that allows AI to redefine system contracts**

### **no instruction that treats AI prose as direct execution authority**

### **no hidden override of planner/executor boundaries**

### **support for assumptions and warnings in output**

### **support for reporting incomplete certainty**

### **Safety must be designed into prompts, not left to hope.**

### 

### **26.11 Prompt Export Rules**

### **Prompt packs and prompt-run artifacts shall be exportable where permissions allow.**

### **Export may include:**

### **prompt-pack version metadata**

### **raw prompt text used for a run**

### **injection manifest**

### **related schema references**

### **provider/model references**

### **Prompt export supports transparency and review.**

### 

## **27. AI Input Artifact Preparation**

### **27.1 Data Selection Rules**

### **The plugin must select input data intentionally rather than dumping every available record into the provider request.**

### **Selection rules shall include:**

### **include profile data relevant to planning**

### **include registry data relevant to page planning**

### **include crawl data relevant to site structure**

### **include prior context only when useful**

### **exclude unrelated operational noise**

### **exclude secrets and prohibited data classes**

### **This improves both quality and safety.**

### 

### **27.2 Input Normalization Rules**

### **Selected input data must be normalized before submission.**

### **Normalization may include:**

### **standard field labeling**

### **consistent value formatting**

### **flattening or summarizing nested structures**

### **omission of redundant or empty values**

### **conversion to schema-friendly representations**

### **language and formatting consistency**

### **Normalized input makes provider behavior more reliable.**

### 

### **27.3 Snapshot Packaging Rules**

### **Each AI run shall use a snapshot package of the relevant input state.**

### **Snapshot packaging shall include:**

### **profile snapshot**

### **crawl snapshot reference or digest**

### **template registry snapshot**

### **prompt-pack version**

### **provider/model context**

### **any attached file manifests**

### **This ensures the run can be understood historically even if current site settings later change.**

### 

### **27.4 Redaction Rules**

### **Before submission, input artifacts shall pass through a redaction layer.**

### **Redaction rules shall include:**

### **remove secrets**

### **remove protected internal values**

### **avoid transmitting irrelevant sensitive data**

### **summarize or omit data that exceeds safety or necessity boundaries**

### **preserve enough meaning for planning quality**

### **Redaction must be consistent and logged at the artifact level where useful.**

### 

### **27.5 File Manifest Rules**

### **If files are included as part of the input package, a manifest must be generated.**

### **The manifest should include:**

### **file identifier**

### **file type**

### **source category**

### **purpose of inclusion**

### **redaction status**

### **attachment status**

### **size or payload note where useful**

### **This supports review and reproducibility.**

### 

### **27.6 Large Payload Handling Rules**

### **The input preparation system shall handle large payloads intentionally.**

### **Large payload handling may include:**

### **summarization**

### **chunking**

### **prioritization**

### **omission of low-value records**

### **attachment substitution where supported**

### **fallback to reduced-context mode when needed**

### **The system must avoid unbounded prompt assembly.**

### 

### **27.7 Structured Input Schema**

### **The plugin should use an internal structured schema for the prepared AI input.**

### **This schema may include:**

### **brand profile object**

### **business profile object**

### **competitor object array**

### **audience/persona array**

### **service/product array**

### **crawl summary object**

### **template registry summary object**

### **page-template registry object**

### **planning request metadata**

### **constraint object**

### **A structured internal schema supports auditing and future provider flexibility.**

### 

### **27.8 Input Validation Before Submission**

### **Prepared inputs must be validated before the provider call is made.**

### **Validation may include:**

### **required context presence**

### **provider readiness**

### **schema completeness**

### **redaction success**

### **attachment readiness where used**

### **prompt-pack compatibility**

### **If input preparation fails validation, the AI run must not proceed silently.**

### 

### **27.9 Submission Logging Rules**

### **Each submission must generate a submission log.**

### **Submission logging shall include:**

### **submission timestamp**

### **user who initiated the run**

### **provider/model selection**

### **prompt-pack version**

### **input snapshot reference**

### **validation result**

### **submission status**

### **retry status if applicable**

### **This log must exist even if the provider call later fails.**

### 

### **27.10 Downloadable Input Artifact Rules**

### **Users with appropriate permissions should be able to access a downloadable representation of the input artifact set.**

### **Downloadable input artifacts may include:**

### **normalized input JSON or equivalent structured form**

### **file manifest**

### **prompt text**

### **provider/model metadata**

### **run identifier**

### **The downloadable artifact should support transparency and review.**

### 

## **28. AI Output Contract and Validation**

### **28.1 Output Philosophy**

### **AI output shall be treated as structured planning data. Human-readable explanation is allowed, but execution shall rely only on validated normalized objects.**

### **The provider response shall not be treated as executable until:**

### **raw output is stored\**

### **schema validation passes\**

### **normalization succeeds\**

### **local object references are resolved\**

### **a Build Plan is created from the normalized output\**

### 

### **28.2 Structured Schema Requirements**

### **The AI output schema shall be mandatory and versioned.**

### **Every valid planning response must include:**

### **schema_version\**

### **run_summary\**

### **site_purpose\**

### **site_structure\**

### **existing_page_changes\**

### **new_pages_to_create\**

### **menu_change_plan\**

### **design_token_recommendations\**

### **seo_recommendations\**

### **warnings\**

### **assumptions\**

### **confidence\**

### **All top-level keys shall always exist. Empty arrays are permitted where no recommendations exist.**

### 

### **28.3 Required Top-Level Sections**

### **The following top-level sections are required:**

#### **schema_version**

### **String. Must match a locally supported AI output schema version.**

#### **run_summary**

### **Object containing:**

### **summary_text (required)\**

### **planning_mode (required enum: new_site, restructure_existing_site, mixed)\**

### **overall_confidence (required enum: high, medium, low)\**

#### **site_purpose**

### **Object containing:**

### **purpose_statement\**

### **primary_conversion_goal\**

### **primary_audiences\**

### **site_flow_summary\**

#### **site_structure**

### **Object containing:**

### **recommended_top_level_pages\**

### **hierarchy_map\**

### **navigation_summary\**

#### **existing_page_changes**

### **Array of existing-page-change objects.**

#### **new_pages_to_create**

### **Array of new-page objects.**

#### **menu_change_plan**

### **Array of menu-change objects.**

#### **design_token_recommendations**

### **Array of token recommendation objects.**

#### **seo_recommendations**

### **Array of SEO recommendation objects.**

#### **warnings**

### **Array of warning objects.**

#### **assumptions**

### **Array of assumption objects.**

#### **confidence**

### **Object containing:**

### **site_structure_confidence\**

### **existing_page_change_confidence\**

### **new_page_confidence\**

### **navigation_confidence\**

### 

### **28.4 Existing Page Change Object Schema**

### **Each existing_page_change object shall contain:**

### **current_page_url (required)\**

### **current_page_title (required)\**

### **action (required enum: keep, replace_with_new_page, rebuild_from_template, merge_and_archive, defer)\**

### **reason (required)\**

### **target_page_title (required unless action=keep)\**

### **target_slug (required unless action=keep)\**

### **target_template_key (required unless action=keep)\**

### **parent_target_url (nullable)\**

### **child_target_urls (array, may be empty)\**

### **section_guidance (array of structured section instructions)\**

### **risk_level (required enum: low, medium, high)\**

### **dependencies (array)\**

### **warnings (array)\**

### **confidence (required enum: high, medium, low)\**

### **Each section_guidance item shall contain:**

### **section_key\**

### **intent\**

### **content_direction\**

### **must_include\**

### **must_avoid\**

### 

### **28.5 New Page Object Schema**

### **Each new_pages_to_create object shall contain:**

### **proposed_page_title (required)\**

### **proposed_slug (required)\**

### **purpose (required)\**

### **template_key (required)\**

### **parent_target_url (nullable)\**

### **child_target_urls (array, may be empty)\**

### **menu_eligible (required boolean)\**

### **page_type (required enum: hub, detail, faq, pricing, request, location, service, other)\**

### **section_guidance (required array)\**

### **dependencies (array)\**

### **warnings (array)\**

### **confidence (required enum: high, medium, low)\**

### 

### **28.6 Hierarchy Object Schema**

### **The site_structure.hierarchy_map object shall contain an array of nodes.**

### **Each node shall contain:**

### **page_url_or_slug\**

### **page_role (enum: top_level, child, grandchild, leaf)\**

### **parent_url_or_slug (nullable)\**

### **children (array)\**

### **hierarchy_reason\**

### **The hierarchy map must be internally consistent. Cycles are invalid.**

### 

### **28.7 Menu Change Object Schema**

### **Each menu_change_plan object shall contain:**

### **menu_context (required enum: header, footer, mobile, off_canvas, sidebar)\**

### **action (required enum: create, rename, replace, update_existing)\**

### **current_menu_name (nullable)\**

### **proposed_menu_name (required)\**

### **items (required array)\**

### **Each items record shall contain:**

### **label\**

### **target_page_title_or_url\**

### **parent_label (nullable)\**

### **position\**

### **action (enum: add, remove, rename, move, keep)\**

### 

### **28.8 Design Token Recommendation Schema**

### **Each design_token_recommendation object shall contain:**

### **token_group (required enum: color, typography, spacing, radius, shadow, component)\**

### **token_name (required)\**

### **current_value (nullable)\**

### **proposed_value (required)\**

### **rationale (required)\**

### **confidence (required enum: high, medium, low)\**

### 

### **28.9 SEO Recommendation Schema**

### **Each seo_recommendation object shall contain:**

### **target_page_title_or_url (required)\**

### **title_recommendation (nullable)\**

### **meta_description_recommendation (nullable)\**

### **schema_type_recommendation (nullable)\**

### **internal_link_recommendations (array)\**

### **notes (array)\**

### **confidence (required enum: high, medium, low)\**

### 

### **28.10 Warning / Assumption / Confidence Schema**

### **Each warning or assumption object shall contain:**

### **category\**

### **message\**

### **affected_scope\**

### **severity (for warnings only: low, medium, high)\**

### **The confidence object shall only use the enum values high, medium, or low.**

### 

### **28.11 Validation Pipeline**

### **Validation shall occur in the following order:**

### **raw response capture\**

### **parse attempt\**

### **top-level schema check\**

### **object-shape validation\**

### **enum validation\**

### **required-field validation\**

### **internal-reference validation\**

### **local-target resolution where applicable\**

### **normalization into internal Build Plan structures\**

### **Any failure shall stop downstream plan creation unless handled under partial-output rules.**

### 

### **28.12 Invalid Output Recovery Rules**

### **If output is invalid:**

### **the raw response shall be preserved\**

### **the run shall be marked validation_failed\**

### **one automated repair attempt may be made using a schema-repair prompt\**

### **if repair fails, the run shall not generate a Build Plan\**

### **the user shall receive an actionable validation-failure message\**

### **No invalid output shall enter the executor.**

### 

### **28.13 Partial Output Handling Rules**

### **Partial output may be accepted only if:**

### **all top-level required sections exist\**

### **the invalidity is limited to item-level records\**

### **invalid records can be removed without corrupting global plan structure\**

### **Dropped records shall be logged and surfaced in the Build Plan as omitted recommendations.**

### 

### **28.14 Raw vs Normalized Output Rules**

### **The system shall store:**

### **raw provider response\**

### **validation report\**

### **normalized output\**

### **dropped-record report if partial handling occurs\**

### **Only normalized output may be used to generate a Build Plan.**

### 

## **29. AI Artifact Storage and Retrieval**

### **29.1 Artifact Categories**

### **AI artifacts shall be stored in clearly categorized forms.**

### **Artifact categories may include:**

### **raw prompt**

### **normalized prompt package**

### **input snapshot**

### **file manifest**

### **raw provider response**

### **normalized output**

### **validation report**

### **retry records**

### **provider usage metadata**

### **exportable artifact bundle**

### **Categories must remain distinct enough for support and export workflows.**

### 

### **29.2 Raw Prompt Storage**

### **The exact prompt payload sent to the provider shall be stored, subject to redaction policy.**

### **Raw prompt storage shall support:**

### **auditability**

### **support review**

### **debugging of planning quality**

### **comparison across prompt-pack versions**

### **export for advanced user review**

### **Raw prompt storage must not expose secrets or prohibited data classes.**

### 

### **29.3 Raw Provider Response Storage**

### **The raw provider response shall be stored for traceability.**

### **This supports:**

### **schema failure diagnosis**

### **provider-behavior analysis**

### **normalization debugging**

### **run review and support**

### **Raw provider response must be stored separately from normalized output.**

### 

### **29.4 Normalized Output Storage**

### **The normalized output shall be stored as the system’s validated planning representation.**

### **Normalized output storage shall support:**

### **build-plan generation**

### **later review**

### **export**

### **change comparison**

### **reproducibility**

### **This is the practical planning artifact used by downstream systems.**

### 

### **29.5 File Attachment Storage**

### **If files were attached or generated as part of the AI run, their metadata and storage references must be preserved.**

### **This may include:**

### **source file references**

### **attached file copies or pointers**

### **redaction state**

### **inclusion rationale**

### **download eligibility**

### **File artifact storage must remain organized and permissioned.**

### 

### **29.6 Run Metadata Storage**

### **Each AI run shall have structured metadata.**

### **This may include:**

### **run identifier**

### **actor reference**

### **timestamp**

### **provider**

### **model**

### **prompt-pack version**

### **validation result**

### **retry count**

### **completion state**

### **build-plan reference if generated**

### **Run metadata is essential for auditing and support.**

### 

### **29.7 Cost and Usage Tracking**

### **Where available and practical, the plugin may store usage or cost-related metadata.**

### **This may include:**

### **token counts**

### **approximate cost**

### **request counts**

### **attachment counts**

### **provider-reported usage metrics**

### **This supports transparency and operational awareness, especially for users paying provider costs directly.**

### 

### **29.8 Artifact Access Permissions**

### **Not every user should be able to view raw AI artifacts.**

### **Access permissions shall distinguish between:**

### **users who can run planning**

### **users who can review build plans**

### **users who can view raw prompts/responses**

### **users who can export artifact bundles**

### **users who can see provider metadata**

### **Artifact access must be capability-controlled.**

### 

### **29.9 Artifact Download Rules**

### **Artifact downloads shall be available only where explicitly allowed.**

### **Download rules shall include:**

### **permission check**

### **bundle generation**

### **redaction enforcement**

### **audit logging of download action**

### **structured archive format**

### **clear naming and version references**

### **Downloads should support transparency without weakening security.**

### 

### **29.10 Artifact Retention Rules**

### **AI artifacts shall be governed by retention policy.**

### **Retention rules may include:**

### **keep until user deletion**

### **keep for a defined operational window**

### **archive versus active distinction**

### **protect build-plan-linked artifacts longer than failed ephemeral runs**

### **support export before cleanup where relevant**

### **Retention must balance operational usefulness and data sprawl.**

### 

### **29.11 Artifact Redaction Rules**

### **Artifacts must obey redaction rules both at storage and at export time.**

### **Redaction rules shall include:**

### **no secrets in stored user-facing views**

### **no secrets in downloadable bundles unless explicitly designed and authorized**

### **suppression of prohibited data classes**

### **masking of sensitive values where needed**

### **consistency between storage policy and export policy**

### **Redaction is required for both trust and security.**

### 

## **30. Build Plan Engine**

### **30.1 Purpose of the Build Plan**

### **The build plan is the operational bridge between validated planning output and approved site change execution.**

### **Its purpose is to:**

### **organize recommendations into actionable steps**

### **keep planning visible**

### **allow review and decision-making**

### **separate proposed changes from executed changes**

### **make site transformation manageable rather than chaotic**

### **The build plan is one of the core product features, not just a summary screen.**

### 

### **30.2 Build Plan Inputs**

### **Build plans shall be generated from controlled inputs.**

### **These inputs may include:**

### **normalized AI output**

### **current site context**

### **template registry references**

### **composition references**

### **crawl snapshot references**

### **token state references**

### **compatibility rules**

### **local system validation results**

### **The build plan must be built from validated data, not raw unverified output.**

### 

### **30.3 Build Plan Generation Rules**

### **The build-plan engine shall convert validated recommendations into a structured operational plan.**

### **Generation rules shall include:**

### **grouping actions by workflow type**

### **associating actions with related pages or menus**

### **attaching rationale where useful**

### **attaching warnings and confidence notes**

### **deriving executable actions only where enough data exists**

### **omitting or flagging non-actionable recommendations**

### **The resulting plan should be structured, legible, and actionable.**

### 

### **30.4 Build Plan Status Model**

### **Each build plan shall have a status model.**

### **Possible statuses may include:**

### **draft**

### **generated**

### **pending review**

### **partially reviewed**

### **partially executed**

### **completed**

### **failed**

### **archived**

### **superseded**

### **Status must reflect real operational state and support resumption.**

### 

### **30.5 Step-Based UI Structure**

### **The build plan shall be presented through a step-based UI model.**

### **Core steps may include:**

### **existing page updates**

### **new page creation**

### **menu/navigation changes**

### **design-token review**

### **SEO/media review**

### **review/publish**

### **logs/rollback**

### **Step-based structure helps users move through complex change sets in a controlled way.**

### 

### **30.6 Persistent Purpose / Flow Sidebar**

### **The build plan UI shall maintain visible context about why the plan exists and what it is trying to accomplish.**

### **The persistent context area may include:**

### **site purpose summary**

### **site flow explanation**

### **plan metadata**

### **run identifier**

### **warnings summary**

### **counts remaining**

### **current progress**

### **Context should stay visible so users do not lose the strategic reason behind the actions.**

### 

### **30.7 Remaining Changes Logic**

### **The build plan shall track which proposed changes remain unresolved.**

### **Remaining-change logic shall include:**

### **identifying approved but not executed actions**

### **identifying denied actions**

### **identifying completed actions**

### **filtering to unresolved items when a plan is reopened**

### **supporting partial completion over time**

### **This makes the plan reusable instead of one-time-only.**

### 

### **30.8 Reopen and Resume Logic**

### **Users shall be able to reopen a build plan and resume from its current state.**

### **Resume behavior shall include:**

### **preserving prior decisions**

### **preserving execution history**

### **showing remaining actions**

### **not rerunning AI planning automatically**

### **supporting a return to any relevant step where unresolved actions remain**

### **Build plans should function as persistent project objects, not transient screens.**

### 

### **30.9 Bulk Action Logic**

### **The plan engine shall support bulk actions where appropriate.**

### **Bulk logic may include:**

### **approve all**

### **deny all**

### **execute all selected**

### **execute all remaining in a step**

### **reject token groups in bulk**

### **apply selected navigation changes together**

### **Bulk actions must still obey permissions, confirmations, and logging rules.**

### 

### **30.10 Individual Action Logic**

### **Users must also be able to act on individual recommendations.**

### **Individual logic shall include:**

### **inspect one page at a time**

### **inspect rationale**

### **approve or deny specific actions**

### **execute individual page builds**

### **apply or skip individual menu changes**

### **review per-item warnings**

### **The system should support precision, not force all-or-nothing decisions.**

### 

### **30.11 Confirmation and Denial Logic**

### **The build plan must support explicit decisions.**

### **Decision logic shall include:**

### **approved status**

### **denied status**

### **reversible pre-execution decision state where appropriate**

### **confirmation UI for impactful actions**

### **clear distinction between “approved for execution” and “already executed”**

### **Denial is not failure; it is a legitimate planning outcome and must be recorded as such.**

### 

### **30.12 Final Completion State Logic**

### **The build plan shall support a meaningful completion state.**

### **Completion state logic shall include:**

### **recognition when all actionable items are resolved**

### **recognition when only denied items remain**

### **recognition when only non-executable informational items remain**

### **user-facing completion messaging**

### **retention of plan history for future review**

### **A completed plan should remain a readable historical record, not disappear once done.**

## **31. Build Plan User Interface Specification**

### **31.1 Information Architecture**

### **The Build Plan UI shall be a three-zone interface:**

### **left persistent context rail\**

### **top stepper and plan controls\**

### **main content workspace\**

### **The left rail shall always remain visible on desktop-width admin screens and shall collapse into a persistent summary drawer on narrower layouts.**

### 

### **31.2 Navigation Model**

### **The stepper shall include exactly these steps in order:**

### **Existing Page Updates\**

### **New Page Creation\**

### **Menus and Navigation\**

### **Design Tokens and Branding\**

### **SEO, Meta, and Media\**

### **Review, Publish, and Finalization\**

### **Logs, History, and Rollback\**

### **Users may jump backward freely.\
Users may jump forward only to steps that are not blocked by unresolved required predecessors.**

### 

### **31.3 Stepper Layout**

### **Each step shall display:**

### **step number\**

### **step title\**

### **status badge (not_started, in_progress, blocked, complete, error)\**

### **unresolved item count\**

### **The active step shall remain visually distinct.**

### 

### **31.4 Always-Visible Context Panel**

### **The context panel shall contain:**

### **plan title\**

### **plan ID\**

### **source AI run ID\**

### **plan status\**

### **site purpose summary\**

### **site flow summary\**

### **unresolved item counts by step\**

### **critical warnings summary\**

### **primary actions:\**

### **save and exit\**

### **export plan\**

### **view source artifacts\**

### 

### **31.5 Table and Detail View Patterns**

### **Every step that lists actionable items shall use:**

### **a table/grid list for overview\**

### **a right-side detail drawer or lower detail panel for item drill-down\**

### **Table columns shall be fixed per step and sortable only where sorting is meaningful.**

### 

### **31.6 Bulk Action UI Patterns**

### **Bulk-action controls shall appear above the item list and include:**

### **apply to all eligible\**

### **apply to selected\**

### **deny all eligible\**

### **clear selection\**

### **Bulk actions shall remain disabled when no eligible rows are present.**

### 

### **31.7 Individual Action UI Patterns**

### **Every row shall support row-level actions, typically:**

### **view detail\**

### **approve\**

### **deny\**

### **execute\**

### **retry\**

### **view diff\**

### **view dependencies\**

### **Only actions valid for the current row state shall appear enabled.**

### 

### **31.8 Status Messaging Patterns**

### **Status messages shall appear at three levels:**

### **global plan level\**

### **step level\**

### **row/item level\**

### **Messages shall include a clear severity style and plain-language explanation.**

### 

### **31.9 Error Display Patterns**

### **Errors shall appear inline with the affected object, and severe step-level errors shall also appear in the step header.**

### **Every error state shall include:**

### **summary\**

### **related object\**

### **retry eligibility\**

### **log reference for authorized users\**

### 

### **31.10 Progress Tracking Patterns**

### **The Build Plan shall display:**

### **total plan completion percentage\**

### **step completion percentage\**

### **queued/running job count\**

### **failed item count\**

### **approved-not-yet-executed count\**

### 

### **31.11 Empty State Patterns**

### **Each step shall define one of these exact empty-state messages:**

### **No recommendations were generated for this step.\**

### **All recommendations in this step have already been resolved.\**

### **This step is blocked until earlier required actions are completed.\**

### **No blank lists without explanatory text are permitted.**

### 

### **31.12 Completion State Patterns**

### **When the plan is complete, the UI shall display:**

### **completion banner\**

### **counts of executed actions\**

### **counts of denied actions\**

### **counts of failed actions\**

### **link to logs/history\**

### **link to export the final plan record**

### 

## **32. Step 1: Existing Page Update Workflow**

### **32.1 Scope of Existing Page Updates**

### **This step covers recommendations related to meaningful public pages that already exist and that the planning system believes should be updated, replaced, restructured, or otherwise materially changed.**

### **It shall not be used for:**

### **new pages that do not yet exist**

### **navigation-only changes**

### **token-only changes**

### **informational recommendations with no existing-page impact**

### **This step is for site-change decisions affecting current page inventory.**

### 

### **32.2 Page Selection Rules**

### **Only pages meeting the plan’s existing-page update criteria shall appear in this step.**

### **Selection may consider:**

### **meaningful public pages discovered in crawl analysis**

### **pages explicitly identified in AI recommendations**

### **pages that can be mapped reliably to current local objects**

### **pages whose update action is understandable and reviewable**

### **Pages should not appear in this step if the system cannot identify them with sufficient confidence.**

### 

### **32.3 Update Recommendation Presentation**

### **Each existing-page recommendation shall be displayed with enough summary information for a quick scan.**

### **Presentation shall include:**

### **current page title**

### **current URL or slug**

### **suggested action type**

### **target template or composition**

### **summary reason for the recommendation**

### **risk or warning indicator**

### **current status**

### **Users must be able to understand what is being proposed before opening the detail view.**

### 

### **32.4 Detail Panel Requirements**

### **The detail panel for an existing-page update shall include:**

### **current page identity**

### **suggested action description**

### **rationale for change**

### **proposed replacement or rebuild outcome**

### **target title and slug**

### **hierarchy implications**

### **parent/child notes where relevant**

### **section-level content instructions or high-level content notes**

### **warnings, assumptions, and dependencies**

### **available actions**

### **The detail panel should support confident approval or denial.**

### 

### **32.5 Accept / Deny Action Rules**

### **Each existing-page recommendation shall support explicit approval or denial.**

### **Approval shall mean:**

### **the recommendation is authorized for later execution or immediate execution depending on workflow design**

### **Denial shall mean:**

### **the recommendation is intentionally rejected and removed from the unresolved queue for that step**

### **Accept and deny actions must be logged and should remain visible in plan history.**

### 

### **32.6 Make All Updates Rules**

### **Where allowed, the step may provide a “Make All Updates” action for all unresolved eligible page-update recommendations.**

### **This action shall:**

### **require appropriate permissions**

### **make the scope of the action clear**

### **require confirmation**

### **respect dependency validation**

### **queue or execute the actions through the normal executor**

### **Bulk-update approval must not bypass safety checks.**

### 

### **32.7 Deny All Updates Rules**

### **Where allowed, the step may provide a “Deny All Updates” action for unresolved existing-page recommendations.**

### **This action shall:**

### **require confirmation**

### **mark all unresolved page updates as denied**

### **preserve that state in the Build Plan record**

### **not execute any page mutations**

### **Bulk denial is a legitimate planning outcome and must remain recorded.**

### 

### **32.8 Mutual Exclusivity Rules**

### **When the UI offers broad opposing actions such as “Make All Updates” and “Deny All Updates,” the system shall prevent contradictory bulk state application in the same immediate action cycle.**

### **Mutual exclusivity rules shall ensure:**

### **once one bulk direction has been committed for the current review pass, the conflicting bulk action is disabled or requires reset/reopen behavior as defined**

### **users cannot accidentally trigger contradictory batch states at once**

### **individual item decisions remain traceable**

### **This reduces chaotic mass-state changes.**

### 

### **32.9 Snapshot Before Update Rules**

### **Before a page update action performs a meaningful mutation, the system shall capture a pre-change snapshot where supported.**

### **The snapshot shall include:**

### **current title**

### **current slug**

### **current status**

### **parent relationship**

### **page content reference**

### **relevant metadata**

### **relevant template or orchestration references**

### **This snapshot supports diffing, auditing, and rollback logic.**

### 

### **32.10 Existing Page Rename / Slug Change Rules**

### **Where the approved workflow involves replacing an existing page, the system may rename the existing page and modify its slug before creating a replacement page.**

### **These changes shall:**

### **be intentional and logged**

### **preserve traceability**

### **avoid creating ambiguous collisions**

### **happen in a predictable order**

### **respect WordPress slug uniqueness rules**

### **The workflow must make the old-versus-new page relationship understandable.**

### 

### **32.11 Private / Archived Handling Rules**

### **When an existing page is being replaced rather than directly overwritten, the original page may be moved into a non-public state.**

### **This may include:**

### **private status**

### **draft/archived equivalent handling**

### **explicit labeling as replaced**

### **internal notes or provenance metadata**

### **The old page should not disappear without trace. It should remain recoverable within the workflow’s retention rules.**

### 

### **32.12 New Replacement Page Creation Rules**

### **When a replacement page is created, the system shall:**

### **create the new page using the approved template or composition**

### **apply approved title and slug**

### **assign hierarchy where applicable**

### **build content structure**

### **attach relevant orchestration metadata**

### **record linkage to the replaced page**

### **Replacement creation must be deterministic and logged.**

### 

### **32.13 Success / Failure Reporting Rules**

### **After an existing-page update action runs, the system shall report:**

### **whether the action succeeded**

### **what was changed**

### **whether warnings occurred**

### **whether rollback is available**

### **what failed if the action did not fully succeed**

### **Users must not have to inspect raw logs just to learn whether a page update worked.**

### 

## **33. Step 2: New Page Creation Workflow**

### **33.1 Scope of New Page Builds**

### **This step covers recommendations to create pages that do not yet exist in the current site structure and that are proposed as part of the recommended site plan.**

### **It does not include:**

### **updates to existing pages**

### **menu-only changes**

### **design-token-only changes**

### **informational-only recommendations**

### **This is the planned page-creation step.**

### 

### **33.2 Suggested Page List Rules**

### **The new-page step shall list only pages that meet the plan’s criteria for actionable page creation.**

### **Each suggested page shall have enough information to be understandable before action is taken.**

### **The list may exclude:**

### **pages lacking sufficient structured planning detail**

### **pages blocked by unresolved dependencies**

### **pages already created and resolved in the current plan state**

### 

### **33.3 Page Metadata Display Requirements**

### **Each proposed new page shall display relevant metadata.**

### **This may include:**

### **proposed title**

### **proposed slug**

### **purpose**

### **target page template or composition**

### **hierarchy position**

### **page-type label**

### **warnings or confidence notes**

### **current state in the Build Plan**

### **This allows the user to compare many planned pages quickly.**

### 

### **33.4 Parent / Child Display Requirements**

### **Where hierarchy is part of the recommendation, the page row or detail view shall show:**

### **intended parent**

### **intended child pages where relevant**

### **whether hierarchy dependency must be resolved first**

### **whether the page is a hub, branch, or leaf recommendation**

### **Hierarchy visibility is important because new page creation is often structural, not isolated.**

### 

### **33.5 Build Page Action Rules**

### **Each proposed new page shall support an individual build action where permissions allow.**

### **Build rules shall include:**

### **validation before build**

### **page creation through the approved template/composition pathway**

### **orchestration metadata assignment**

### **hierarchy assignment where appropriate**

### **status feedback**

### **logging of the action**

### **Individual page building should allow focused execution without requiring whole-plan commitment.**

### 

### **33.6 Build All Pages Rules**

### **Where allowed, the step may provide a “Build All Pages” action for all unresolved eligible new-page recommendations.**

### **This action shall:**

### **require confirmation**

### **validate dependencies**

### **queue or execute builds through the normal execution engine**

### **preserve per-item outcome reporting**

### **Bulk build must remain observable and recoverable.**

### 

### **33.7 Build Selected Pages Rules**

### **The step may also support “Build Selected Pages” for a user-selected subset.**

### **This is useful where:**

### **some pages depend on review**

### **some pages are strategically urgent**

### **users want to phase execution**

### **Selection-based building must still respect validation and logging.**

### 

### **33.8 Dependency Validation Rules**

### **Before a new page is built, the system shall validate dependencies.**

### **Dependencies may include:**

### **required parent page existence**

### **required template availability**

### **required composition validity**

### **required field-group derivation**

### **required token state where relevant**

### **absence of disqualifying slug collisions**

### **A page that fails dependency validation should be blocked with a clear reason.**

### 

### **33.9 Post-Build Status Rules**

### **After building a new page, the system shall update the page item’s state.**

### **Possible post-build states may include:**

### **built successfully**

### **built with warnings**

### **failed**

### **blocked after attempt**

### **requires follow-up review**

### **The Build Plan must preserve item-level status history.**

### 

### **33.10 Retry and Recovery Rules**

### **If a new-page build fails, the system shall support a governed retry or recovery pathway.**

### **This may include:**

### **correcting dependency issues**

### **retrying the build action**

### **reviewing logs**

### **confirming partial output cleanup or retention**

### **preserving the failure reason in the item history**

### **Retry should not create duplicate confusion or hidden side effects.**

### 

## **34. Step 3: Menu and Navigation Workflow**

### **34.1 Current vs Proposed Navigation Comparison**

### **The menu workflow shall present a comparison between current site navigation and proposed site navigation.**

### **This comparison should support:**

### **structural clarity**

### **visibility into additions, removals, reordering, and renaming**

### **relationship to newly created or updated pages**

### **user understanding of why the change is recommended**

### **Navigation changes must be reviewable as a system, not only as isolated menu items.**

### 

### **34.2 Header / Footer / Mobile / Off-Canvas Scope**

### **The plugin shall account for multiple navigation contexts where relevant.**

### **Scope may include:**

### **header menus**

### **footer menus**

### **mobile-only navigation**

### **off-canvas navigation**

### **sidebar navigation where applicable**

### **Not every site will use every menu context, but the system should model them where they matter.**

### 

### **34.3 Menu Difference Detection Rules**

### **The system shall detect differences between current and proposed navigation structures.**

### **Difference detection may include:**

### **missing pages in navigation**

### **outdated menu labels**

### **ordering differences**

### **hierarchy differences**

### **duplicate navigation exposure**

### **location-assignment differences**

### **Difference detection should support user review and safe application.**

### 

### **34.4 Menu Rename Rules**

### **When menu names themselves are proposed to change, the system shall treat those changes explicitly.**

### **Rename rules shall include:**

### **preserve reference to the original menu**

### **show the proposed new name**

### **log the rename action**

### **avoid ambiguous overwrites where multiple menus have similar names**

### **Menu rename should be intentional and inspectable.**

### 

### **34.5 Menu Creation Rules**

### **The system shall support creation of new menus where the proposed structure requires them.**

### **Menu creation rules shall include:**

### **menu naming**

### **menu context assignment**

### **item population**

### **relationship to page hierarchy**

### **creation logging**

### **conflict handling if a similar menu already exists**

### **New menus must be first-class change actions, not hidden side effects.**

### 

### **34.6 Menu Item Assignment Rules**

### **Menu item assignment shall follow structured recommendations.**

### **Assignment rules may include:**

### **link target page**

### **label**

### **order**

### **nesting or submenu relationships**

### **contextual visibility by menu type**

### **inclusion/exclusion decisions based on plan logic**

### **Assignments must support both individual and bulk navigation updates.**

### 

### **34.7 Menu Location Assignment Rules**

### **Where the site uses theme menu locations, the system shall support mapping the proposed menus to those locations.**

### **Location assignment rules shall include:**

### **awareness of registered menu locations**

### **ability to reassign a menu to a location**

### **preservation of existing location assignments until the approved change is applied**

### **validation that a proposed location exists in the current theme environment**

### **Location assignment must respect the active theme environment.**

### 

### **34.8 Accept / Deny Change Rules**

### **Each proposed navigation change shall support review, approval, or denial as appropriate.**

### **The system shall allow:**

### **per-item decision-making**

### **grouped approval where related**

### **denial of items that the user does not wish to apply**

### **preservation of denied state in plan history**

### **Navigation changes must be governed like other plan actions.**

### 

### **34.9 Bulk Apply Rules**

### **Where appropriate, the navigation step may support bulk apply behavior.**

### **Bulk apply rules shall include:**

### **clear scope of affected menus**

### **confirmation**

### **validation of page references and menu locations**

### **execution logging**

### **post-apply status reporting**

### **Bulk apply should remain safe and reversible where supported.**

### 

### **34.10 Navigation Validation Rules**

### **Before applying navigation changes, the system shall validate:**

### **referenced pages exist**

### **parent/child relationships are not contradictory**

### **theme locations are available if needed**

### **menus being renamed or replaced are identifiable**

### **proposed nesting is coherent**

### **Navigation validation helps prevent broken or misleading menu states.**

### 

## **35. Step 4: Design Token and Branding Workflow**

### **35.1 Token Recommendation Review**

### **This step shall present proposed design-token changes in a structured review format.**

### **The review must show:**

### **what token is being changed**

### **the current value**

### **the proposed value**

### **the role of that token**

### **rationale or context where useful**

### **confidence or warning markers where relevant**

### **Users should be able to review token recommendations strategically, not as a random list of values.**

### 

### **35.2 Current vs Proposed Token Comparison**

### **The UI shall support side-by-side or equivalent comparison between current active tokens and proposed token values.**

### **Comparisons may include:**

### **color role comparison**

### **typography comparison**

### **spacing scale comparison**

### **component variant comparison**

### **surface treatment comparison**

### **Comparison must make change impact legible before application.**

### 

### **35.3 Accept / Reject Token Groups**

### **Users shall be able to accept or reject token changes either individually or in logical groups.**

### **Token groups may include:**

### **colors**

### **typography**

### **spacing**

### **components**

### **surfaces**

### **buttons/forms/cards**

### **Grouping helps users make meaningful branding decisions without excessive micro-clicking.**

### 

### **35.4 Live Preview Rules**

### **Where practical, the workflow may support preview behavior for token changes.**

### **Preview rules shall include:**

### **preview must not silently overwrite live active tokens**

### **preview state must be clearly indicated as preview**

### **preview must remain scoped and reversible**

### **preview failures must not damage current styling state**

### **Preview is desirable, but must remain operationally safe.**

### 

### **35.5 Manual Override Rules**

### **Users shall be able to override proposed token values where permissions allow.**

### **Manual override rules shall include:**

### **editable value inputs**

### **user action tracking**

### **distinction between AI recommendation and user-chosen value**

### **validation of token format where applicable**

### **preservation of the override in active token state**

### **Human control remains authoritative in branding decisions.**

### 

### **35.6 Brand Asset Impact Rules**

### **If token recommendations are tied to uploaded assets or brand references, the system should make that relationship visible where helpful.**

### **This may include:**

### **showing the asset source of a derived color**

### **indicating that typography logic aligns with stated brand direction**

### **explaining whether a token suggestion was influenced by current site visuals or profile inputs**

### **Asset influence should be explanatory, not mysterious.**

### 

### **35.7 Generated CSS Update Rules**

### **When token changes are accepted, the plugin shall update the generated token-driven styling output through the approved stylesheet pipeline.**

### **Update rules shall include:**

### **deterministic regeneration**

### **versioning or cache-busting where needed**

### **preservation of fixed selector contracts**

### **logging of the token update event**

### **error handling if regeneration fails**

### **Token application must be traceable and safe.**

### 

### **35.8 Safe Revert Rules**

### **The token workflow shall support safe reversion to a prior active token state where retained.**

### **Revert rules shall include:**

### **identifiable prior token set**

### **confirmation before revert**

### **regeneration of token-driven output**

### **logging of revert action**

### **clear user feedback on success or failure**

### **Brand changes should not be one-way traps.**

### 

## **36. Step 5: SEO, Meta, and Media Workflow**

### **36.1 SEO Recommendation Scope**

### **This step shall process only these recommendation classes:**

### **title recommendations\**

### **meta description recommendations\**

### **schema type recommendations\**

### **featured-image/media direction recommendations\**

### **internal-link recommendations\**

### **It shall not create net-new pages or change navigation.**

### 

### **36.2 Metadata Review Rules**

### **Each metadata row shall include:**

### **target page\**

### **current value\**

### **proposed value\**

### **action type\**

### **rationale\**

### **confidence\**

### **accept/deny controls\**

### **Title and meta changes shall be independently reviewable.**

### 

### **36.3 Featured Image / Media Guidance Rules**

### **Featured-image/media guidance shall be displayed as structured recommendation text only unless a specific media integration is enabled.**

### **Media recommendations shall not auto-source images. They shall remain guidance or mapped-media actions only where explicitly supported later.**

### 

### **36.4 Schema Suggestion Rules**

### **Schema suggestions shall remain recommendation-only unless the site has an explicitly supported schema integration pathway configured.**

### **Accepted schema suggestions shall be stored in plan history even if not auto-applied.**

### 

### **36.5 Internal Linking Recommendation Rules**

### **Internal-link recommendations shall include:**

### **source page\**

### **target page\**

### **suggested anchor/context note\**

### **rationale\**

### **These recommendations may be accepted, denied, or deferred.\
They shall not auto-edit post body content in the current product scope unless later approved as a separate execution feature.**

### 

### **36.6 Plugin Interoperability Rules**

### **If an SEO plugin integration is active, accepted metadata may be written through that integration layer.\
If no supported SEO integration is active, accepted metadata shall be stored in the plugin’s own metadata layer or as queued advisory data, depending on implementation.**

### **The system must always show which storage path was used.**

### 

### **36.7 Accept / Reject Workflow**

### **Users shall be able to:**

### **accept all metadata items for one page\**

### **accept all selected items\**

### **deny individual items\**

### **deny all unresolved items in the step\**

### **Accepted items enter the execution queue only after passing validation.**

### 

### **36.8 Save and Apply Rules**

### **Applying accepted items shall:**

### **validate target page existence\**

### **validate field lengths/format where applicable\**

### **write values through the correct integration/storage path\**

### **log each action\**

### **update row and step status\**

### **Partial application shall be reported as partial success, not total success.**

### 

## **37. Step 6: Review, Publish, and Finalization Workflow**

### **37.1 Draft Review Rules**

### **This step shall show all pending publishable outputs, including:**

### **newly built draft pages\**

### **replacement pages awaiting publish\**

### **hierarchy changes awaiting final commit\**

### **token sets awaiting final application if not already applied\**

### **Each row shall show:**

### **target object\**

### **current state\**

### **blockers\**

### **preview link where applicable\**

### **finalization action\**

### 

### **37.2 Pending Change Queue**

### **The pending queue shall be split into:**

### **publish-ready\**

### **blocked\**

### **failed\**

### **deferred\**

### **Only publish-ready items may be finalized.**

### 

### **37.3 Publish Readiness Checks**

### **Before finalization, each page must pass:**

### **page exists\**

### **slug valid\**

### **parent valid\**

### **required content exists\**

### **no unresolved critical execution failures\**

### **no unresolved critical dependency flags\**

### **If any check fails, the row remains blocked.**

### 

### **37.4 Conflict Detection Rules**

### **The system shall detect:**

### **slug conflicts\**

### **hierarchy conflicts\**

### **page already published under same target role\**

### **menu references to missing pages\**

### **publish target already superseded by a newer plan action\**

### **Conflicts shall block finalization until resolved or denied.**

### 

### **37.5 Final Approval Rules**

### **Final approval shall require:**

### **item in approved state\**

### **all blocking validations passed\**

### **user has execution and finalization permission\**

### **A finalization confirmation modal shall state whether the action publishes, swaps, or only marks completed.**

### 

### **37.6 Publication Rules**

### **If a page is finalized to public:**

### **its status shall be set to publish\**

### **the replaced page, if any, shall remain in its archived/private replacement state\**

### **menu updates linked to that page may be executed in the same queue batch if approved\**

### **the action shall be logged with publish timestamp and actor\**

### 

### **37.7 Completion Reporting Rules**

### **When all finalizable items are resolved, the step shall report:**

### **number published\**

### **number completed without publication\**

### **number blocked\**

### **number denied\**

### **number failed\**

### **This summary becomes part of the permanent Build Plan record.**

### 

### 

## **38. Step 7: Logs, History, and Rollback Workflow**

### **38.1 Action History Scope**

### **This step shall list every material action tied to the Build Plan, including:**

### **approvals\**

### **denials\**

### **executions\**

### **retries\**

### **failures\**

### **finalizations\**

### **rollbacks\**

### 

### **38.2 Log Presentation Rules**

### **Logs shall be grouped by date and sortable by:**

### **action type\**

### **object type\**

### **status\**

### **actor\**

### **Each row shall include:**

### **timestamp\**

### **action\**

### **object\**

### **actor\**

### **result\**

### **“view detail” link\**

### 

### **38.3 Before / After Snapshot Rules**

### **For actions with snapshots, the detail view shall show:**

### **before summary\**

### **after summary\**

### **diff type\**

### **rollback availability\**

### **snapshot IDs\**

### 

### **38.4 Rollback Eligibility Rules**

### **An action is rollback-eligible only if all are true:**

### **pre-change snapshot exists\**

### **rollback handler exists for the action type\**

### **target object is still resolvable\**

### **no later action makes rollback unsafe without warning\**

### **user has rollback permission\**

### 

### **38.5 Rollback Execution Rules**

### **Rollback shall require:**

### **user confirmation\**

### **eligibility validation\**

### **queue insertion as a rollback job\**

### **post-rollback logging\**

### **update of Build Plan history\**

### **Rollback shall never execute as an inline silent mutation.**

### 

### **38.6 Rollback Failure Handling**

### **If rollback fails, the UI shall display:**

### **rollback attempt status\**

### **reason for failure\**

### **whether partial rollback occurred\**

### **log reference\**

### **recommendation for next action\**

### 

### **38.7 Audit Trail Requirements**

### **The audit trail shall be immutable except for retention cleanup under approved policy.**

### **Every audit record shall capture:**

### **actor\**

### **timestamp\**

### **action type\**

### **target\**

### **result\**

### **related job/run/plan IDs**

### 

## **39. Planner vs Executor Specification**

### **39.1 Planner Responsibilities**

### **The planner is responsible for producing structured recommendations and preparing them for user review.**

### **Planner responsibilities include:**

### **using onboarding and crawl context**

### **packaging context for AI**

### **receiving and validating AI output**

### **normalizing recommendations**

### **generating Build Plan structures**

### **exposing rationale, warnings, and assumptions**

### **The planner is an analysis-and-proposal system, not an execution actor.**

### 

### **39.2 Planner Output Boundaries**

### **The planner may output:**

### **recommended pages**

### **recommended changes**

### **recommended hierarchy**

### **recommended menus**

### **recommended token values**

### **recommended SEO/media guidance**

### **assumptions and warnings**

### **confidence indicators**

### **The planner may not directly perform site mutations solely by producing those outputs.**

### 

### **39.3 Executor Responsibilities**

### **The executor is responsible for carrying out approved changes.**

### **Executor responsibilities include:**

### **creating pages**

### **rebuilding pages**

### **replacing pages**

### **changing hierarchy**

### **updating menus**

### **applying token changes**

### **recording execution outcomes**

### **exposing errors and status**

### **The executor must work only from approved, validated action inputs.**

### 

### **39.4 Executor Input Requirements**

### **The executor shall accept only governed inputs.**

### **Accepted inputs shall require:**

### **valid action type**

### **valid target object reference**

### **validated dependencies**

### **permissioned user context or trusted scheduled job context**

### **Build Plan approval state where required**

### **safe execution preconditions**

### **The executor must reject malformed or unauthorized action requests.**

### 

### **39.5 No Direct-Mutation Rule for Planner**

### **The planner shall never directly mutate pages, menus, tokens, or other site structures as part of planning.**

### **This means:**

### **no silent auto-build at the end of a successful AI run**

### **no direct write actions during normalization**

### **no destructive changes during plan generation**

### **This rule is fundamental and non-negotiable.**

### 

### **39.6 Approval Gate Rules**

### **The system shall enforce approval gates between recommendation and execution.**

### **Approval gates may apply at:**

### **plan level**

### **step level**

### **item level**

### **finalization level**

### **The exact gate sequence may vary by action type, but no high-impact execution should bypass the approved review path.**

### 

### **39.7 Execution Authorization Rules**

### **Execution must require both:**

### **the item or action to be in an executable plan state**

### **the actor to have the required permission**

### **This dual authorization model protects against both logic mistakes and user-overreach.**

### 

### **39.8 Safe Failure Boundaries**

### **If either the planner or executor fails, the system must contain that failure.**

### **Safe-failure boundaries include:**

### **planner failure must not mutate the site**

### **executor failure must not corrupt unrelated objects**

### **partial success must be logged as partial success**

### **failures must preserve enough state for diagnosis**

### **This preserves operational trust.**

### 

## **40. Execution Engine**

### **40.1 Execution Job Types**

### **The execution engine shall support multiple job types.**

### **Job types may include:**

### **create page**

### **replace page**

### **update page metadata**

### **assign page hierarchy**

### **create menu**

### **update menu**

### **apply token set**

### **finalize plan**

### **rollback action**

### **Job typing supports logging, retry policy, and dependency handling.**

### 

### **40.2 Single-Action Execution Flow**

### **A single-action flow shall generally include:**

### **validate request**

### **validate permissions**

### **validate dependencies**

### **capture snapshot where required**

### **perform mutation**

### **record outcome**

### **update Build Plan state**

### **emit status feedback**

### **This flow should be deterministic and reusable across many action types.**

### 

### **40.3 Bulk Execution Flow**

### **Bulk execution shall be built as a sequence of governed actions rather than a magical mass mutation.**

### **Bulk flow shall include:**

### **collect eligible actions**

### **validate action set**

### **determine order**

### **queue or execute each action**

### **track per-item outcome**

### **aggregate result summary**

### **Bulk execution must preserve item-level traceability.**

### 

### **40.4 Dependency Resolution Rules**

### **The engine must resolve dependencies before execution.**

### **Dependencies may include:**

### **parent page existence**

### **template availability**

### **composition validity**

### **menu target page existence**

### **token system readiness**

### **no conflicting plan state**

### **Dependency failure must block or delay execution rather than produce undefined results.**

### 

### **40.5 Idempotency Rules**

### **Execution should be designed to reduce duplicate side effects from repeated triggers.**

### **Idempotency rules may include:**

### **action identifiers**

### **duplicate-run detection**

### **safe retries that do not create multiple duplicate pages unnecessarily**

### **state-aware “already completed” handling**

### **This is especially important for queued or retried work.**

### 

### **40.6 Retry Rules**

### **Certain failed actions may be retried where the failure is transient or repairable.**

### **Retry rules shall include:**

### **retry eligibility**

### **maximum retry count**

### **distinction between automatic and manual retry**

### **preservation of prior failure history**

### **no silent infinite retry loop**

### **Retry must be controlled and visible.**

### 

### **40.7 Partial Failure Rules**

### **If a multi-part action partially succeeds, the system must report that partial outcome clearly.**

### **Partial-failure handling shall include:**

### **identify what succeeded**

### **identify what failed**

### **preserve logs and snapshots**

### **determine whether rollback or manual intervention is recommended**

### **mark Build Plan state accurately**

### **Partial success must not be mislabeled as total success.**

### 

### **40.8 Recovery Rules**

### **Recovery may include:**

### **retrying**

### **rolling back**

### **revalidating dependencies**

### **manual correction followed by retry**

### **quarantining failed items from broader bulk flow**

### **Recovery should be guided by action type and failure reason.**

### 

### **40.9 Status and Progress Reporting**

### **The execution engine shall emit status and progress information.**

### **This may include:**

### **queued**

### **running**

### **waiting on dependency**

### **retrying**

### **completed**

### **failed**

### **partially completed**

### **rolled back**

### **Status reporting must support both user-facing UI and internal diagnostics.**

### 

### **40.10 Execution Completion Rules**

### **An execution job or job set shall be considered complete only when:**

### **all eligible sub-actions are resolved**

### **results are recorded**

### **Build Plan state is updated**

### **post-action logs are written**

### **Completion must be meaningful, not merely “the process stopped running.”**

### 

## **41. Diff, Snapshot, and Rollback System**

### **41.1 Snapshot Scope**

### **The system shall define what kinds of objects can be snapshotted.**

### **Snapshot scope may include:**

### **pages**

### **page metadata**

### **hierarchy assignments**

### **menus**

### **token sets**

### **selected Build Plan state transitions**

### **other execution-relevant objects where useful**

### **Snapshots should focus on meaningful recoverability.**

### 

### **41.2 Pre-Change Snapshot Rules**

### **Before a rollback-eligible change, a pre-change snapshot shall be captured where practical.**

### **Pre-change snapshots should preserve enough information to support:**

### **diff display**

### **audit review**

### **rollback attempt**

### **failure diagnosis**

### **The exact snapshot depth may vary by action type.**

### 

### **41.3 Post-Change Result Recording**

### **After execution, the system shall record the post-change state or result reference.**

### **This allows:**

### **before/after comparison**

### **action verification**

### **rollback reasoning**

### **user-facing change summaries**

### **The system should not only know what it intended to do, but what it actually did.**

### 

### **41.4 Content Diff Rules**

### **Content diffs shall support page-level change understanding.**

### **This may include:**

### **title changes**

### **slug changes**

### **major section-structure changes**

### **content replacement indicators**

### **status changes**

### **Diffs should prioritize usability over perfect low-level textual complexity.**

### 

### **41.5 Structure Diff Rules**

### **Structure diffs shall cover changes to:**

### **page hierarchy**

### **template assignment**

### **section composition**

### **plan structure relevance**

### **Structure changes are often more important than raw text differences in this system.**

### 

### **41.6 Navigation Diff Rules**

### **Navigation diffs shall cover:**

### **menu additions/removals**

### **label changes**

### **order changes**

### **nesting changes**

### **menu location changes**

### **Navigation diffs should be understandable without requiring raw menu-object inspection.**

### 

### **41.7 Token Diff Rules**

### **Token diffs shall support visual-system review.**

### **This may include:**

### **prior token value**

### **new token value**

### **token role**

### **group classification**

### **whether the value was AI-proposed or user-overridden**

### **Token diffs make branding changes reviewable.**

### 

### **41.8 Rollback Data Retention Rules**

### **Rollback-capable data shall be retained according to retention policy.**

### **Retention rules shall consider:**

### **action importance**

### **plan relationship**

### **storage volume**

### **whether newer changes make old rollback impractical**

### **user-selected cleanup choices**

### **The system must avoid both permanent clutter and premature loss of recovery capability.**

### 

### **41.9 Rollback Safety Checks**

### **Before rollback, the system shall validate:**

### **rollback data exists**

### **target objects still resolve**

### **current state is compatible enough for rollback attempt**

### **permission requirements are met**

### **rollback will not silently overwrite newer unrelated work without warning**

### **Rollback safety checks reduce dangerous reversions.**

### 

### **41.10 User-Facing Rollback UX Rules**

### **Rollback UX shall be deliberate and transparent.**

### **The UX should include:**

### **what will be rolled back**

### **why rollback is available**

### **warnings about current-state conflicts**

### **confirmation**

### **status feedback**

### **result logging**

### **Rollback is a recovery workflow, not a casual undo button.**

### 

## **42. Job Queue and Long-Running Task Infrastructure**

### **42.1 Queue Purpose**

### **The queue exists to support tasks that should not be treated as immediate synchronous admin actions.**

### **Its purpose is to support:**

### **crawl runs**

### **AI runs**

### **bulk page creation**

### **bulk page replacement**

### **large navigation updates**

### **export generation**

### **report delivery**

### **rollback processing where needed**

### **The queue is an operational control system, not just a performance convenience.**

### 

### **42.2 Queueable Job Types**

### **Queueable job types may include:**

### **crawl job**

### **AI submission job**

### **validation/retry job**

### **page build job**

### **page replacement job**

### **menu application job**

### **token application job**

### **export bundle job**

### **reporting job**

### **rollback job**

### **Each queueable type should have clear handling rules.**

### 

### **42.3 Scheduling Model**

### **The scheduling model shall support both user-triggered and system-triggered jobs.**

### **This may include:**

### **immediate queue insertion from admin actions**

### **delayed retry scheduling**

### **recurring heartbeat scheduling**

### **maintenance cleanup scheduling**

### **Scheduling should be explicit and governed.**

### 

### **42.4 WP-Cron Usage Rules**

### **The plugin may use WordPress scheduling infrastructure for recurring or deferred work, but must do so intentionally.**

### **WP-Cron usage rules shall include:**

### **register known recurring tasks**

### **unschedule them on deactivation where required**

### **avoid excessive scheduled noise**

### **avoid assuming exact timing guarantees**

### **WP-Cron may support the queue, but does not replace thoughtful job handling.**

### 

### **42.5 Real Cron Compatibility Notes**

### **The system should be compatible with environments that use a real server cron to trigger WordPress scheduled tasks.**

### **Documentation and implementation should acknowledge that:**

### **low-traffic sites may delay WP-Cron-triggered events**

### **real cron may improve reliability**

### **the plugin should behave correctly in both conditions**

### **This is an operational compatibility note, not a separate feature.**

### 

### **42.6 Queue State Model**

### **The queue shall use explicit states.**

### **Possible queue states include:**

### **pending**

### **scheduled**

### **running**

### **retrying**

### **completed**

### **failed**

### **cancelled**

### **dead/stalled**

### **States must have clear meanings and support supportability.**

### 

### **42.7 Dead Job Recovery Rules**

### **The system shall detect and handle dead or stalled jobs where possible.**

### **Dead job recovery may include:**

### **stale lock detection**

### **failure classification**

### **move to failed/dead state**

### **allow manual retry**

### **preserve error context**

### **Jobs must not disappear into silent limbo.**

### 

### **42.8 Concurrency Rules**

### **The queue system shall manage concurrency carefully.**

### **Concurrency rules may include:**

### **avoid duplicate execution of the same high-impact job**

### **prevent simultaneous conflicting actions on the same target object where practical**

### **support safe parallelism only where actions are independent**

### **Concurrency must be managed to preserve data integrity.**

### 

### **42.9 Locking Rules**

### **The queue shall support locking behavior where needed.**

### **Locking may apply to:**

### **individual jobs**

### **target pages**

### **Build Plans**

### **exports in progress**

### **reporting deliveries**

### **Locks should prevent destructive overlap without becoming permanent blockers.**

### 

### **42.10 Queue Monitoring and Admin Visibility**

### **Admins with the appropriate permissions shall be able to inspect queue state.**

### **Queue visibility may include:**

### **job type**

### **status**

### **timestamps**

### **target object**

### **retry count**

### **failure reason summary**

### **Operationally important queues must not be invisible.**

### 

## **43. Security Specification**

### **43.1 Security Objectives**

### **The security objective of the plugin is to protect:**

### **site integrity**

### **user permissions**

### **secrets and credentials**

### **operational data**

### **imported/exported data**

### **external communication pathways**

### **Security must be woven into architecture, not bolted on after implementation.**

### 

### **43.2 Threat Model**

### **The plugin shall consider at least the following threat classes:**

### **unauthorized admin action**

### **privilege escalation**

### **CSRF-like action triggering**

### **malformed input leading to unintended writes**

### **secret leakage**

### **insecure export/download access**

### **unsafe external request handling**

### **import of malicious files**

### **log-based data leakage**

### **unintended destructive execution from invalid plan state**

### **The threat model shall inform both UI and backend design.**

### 

### **43.3 Capability Enforcement Rules**

### **Every privileged action shall require capability checks.**

### **This includes actions such as:**

### **managing templates**

### **running onboarding**

### **managing providers**

### **triggering AI runs**

### **approving Build Plans**

### **executing changes**

### **accessing logs**

### **downloading artifacts**

### **importing/exporting data**

### **viewing diagnostics**

### **Capability checks must occur server-side, not just in UI visibility.**

### 

### **43.4 Nonce Rules**

### **All mutation-capable requests initiated through admin interfaces shall use nonce protection or an equivalent secure request-validation pattern consistent with WordPress architecture.**

### **Nonce rules shall include:**

### **generate nonces for actions**

### **verify nonces on receipt**

### **reject invalid or missing nonce where required**

### **not mistake nonce checks for permission checks**

### **Nonces support request integrity, but do not replace authorization.**

### 

### **43.5 Authentication Boundaries**

### **The plugin shall rely on WordPress authentication boundaries for admin access and shall not create informal bypasses.**

### **Authentication boundaries include:**

### **logged-in user context**

### **privileged server-side execution context for scheduled tasks**

### **secure provider credential storage and usage**

### **no front-end exposure of administrative mutation endpoints without proper control**

### **Authentication must be respected across all major workflows.**

### 

### **43.6 Authorization Boundaries**

### **Authorization must be role/capability-specific and action-specific.**

### **The plugin must distinguish between:**

### **view permission**

### **edit permission**

### **approve permission**

### **execute permission**

### **export permission**

### **diagnostics permission**

### **Authorization must not collapse into “admin can do everything, everyone else can do nothing” unless intentionally configured that way.**

### 

### **43.7 Input Sanitization Rules**

### **All incoming user-controlled input shall be sanitized according to its data type and purpose.**

### **Sanitization shall cover:**

### **text fields**

### **URLs**

### **file names**

### **structured arrays**

### **numeric values**

### **select values**

### **imported package metadata**

### **provider config inputs**

### **Sanitization must occur before storage or execution use.**

### 

### **43.8 Output Escaping Rules**

### **All user-facing output shall be escaped appropriately for its rendering context.**

### **This includes:**

### **admin text output**

### **attributes**

### **URLs**

### **downloadable file names**

### **logs rendered in admin UI**

### **Escaping is a baseline requirement, not an optional hardening pass.**

### 

### **43.9 REST Security Rules**

### **If the plugin exposes REST endpoints, each endpoint must include:**

### **permission callback**

### **input validation**

### **controlled schema or arg handling**

### **safe error responses**

### **no assumption that a route is safe because it is admin-oriented**

### **REST must be treated as a formal attack surface.**

### 

### **43.10 AJAX Security Rules**

### **AJAX handlers shall use:**

### **capability checks**

### **request validation**

### **nonce checks where applicable**

### **input sanitization**

### **safe output handling**

### **AJAX actions should not become the weakest operational link.**

### 

### **43.11 File Upload Security Rules**

### **Any file-upload workflow shall validate:**

### **file type**

### **file size**

### **file origin expectations where possible**

### **storage destination**

### **import suitability if the file affects data state**

### **Uploads must not be trusted simply because they were initiated through the admin UI.**

### 

### **43.12 ZIP Import Security Rules**

### **ZIP import is a high-risk workflow and shall be tightly controlled.**

### **Import rules shall include:**

### **permission requirement**

### **package structure validation**

### **manifest validation**

### **version compatibility checks**

### **no arbitrary code execution from imported files**

### **safe extraction handling**

### **rejection of malformed or unexpected packages**

### **ZIP import must be treated as a controlled restore mechanism, not a generic unpack process.**

### 

### **43.13 Secrets Handling Rules**

### **Secrets include:**

### **AI API keys**

### **sensitive tokens**

### **remote credentials**

### **any other privileged authentication values**

### **Secrets rules shall include:**

### **never expose in front-end code**

### **never include in logs or reports**

### **restrict admin display**

### **support secure update and replacement**

### **avoid inclusion in exports unless explicitly and securely designed**

### **Secrets protection is mandatory.**

### 

### **43.14 Logging Redaction Rules**

### **Logs shall redact or omit sensitive data.**

### **Redaction rules shall cover:**

### **secrets**

### **tokens**

### **passwords**

### **confidential request headers**

### **protected personal data where not necessary**

### **raw payload data that exceeds safe exposure**

### **Logs must be useful without becoming a liability.**

### 

### **43.15 External Request Allowlist Rules**

### **External requests shall be restricted to known and approved destinations.**

### **Allowlist rules shall include:**

### **AI provider endpoints**

### **reporting endpoints or destinations**

### **update endpoints if added later**

### **no arbitrary outbound request behavior through user-controlled URLs without explicit approved purpose**

### **Outbound communication must remain intentional and bounded.**

### 

### **43.16 Secure Failure Behavior**

### **When security checks fail, the system shall fail safely.**

### **Safe failure behavior includes:**

### **reject action**

### **log appropriately**

### **expose a controlled user-facing error**

### **do not partially perform restricted behavior**

### **do not reveal unnecessary sensitive detail**

### **Security failure should stop unsafe work, not degrade into undefined execution.**

### 

## **44. Permissions and Capability Matrix**

### **44.1 Capability Design Philosophy**

### **The plugin shall implement a least-privilege model using custom capabilities.**

### **No major workflow shall rely only on generic WordPress edit permissions.**

### 

### **44.2 Default Roles and Mappings**

### **Default mappings:**

#### **Administrator**

### **Granted all plugin capabilities by default.**

#### **Editor**

### **Granted:**

### **view Build Plans\**

### **review helper docs\**

### **edit page content on built pages\**

### **view non-sensitive logs\**

### **deny/approve low-risk content-level recommendations\**

### **Editors shall not receive:**

### **provider management\**

### **reporting settings\**

### **export of raw artifacts\**

### **plan execution for replacements, rollbacks, or reporting config\**

#### **Author / Contributor / Subscriber**

### **Granted no plugin management capabilities by default.**

### 

### **44.3 Custom Capability Definitions**

### **The plugin shall register at minimum the following capabilities:**

### **aio_manage_settings\**

### **aio_manage_section_templates\**

### **aio_manage_page_templates\**

### **aio_manage_compositions\**

### **aio_manage_brand_profile\**

### **aio_run_onboarding\**

### **aio_manage_ai_providers\**

### **aio_run_ai_plans\**

### **aio_view_build_plans\**

### **aio_approve_build_plans\**

### **aio_execute_build_plans\**

### **aio_execute_page_replacements\**

### **aio_manage_navigation_changes\**

### **aio_manage_token_changes\**

### **aio_finalize_plan_actions\**

### **aio_view_logs\**

### **aio_view_sensitive_diagnostics\**

### **aio_download_artifacts\**

### **aio_export_data\**

### **aio_import_data\**

### **aio_execute_rollbacks\**

### **aio_manage_reporting_and_privacy\**

### 

### **44.4 View vs Execute Permissions**

### **The following separation is mandatory:**

### **viewing a Build Plan does not imply approving it\**

### **approving a Build Plan does not imply executing it\**

### **executing does not imply rollback authority\**

### **viewing logs does not imply viewing raw AI artifacts\**

### **exporting data does not imply importing data\**

### 

### **44.5 Template Management Permissions**

### **Users with aio_manage_section_templates, aio_manage_page_templates, or aio_manage_compositions may:**

### **create\**

### **edit\**

### **deprecate\**

### **archive\**

### **inspect compatibility metadata\**

### **They may not manage provider credentials unless separately permitted.**

### 

### **44.6 AI Configuration Permissions**

### **Only users with aio_manage_ai_providers may:**

### **add/update provider credentials\**

### **change provider defaults\**

### **inspect provider test results\**

### **alter provider-facing settings\**

### **Only users with aio_run_ai_plans may initiate new planning runs.**

### 

### **44.7 Build Plan Permissions**

### **Users with aio_view_build_plans may read plan details.\
Users with aio_approve_build_plans may approve or deny recommendations.\
Users with aio_execute_build_plans may execute non-destructive build actions.\
Users with aio_finalize_plan_actions may publish/finalize.**

### 

### **44.8 Execution Permissions**

### **High-impact execution permissions are split as follows:**

### **aio_execute_page_replacements for replacing existing pages\**

### **aio_manage_navigation_changes for menu changes\**

### **aio_manage_token_changes for branding/token application\**

### **aio_execute_rollbacks for rollback jobs\**

### **These shall not be granted broadly by default outside Administrators.**

### 

### **44.9 Artifact Download Permissions**

### **Only users with aio_download_artifacts may download:**

### **raw prompts\**

### **raw responses\**

### **normalized outputs\**

### **AI bundles\**

### **diagnostic bundles\**

### **Users may still view summarized Build Plan rationale without raw artifact access.**

### 

### **44.10 Logs and Diagnostics Permissions**

### **Users with aio_view_logs may view normal operational logs.\
Users with aio_view_sensitive_diagnostics may view:**

### **provider error details\**

### **reporting failure details\**

### **queue payload summaries\**

### **import/export diagnostics\**

### 

### **44.11 Privacy Control Permissions**

### **Only users with aio_manage_reporting_and_privacy may:**

### **inspect reporting settings\**

### **inspect retention settings\**

### **trigger privacy-related exports/erasures where available\**

### **manage uninstall/export preferences\**

### 

### **44.12 Future Role Extension Rules**

### **The capability system shall support custom role mapping by site administrators or role-management plugins without altering internal capability names.**

### 

## **45. Diagnostics, Error Handling, and Reporting**

### **45.1 Error Classification Model**

### **The plugin shall classify errors into meaningful categories.**

### **Categories may include:**

### **validation error**

### **dependency error**

### **execution error**

### **provider error**

### **queue error**

### **reporting error**

### **import/export error**

### **security error**

### **compatibility error**

### **Classification improves supportability and user understanding.**

### 

### **45.2 Severity Levels**

### **Errors shall be assigned severity levels.**

### **Suggested severity levels include:**

### **info**

### **warning**

### **error**

### **critical**

### **Severity should influence:**

### **UI messaging**

### **logging prominence**

### **developer reporting behavior**

### **urgency in support review**

### 

### **45.3 User-Facing Error Messages**

### **User-facing errors shall be understandable and actionable.**

### **They should:**

### **explain what failed**

### **avoid unnecessary technical noise**

### **indicate whether retry is possible**

### **point the user toward the next sensible step**

### **Users should not need raw stack traces to understand ordinary failures.**

### 

### **45.4 Admin-Facing Error Details**

### **Privileged users may be shown deeper error information.**

### **Admin-facing details may include:**

### **internal code or category**

### **target object**

### **failure context**

### **retry recommendation**

### **log reference**

### **provider response summary where safe**

### **Admin detail should help resolve the issue without exposing secrets.**

### 

### **45.5 Structured Error Log Format**

### **Errors shall be logged in structured form.**

### **A structured error record may include:**

### **error ID**

### **category**

### **severity**

### **timestamp**

### **actor context**

### **target object**

### **related job/plan/run reference**

### **sanitized message**

### **remediation hint if known**

### **Structured logs are easier to filter, export, and support.**

### 

### **45.6 Recovery Recommendation Rules**

### **Where possible, the system should provide recovery hints.**

### **Recovery recommendations may indicate:**

### **retry now**

### **fix dependency first**

### **revalidate provider credentials**

### **inspect import package**

### **review permissions**

### **contact support or inspect diagnostics**

### **Recovery guidance should be pragmatic, not generic.**

### 

### **45.7 Developer Reporting Rules**

### **Under the private-distribution model, the plugin may report certain errors to the designated developer/support destination.**

### **Reporting rules shall include:**

### **severity threshold logic**

### **event eligibility rules**

### **payload redaction**

### **structured formatting**

### **failure logging if delivery fails**

### **Developer reporting must remain controlled and documented.**

### 

### **45.8 Error Email Format**

### **When error emails are sent, they shall follow a consistent structure.**

### **This may include:**

### **severity**

### **site reference**

### **category**

### **summary**

### **context**

### **what was expected**

### **what occurred**

### **sanitized technical detail**

### **relevant environment notes**

### **Consistency improves support processing.**

### 

### **45.9 Error Redaction Rules**

### **Errors must be redacted before they are displayed broadly or reported externally.**

### **Redaction shall remove or mask:**

### **secrets**

### **tokens**

### **passwords**

### **sensitive raw payloads**

### **prohibited personal data**

### **session-specific values where not needed**

### **Redaction must be systematic, not optional.**

### 

### **45.10 Debug Mode vs Production Mode**

### **The plugin may support different diagnostics verbosity modes.**

### **Production mode should:**

### **favor user-safe messages**

### **avoid oversharing details**

### **preserve logs internally**

### **Debug mode may:**

### **expose richer internal detail to authorized users**

### **aid troubleshooting**

### **never expose secrets**

### **Mode differences must remain permission-aware.**

### 

## **46. Mandatory Install Notification, Heartbeat, and Developer Reporting**

### **46.1 Private Distribution Exception Statement**

### **This plugin is privately distributed and therefore intentionally includes mandatory operational reporting that is outside repository-style opt-in expectations. This reporting is part of the product’s support model and shall be disclosed in the admin interface and product documentation.**

### 

### **46.2 Installation Notification Trigger Rules**

### **A single installation notification shall be sent on the first successful activation after all of the following are true:**

### **environment validation passed\**

### **required dependencies are present\**

### **plugin tables/options initialized successfully\**

### **A duplicate installation notice shall not be sent again unless:**

### **the plugin is fully uninstalled and reinstalled, or\**

### **the site domain changes and the installation is treated as a new installation record\**

### 

### **46.3 Installation Email Payload Rules**

### **Installation notifications shall be sent to:**

### **AIOpagebuilder@steadyhandmarketing.com**

### **Subject format:**

### **Plugin successfully installed on \[website address\]**

### **Required body fields:**

### **website address\**

### **plugin version\**

### **WordPress version\**

### **PHP version\**

### **server IP address if available\**

### **admin contact email\**

### **timestamp\**

### **dependency readiness summary\**

### **Secrets and prohibited data must never be included.**

### 

### **46.4 Monthly Heartbeat Trigger Rules**

### **A heartbeat shall be sent once every calendar month.\
The scheduled event shall run on the first available cron execution after the stored monthly due date.**

### **Only one successful heartbeat may be recorded per site per calendar month.**

### 

### **46.5 Heartbeat Email Payload Rules**

### **Heartbeat notifications shall be sent to:**

### **AIOpagebuilder@steadyhandmarketing.com**

### **Subject format:**

### **Heart beat - \[website address\] - \[status of site\]**

### **\[status of site\] shall be one of:**

### **healthy\**

### **warning\**

### **degraded\**

### **critical\**

### **Body shall include:**

### **website address\**

### **plugin version\**

### **WordPress version\**

### **PHP version\**

### **admin contact email\**

### **server IP address if available\**

### **last successful AI run timestamp if any\**

### **last successful Build Plan execution timestamp if any\**

### **current health summary\**

### **current queue warning count\**

### **current unresolved critical error count\**

### 

### **46.6 Error Reporting Trigger Rules**

### **Developer error reporting shall trigger when an event meets one of these criteria:**

### **severity = critical\**

### **the same error repeats 3 times within 24 hours\**

### **a page replacement action fails after final retry\**

### **a Build Plan finalization fails at the publish stage\**

### **an import/restore fails after validation passed\**

### **the queue enters dead/stalled state for more than 15 minutes\**

### **a migration fails\**

### 

### **46.7 Severity-Based Reporting Rules**

### **Severity handling:**

### **info: local log only\**

### **warning: local log only unless repeated 10+ times in 24 hours\**

### **error: report if tied to plan execution, restore, or queue failure\**

### **critical: report immediately\**

### 

### **46.8 Data Inclusion Rules**

### **Allowed report fields:**

### **severity\**

### **website address\**

### **plugin version\**

### **WordPress version\**

### **PHP version\**

### **error category\**

### **sanitized error summary\**

### **expected behavior\**

### **actual behavior\**

### **related Build Plan / job / run ID\**

### **admin contact email\**

### **server IP if available\**

### **timestamp\**

### 

### **46.9 Data Exclusion Rules**

### **Never include:**

### **passwords\**

### **API keys\**

### **bearer tokens\**

### **auth cookies\**

### **nonces\**

### **raw database credentials\**

### **full unpublished page content\**

### **full raw AI payloads unless explicitly requested through a support export not part of routine reporting\**

### 

### **46.10 Reporting Failure Handling**

### **If a report fails:**

### **the failure shall be logged locally\**

### **up to 3 retries may be attempted using exponential backoff\**

### **repeated delivery failure shall not block unrelated plugin function\**

### **diagnostics shall surface the reporting failure state\**

### 

### **46.11 Settings and Disclosure Screen**

### **The plugin shall include a Reporting and Privacy screen that discloses:**

### **installation notification behavior\**

### **heartbeat behavior\**

### **error reporting behavior\**

### **destination email\**

### **included data categories\**

### **excluded data categories\**

### **local retention settings related to reporting logs\**

### **This screen may expose verbosity controls where product policy allows, but may not disable mandatory reporting.**

### 

### **46.12 Delivery Reliability Rules**

### **Reporting delivery shall use queued jobs where possible.\
Failed sends shall be deduplicated so the same failure event does not spam repeated reports indefinitely.\
Every attempted report shall generate a reporting log entry with delivery status.**

### 

## **47. Privacy, Data Handling, and Disclosure**

### **47.1 Privacy Philosophy**

### **The plugin shall follow a privacy philosophy based on data awareness, disclosure, minimization where practical, and controlled external transfer.**

### **Private distribution does not eliminate the responsibility to be transparent and disciplined about data handling.**

### 

### **47.2 Site Data Collection Categories**

### **The plugin may collect or store categories such as:**

### **plugin settings**

### **profile inputs**

### **crawl summaries**

### **template usage data**

### **Build Plan records**

### **execution logs**

### **reporting records**

### **AI artifact records**

### **These categories must be documented and governed.**

### 

### **47.3 Personal Data Categories**

### **The plugin may handle some personal or quasi-personal data such as:**

### **administrator contact email**

### **user references in logs**

### **actor identifiers on approvals/execution**

### **user-provided business contact information**

### **Personal data handling must be treated intentionally and not ignored simply because the plugin is admin-focused.**

### 

### **47.4 External Transfer Categories**

### **External transfers may include:**

### **AI planning requests**

### **AI file attachments where supported**

### **installation reporting**

### **heartbeat reporting**

### **error reporting**

### **The plugin must clearly recognize which data classes may leave the site.**

### 

### **47.5 AI Payload Disclosure Rules**

### **The plugin shall disclose that AI planning involves sending selected structured data to the chosen provider.**

### **Disclosure should explain at an appropriate level:**

### **what categories of data may be sent**

### **why they are sent**

### **that provider usage may incur cost**

### **that output is used for planning and Build Plan generation**

### **This supports informed administration.**

### 

### **47.6 Reporting Disclosure Rules**

### **The plugin shall disclose that operational reporting is part of the private-distribution model.**

### **Disclosure should explain:**

### **installation notifications**

### **recurring heartbeat**

### **error reporting**

### **general categories of transmitted data**

### **exclusion of secrets and prohibited data**

### **This should be visible in plugin documentation and relevant admin screens.**

### 

### **47.7 Retention Policies**

### **The plugin shall define retention policies for key data classes.**

### **Retention should cover:**

### **logs**

### **AI artifacts**

### **crawl snapshots**

### **reports**

### **exports**

### **rollback records**

### **queue history**

### **Retention policies should balance usefulness, privacy, and storage growth.**

### 

### **47.8 Redaction Policies**

### **The plugin shall define and apply redaction policies for:**

### **logs**

### **reports**

### **exports**

### **artifact views**

### **support-oriented data bundles**

### **Redaction must be systematic and consistent across the product.**

### 

### **47.9 Exporter Integration**

### **Where appropriate within WordPress privacy tooling expectations, the plugin should support exporter integration for personal data categories it controls.**

### **Exporter support should be designed with:**

### **relevance**

### **permission**

### **practical interpretability**

### **This aligns the plugin with WordPress-style privacy-aware engineering.**

### 

### **47.10 Eraser Integration**

### **Where practical and appropriate, the plugin should support eraser integration for personal-data categories it controls, subject to the product’s retention and audit needs.**

### **Eraser support must not destroy necessary system integrity without policy. It should operate within defined limits.**

### 

### **47.11 Suggested Privacy Policy Text**

### **The plugin should provide suggested privacy-policy language that site administrators can review and adapt.**

### **Suggested text should describe:**

### **stored data categories**

### **AI-provider transfers**

### **reporting behavior**

### **retention concepts**

### **admin-facing operational logs where relevant**

### **The plugin should assist transparency, not leave administrators guessing.**

### 

### **47.12 Admin Transparency Requirements**

### **The admin experience shall surface enough information that a responsible site administrator can understand:**

### **what data the plugin stores**

### **what data may be sent externally**

### **why that happens**

### **what operational records exist**

### **how export and removal work**

### **Transparency is a product requirement, not merely a compliance aspiration.**

### 

## **48. Logging, Auditing, and Observability**

### **48.1 Audit Log Scope**

### **The plugin shall maintain audit visibility for major operational actions.**

### **Scope may include:**

### **onboarding submissions**

### **AI runs**

### **Build Plan approvals/denials**

### **page builds and replacements**

### **navigation changes**

### **token applications**

### **export/import actions**

### **reporting deliveries**

### **rollbacks**

### **The audit log should support both operational support and governance.**

### 

### **48.2 Action-Level Logging Rules**

### **Every meaningful mutation action shall produce an action log entry.**

### **An action-level log should capture:**

### **action type**

### **actor**

### **target**

### **timestamp**

### **status**

### **related plan/run/job references**

### **warning/error indicators**

### **This ensures that major changes are never invisible.**

### 

### **48.3 Queue Logging Rules**

### **Queued jobs shall produce queue-oriented log events.**

### **These may include:**

### **job created**

### **job started**

### **job retried**

### **job completed**

### **job failed**

### **job cancelled**

### **stale/dead job detected**

### **Queue logs are essential to understanding long-running workflows.**

### 

### **48.4 AI Run Logging Rules**

### **AI runs shall produce dedicated log records.**

### **These records may include:**

### **run start**

### **validation result**

### **provider outcome**

### **normalization result**

### **retry behavior**

### **artifact references**

### **final status**

### **AI runs are operationally important and must remain inspectable.**

### 

### **48.5 Change Execution Logging Rules**

### **Execution logs shall record the performance of approved actions.**

### **This includes:**

### **page creation**

### **page replacement**

### **metadata application**

### **hierarchy changes**

### **menu changes**

### **token application**

### **Execution logs are one of the primary observability layers in the system.**

### 

### **48.6 Menu Change Logging Rules**

### **Navigation changes shall produce specific log records that preserve:**

### **what menu changed**

### **what items were added/removed/reordered**

### **what locations changed**

### **who triggered the change**

### **whether it succeeded**

### **Navigation can affect many pages at once and must remain auditable.**

### 

### **48.7 Token Change Logging Rules**

### **Token changes shall produce log entries that preserve:**

### **token set identifier**

### **changed token groups**

### **old/new values or references**

### **actor**

### **timestamp**

### **result status**

### **This allows visual-system changes to be inspected historically.**

### 

### **48.8 Reporting / Telemetry Logging Rules**

### **Reporting actions shall also be logged.**

### **This includes:**

### **installation reports**

### **heartbeat sends**

### **diagnostics reports**

### **failures to report**

### **retry attempts**

### **Reporting observability is especially important because outbound communication may fail silently otherwise.**

### 

### **48.9 Log Retention Rules**

### **Log retention shall follow defined categories and not be left to uncontrolled indefinite growth.**

### **Retention may vary by log type:**

### **queue logs**

### **execution logs**

### **reporting logs**

### **AI logs**

### **security-related logs**

### **Retention must balance operational utility and storage responsibility.**

### 

### **48.10 Log Export Rules**

### **Authorized users shall be able to export logs where product policy allows.**

### **Log export rules shall include:**

### **permission checks**

### **redaction**

### **date or plan filtering**

### **structured export format**

### **clear labeling**

### **Export supports support workflows and audit review.**

### 

## **49. Admin Information Architecture**

### **49.1 Top-Level Menu Structure**

### **Top-level menu label:**

### **AIO Page Builder**

### 

### **49.2 Submenu / Tab Structure**

### **Required submenu structure:**

### **Dashboard\**

### **Section Templates\**

### **Page Templates\**

### **Compositions\**

### **Onboarding & Profile\**

### **Crawl Snapshots\**

### **AI Providers\**

### **AI Runs\**

### **Build Plans\**

### **Queue & Logs\**

### **Privacy, Reporting & Settings\**

### **Import / Export\**

### 

### **49.3 Screen Hierarchy**

### **Screen hierarchy shall follow this order of use:**

### **Dashboard as overview\**

### **Onboarding & Profile for context creation\**

### **Crawl Snapshots and AI Providers as planning prerequisites\**

### **AI Runs as source artifacts\**

### **Build Plans as operational action center\**

### **Queue & Logs as support/monitoring layer\**

### **Privacy, Reporting & Settings as policy/installation layer\**

### **Templates screens remain globally accessible but are conceptually foundational rather than day-to-day operational.**

### 

### **49.4 Screen Entry Points**

### **Each major screen shall expose at least one clear entry action:**

### **Dashboard → Start/Resume Onboarding\**

### **Onboarding → Run Crawl / Submit Planning Request\**

### **AI Runs → Create Build Plan\**

### **Build Plans → Open Active Plan\**

### **Queue & Logs → Open Failed Job / Related Plan\**

### **Import / Export → Create Export / Restore Package\**

### 

### **49.5 Dashboard Screen**

### **The Dashboard shall include:**

### **environment readiness card\**

### **dependency readiness card\**

### **provider readiness card\**

### **last crawl summary\**

### **last AI run summary\**

### **active Build Plans summary\**

### **queue warnings\**

### **recent critical errors\**

### **quick actions\**

### 

### **49.6 Templates Screen**

### **Section Templates screen shall include:**

### **searchable template table\**

### **category filter\**

### **status filter\**

### **version column\**

### **helper-doc access\**

### **compatibility summary\**

### **deprecation marker\**

### 

### **49.7 Page Templates Screen**

### **Page Templates screen shall include:**

### **page template list\**

### **composition list toggle or tab\**

### **section-order preview\**

### **status and version\**

### **one-pager access\**

### **compatibility notes\**

### **create/edit composition controls for authorized users\**

### 

### **49.8 Onboarding Screen**

### **Onboarding & Profile shall include:**

### **current profile summary\**

### **resume draft onboarding\**

### **rerun onboarding\**

### **asset intake status\**

### **crawl trigger panel\**

### **change-detection notice before new AI run\**

### 

### **49.9 AI Providers Screen**

### **AI Providers shall include:**

### **provider list\**

### **credential status\**

### **model defaults\**

### **connection test result\**

### **disclosure note about external transfer and cost\**

### **last successful provider use timestamp\**

### **Raw keys shall never be displayed in full after save.**

### 

### **49.10 Build Plans Screen**

### **Build Plans shall include:**

### **plan list\**

### **status filter\**

### **source AI run\**

### **created timestamp\**

### **unresolved count\**

### **completion state\**

### **open/export/archive actions\**

### 

### **49.11 Logs and Diagnostics Screen**

### **Queue & Logs shall include tabs for:**

### **Queue\**

### **Execution Logs\**

### **AI Runs\**

### **Reporting Logs\**

### **Import/Export Logs\**

### **Critical Errors\**

### **Each tab shall support filtering and row-to-object navigation.**

### 

### **49.12 Privacy and Settings Screen**

### **Privacy, Reporting & Settings shall include:**

### **reporting disclosure\**

### **retention settings\**

### **uninstall/export behavior\**

### **environment summary\**

### **plugin version summary\**

### **privacy-policy helper text\**

### **diagnostics verbosity controls if allowed\**

### **current report destination display**

## **50. User Experience and Interface Standards**

### **50.1 Admin UI Design Principles**

### **The admin interface shall be designed around clarity, confidence, consistency, and operational usefulness.**

### **The core design principles are:**

### **clarity over novelty**

### **structure over clutter**

### **guided workflows over ambiguous blank states**

### **visible status over hidden state**

### **confidence-building review over surprise automation**

### **consistency across screens and actions**

### **The admin UI should feel like a structured operating system for the plugin, not a collection of disconnected settings pages.**

### 

### **50.2 Layout Consistency Rules**

### **All major admin screens shall follow a consistent layout logic.**

### **Layout consistency rules shall include:**

### **consistent page headers**

### **consistent page-level action placement**

### **consistent panel hierarchy**

### **predictable table/detail layouts**

### **consistent filter and search placement**

### **consistent spacing and visual rhythm**

### **consistent iconography and badge usage where used**

### **Users should not have to relearn the interface on every screen.**

### 

### **50.3 Feedback and Affordance Standards**

### **The interface shall clearly indicate what can be clicked, edited, approved, denied, retried, exported, or rolled back.**

### **Feedback and affordance standards shall include:**

### **visible button hierarchy**

### **clear hover/focus states**

### **disabled states that explain why an action is unavailable**

### **visible loading or processing feedback**

### **success and failure responses after action**

### **enough local feedback that the user knows whether the system understood the action**

### **The UI should not make users guess whether something is actionable or what happened after they clicked it.**

### 

### **50.4 Pretty Popup / Modal Standard**

### **Where popups or modals are used, they shall be intentionally styled, readable, and operationally helpful.**

### **Modal standards shall include:**

### **clear title**

### **concise purpose statement**

### **visible consequences of the action**

### **primary and secondary actions**

### **clear cancellation behavior**

### **accessibility-compliant focus handling**

### **no browser-native alert or confirm dialogs for core product actions unless used as a fallback**

### **Modals should feel polished, but must not become vague or decorative.**

### 

### **50.5 Confirmation UI Standard**

### **High-impact actions shall require clear confirmation UI.**

### **Confirmation standards shall include:**

### **explicit statement of what is about to happen**

### **indication of what objects will be affected**

### **indication of whether the action is reversible**

### **indication of whether the action will queue or execute immediately**

### **affirmative action label that describes the action rather than generic “OK”**

### **Confirmation should reduce accidental destructive behavior without creating needless friction for every small action.**

### 

### **50.6 Empty State Standard**

### **Empty states shall be intentional and informative.**

### **An empty state should explain:**

### **why the screen or step is empty**

### **whether that is a good outcome or a missing setup condition**

### **what the user can do next**

### **whether the state is temporary, complete, or blocked**

### **Empty states should never look like the UI broke.**

### 

### **50.7 Loading State Standard**

### **The UI shall provide loading states whenever data is being fetched, processed, or prepared.**

### **Loading standards shall include:**

### **clear indication that work is in progress**

### **scope-appropriate loading behavior, such as inline loaders, row-level states, or page-level overlays**

### **preservation of enough context that the user knows what is loading**

### **no fake completion signals before real completion**

### **Loading states should reduce uncertainty, not increase it.**

### 

### **50.8 Error State Standard**

### **Error states shall be visible, legible, and appropriately scoped.**

### **An error state should indicate:**

### **what failed**

### **whether the failure is local or global**

### **whether the user can retry**

### **where to look for more detail if they have permission**

### **whether any partial work succeeded**

### **Error presentation should support recovery, not panic.**

### 

### **50.9 Success State Standard**

### **Successful actions shall be acknowledged clearly.**

### **Success-state behavior may include:**

### **a confirmation message**

### **changed status badges**

### **updated row state**

### **a summary of what happened**

### **access to next relevant action**

### **The system should never leave the user wondering whether a successful action actually succeeded.**

### 

### **50.10 Destructive Action UX Standard**

### **Destructive or high-impact actions must be visually and behaviorally distinct from ordinary actions.**

### **This standard shall include:**

### **clear labeling**

### **visual separation from safer controls**

### **confirmation requirement where appropriate**

### **indication of affected objects**

### **indication of reversibility or non-reversibility**

### **logging and traceability after action**

### **Destructive actions must never be disguised as routine clicks.**

### 

### **50.11 Accessibility UI Standards**

### **The admin UI shall follow accessibility-oriented interface practices.**

### **UI accessibility standards include:**

### **keyboard navigability**

### **visible focus states**

### **semantic labeling**

### **understandable button text**

### **screen-reader-friendly status messaging**

### **accessible modal behavior**

### **contrast-aware badge and notice design**

### **appropriate use of ARIA only where semantic HTML is insufficient**

### **Accessibility is a baseline interface requirement.**

### 

## **51. Accessibility Specification**

### **51.1 Accessibility Objectives**

### **The plugin shall aim to produce an admin and front-end experience that is understandable, navigable, and usable by a broad range of users.**

### **Accessibility objectives include:**

### **keyboard usability**

### **screen-reader compatibility**

### **semantic clarity**

### **readable focus behavior**

### **contrast-aware styling**

### **accessible form interaction**

### **accessible modal and stepper behavior**

### **Accessibility must be treated as a product requirement rather than a late polish pass.**

### 

### **51.2 Admin UI Accessibility Rules**

### **Admin interfaces shall follow accessible interaction patterns.**

### **Rules include:**

### **semantic page structure**

### **labeled controls**

### **visible focus indicators**

### **keyboard-operable action controls**

### **screen-reader-readable notices and status areas**

### **no reliance on color alone to communicate meaning**

### **accessible table and detail interactions**

### **The admin UI must remain usable under realistic assistive-navigation conditions.**

### 

### **51.3 Front-End Output Accessibility Rules**

### **Where the plugin generates front-end page output, that output must follow accessibility expectations built into section and page templates.**

### **These rules include:**

### **meaningful heading hierarchy**

### **clear CTA labeling**

### **alt-text support**

### **semantic list usage where relevant**

### **accessible accordion/detail patterns where used**

### **sufficient color contrast under active token values**

### **avoidance of inaccessible decorative-only structure for important content**

### **Accessibility must be built into the template system, not delegated entirely to the editor.**

### 

### **51.4 Keyboard Navigation Rules**

### **All major admin workflows shall be operable by keyboard.**

### **Keyboard rules include:**

### **reachable primary actions**

### **logical tab order**

### **focus retention after action where appropriate**

### **modal focus trapping and release**

### **keyboard access to filters, selections, and step navigation**

### **no mouse-only critical actions**

### **Keyboard users must be able to operate the plugin meaningfully.**

### 

### **51.5 Focus Management Rules**

### **Focus behavior shall be deliberate and predictable.**

### **Focus rules shall include:**

### **visible focus styling**

### **focus moved to modals when opened**

### **focus returned appropriately when modals close**

### **focus placed on meaningful success/error context after major actions where helpful**

### **no unexpected focus jumps during async updates unless clearly beneficial**

### **Poor focus handling can make complex interfaces unusable.**

### 

### **51.6 Semantic Heading Rules**

### **The plugin shall use semantic heading structure in both admin UI and front-end output.**

### **Heading rules include:**

### **headings should reflect content hierarchy**

### **heading order should be logical**

### **headings should not be used purely for visual styling when another element is appropriate**

### **generated page sections should align with page-level heading hierarchy rules**

### **Semantic headings improve usability for all users, not just screen-reader users.**

### 

### **51.7 Landmark and ARIA Rules**

### **The plugin shall use semantic landmarks and ARIA support appropriately.**

### **Rules include:**

### **prefer semantic HTML before ARIA**

### **use landmarks where they improve navigation**

### **use ARIA for relationships or interaction patterns only when needed**

### **avoid redundant or misleading ARIA**

### **ensure stateful controls communicate state accessibly**

### **ARIA should clarify, not compensate for poor structure.**

### 

### **51.8 Color Contrast Rules**

### **The plugin’s admin UI and token-driven front-end system must support accessible contrast.**

### **Contrast rules include:**

### **text contrast against backgrounds must remain readable**

### **token recommendations should avoid obvious inaccessible combinations**

### **error/warning/success states must not rely on color alone**

### **badges and interface labels must remain legible across supported surfaces**

### **Contrast handling is especially important because the product allows dynamic token values.**

### 

### **51.9 Form Accessibility Rules**

### **Forms shall be accessible and understandable.**

### **Form rules include:**

### **visible labels**

### **association of labels with inputs**

### **clear error messaging**

### **indication of required fields**

### **accessible helper text**

### **field grouping where useful**

### **no placeholder-only labeling**

### **Forms are central to onboarding, settings, and review workflows and must remain usable.**

### 

### **51.10 Modal / Popup Accessibility Rules**

### **Modal interfaces shall meet accessible interaction expectations.**

### **Modal accessibility rules include:**

### **keyboard open/close support**

### **focus trapped within modal while open**

### **focus returned to invoking control when closed**

### **ESC or approved close behavior where appropriate**

### **screen-reader readable title and content**

### **no inaccessible full-screen blocking behavior without escape path**

### **Because the product uses styled modals for important confirmations, this area requires extra care.**

### 

## **52. Import, Export, Backup, and Restore**

### **52.1 Export Use Cases**

### **The plugin shall support these export modes:**

### **Full operational backup\**

### **Pre-uninstall backup\**

### **Support bundle\**

### **Template-only export\**

### **Plan/artifact export\**

### **Each export mode shall define included and excluded categories.**

### 

### **52.2 Export Package Structure**

### **All exports shall use a ZIP archive with this root structure:**

### **manifest.json\**

### **settings/\**

### **profiles/\**

### **registries/\**

### **compositions/\**

### **plans/\**

### **tokens/\**

### **artifacts/ (optional)\**

### **logs/ (optional)\**

### **docs/ (optional)\**

### 

### **52.3 Export Manifest Rules**

### **manifest.json shall contain:**

### **export type\**

### **export timestamp\**

### **plugin version\**

### **schema version\**

### **source site URL\**

### **included categories\**

### **excluded categories\**

### **package checksum list\**

### **restore notes\**

### **compatibility flags\**

### 

### **52.4 Included Data Categories**

### **Full export shall include by default:**

### **plugin settings excluding secrets\**

### **brand/business profile\**

### **section/page template registry snapshots\**

### **compositions\**

### **Build Plans\**

### **token sets\**

### **uninstall/restore metadata\**

### 

### **52.5 Optional Data Categories**

### **Optional export categories:**

### **raw AI artifacts\**

### **normalized AI outputs\**

### **crawl snapshots\**

### **logs\**

### **reporting history\**

### **rollback snapshots\**

### **These may be included or excluded by export mode.**

### 

### **52.6 Excluded Data Categories**

### **Always excluded by default:**

### **API keys\**

### **passwords\**

### **auth/session tokens\**

### **runtime lock rows\**

### **temporary cache entries\**

### **corrupted partial package remnants\**

### 

### **52.7 Import Validation Rules**

### **Before import:**

### **permission check\**

### **ZIP integrity check\**

### **manifest check\**

### **schema-version support check\**

### **checksum verification where available\**

### **prohibited file rejection\**

### **conflict pre-scan\**

### **Import shall stop before writing if any blocking validation fails.**

### 

### **52.8 Restore Order of Operations**

### **Restore shall run in this order:**

### **settings\**

### **profile data\**

### **registries\**

### **compositions\**

### **token sets\**

### **Build Plans\**

### **crawl snapshots (if included)\**

### **AI artifacts (if included)\**

### **logs (if included)\**

### 

### **52.9 Conflict Resolution Rules**

### **When conflicts exist, the restore UI shall force the user to choose one of:**

### **overwrite incoming object over current\**

### **keep current and skip import object\**

### **import as duplicate/new object where allowed\**

### **cancel restore\**

### **No silent overwrite is permitted.**

### 

### **52.10 Cross-Version Import Rules**

### **Import compatibility rules:**

### **same major schema version: allowed\**

### **older supported schema version: allowed with migration\**

### **newer unsupported schema version: blocked\**

### **older deprecated schema below migration floor: blocked\**

### 

### **52.11 Uninstall Prompt Export Rules**

### **Before uninstall cleanup, the plugin shall present an export prompt with these choices:**

### **Export full backup\**

### **Export settings/profile only\**

### **Skip export and continue\**

### **Cancel uninstall\**

### **The uninstall screen shall clearly state that built pages will remain.**

### 

## **53. Plugin Lifecycle Management**

### **53.1 Activation Flow**

### **Activation shall execute:**

### **environment validation\**

### **required dependency check\**

### **option initialization\**

### **table/schema creation check\**

### **capability registration\**

### **recurring schedule registration\**

### **install notification eligibility check\**

### **If any blocking prerequisite fails, activation shall abort with explanation.**

### 

### **53.2 First-Time Setup Flow**

### **On first successful activation, the plugin shall direct authorized users to Dashboard or Onboarding & Profile with:**

### **welcome state\**

### **dependency summary\**

### **reporting disclosure\**

### **next-step action\**

### 

### **53.3 Upgrade Flow**

### **On version change, the plugin shall:**

### **detect old version\**

### **compare schema versions\**

### **run migrations as needed\**

### **record upgrade result\**

### **surface upgrade notices if user attention is required\**

### 

### **53.4 Migration Flow**

### **Migrations shall be:**

### **versioned\**

### **ordered\**

### **logged\**

### **retry-aware where safe\**

### **blocked from re-running once marked successful unless explicitly designed otherwise\**

### 

### **53.5 Deactivation Flow**

### **On deactivation, the plugin shall:**

### **unschedule cron jobs\**

### **stop queue workers\**

### **retain all plugin-owned data by default\**

### **leave built pages unchanged\**

### **No uninstall cleanup shall occur on deactivation.**

### 

### **53.6 Uninstall Flow**

### **On uninstall, the plugin shall:**

### **present export opportunity\**

### **present cleanup choices\**

### **remove scheduled events\**

### **remove plugin-owned tables/options only if chosen\**

### **leave built pages untouched\**

### 

### **53.7 Reinstall Flow**

### **On reinstall after uninstall:**

### **environment validation shall run again\**

### **the plugin shall start as a fresh install unless a restore package is imported\**

### **built pages previously created by the plugin may still exist, but shall not be automatically rebound to prior plan state without explicit restore\**

### 

### **53.8 Restore From Export Flow**

### **Restore shall be available after fresh install or existing install, subject to import rules.**

### **A successful restore shall:**

### **rebuild plugin-owned settings/state\**

### **reattach plans, tokens, profiles, and supported artifacts\**

### **not rewrite page content unless the restore package explicitly includes a future approved page-rebinding path\**

### 

### **53.9 Built Page Survival Guarantees**

### **The plugin guarantees that uninstall cleanup shall not intentionally delete pages created by the plugin unless a future optional destructive cleanup mode is separately approved and explicitly chosen by the user.**

### 

## **54. Compatibility and Interoperability**

### **54.1 Theme Compatibility Strategy**

### **Official support target:**

### **GeneratePress\**

### **General compatibility target:**

### **standards-compliant block-capable themes\**

### **Unsupported by policy:**

### **themes that disable or fundamentally alter core page/block behavior in ways that break native content rendering\**

### 

### **54.2 GenerateBlocks Compatibility Rules**

### **GenerateBlocks is part of the required stack and shall be treated as first-class supported composition infrastructure. Template output and testing shall assume GenerateBlocks is present.**

### 

### **54.3 ACF Compatibility Rules**

### **ACF Pro is required and shall be treated as a locked architectural dependency. Field-group generation and assignment behavior shall be tested against the supported ACF version floor and current validated release range.**

### 

### **54.4 LPagery Compatibility Rules**

### **LPagery is officially supported only for workflows that rely on the token map defined in Section 21.**

### **Rules:**

### **LPagery is optional\**

### **LPagery absence shall disable token-driven bulk workflows only\**

### **unsupported token-field combinations shall be blocked or warned, not silently accepted\**

### 

### **54.5 SEO Plugin Compatibility Rules**

### **The plugin shall support interoperability mode, not total replacement mode.**

### **Initial official compatibility posture:**

### **store recommendations independently by default\**

### **support later direct write adapters only for explicitly approved SEO plugins\**

### **never assume one SEO plugin is universally present\**

### 

### **54.6 Featured Image / Media Plugin Compatibility Rules**

### **The plugin shall rely on normal WordPress media handling by default.\
No plugin-specific featured-image integration is required for core operation.\
Later adapters may be added for approved media plugins.**

### 

### **54.7 Caching Plugin Considerations**

### **The plugin shall be compatible with normal page caching so long as:**

### **asset versioning is respected\**

### **admin actions that change token-driven CSS or published output surface cache refresh guidance where needed\**

### **The plugin shall not attempt to directly manage every cache layer by default.**

### 

### **54.8 Security Plugin Considerations**

### **The plugin shall be built to coexist with common security plugins by:**

### **using proper nonces\**

### **enforcing capabilities\**

### **avoiding unsafe endpoint patterns\**

### **avoiding client-side secret handling\**

### **If a security plugin blocks required REST/AJAX behavior, diagnostics shall identify the blocked workflow.**

### 

### **54.9 Multisite Compatibility Decision**

### **Formal decision:\
The plugin shall support site-level operation on WordPress multisite but shall not support network-wide centralized management in the initial full product specification.**

### **This means:**

### **each site manages its own settings, plans, and artifacts\**

### **no shared network registry or shared provider pool is assumed\**

### **network activation is not an officially supported operating mode unless separately validated later\**

### 

### **54.10 Plugin Conflict Detection Rules**

### **The plugin shall detect and warn about:**

### **missing required dependencies\**

### **unsupported dependency versions\**

### **blocked REST/admin conditions\**

### **unsupported network activation state on multisite\**

### **potential SEO ownership conflicts where a direct integration is active\**

### **import package/schema incompatibility**

### 

### 

## **55. Performance and Scalability**

### **55.1 Performance Objectives**

### **The plugin shall aim to remain responsive, understandable, and operationally safe even as the site, template library, logs, and artifacts grow.**

### **Performance objectives include:**

### **acceptable admin usability**

### **reasonable front-end impact**

### **scalable log handling**

### **manageable artifact storage**

### **controlled heavy-process execution through queueing**

### **Performance must support both small and larger installs.**

### 

### **55.2 Admin Performance Rules**

### **Admin screens shall avoid unnecessarily expensive operations during ordinary page load.**

### **Rules include:**

### **do not load every artifact or log record on initial screen render**

### **use pagination or filtering for large datasets**

### **avoid eager loading of unrelated heavy data**

### **keep Build Plan screens focused on current plan context**

### **The admin must remain operable as usage grows.**

### 

### **55.3 Front-End Performance Rules**

### **The plugin shall minimize front-end overhead.**

### **Rules include:**

### **load only needed front-end assets where practical**

### **favor static content over expensive runtime computation**

### **avoid plugin-dependent rendering for content that could be stored statically**

### **avoid excessive DOM bloat where template design can remain disciplined**

### **Front-end performance matters because the plugin produces live site content.**

### 

### **55.4 Query Minimization Rules**

### **The plugin should minimize wasteful queries.**

### **This includes:**

### **using the right storage model for the right data**

### **indexing custom tables appropriately**

### **avoiding repetitive lookups in loops where caching or batching is better**

### **querying only the data needed for the current screen or action**

### **Query discipline is part of maintainability and performance.**

### 

### **55.5 Asset Loading Rules**

### **Assets should be loaded intentionally.**

### **Rules include:**

### **admin assets only on relevant screens**

### **front-end assets only where needed**

### **no unnecessary global asset loading**

### **versioning for cache behavior**

### **modular asset structure where possible**

### **Asset discipline improves both performance and compatibility.**

### 

### **55.6 Queue Offloading Rules**

### **Heavy work should be offloaded to queued execution where appropriate.**

### **This includes:**

### **crawl runs**

### **AI runs**

### **export generation**

### **bulk page operations**

### **repeated reporting retries**

### **Queue offloading should protect the admin UX from long blocking operations.**

### 

### **55.7 Large Site Handling Rules**

### **The plugin shall support bounded behavior on larger sites.**

### **This may include:**

### **crawl scope control**

### **paginated plan views**

### **summarized artifact handling**

### **filtered logs**

### **lazy loading of heavy datasets**

### **re-crawl comparison instead of reprocessing everything blindly**

### **Large sites should not automatically make the plugin unusable.**

### 

### **55.8 Large Template Library Handling Rules**

### **As the template registry grows, the system shall remain navigable.**

### **This may include:**

### **filtering by category**

### **status filtering**

### **search**

### **deprecation handling**

### **composition validation that does not become excessively slow**

### **The registry must remain an asset, not become its own clutter problem.**

### 

### **55.9 AI Artifact Volume Handling Rules**

### **AI artifacts can become large and numerous over time.**

### **Handling rules include:**

### **retention management**

### **summary views**

### **optional inclusion in exports**

### **storage distinction between raw and normalized data**

### **avoidance of loading large raw artifacts unnecessarily in list views**

### **Artifact volume must be expected and managed.**

### 

### **55.10 Logging Volume Handling Rules**

### **Logs can grow quickly and require governance.**

### **Handling rules include:**

### **retention windows**

### **filtering**

### **pagination**

### **archival policy**

### **export support**

### **no unbounded in-memory loading of large logs in admin UI**

### **Operational visibility must remain scalable.**

### 

## **56. Testing and Quality Assurance**

### **56.1 QA Philosophy**

### **QA shall focus on correctness, safety, usability, recoverability, and long-term maintainability.**

### **The philosophy is:**

### **test the system as it is meant to be used**

### **test failure paths, not only happy paths**

### **test cross-domain interactions**

### **test product promises, especially survivability and planner/executor separation**

### **QA is not only about bugs. It is about product trust.**

### 

### **56.2 Unit Test Scope**

### **Unit tests should cover isolated logic where practical.**

### **This may include:**

### **validators**

### **normalizers**

### **token mapping logic**

### **schema handling**

### **provider driver utilities**

### **migration helpers**

### **compatibility rules**

### **Unit tests help stabilize reusable logic.**

### 

### **56.3 Integration Test Scope**

### **Integration tests should cover meaningful interactions between subsystems.**

### **Examples include:**

### **onboarding to AI run preparation**

### **AI output validation to Build Plan generation**

### **template composition to ACF assignment**

### **execution action to snapshot/log creation**

### **export generation to import validation**

### **Integration testing is especially important for this product because many features are orchestration features.**

### 

### **56.4 End-to-End Test Scope**

### **End-to-end tests should cover major user workflows.**

### **Examples include:**

### **first install to onboarding completion**

### **AI run to Build Plan creation**

### **Build Plan approval to page creation**

### **page replacement flow**

### **token review flow**

### **uninstall export and restore flow**

### **End-to-end testing verifies that the product works as a product, not just as pieces.**

### 

### **56.5 Security Test Scope**

### **Security tests should cover:**

### **permission enforcement**

### **nonce validation**

### **unsafe request rejection**

### **import safety**

### **secret leakage prevention**

### **redaction correctness**

### **endpoint authorization**

### **Security testing is required, not optional.**

### 

### **56.6 Accessibility Test Scope**

### **Accessibility testing should cover:**

### **keyboard navigation**

### **focus management**

### **screen-reader labeling logic**

### **modal accessibility**

### **form accessibility**

### **contrast review**

### **front-end section accessibility in generated pages**

### **Accessibility needs real testing, not only good intentions.**

### 

### **56.7 Performance Test Scope**

### **Performance testing should cover:**

### **admin page responsiveness under realistic data volumes**

### **heavy workflow queue behavior**

### **export generation behavior**

### **crawl limits**

### **artifact rendering/listing behavior**

### **front-end asset impact**

### **Performance should be evaluated in realistic conditions, not only small demo sites.**

### 

### **56.8 Failure Scenario Test Matrix**

### **The QA plan shall include a failure-scenario matrix.**

### **Scenarios may include:**

### **provider auth failure**

### **invalid AI output**

### **queue deadlock**

### **failed page replacement**

### **failed menu update**

### **failed export package**

### **restore conflict**

### **reporting transport failure**

### **migration interruption**

### **Failure testing is critical for this product’s safety claims.**

### 

### **56.9 Migration Test Matrix**

### **Migration tests shall cover:**

### **upgrade from prior versions**

### **schema changes**

### **table changes**

### **retained Build Plans**

### **retained artifacts**

### **retained token sets**

### **retained template registries**

### **retained field assignments**

### **Migration safety is essential in long-term private distribution.**

### 

### **56.10 Compatibility Test Matrix**

### **Compatibility testing should include combinations of:**

### **supported WordPress versions**

### **supported PHP versions**

### **supported dependency versions**

### **GeneratePress/GenerateBlocks environments**

### **ACF presence/version**

### **LPagery present/absent**

### **caching/security plugin coexistence where practical**

### **Compatibility must be tested as a matrix, not as a single perfect environment.**

### 

### **56.11 Manual QA Checklists**

### **The product should maintain manual QA checklists for:**

### **onboarding**

### **AI provider setup**

### **Build Plan steps**

### **page creation**

### **page replacement**

### **token updates**

### **export/import**

### **uninstall/reinstall**

### **logs and diagnostics**

### **reporting visibility**

### **Manual QA remains valuable for workflow-heavy systems.**

### 

### **56.12 Release Gate Criteria**

### **No release should ship without meeting agreed release gates.**

### **Release gates shall include:**

### **critical workflow validation**

### **major regression check**

### **migration safety where applicable**

### **no unresolved high-severity security issue**

### **documentation and changelog readiness**

### **known-risk review**

### **Release quality must be intentional.**

### 

## **57. Coding Standards and Development Conventions**

### **57.1 WordPress Coding Standards**

### **The plugin shall follow WordPress-oriented coding standards where practical for PHP and related platform behaviors.**

### **This includes:**

### **predictable WordPress-style function usage**

### **safe data handling**

### **familiar code organization**

### **readability for WordPress-experienced maintainers**

### **Private distribution is not a reason to abandon disciplined standards.**

### 

### **57.2 Namespacing Strategy**

### **The codebase shall use a consistent namespacing or equivalent isolation strategy to avoid collisions and improve structure.**

### **This applies to:**

### **PHP classes**

### **service identifiers**

### **hooks where custom naming applies**

### **constants where needed**

### **schema names where relevant**

### **Namespacing must be systematic, not partial.**

### 

### **57.3 File and Class Naming Conventions**

### **Files and classes shall use predictable naming patterns.**

### **Conventions should support:**

### **easy discovery**

### **one responsibility per class where feasible**

### **alignment with domain boundaries**

### **consistency across modules**

### **Naming should reduce maintenance friction.**

### 

### **57.4 Hook Naming Conventions**

### **Any custom hooks or filters introduced by the plugin shall use clear and namespaced naming.**

### **Hook naming should:**

### **reflect plugin identity**

### **reflect action/purpose**

### **avoid collisions**

### **be documented if public extension is intended**

### **Hooks are part of the system contract when exposed.**

### 

### **57.5 Data Schema Naming Conventions**

### **Schema objects, table columns, manifest fields, and structured JSON keys shall follow a consistent naming convention.**

### **Conventions should prioritize:**

### **predictability**

### **machine-readability**

### **human interpretability**

### **stability across versions where feasible**

### **Schema names must not drift casually.**

### 

### **57.6 CSS Naming Conventions**

### **CSS naming shall follow the plugin’s fixed contract policy.**

### **Conventions shall include:**

### **plugin-prefixed names**

### **section/page scope logic**

### **role-oriented child selectors**

### **explicit modifiers**

### **collision avoidance**

### **CSS naming is part of product architecture.**

### 

### **57.7 JavaScript Naming Conventions**

### **JavaScript code shall use a consistent naming and module organization strategy.**

### **This shall apply to:**

### **components**

### **stores/state objects if used**

### **events**

### **actions**

### **helpers**

### **admin modules**

### **JavaScript structure must remain understandable at scale.**

### 

### **57.8 PHP Organization Standards**

### **PHP code shall be organized by domain and responsibility.**

### **Organization standards should include:**

### **separation of bootstrap from service logic**

### **separation of admin from execution logic**

### **separation of data access from orchestration logic**

### **testable service boundaries**

### **minimal uncontrolled global state**

### **PHP organization should reflect the architecture defined in the specification.**

### 

### **57.9 Documentation Standards**

### **Internal technical documentation shall be maintained alongside development.**

### **Documentation standards shall include:**

### **class/service purpose documentation**

### **data contract documentation**

### **migration notes**

### **provider-driver notes**

### **exposed extension-point documentation where relevant**

### **Documentation is essential for long-term maintainability.**

### 

### **57.10 Inline Comment Standards**

### **Inline comments shall be used deliberately.**

### **They should explain:**

### **non-obvious logic**

### **security-sensitive reasoning**

### **migration edge cases**

### **compatibility workarounds**

### **why something is done, not merely what obvious code is doing**

### **Comments should improve maintainability, not create noise.**

### 

## **58. Release Management and Versioning**

### **58.1 Plugin Versioning Strategy**

### **The plugin shall use a clear and consistent versioning strategy.**

### **Versioning must support:**

### **upgrade detection**

### **migration control**

### **release-note clarity**

### **compatibility reasoning**

### **support troubleshooting**

### **The version system should be stable and understandable.**

### 

### **58.2 Template Registry Versioning**

### **The section and page template registries shall be version-aware.**

### **This versioning should support:**

### **registry evolution**

### **deprecation handling**

### **historical plan interpretation**

### **export/import compatibility**

### **migration of dependent structures where needed**

### **Registry versioning is essential because templates are a core system contract.**

### 

### **58.3 Prompt Pack Versioning**

### **Prompt packs shall be versioned independently from the plugin where useful.**

### **This supports:**

### **AI planning comparison**

### **prompt regression analysis**

### **historical run interpretation**

### **safer prompt evolution**

### **Prompt changes must be traceable.**

### 

### **58.4 Schema Versioning**

### **Structured schemas used for input, output, manifests, or tables shall be version-aware.**

### **Schema versioning supports:**

### **migration**

### **compatibility checks**

### **debugging**

### **import/export safety**

### **Schema drift without versioning is not acceptable.**

### 

### **58.5 Migration Versioning**

### **Migrations shall be tied to explicit version logic.**

### **This includes:**

### **plugin version**

### **schema version**

### **table version**

### **registry version where relevant**

### **Migration versioning allows controlled upgrade behavior.**

### 

### **58.6 Release Notes Standards**

### **Each meaningful release should include release notes.**

### **Release notes should cover:**

### **what changed**

### **what was added**

### **what was fixed**

### **migrations or compatibility notes**

### **deprecations**

### **any known limitations**

### **Release notes are part of operational trust.**

### 

### **58.7 Breaking Change Policy**

### **Breaking changes shall be treated explicitly.**

### **A breaking change may include:**

### **removed support**

### **incompatible schema shift**

### **changed contract behavior**

### **changed export format without compatible restore support**

### **changed template meaning that invalidates old assumptions**

### **Breaking changes must be documented and, where possible, mitigated.**

### 

### **58.8 Deprecation Policy**

### **The plugin shall prefer deprecation over abrupt removal where practical.**

### **Deprecation policy shall include:**

### **mark deprecated**

### **explain why**

### **indicate replacement**

### **define removal timing if relevant**

### **preserve compatibility for a reasonable period where feasible**

### **Deprecation is part of responsible evolution.**

### 

### **58.9 Rollback Release Policy**

### **If a release causes severe issues, the product should support a rollback-oriented release policy.**

### **This may include:**

### **keeping prior release packages**

### **documenting rollback paths**

### **minimizing irreversible migrations where possible**

### **supporting restore from export where needed**

### **Release rollback planning is part of operational maturity.**

### 

## **59. Build Plan and Implementation Roadmap**

### **59.1 Roadmap Philosophy**

### **The roadmap shall follow a dependency-first sequence that locks contracts before high-complexity automation is built.**

### 

### **59.2 Build Order Principles**

### **Build order shall prioritize:**

### **architecture and storage\**

### **registries and field model\**

### **rendering and survivability\**

### **onboarding and crawl\**

### **AI and schema validation\**

### **Build Plan UI\**

### **execution and rollback\**

### **reporting/export/hardening\**

### 

### **59.3 Foundational Build Phase**

### **Deliverables:**

### **plugin bootstrap\**

### **file/module structure\**

### **settings framework\**

### **lifecycle hooks\**

### **custom tables framework\**

### **capability registration\**

### **diagnostics skeleton\**

### **Acceptance gate:**

### **plugin installs, activates, deactivates cleanly\**

### **environment validation works\**

### **no critical bootstrap errors\**

### 

### **59.4 Registry and Content Model Phase**

### **Deliverables:**

### **section template object\**

### **page template object\**

### **composition object\**

### **version snapshot object\**

### **registry admin screens\**

### **registry export basics\**

### **Acceptance gate:**

### **templates can be created, viewed, versioned, and deprecated\**

### 

### **59.5 Rendering and ACF Phase**

### **Deliverables:**

### **section blueprint rendering\**

### **page-template instantiation\**

### **ACF group registration\**

### **page assignment logic\**

### **native block output pipeline\**

### **survivability test pass\**

### **Acceptance gate:**

### **page builds render correctly and remain meaningful with plugin deactivated\**

### 

### **59.6 Onboarding and Profile Phase**

### **Deliverables:**

### **profile storage\**

### **onboarding UI\**

### **saved drafts\**

### **rerun/prefill logic\**

### **provider-less onboarding readiness state\**

### **Acceptance gate:**

### **complete profile intake works end-to-end\**

### 

### **59.7 Crawl Engine Phase**

### **Deliverables:**

### **crawler\**

### **crawl snapshot tables\**

### **meaningful-page classification\**

### **recrawl comparison\**

### **Acceptance gate:**

### **crawl produces stable snapshots on supported test sites\**

### 

### **59.8 AI Provider and Prompt Phase**

### **Deliverables:**

### **provider drivers\**

### **credential handling\**

### **prompt packs\**

### **input packaging\**

### **output validation\**

### **artifact storage\**

### **Acceptance gate:**

### **valid AI run produces normalized output or explicit validation failure without corruption\**

### 

### **59.9 Build Plan UI Phase**

### **Deliverables:**

### **Build Plan object model\**

### **plan generation\**

### **stepper UI\**

### **step 1–7 shells\**

### **row/detail view system\**

### **Acceptance gate:**

### **users can open, navigate, approve, and deny plan items\**

### 

### **59.10 Execution Engine Phase**

### **Deliverables:**

### **single action executor\**

### **bulk executor\**

### **queue integration\**

### **page build/replacement\**

### **menu apply\**

### **token apply\**

### **finalize flow\**

### **Acceptance gate:**

### **approved actions execute with per-item status and logs\**

### 

### **59.11 Diff / Rollback Phase**

### **Deliverables:**

### **snapshots\**

### **diff summaries\**

### **rollback job type\**

### **rollback UI\**

### **rollback validation\**

### **Acceptance gate:**

### **at least page replacement and token changes support rollback where designed\**

### 

### **59.12 Diagnostics and Reporting Phase**

### **Deliverables:**

### **logs screen\**

### **queue monitoring\**

### **install report\**

### **heartbeat report\**

### **error reporting\**

### **reporting failure handling\**

### **Acceptance gate:**

### **reporting works and failures are logged clearly\**

### 

### **59.13 Import / Export Phase**

### **Deliverables:**

### **export modes\**

### **ZIP packaging\**

### **restore flow\**

### **uninstall export prompt\**

### **import validation\**

### **Acceptance gate:**

### **clean export/import cycle restores plugin state on test environment\**

### 

### **59.14 Hardening and QA Phase**

### **Deliverables:**

### **security review fixes\**

### **accessibility fixes\**

### **performance tuning\**

### **migration testing\**

### **compatibility test pass\**

### **redaction review\**

### **Acceptance gate:**

### **all high-severity issues closed or formally waived\**

### 

### **59.15 Production Readiness Phase**

### **Deliverables:**

### **release candidate\**

### **release notes\**

### **support package\**

### **known-risk register\**

### **sign-off artifacts\**

### **Acceptance gate:**

### **Product Owner, QA, and technical lead approve release**

### 

### 

## **60. Milestones, Deliverables, and Acceptance Gates**

### **60.1 Milestone Definitions**

### **The roadmap shall use these milestones:**

### **M1: Foundation & Lifecycle\**

### **M2: Registries & Templates\**

### **M3: Rendering & ACF\**

### **M4: Onboarding & Profiles\**

### **M5: Crawl Engine\**

### **M6: AI Planning Core\**

### **M7: Build Plan UI\**

### **M8: Execution Engine\**

### **M9: Rollback & Diff\**

### **M10: Reporting & Diagnostics\**

### **M11: Import/Export & Restore\**

### **M12: Hardening & Release\**

### 

### **60.2 Deliverable Checklist Per Milestone**

### **Each milestone must include:**

### **code complete for milestone scope\**

### **migrations updated if needed\**

### **documentation updated\**

### **QA checklist completed\**

### **known limitations recorded\**

### **acceptance artifacts stored\**

### 

### **60.3 Entry Criteria**

### **A milestone may start only when:**

### **prerequisite milestone accepted\**

### **unresolved blocker decisions for that scope are closed\**

### **data/schema dependencies are approved\**

### **UI dependencies are stable enough for implementation\**

### 

### **60.4 Exit Criteria**

### **A milestone exits only when:**

### **acceptance tests pass\**

### **no critical or high-severity unresolved defect remains in milestone scope\**

### **documentation is updated\**

### **sign-off is recorded\**

### 

### **60.5 Acceptance Test Requirements**

### **Each milestone shall define:**

### **minimum happy-path tests\**

### **minimum failure-path tests\**

### **migration/compatibility tests where applicable\**

### **role/capability checks where applicable\**

### 

### **60.6 Documentation Completion Requirements**

### **For milestone acceptance, the following must exist:**

### **updated spec impacts recorded\**

### **changelog draft\**

### **internal implementation notes\**

### **QA notes\**

### **user/admin guidance for new workflows if exposed\**

### 

### **60.7 Demo / Review Requirements**

### **Milestones M3 and above require a formal review/demo to the Product Owner.\
Security-sensitive milestones additionally require technical review.\
Release milestone requires QA review.**

### 

### **60.8 Sign-Off Requirements**

### **Required sign-off by milestone:**

### **M1–M4: Product Owner + Technical Lead\**

### **M5–M10: Product Owner + Technical Lead + QA\**

### **M11–M12: Product Owner + Technical Lead + QA + Security Review where applicable**

### 

## **61. Known Risks, Open Questions, and Decision Log**

### **61.1 Product Risks**

### **Current known product risks:**

### **Build Plan complexity may overwhelm users if not staged carefully.\**

### **Too many advanced settings may dilute the plugin’s structured nature.\**

### **Aggressive replacement workflows may feel risky without excellent diff/rollback UX.\**

### 

### **61.2 Technical Risks**

### **Current known technical risks:**

### **page survivability while retaining rich orchestration metadata\**

### **migration complexity across template/schema changes\**

### **queue reliability on low-quality hosting\**

### **export/import completeness without over-exporting sensitive data\**

### 

### **61.3 Operational Risks**

### **Current known operational risks:**

### **heartbeat/report delivery failures due to host email limitations\**

### **broad support matrix across private installs\**

### **user environments with blocked REST/AJAX or strict security tools\**

### 

### **61.4 Privacy / Reporting Risks**

### **Current known privacy/reporting risks:**

### **misunderstanding of mandatory reporting behavior\**

### **accidental oversharing in diagnostics if redaction is incomplete\**

### **export bundles containing more than intended if package assembly is poorly scoped\**

### 

### **61.5 AI Output Reliability Risks**

### **Current AI risks:**

### **schema-invalid responses\**

### **low-confidence but persuasive bad recommendations\**

### **provider differences causing inconsistent plan quality\**

### **poor user inputs degrading output quality\**

### 

### **61.6 Compatibility Risks**

### **Current compatibility risks:**

### **future GenerateBlocks changes\**

### **future ACF changes\**

### **multisite site-level edge cases\**

### **SEO plugin ownership overlap\**

### 

### **61.7 Open Questions**

### **Open questions that still require formal final decisions:**

### **Which SEO plugins receive first-party adapter support first?\**

### **Which provider set is included in the first production release?\**

### **Will media recommendation remain advisory only, or will a first media integration be included?\**

### **What exact release/update delivery mechanism will be used for private updates?\**

### 

### **61.8 Deferred Decisions**

### **Deferred decisions currently include:**

### **first-party SEO adapter list\**

### **first-party featured-image/media adapter list\**

### **future network-aware multisite management\**

### **future direct internal-link insertion execution\**

### **These are deferred intentionally and do not block the core architecture.**

### 

### **61.9 Decision Log Structure**

### **Every decision record shall contain:**

### **Decision ID\**

### **Date\**

### **Owner\**

### **Status (proposed, approved, superseded, rejected)\**

### **Summary\**

### **Rationale\**

### **Alternatives considered\**

### **Impacted sections\**

### **Effective version\**

### 

### **61.10 Escalation Rules**

### **Escalation path:**

### **implementation issue → technical lead\**

### **product/scope issue → Product Owner\**

### **security/privacy issue → Product Owner + security reviewer\**

### **release-blocking risk → formal milestone review\**

### **No critical unresolved issue may be silently carried into release.**

### 

### 

## **62. Appendices**

### **62.1 Glossary**

### **The Glossary appendix shall define key product and technical terms used throughout the specification.**

### **This should include:**

### **template-related terms**

### **AI/planning terms**

### **execution terms**

### **storage/model terms**

### **reporting/privacy terms**

### **The glossary is intended to reduce ambiguity and help future collaborators enter the project faster.**

### 

## **62. Appendices**

### **62.1 Glossary**

### The Glossary appendix shall be mandatory and include all defined terms used in architecture, planning, execution, reporting, storage, and privacy sections.

### Minimum required entries:

### section template\

### page template\

### composition\

### Build Plan\

### AI run\

### artifact\

### snapshot\

### rollback\

### token set\

### reporting event\

### 

### **62.2 Capability Matrix Appendix**

### The appendix shall include a full table mapping:

### each custom capability\

### Administrator\

### Editor\

### Author\

### Contributor\

### Subscriber\

### custom/support roles if later added\

### This appendix must mirror Section 44 exactly.

### 

### **62.3 Data Schema Appendix**

### The appendix shall contain:

### CPT schema summaries\

### table schemas\

### key option structures\

### token set schema\

### Build Plan storage schema\

### crawl snapshot schema\

### 

### **62.4 Prompt Schema Appendix**

### The appendix shall document:

### prompt-pack object structure\

### system prompt sections\

### injection manifest shape\

### provider-specific notes\

### prompt version fields\

### 

### **62.5 AI Output Schema Appendix**

### The appendix shall contain the fully formalized machine schema matching Section 28, including:

### top-level object definitions\

### enums\

### required fields\

### nullable rules\

### example valid payload\

### example invalid payload notes\

### 

### **62.6 Export ZIP Manifest Appendix**

### The appendix shall contain:

### exact manifest.json schema\

### folder tree\

### package examples by export type\

### restore notes\

### 

### **62.7 Error Email Templates Appendix**

### The appendix shall define:

### exact subject formats\

### body block order\

### severity variants\

### included field list\

### excluded field list\

### 

### **62.8 Heartbeat Email Templates Appendix**

### The appendix shall define:

### exact subject format\

### body sections\

### status enum values\

### example healthy / warning / critical messages\

### 

### **62.9 Install Notification Email Template Appendix**

### The appendix shall define:

### exact subject\

### required body fields\

### example payload\

### duplicate suppression notes\

### 

### **62.10 Admin Screen Inventory Appendix**

### The appendix shall list every screen with:

### screen slug\

### title\

### capability required\

### primary actions\

### related object domains\

### 

### **62.11 Section Template Inventory Appendix**

### The appendix shall list every section template with:

### key\

### name\

### purpose\

### category\

### variants\

### helper status\

### deprecation status\

### version\

### 

### **62.12 Page Template Inventory Appendix**

### The appendix shall list every page template with:

### key\

### name\

### purpose\

### ordered sections\

### optional sections\

### hierarchy hint\

### one-pager status\

### version\

### deprecation status
