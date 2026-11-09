# Shared contacts (November 2026)

A contact is a person or organization. Programs, scholarships and buildings can each have any number of
them (and a contact can belong to any number of records), chosen on their own form with one searchable
picker per contact. Colleges and departments have no contacts. The plan and its reasoning are in
`docs/ic-contacts-plan.md`.

## Database

### `ic_contacts`

| Column | Notes |
|---|---|
| `id` | `INT UNSIGNED` |
| `type` | `Person` or `Organization` (`IcContactType`) |
| `last_name`, `first_name` | Both required for a Person |
| `organization` | Required for an Organization |
| `email`, `phone`, `room`, `area` | Optional |
| `url`, `forager_url`, `image_url` | Optional; must be valid URLs |
| `created`, `created_by`, `updated`, `updated_by` | Filled by Gedmo; `*_by` hold the username |

`created_by` and `updated_by` are required and need a logged-in user, so a console import or seed must
set them itself.

### Link tables

A record's contacts live in a link table: one row per (record, contact), with a composite primary key.
The record side has `ON DELETE CASCADE`, so deleting a program, scholarship or building drops its links.
The contact side has no `ON DELETE` action, so a contact that is still linked can't be deleted.

| Table | Columns | Mapping |
|---|---|---|
| `map_building_contact` | `building_id` (`INT`), `contact_id` | `MapBuilding::$icContacts` (`ManyToMany`), with `contactIds` / `contactNames` virtual properties for the form |
| `scholarship_contact` | `scholarship_id`, `contact_id` | `Scholarship::$icContacts` (`ManyToMany`), same virtual properties |
| `program_contact` | `program_id`, `contact_id` | No entity: the `programs` entity manager maps only `App\Entity\Programs`, so it can't hold an association to `IcContact`. `ProgramsRepository::updateProgramContacts()` writes it with raw SQL, like `program_college_link` |

`Scholarship::$contact` is a different thing: the free-text contact block (`schlrshp_contact`). It stays
required, and is still published as `contact`. `scholarships_scholarship.schlrshp_contact_id` is an unused
placeholder that nothing writes. It is left as it was.

## Admin screens

Global admins manage contacts at `/admin/contacts`: list with search and paging, add, edit, delete. A
contact can't be deleted while a program, scholarship or building uses it. The edit page lists what uses it
and disables the delete button, and the API refuses too (409 with the reason).

## Forms

`IcContactCards.vue` is the contact section of the program, scholarship and building forms. It follows the
building bathrooms design: one card per contact with a picker and a remove button, plus a dashed "Add contact"
card. On buildings it is the "Contacts" tab next to Bathrooms, Dining and the rest. Read-only, it lists the
names. A contact can be picked once per record.

## API

| Route | Who | What |
|---|---|---|
| `GET/POST /api/admin/contacts`, `GET/PUT/DELETE /api/admin/contacts/{id}` | Global admins | CRUD |
| `GET /api/ic/contacts/options` | Global admins and anyone with a map, programs or scholarship CREATE role (their EDIT and ADMIN roles inherit it) | Dropdown rows: `id`, `displayName`, `type`, `email` |

Each record's own save route takes the list of contact ids: `contactIds`, or `contact_ids` on programs. It
replaces the record's contacts, so `[]` (or `null`) clears them and a missing key leaves them unchanged.
`null` or `''` entries (a card with no choice yet) and duplicates are skipped, and an id that doesn't exist
returns 422. Reading a record back gives `contactIds` and `contactNames` (a comma-separated `contact_ids`
string on programs, which come from a raw SQL row).

## Public API

No login needed. Each record carries `icContactsInfo`, a list that is empty without contacts and sorted by
name otherwise. Each entry is:

```json
{ "name": "Jane Doe", "email": "jdoe@emich.edu", "phone": "734-487-1234", "url": "https://www.emich.edu/example" }
```

`name` is "First Last" for a Person and the organization for an Organization. `email`, `phone` and `url` can
be `null`. Nothing else about the contact is published, including its id.

| Route | Records |
|---|---|
| `GET /api/external/scholarships/all`, `/search`, `/{id}` | Scholarships |
| `GET /api/external/mapitems` | Buildings only; other item types have no such key |
| `GET /api/external/programs/programs`, `/search` | Programs |

Every feed builds this from `IcContact::toPublicArray()`, so a new contact field stays private until it is
added there. `getIcContacts()` is `#[Ignore]` on both entities, so serializing one with no groups can't publish
the whole contact. `Programs` serializes with no groups, so any new getter or property on it is public. Its contacts are in a
link table with no property, which keeps them out of that output.

## Migrations

| Version | What it does | Reversible |
|---|---|---|
| `Version20261102000000` | Create `ic_contacts` | Yes, but its rows are lost |
| `Version20261102010000` | Create the three link tables | Yes, but which contacts each record had is lost |

The second one also removes the `contact_id` columns that an earlier version of it added to `ic_colleges`,
`ic_departments`, `program_programs`, `scholarships_scholarship` and `map_building`, if a development database
still has them. Roll it back and re-apply it there to clean up. Nothing in production had them.

Both are safe to re-run. Back up before migrating.
