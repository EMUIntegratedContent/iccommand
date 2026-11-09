# IcContact — shared contact entity

## Context
Programs, scholarships and buildings each need contacts (people or organizations). Like `ic_colleges` /
`ic_departments`, contacts are shared across IC Command apps, so they live in one `ic_contacts` table
managed by global admins. Each consuming record can have any number of them, picked in its edit form
with one searchable picker per contact. The what and how are in `docs/ic-contacts.md`; this file keeps
the reasoning and the gotchas.

Decisions (confirmed):
- **Many-to-many** for programs, scholarships and buildings, through link tables. The first version of
  the PR gave all five record types a single `contact_id`; review changed that.
- **Colleges and departments have no contacts.** (The first version had them. Review removed them.)
- Deleting a contact in use is **blocked** with usage counts (same as colleges/departments). The contact
  side of each link FK has no cascade.
- Only global admins create/edit contacts, at `/admin/contacts`. Module forms only pick existing ones.
- Public feeds for scholarships, buildings and programs include each record's contacts, limited to name,
  email, phone and url. No contact id or other contact fields.
- The public key is `icContactsInfo`, a list that is `[]` without contacts. Scholarships keep publishing
  `contact` (the HTML block) as is.
- Scholarship free-text contact (`schlrshp_contact`) stays required, even when contacts are picked.
- `created_by` / `updated_by` are username strings, like `Scholarship`, `SocialMedia`, `Redirect` and CAS (no FK to `user`).
- All target tables are InnoDB, locally and in prod.
- The UI follows the building bathrooms design (cards with a remove button and a dashed "add" card). Buildings
  get it as a "Contacts" tab; programs and scholarships have no tabs, so they get the same grid in a section.

## Gotchas
1. **Audit fields need a logged-in user.** `created_by` / `updated_by` are `NOT NULL` strings filled by
   Gedmo Blameable, as on `Scholarship` and `SocialMedia`. Web requests are fine, but a console import or
   seed has no user, so it must set them itself.
2. **Column types must match the referenced id.** `ic_contacts.id`, `program_programs.id` and
   `scholarships_scholarship.id` are `INT UNSIGNED`; `map_item.id` (so `map_building.id`) is a signed `INT`.
3. **Buildings use JOINED inheritance**: the link table points at `map_building` (the subclass table), and the
   mapping lives on `MapBuilding`, not `MapItem`.
4. **Programs is mostly raw DBAL, and its entity manager can't load `IcContact`.** `program_contact` has no
   entity. `ProgramsRepository::updateProgramContacts()` rewrites it with raw SQL (like `program_college_link`),
   `getProgram()` returns the ids as a `GROUP_CONCAT` string, and the public feeds read it through
   `IcContactRepository::findContactIdsByRecord()`. Because `Programs` has no contact property, it can't leak
   through its group-less serialization (`/api/external/programs/programs`, `ProgramWebsites::getProgramRef()`).
5. **The record side of each link FK cascades.** A program is deleted through the ORM, which knows nothing
   about `program_contact`, so without `ON DELETE CASCADE` deleting a program that has contacts would fail.
   The same rule is mapped on `MapBuilding` / `Scholarship` so schema diffs stay quiet.
6. **Dropdown endpoint can't live under `/api/admin`** — that's global-admin only, but map / programs /
   scholarship editors need it. It's at `GET /api/ic/contacts/options` with an `IsGranted` Expression
   listing `ROLE_GLOBAL_ADMIN` + each module's CREATE role (EDIT and ADMIN inherit it). The prefix is registered in `config/routes.yaml`.
7. **Mixed table collations** (`program_programs` latin1, `map_building` utf8mb3) don't matter for INT FKs.
8. **Scholarship already has contact fields.** `Scholarship::$contact` is the required free-text block
   (`schlrshp_contact`), and `Scholarship::$contactId` was a loose, unused placeholder (`schlrshp_contact_id`).
   The association is therefore `$icContacts`, and the placeholder is dropped by the link-table migration.
   Nothing wrote it, so no deploy check is needed on its values.
