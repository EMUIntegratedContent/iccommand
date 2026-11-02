# IcContact — shared contact entity

## Context
Colleges, departments, scholarships, programs and buildings each need an optional contact (person or
organization). Like `ic_colleges` / `ic_departments`, contacts are shared across IC Command apps, so
they live in one `ic_contacts` table managed by global admins. Each consuming record gets a nullable
`contact_id` FK, picked in its edit form with a searchable dropdown.

Decisions (confirmed):
- "Departments" = `ic_departments` (not `directory_department`).
- Deleting a contact in use is **blocked** with usage counts (same as colleges/departments). FKs have no cascade.
- Only global admins create/edit contacts, at `/admin/contacts`. Module forms only pick existing ones.
- Public feeds for scholarships, buildings, programs, departments and colleges include each record's contact,
  limited to name, email, phone and url. No contact id or other contact fields.
- The public key is `icContactInfo` in all five feeds. Scholarships keep publishing `contact` (the HTML block) as is.
- Scholarship free-text contact (`schlrshp_contact`) stays required, even when a contact is picked.
- `created_by` / `updated_by` are username strings, like `Scholarship`, `SocialMedia`, `Redirect` and CAS (no FK to `user`).
- All target tables are InnoDB, locally and in prod.

## Gotchas found while planning
1. **Audit fields need a logged-in user.** `created_by` / `updated_by` are `NOT NULL` strings filled by
   Gedmo Blameable, as on `Scholarship` and `SocialMedia`. Web requests are fine, but a console import or
   seed has no user, so it must set them itself.
2. **`contact_id` is `INT UNSIGNED`** everywhere; `ic_contacts.id` is `INT UNSIGNED` (matches Ic*).
3. **Buildings use JOINED inheritance**: column goes on `map_building` (the subclass table), not `map_item`.
4. **Programs is mostly raw DBAL.** `getProgram()` selects `p.*` so `contact_id` comes back
   automatically, but create/update go through the `Programs` entity in `ProgramsController`
   (lines ~285 and ~417) — set the contact there. Counting usage needs raw SQL on `program_programs.contact_id`.
5. **Dropdown endpoint can't live under `/api/admin`** — that's global-admin only, but map / programs /
   scholarship editors need it. Put it at `GET /api/ic/contacts/options` with an `IsGranted` Expression
   listing `ROLE_GLOBAL_ADMIN` + each module's CREATE role (EDIT and ADMIN inherit it). Register the prefix in `config/routes.yaml`.
6. **Mixed table collations** (`program_programs` latin1, `map_building` utf8mb3) don't matter for INT
   FKs. No engine check is needed in the migration, since all target tables are InnoDB.
7. Scholarship references colleges/departments as plain ints (no association). For contact, use a
   real `ManyToOne` on all five entities so Doctrine enforces the FK and forms get the name; serialize
   via `contactId` / `contactName` virtual getters (the `IcDepartment::getCollegeId()` pattern) to avoid
   nesting / circular serialization.
8. **Scholarship already has contact fields.** `Scholarship::$contact` is the required free-text block
   (`schlrshp_contact`), and `Scholarship::$contactId` is a loose placeholder (`schlrshp_contact_id`, signed `INT`,
   no FK, "deferred to a later phase"). So the association can't be called `$contact`: use `$icContact` on all
   five entities. The placeholder becomes the new FK, renamed to `contact_id`. `ScholarshipController::INT_FIELDS`
   accepts `contactId` today, but no frontend code sends it. Locally, all 671 scholarships have it `NULL`; prod
   hasn't been checked, so the migration aborts if it holds values.
9. **`Programs` can't hold an association, and is serialized with no groups.** The `programs` entity manager maps only
   `App\Entity\Programs`, so a `ManyToOne` to `IcContact` breaks it: `Programs` gets a plain `$contact_id` int, like
   `type_id` and `degree_id`, plus the database FK. With no groups, the serializer outputs every property and getter,
   publicly on `/api/external/programs/programs` and through `ProgramWebsites::getProgramRef()`, so both the property
   and `getContactId()` are `#[Ignore]`.
10. **`/api/external/mapitems` shares the `bldgs` group with the authenticated map API**, and the edit form needs
    `contactId` from that group. Ignore `contactId` / `contactName` on the external route only, as
    `ScholarshipExternalController::PUBLIC_CONTEXT` does.
11. **`findForDropdown()` is shared.** `IcCollegeRepository` / `IcDepartmentRepository::findForDropdown()` also serve
    the admin and scholarship dropdowns, so `IcExternalController` needs its own public method to add contacts.
12. **People's email and phone become public** on endpoints that need no login. Every feed builds contact data from
    one whitelist, `IcContact::toPublicArray()`, so a new contact field can't leak by default.

## Data model

