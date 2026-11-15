# Shared advisors (November 2026)

This app was called Contacts until November 15, 2026. `Version20261115000000` renamed its tables, columns
and roles (see Migrations below); the older docs it links to still use the old names.

An advisor is a person or organization. Programs and scholarships can each have any number of them (and an
advisor can belong to any number of records), chosen on their own form with one searchable picker per
advisor. An advisor can be in one building, chosen on the advisor's own form. Buildings, colleges and
departments don't list advisors. The plan and its reasoning are in `docs/ic-contacts-plan.md` (buildings
there are the earlier design, replaced by `Version20261115010000`).

## Database

### `ic_advisors`

| Column | Notes |
|---|---|
| `id` | `INT UNSIGNED` |
| `type` | `Person` or `Organization` (`IcAdvisorType`) |
| `last_name`, `first_name` | Both required for a Person |
| `organization` | Required for an Organization |
| `email`, `phone`, `room`, `area` | Optional |
| `building_id` | Optional `INT`, FK to `map_building` with `ON DELETE SET NULL`: deleting the building clears it and keeps the advisor. `IcAdvisor::$building` (`ManyToOne` to `MapBuilding`) |
| `url`, `forager_url`, `image_url` | Optional; must be valid URLs |
| `created`, `created_by`, `updated`, `updated_by` | Filled by Gedmo; `*_by` hold the username |

`created_by` and `updated_by` are required and need a logged-in user, so a console import or seed must
set them itself.

### Link tables

A record's advisors live in a link table: one row per (record, advisor), with a composite primary key.
The record side has `ON DELETE CASCADE`, so deleting a program or scholarship drops its links.
The advisor side has no `ON DELETE` action, so an advisor that is still linked can't be deleted.

| Table | Columns | Mapping |
|---|---|---|
| `scholarship_advisor` | `scholarship_id`, `advisor_id` | `Scholarship::$icAdvisors` (`ManyToMany`), with `advisorIds` / `advisorNames` virtual properties for the form |
| `program_advisor` | `program_id`, `advisor_id` | No entity: the `programs` entity manager maps only `App\Entity\Programs`, so it can't hold an association to `IcAdvisor`. `ProgramsRepository::updateProgramAdvisors()` writes it with raw SQL, like `program_college_link` |

`Scholarship::$contact` is a different thing: the free-text contact block (`schlrshp_contact`). It stays
required, keeps its name, and is still published as `contact`. The old unused `schlrshp_contact_id` column
was dropped by `Version20261102010000`.

## Advisors app

Advisors is its own app at `/advisors` (Program Marketing menu and homepage section, with Degrees &
Programs), separate from the admin area. Roles, each inheriting the one before: `ROLE_ADVISORS_VIEW` (list, read-only form),
`ROLE_ADVISORS_EDIT` (add, edit), `ROLE_ADVISORS_DELETE` (delete) and `ROLE_ADVISORS_ADMIN` (also manages
who has access, at `/advisors/manage`). Global admins can do everything. An advisor can't be deleted while a program or scholarship uses it. The edit page lists what uses it
and disables the delete button, and the API refuses too (409 with the reason). Its own building doesn't
count: that's a property of the advisor, not a use of it.

## Forms

`IcAdvisorCards.vue` is the advisor section of the program and scholarship forms. It follows the
building bathrooms design: one card per advisor with a picker and a remove button, plus a dashed "Add advisor"
card. Read-only, it lists the names. An advisor can be picked once per record.

The advisor form (`AdvisorForm.vue`) has a searchable Building picker, filled from
`/api/advisors/buildings`. Read-only, it shows the building's name or "None". The advisor list has a
Building column.

## API

| Route | Who | What |
|---|---|---|
| `GET /api/advisors`, `GET /api/advisors/{id}` | `ROLE_ADVISORS_VIEW` and up, global admins | List (search, paging), read. Rows carry `building_id` and `building_name` |
| `POST /api/advisors`, `PUT /api/advisors/{id}` | `ROLE_ADVISORS_EDIT` and up, global admins | Create, update. `buildingId` sets the building; `null` or `''` clears it, a missing key leaves it, and an id that isn't a building returns 422. The response has `buildingId` and `buildingName` |
| `GET /api/advisors/buildings` | `ROLE_ADVISORS_EDIT` and up, global admins | The Building picker's rows: `id`, `name`, sorted by name. Separate from `/api/mapbuildings/`, which needs a map role |
| `DELETE /api/advisors/{id}` | `ROLE_ADVISORS_DELETE` and up, global admins | Delete (409 while in use) |
| `GET /api/ic/advisors/options` | Global admins and anyone with a programs or scholarship CREATE role (their EDIT and ADMIN roles inherit it) | Dropdown rows: `id`, `displayName`, `type`, `email` |

Each record's own save route takes the list of advisor ids: `advisorIds`, or `advisor_ids` on programs. It
replaces the record's advisors, so `[]` (or `null`) clears them and a missing key leaves them unchanged.
`null` or `''` entries (a card with no choice yet) and duplicates are skipped, and an id that doesn't exist
returns 422. Reading a record back gives `advisorIds` and `advisorNames` (a comma-separated `advisor_ids`
string on programs, which come from a raw SQL row).

## Public API

No login needed. Each record carries `icAdvisorsInfo`, a list that is empty without advisors and sorted by
name otherwise. Each entry is:

```json
{ "name": "Jane Doe", "email": "jdoe@emich.edu", "phone": "734-487-1234", "url": "https://www.emich.edu/example", "buildingName": "Pray-Harrold" }
```

`name` is "First Last" for a Person and the organization for an Organization. `email`, `phone`, `url` and
`buildingName` can be `null`. Nothing else about the advisor is published, including its id and its
building's id.

| Route | Records |
|---|---|
| `GET /api/external/scholarships/all`, `/search`, `/{id}` | Scholarships |
| `GET /api/external/programs/programs`, `/search` | Programs |

`GET /api/external/mapitems` has no advisors: buildings don't list them.

Every feed builds this from `IcAdvisor::toPublicArray()`, so a new advisor field stays private until it is
added there. `getIcAdvisors()` is `#[Ignore]` on `Scholarship`, and `getBuilding()` is `#[Ignore]` on
`IcAdvisor`, so serializing either with no groups can't publish a whole advisor or building. `Programs` serializes with no groups, so any new getter or property on it is public. Its advisors are in a
link table with no property, which keeps them out of that output.

## Migrations

| Version | What it does | Reversible |
|---|---|---|
| `Version20261102000000` | Create `ic_contacts` | Yes, but its rows are lost |
| `Version20261102010000` | Create the three link tables (`program_contact`, `scholarship_contact`, `map_building_contact`) | Yes, but which contacts each record had is lost |
| `Version20261115000000` | Rename to advisors: `ic_contacts` to `ic_advisors`, each `*_contact` link table to `*_advisor`, `contact_id` to `advisor_id`, their indexes and foreign keys to the names Doctrine expects, and `ROLE_CONTACTS_*` to `ROLE_ADVISORS_*` in `user.roles` | Yes, nothing is lost |
| `Version20261115010000` | Drop `map_building_advisor` and add `ic_advisors.building_id`. Old building links aren't carried over (a building listing an advisor isn't the advisor being in that building) | Yes, but each advisor's building is lost, and `map_building_advisor` comes back empty |

The second one also removes the `contact_id` columns that an earlier version of it added to `ic_colleges`,
`ic_departments`, `program_programs`, `scholarships_scholarship` and `map_building`, if a development database
still has them. Roll it back and re-apply it there to clean up. Nothing in production had them.

All are safe to re-run. Back up before migrating.