9. **`/api/external/mapitems` shares the `bldgs` group with the authenticated map API**, and the edit form needs
   `contactIds` / `contactNames` from that group. They're ignored on the external route only, as
   `ScholarshipExternalController::PUBLIC_CONTEXT` does.
10. **People's email and phone become public** on endpoints that need no login. Every feed builds contact data from
    one whitelist, `IcContact::toPublicArray()`, so a new contact field can't leak by default.
11. **Duplicates and empty cards.** An empty card (a contact not chosen yet) is `null` in the form's list. The API
    skips nulls and duplicates, so the forms send the list as it is.

## Data model
`ic_contacts` is described in `docs/ic-contacts.md`. The link tables:

| Table | Columns | Primary key |
|---|---|---|
| `program_contact` | `program_id INT UNSIGNED`, `contact_id INT UNSIGNED` | both |
| `scholarship_contact` | `scholarship_id INT UNSIGNED`, `contact_id INT UNSIGNED` | both |
| `map_building_contact` | `building_id INT`, `contact_id INT UNSIGNED` | both |

FK and index names are the ones Doctrine generates, so schema diffs stay quiet (`program_contact` has no
mapping but uses the same names).

## Migrations (hand-written, re-runnable, per CLAUDE.md conventions)
- `Version20261102000000` — create `ic_contacts`.
- `Version20261102010000` — create the three link tables if missing (`warnIf` when they exist). It also drops
  the `contact_id` columns the first version of this migration added, if a development database still has them;
  that is done in both `up()` and `down()`, so rolling back and re-applying cleans up. That first version never
  reached production.

## Public API
Each record gets `icContactsInfo`, a list sorted by name whose entries are:
```json
{ "name": "Jane Doe", "email": "jdoe@emich.edu", "phone": "734-487-1234", "url": "https://www.emich.edu/example" }
```
`name` is "First Last" for a Person and `organization` for an Organization. The other three can be `null`.

| Route | How |
|---|---|
| `GET /api/external/scholarships/all`, `/search`, `/{id}` | `Scholarship::getIcContactsInfo()` in the `scholarship` group; `contactId`, `contactIds` and `contactNames` are in `PUBLIC_CONTEXT`'s ignores |
| `GET /api/external/mapitems` | `MapBuilding::getIcContactsInfo()` in the `bldgs` group; `contactIds` / `contactNames` ignored on this route. Buildings only |
| `GET /api/external/programs/programs` | `$row['icContactsInfo']` from `IcService::getIcContactsInfoByRecord()`, next to `colleges` / `departments` |
| `GET /api/external/programs/search` | `IcService::addProgramContactsInfo()` on the result rows |

No query per record: scholarships and buildings load their contacts eagerly, and the program feeds read the
link table once.

## Verification
1. Migrate (after a backup), then migrate again → only warnings. `doctrine:schema:update --dump-sql | grep -i contact`
   → only `program_contact`, which has no mapping, and the unused scholarship placeholder is untouched.
2. Rollback test: `migrations:execute --down` both versions, then migrate again.
3. As global admin: create Person + Organization contacts at `/admin/contacts`; validation rejects bad input.
4. Assign several contacts in each of the three forms; reload → persists; remove one, save → gone.
5. Delete a contact in use → disabled with counts; API `DELETE` → 409. Clear all uses → delete works.
6. As a non-admin with only e.g. ROLE_MAP_EDIT: `/api/ic/contacts/options` works, `/api/admin/contacts` → 403.
7. Logged out, call the six public routes. Records with contacts return `icContactsInfo` with exactly
   `name`, `email`, `phone`, `url` per entry; records without return `[]`. Search each response for `contactId`,
   `contactIds`, `contact_id`, `icContact`, `forager_url`, `room` and `createdBy` from the contact: none should appear.
8. phpunit in `tests/Api/Admin` and `tests/Api/Ic` (see CLAUDE.md for the test-env workaround; clean up rows).
