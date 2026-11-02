# Shared contacts (November 2026)

A contact is a person or organization. Colleges, departments, scholarships, programs and buildings can
each have zero or one, chosen on their own form with a searchable dropdown. The plan and its reasoning
are in `docs/ic-contacts-plan.md`.

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

### `contact_id`

`contact_id INT UNSIGNED NULL`, with an FK to `ic_contacts` and no `ON DELETE` action, is on `ic_colleges`,
`ic_departments`, `scholarships_scholarship`, `program_programs` and `map_building`. On scholarships it
replaces the unused `schlrshp_contact_id` placeholder.

| Entity | Mapping |
|---|---|
| `IcCollege`, `IcDepartment`, `Scholarship`, `MapBuilding` | `$icContact` (`ManyToOne`), with `contactId` / `contactName` virtual properties for the forms |
| `Programs` | Plain `$contact_id` int. The `programs` entity manager maps only `App\Entity\Programs`, so it can't hold an association to `IcContact` |

`Scholarship::$contact` is a different thing: the free-text contact block (`schlrshp_contact`). It stays
required, and is still published as `contact`.

## Admin screens

Global admins manage contacts at `/admin/contacts`: list with search and paging, add, edit, delete. A
contact can't be deleted while anything uses it. The edit page lists what uses it and disables the delete
button, and the API refuses too (409 with the reason).

## API

| Route | Who | What |
|---|---|---|
| `GET/POST /api/admin/contacts`, `GET/PUT/DELETE /api/admin/contacts/{id}` | Global admins | CRUD |
| `GET /api/ic/contacts/options` | Global admins and anyone with a map, programs or scholarship CREATE role (their EDIT and ADMIN roles inherit it) | Dropdown rows: `id`, `displayName`, `type`, `email` |

Each record's own save route takes the contact id: `contactId`, or `contact_id` on programs. `null` or `''`
clears it, a missing key leaves it unchanged, and an id that doesn't exist returns 422.

## Public API

No login needed. Each record carries `icContactInfo`, which is `null` without a contact:

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
| `GET /api/external/ic/colleges`, `/departments` | Colleges and departments |

Every feed builds this from `IcContact::toPublicArray()`, so a new contact field stays private until it is
added there. `getIcContact()` is `#[Ignore]` on every entity, so serializing one with no groups can't publish
the whole contact. Take care with `Programs`: it serializes with no groups, so any new getter or property on it
is public unless marked `#[Ignore]`.

## Migrations

| Version | What it does | Reversible |
|---|---|---|
| `Version20261102000000` | Create `ic_contacts` | Yes, but its rows are lost |
| `Version20261102010000` | Add `contact_id` with index and FK to the five tables; rename the scholarship placeholder. Stops first if `schlrshp_contact_id` holds any values | Yes, but which contact each record had is lost, except on scholarships |

Both are safe to re-run. Back up before migrating.