### `ic_contacts` (new) — `src/Entity/Ic/IcContact.php`, `#[ORM\Table(name: 'ic_contacts')]`
| column | type | notes |
|---|---|---|
| id | INT UNSIGNED AI PK | |
| type | VARCHAR(20) NOT NULL | PHP backed enum `IcContactType` (`Organization`, `Person`) in `src/Entity/Ic/` (like `EmergencySeverity`) |
| last_name, first_name | VARCHAR(100) NULL | required when type = Person (`Assert\Callback`) |
| organization | VARCHAR(255) NULL | required when type = Organization |
| email | VARCHAR(255) NULL | `Assert\Email` |
| phone | VARCHAR(50) NULL | |
| room, area | VARCHAR(255) NULL | |
| url, forager_url, image_url | VARCHAR(1024) NULL | `Assert\Url` |
| created, updated | DATETIME NOT NULL | Gedmo Timestampable |
| created_by, updated_by | VARCHAR(255) NOT NULL | Gedmo Blameable on a string column (`Scholarship::$createdBy` pattern), stores the username |

utf8mb4_unicode_ci, InnoDB. Serializer group `ic`, including `createdBy` / `updatedBy`; virtual `displayName`
getter (Person: "Last, First"; Organization: organization).

### `contact_id INT UNSIGNED NULL` + FK → `ic_contacts.id` (no ON DELETE action) + index on:
`ic_colleges`, `ic_departments`, `scholarships_scholarship`, `program_programs`, `map_building`.
FK and index names are the ones Doctrine generates, so schema diffs stay quiet, except `program_programs`, which has
no association: `FK_program_programs_contact`. On `scholarships_scholarship`, this is the renamed `schlrshp_contact_id` (no new column).

## Migrations (hand-written, re-runnable, per CLAUDE.md conventions)
- `src/Migrations/Version20261102000000.php` — create `ic_contacts`. Skip with `warnIf` if table exists.
  Down: drop table; MariaDB refuses while any `contact_id` FK references it.
