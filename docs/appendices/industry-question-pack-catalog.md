# Industry Question Pack Catalog (Prompt 362)

**Spec:** industry-question-pack-contract.md; industry-onboarding-field-contract.md; industry-profile-schema.md.  
**Purpose:** Lists built-in question packs for the first four industries. Answers persist in Industry Profile under `question_pack_answers[industry_key][field_key]`.

---

## 1. Loading

- **Source:** `plugin/src/Domain/Industry/Profile/QuestionPacks/*.php` (cosmetology-nail-pack.php, realtor-pack.php, plumber-pack.php, disaster-recovery-pack.php).
- **Registry:** Industry_Question_Pack_Registry. Industry_Packs_Module loads definitions via Industry_Question_Pack_Definitions::default_packs().
- **Storage mapping:** Industry_Profile_Repository::merge_profile( array( 'question_pack_answers' => $by_industry ) ). Shape: `{ [industry_key]: { [field_key]: scalar } }`. Read via get_profile()[ 'question_pack_answers' ].

---

## 2. Packs and fields

| industry_key | pack_id | name | Fields (key → label, type) |
|--------------|---------|------|----------------------------|
| cosmetology_nail | cosmetology_nail | Cosmetology / Nail | service_types (Primary service types, text), booking_style (Booking style, text), license_notes (License or compliance notes, textarea) |
| realtor | realtor | Realtor | market_focus (Market focus, text), listing_types (Listing types, text), service_areas (Service areas or geography, textarea) |
| plumber | plumber | Plumber | service_scope (Service scope, text), emergency_offered (Emergency service offered, boolean), service_areas (Service areas, textarea) |
| disaster_recovery | disaster_recovery | Disaster Recovery | response_type (Response type, text), emergency_24_7 (24/7 emergency response, boolean), coverage_areas (Coverage areas, textarea) |

---

## 3. Validation and help text

- Field definitions may include optional `help_text` for UI and validation notes.
- Answers are scalar only; invalid or missing entries are stripped on normalize. No secrets.
- Admin-only mutation via onboarding or profile editing (capability and nonce enforced at handler).

---

## 4. Pack intents

- **cosmetology_nail:** Gather cosmetology or nail business context for services, booking, and compliance.
- **realtor:** Gather real estate agent context: market focus, listing types, and geography.
- **plumber:** Gather plumbing business context: residential vs commercial, emergency vs scheduled.
- **disaster_recovery:** Gather disaster recovery / restoration context: response type and scope.
