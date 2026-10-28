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
- Public `/api/external/*` feeds are unchanged for now.

## Gotchas found while planning
1. **Audit fields map to `User`, unlike every other entity.** Existing entities store `createdBy` as a
   string username. Gedmo Blameable (stof, already enabled) sets the `User` object when the field is a
   `ManyToOne` association. Columns: `created_by_id` / `updated_by_id`, nullable (console commands /
   migrations have no user), FK to `user.id` `ON DELETE SET NULL`. **`user.id` is signed `INT`**, so
   these columns are `INT` (signed); a mismatch gives errno 150.
2. **`contact_id` is `INT UNSIGNED`** everywhere; `ic_contacts.id` is `INT UNSIGNED` (matches Ic*).
3. **Buildings use JOINED inheritance**: column goes on `map_building` (the subclass table), not `map_item`.
4. **Programs is mostly raw DBAL.** `getProgram()` selects `p.*` so `contact_id` comes back
   automatically, but create/update go through the `Programs` entity in `ProgramsController`
   (lines ~285 and ~417) — set the contact there. Counting usage needs raw SQL on `program_programs.contact_id`.
5. **Dropdown endpoint can't live under `/api/admin`** — that's global-admin only, but map / programs /
   scholarship editors need it. Put it at `GET /api/ic/contacts/options` with an `IsGranted` Expression
   listing `ROLE_GLOBAL_ADMIN` + each module's edit role. Register the prefix in `config/routes.yaml`.
6. **Mixed table collations** (`program_programs` latin1, `map_building` utf8mb3) don't matter for INT
   FKs, but migration should `abortIf` any target table isn't InnoDB (local DB: all InnoDB; prod unverified).
7. Scholarship references colleges/departments as plain ints (no association). For contact, use a
   real `ManyToOne` on all five entities so Doctrine enforces the FK and forms get the name; serialize
   via `contactId` / `contactName` virtual getters (the `IcDepartment::getCollegeId()` pattern) to avoid
   nesting / circular serialization.

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
| created_by_id, updated_by_id | INT NULL → `user.id` ON DELETE SET NULL | Gedmo Blameable on ManyToOne `User` |

utf8mb4_unicode_ci, InnoDB. Serializer group `ic`; virtual `displayName` getter (Person: "Last, First";
Organization: organization) and `createdByName` / `updatedByName` for the admin form. Don't serialize the
`User` objects themselves.

### `contact_id INT UNSIGNED NULL` + FK → `ic_contacts.id` (no ON DELETE action) + index on:
`ic_colleges`, `ic_departments`, `scholarships_scholarship`, `program_programs`, `map_building`.
FK names: `FK_<table>_contact`.

## Migrations (hand-written, re-runnable, per CLAUDE.md conventions)
- `src/Migrations/Version20261029000000.php` — create `ic_contacts`. Skip with `warnIf` if table exists;
  `abortIf` `user` table missing or `user.id` type isn't `int` signed. Down: `abortIf` any `contact_id`
  column still exists, else drop table.
- `src/Migrations/Version20261029010000.php` — for each of the 5 tables: check `information_schema.COLUMNS`
  / `TABLE_CONSTRAINTS`; add column + index + FK only if missing (`warnIf` when already present).
  `abortIf` table not InnoDB. Under `--dry-run` the first migration hasn't run, so tolerate `ic_contacts`
  missing (don't abort on it; just skip FK existence checks). Down: drop FK, index, column per table.
- Pattern to copy: `Version20261028000000.php` (`columnExists()` helper) and `Version20261008010000.php`.

## Backend
- **Entity/repo/service**: `IcContact`, `IcContactType`, `src/Repository/Ic/IcContactRepository.php`
  (pagination + search by name/org/email, usage counts via one SQL with 5 subquery `COUNT`s, dropdown list
  `id, displayName, type, email`). Add contact methods to `src/Service/IcService.php` mirroring the college
  ones (`getContactsPagination`, `getContactWithUsage`, `getContactsForDropdown`, `contactDeleteBlocker`
  reusing `blockerMessage()`).
- **Add `contact` ManyToOne** (+ `contactId`/`contactName` getters in each entity's existing group) to
  `IcCollege` (`ic`), `IcDepartment` (`ic`), `Scholarship` (`scholarship`), `Programs`, `MapBuilding` (`bldgs`).
  `JoinColumn(name: 'contact_id', nullable: true)`, `targetEntity: IcContact::class`.
- **Admin API**: `src/Controller/Api/Admin/IcContactController.php` — `GET/POST /api/admin/contacts`,
  `GET/PUT/DELETE /api/admin/contacts/{id}`, copied from `IcDepartmentController` (applyFields, validate, 409
  `inUse`). Register `admin_ic_contacts` in `config/routes.yaml` with prefix `/api/admin/`.
- **Options API**: `src/Controller/Api/Ic/IcContactOptionsController.php` → `GET /api/ic/contacts/options`,
  registered in `config/routes.yaml` under `/api/ic/`.
- **Admin pages**: `src/Controller/Admin/IcContactController.php` (`/admin/contacts`, `/create`, `/{id}`),
  `templates/admin/contacts/`, link in `templates/admin/nav_admin.html.twig`.
- **Save paths** — accept `contactId` (int or null), resolve with `IcService::getContact()`, 400 if id unknown:
  - `IcCollegeController` / `IcDepartmentController::applyFields`
  - `ScholarshipController::applyFields`
  - `ProgramsController` create + update (~285, ~417)
  - `MapItemController` building branch (~195, ~418, next to `setBuildingType`)
  - Programs edit GET returns raw `p.*` → already includes `contact_id`; frontend reads that key.

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

## Verification
1. `docker exec iccommand-web-1 php bin/console doctrine:migrations:migrate --dry-run`, then real migrate (after backup).
   Re-run migrate → only warnings, no errors. `doctrine:schema:validate` → mapping + DB in sync.
2. Rollback test: `migrations:execute --down` both versions, then migrate again.
3. As global admin: create Person + Organization contacts at `/admin/contacts`; confirm created/updated by
   show your user; validation (Person w/o name, bad URL/email) rejected.
4. Assign the contact in each of the 5 forms; reload → persists; check `contact_id` in DB.
5. Try deleting the contact → disabled with counts; API `DELETE` → 409. Clear all uses → delete works.
6. As a non-admin with only e.g. ROLE_MAP_EDIT: `/api/ic/contacts/options` works, `/api/admin/contacts` → 403.
7. Public feeds (`/api/external/scholarships/all`, programs search, map) unchanged.
8. Optional phpunit in `tests/Api/Admin` for contact CRUD + delete blocker (see test-env workaround memory; clean up rows).