- `src/Migrations/Version20261102010000.php` — for each of the 5 tables: check `information_schema.COLUMNS`
  / `TABLE_CONSTRAINTS`; add column + index + FK only if missing (`warnIf` when already present).
  Under `--dry-run` the first migration hasn't run, so tolerate `ic_contacts`
  missing (don't abort on it; just skip FK existence checks). Down: drop FK, index, column per table.
  For `scholarships_scholarship`: `abortIf` any `schlrshp_contact_id` is non-null, then
  `CHANGE schlrshp_contact_id contact_id INT UNSIGNED NULL` and add index + FK. Its down reverses the rename back to
  signed `INT` rather than dropping the column.
- Pattern to copy: `Version20261028000000.php` (`columnExists()` helper) and `Version20261008010000.php`.

## Backend
- **Entity/repo/service**: `IcContact`, `IcContactType`, `src/Repository/Ic/IcContactRepository.php`
  (pagination + search by name/org/email, usage counts via one SQL with 5 subquery `COUNT`s, dropdown list
  `id, displayName, type, email`). Add contact methods to `src/Service/IcService.php` mirroring the college
  ones (`getContactsPagination`, `getContactWithUsage`, `getContactsForDropdown`, `contactDeleteBlocker`
  reusing `blockerMessage()`).
- **Add `$icContact` ManyToOne** (+ `contactId`/`contactName` getters in each entity's existing group) to
  `IcCollege` (`ic`), `IcDepartment` (`ic`), `Scholarship` (`scholarship`), `MapBuilding` (`bldgs`). `Programs` gets a
  plain `#[Ignore]` `$contact_id` instead (gotcha 9).
  `JoinColumn(name: 'contact_id', nullable: true)`, `targetEntity: IcContact::class`. On `Scholarship`, this replaces
  the `$contactId` column and `setContactId()`; drop `contactId` from `INT_FIELDS`, and leave `$contact` alone.
- **Admin API**: `src/Controller/Api/Admin/IcContactController.php` — `GET/POST /api/admin/contacts`,
  `GET/PUT/DELETE /api/admin/contacts/{id}`, copied from `IcDepartmentController` (applyFields, validate, 409
  `inUse`). Register `admin_ic_contacts` in `config/routes.yaml` with prefix `/api/admin/`.
- **Options API**: `src/Controller/Api/Ic/IcContactOptionsController.php` → `GET /api/ic/contacts/options`,
  registered in `config/routes.yaml` under `/api/ic/`.
- **Admin pages**: `src/Controller/Admin/IcContactController.php` (`/admin/contacts`, `/create`, `/{id}`),
  `templates/admin/contacts/`, link in `templates/admin/nav_admin.html.twig`.
- **Save paths** — accept `contactId` (`contact_id` on Programs; int or null), resolve with `IcService::resolveContact()`,
  422 if id unknown:
  - `IcCollegeController` / `IcDepartmentController::applyFields`
  - `ScholarshipController::applyFields`
  - `ProgramsController` create + update (~285, ~417)
  - `MapItemController` building branch (~195, ~418, next to `setBuildingType`)
  - Programs edit GET returns raw `p.*` → already includes `contact_id`; frontend reads that key.

## Public API
Each record gets `icContactInfo`, either `null` or:
```json
{ "name": "Jane Doe", "email": "jdoe@emich.edu", "phone": "734-487-1234", "url": "https://www.emich.edu/example" }
```
`name` is "First Last" for a Person and `organization` for an Organization. The other three can be `null`.

Add `IcContact::toPublicArray()` (the whitelist), plus `IcContactRepository::findPublicInfoById()`, which returns
all contacts keyed by id for the array and raw-SQL feeds. Add a `getIcContactInfo()` getter on `Scholarship` and
`MapBuilding`.

| Route | Change |
|---|---|
| `GET /api/external/scholarships/all`, `/search`, `/{id}` | `getIcContactInfo()` in the `scholarship` group; add `contactName` to `PUBLIC_CONTEXT` ignores (`contactId` is already there) |
| `GET /api/external/mapitems` | `getIcContactInfo()` in the `bldgs` group; add `IGNORED_ATTRIBUTES` for `contactId` / `contactName` on this route. Buildings only; other item types have no such key |
| `GET /api/external/programs/programs` | Set `$row['icContactInfo']` from `findPublicInfoById()`, next to the existing `$row['colleges']` / `$row['departments']` |
| `GET /api/external/programs/search` | Select `p.contact_id` in `ProgramsRepository::searchDegreePrograms()`, map it to `icContactInfo` in the existing post-processing loop, and unset `contact_id` |
| `GET /api/external/ic/colleges`, `/departments` | New `IcService::getPublicColleges()` / `getPublicDepartments()`, which add `icContactInfo` to the existing rows. Internal dropdowns are unchanged |

Avoid one query per record: use `findPublicInfoById()` for programs and the `ic` lists, and fetch the association
with the query (`fetch: 'EAGER'` or a join) for scholarships and buildings.

## Frontend
- **Shared picker** `assets/js/components/ic/IcContactSelect.vue`: wraps `vue-multiselect` (already used in
  `MapItemForm.vue`), fetches `/api/ic/contacts/options` once, `v-model` = contact id or null, label =
  displayName (+ email), clearable, searchable. Register in `assets/js/app.js` if forms rely on global
  registration; otherwise import locally.
- Use it in: `admin/CollegeForm.vue`, `admin/DepartmentForm.vue`, `scholarship/ScholarshipForm.vue`,
  `programs/ProgramForm.vue`, `map/MapItemForm.vue` (inside `v-if="record.itemType == 'building'"`).
  Each sends `contactId` in its save payload; read-only view shows the contact name.
- **Admin CRUD**: `admin/ContactList.vue` + `admin/ContactForm.vue` copied from `DepartmentList/Form.vue`.
  Form: type radio toggles Person fields (first/last) vs Organization field; shows created/updated by+date;
  reuse `IcDeleteModal.vue` and `IcUsageLists.vue` for blocked-delete display.
- `npm run build`, then `bin/check-frontend-build` (public/build is committed).

## Docs
- New `docs/ic-contacts.md` (or a section in `docs/shared-colleges-departments.md`): table, FKs, delete rule, endpoints.
- Update CLAUDE.md "Shared Colleges & Departments" to mention `ic_contacts`.
- `docs/scholarship-search-api.md`: add `icContactInfo` to the field table and example, noting how it differs from `contact`.
- `docs/shared-colleges-departments.md`: public list rows gain `icContactInfo`, and no longer match the
  `/api/scholarships/{colleges,departments}` output.

## Verification
1. `docker exec iccommand-web-1 php bin/console doctrine:migrations:migrate --dry-run`, then real migrate (after backup).
   Re-run migrate → only warnings, no errors. `doctrine:schema:update --dump-sql | grep -i contact` → only the
   `program_programs` FK and index, which have no mapping. (`schema:validate` already fails on unrelated tables.)
2. Rollback test: `migrations:execute --down` both versions, then migrate again.
3. As global admin: create Person + Organization contacts at `/admin/contacts`; confirm created/updated by
   show your username; validation (Person w/o name, bad URL/email) rejected.
4. Assign the contact in each of the 5 forms; reload → persists; check `contact_id` in DB.
5. Try deleting the contact → disabled with counts; API `DELETE` → 409. Clear all uses → delete works.
6. As a non-admin with only e.g. ROLE_MAP_EDIT: `/api/ic/contacts/options` works, `/api/admin/contacts` → 403.
7. Logged out, call all eight public routes in the Public API table. Records with a contact return `icContactInfo` with
   exactly `name`, `email`, `phone`, `url`; records without return `null`. Search each response for `contactId`,
   `contact_id`, `icContact`, `forager_url`, `room` and `createdBy` from the contact: none should appear. Scholarship
   `contact` still returns its HTML, and existing fields are unchanged.
8. Optional phpunit in `tests/Api/Admin` for contact CRUD + delete blocker (see test-env workaround memory; clean up rows).
