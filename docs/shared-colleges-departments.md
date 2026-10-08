# Shared colleges and departments (October 2026)

Summary of the work merged in PR #172 (`depts` branch), done on 2026-10-08.

## Why

Colleges and departments were Programs-only legacy tables (`program_colleges`,
`program_departments`), yet the Scholarships app used them too. `program_departments` held one row
**per catalog**: 64 rows for 49 distinct names, plus spelling variants such as "Nursing" vs
"School of Nursing". The Programs and Scholarships dropdowns showed those duplicates, and nothing
could be edited without touching the database.

The `ic_` prefix now marks a table shared by more than one IC Command app.

## Database changes

### `ic_colleges`
- `program_colleges` renamed to `ic_colleges`. Ids unchanged; converted to `utf8mb4_unicode_ci`.
- `college` widened from `VARCHAR(50)` to `VARCHAR(100)`.

### `ic_departments` (new)
- Columns: `id`, `college_id` (FK to `ic_colleges`, required), `department` (`VARCHAR(255)`, unique).
- Seeded with 50 rows:
  - the 42 departments in the 2026 department list (`private/new_departments_list.csv`);
  - 8 legacy rows the list has no counterpart for (the "Interdisciplinary (...)" rows, University
    Advising & Career Development Center, Pre-professional Studies).
- Each new id reuses the **lowest** legacy `program_departments` id of the rows it replaces. For
  example, 24 and 51 (both "Leadership & Counseling") became 24. This kept most existing
  references and public `?department=` URLs working. The 8 departments new in the CSV got ids 65–72.
- "Environmental Science and Society - HIDDEN" (legacy id 64, no college) was defunct and dropped.
  Its 4 secondary program links were removed.

### `program_departments` retired
- Kept as `program_departments_bk`. Each row has a new `department_id` column recording which
  `ic_departments` row it became. Safe to drop in a later release.

### Program ↔ department / college links
- `program_inter_dept` (program → departments) and `program_college_link` (program → colleges) are
  now the **only** record of a program's departments and colleges. Both allow several per program.
- `program_programs.department_id` and `program_programs.college_id` were **dropped**. They only
  ever held the first selected value, and every value was already in the link tables.
- `program_inter_dept` was rebuilt with a primary key `(program_id, department_id)` and FKs to
  `program_programs` and `ic_departments`. This also removed duplicate rows and 15 rows pointing
  at programs that no longer exist.

### Scholarships
- `scholarships_scholarship.schlrshp_department_id` is now `INT UNSIGNED` with an FK to
  `ic_departments` (`ON DELETE SET NULL`). Existing values were remapped to the new ids.
- `schlrshp_college_id` already pointed at college ids, which didn't change.

## Admin screens

Under **IC Command Administration** (global admins only), next to Manage Users:

| Page | What it does |
|---|---|
| `/admin/colleges` | List with search and paging. Shows each college's departments, programs and scholarships counts. Add / edit (name, URL) / delete. |
| `/admin/departments` | List with search and paging. Shows each department's college and its programs and scholarships counts. Add / edit (name, college) / delete. |

- A college can't be deleted while any department, program or scholarship uses it. A department
  can't be deleted while any program or scholarship uses it. The edit page shows the usage counts
  and disables the delete button; the API refuses too (409 with the reason).
- Program counts come from the link tables (`program_college_link`, `program_inter_dept`).
- API (global admin only): `GET/POST /api/admin/colleges`, `GET /api/admin/colleges/dropdown`,
  `GET/PUT/DELETE /api/admin/colleges/{id}`, and the same set (minus `dropdown`) under
  `/api/admin/departments`.
- Code: `src/Entity/Ic/`, `src/Repository/Ic/`, `src/Service/IcService.php`,
  `src/Controller/Admin/Ic*Controller.php`, `src/Controller/Api/Admin/Ic*Controller.php`,
  `assets/js/components/admin/{College,Department}{List,Form}.vue`, `IcDeleteModal.vue`.

The Programs and Scholarships dropdowns (`/api/programs/{colleges,departments}`,
`/api/scholarships/{colleges,departments}`) read from the same repositories, so changes made here
show up in both apps immediately.

## Public API changes (breaking)

| Route | Before | Now |
|---|---|---|
| `GET /api/external/programs/search` (each result) | `department` (one name), `college` (one id), `url` (college URL) | `departments` (array of names) and `colleges` (array of names). `url` removed. |
| `GET /api/external/programs/programs` | `departmentId`, `collegeId` | `departments` and `colleges` (arrays of names, alphabetical) |
| `GET /api/external/scholarships/all`, `/search`, `/{id}` | `collegeId`, `departmentId` | `college` and `department` (name or `null`, same position in the output) |

- Search **filters still take ids**: `?department=` and `?college=` on both search routes. The
  degree page's Area of Study list still pairs each name with its ids.
- Department and college filters on the program search no longer narrow what a result lists: a
  program matched by one department still lists all of its departments.
- Sorting the program search by department or college uses the first name alphabetically.
- The admin API still returns ids; the edit forms need them for their dropdowns.
- `docs/scholarship-search-api.md` is updated to match.

## Other fixes

- **Delete confirmation dialogs** stayed open after a failed delete. In some cases (App Manage's
  "Revoke", map image delete) they stayed open even after success. Clearing the "delete" text
  disabled the button before Bootstrap's close handler saw the click. Fixed in every delete modal:
  Social Media, Scholarship, Directory Department, Program, Program Website, Map Item, Map Image,
  Redirect, and App Manage.
- The admin edit forms put the back arrow on the left and the lock toggle on the right, matching
  Manage Users.

## Migrations

| Version | What it does | Reversible |
|---|---|---|
| `Version20261008000000` | Rename `program_colleges` to `ic_colleges` | Yes |
| `Version20261008010000` | Create and seed `ic_departments`; remap programs, `program_inter_dept` and scholarships; rename `program_departments` to `program_departments_bk` | **No.** Restore a backup instead. |
| `Version20261009000000` | Widen `ic_colleges.college` to 100 | Yes |
| `Version20261010000000` | Copy any missing `department_id` / `college_id` values into the link tables, then drop both columns | Approximately (re-adds the columns filled with the lowest linked id) |

Safety checks (each stops the migration before it changes anything, and also runs under `--dry-run`):
- **`Version20261008010000`:**
  - every legacy `program_departments` id must be in its mapping;
  - each id must hold the department name the mapping was built from, so a database whose ids mean
    something else can't be silently mislinked;
  - no program or scholarship may point at a department that wouldn't survive the remap.
- **`Version20261010000000`:** every non-zero `college_id` must be a real college.

The migrations copy the data each database already has, so local, staging and prod each keep
their own programs and scholarships. `sql/seed_pivot_tables.sql` is kept, but
`Version20261010000000` now does its job automatically.

## Deploying

The code and the migrations must go out together: the new code expects the `ic_*` tables and no
longer sets the dropped program columns.

1. Back up: `mysqldump ic > ic_pre_ic_tables_$(date +%F).sql` (MariaDB can't roll back schema
   changes).
2. Optional dry run before the app changes:
   `git fetch && git checkout origin/master -- src/Migrations`, then
   `php bin/console doctrine:migrations:migrate --dry-run`.
3. Deploy the code.
4. `php bin/console doctrine:migrations:migrate`, then `php bin/console cache:clear`.
5. Spot-check:
   - `/admin/colleges` and `/admin/departments` show counts;
   - a program edit page shows its colleges and departments;
   - `/api/external/programs/search?department=24` returns results;
   - `/api/external/scholarships/all` has `college` and `department` names.

Status as of 2026-10-08: local is fully migrated. Staging passed the dry run. Prod has run every
older migration and is waiting on this deploy.

## Loose ends

- **Modern Campus `degrees-index.pcf`:** the Leadership & Counseling banner override checks for
  department ids `24` and `51` together. Area of Study now emits only `24`, so that check needs to
  become a single-id `'24'` check. The 25 and 26 overrides still work.
- **Old public URLs:** `?department=` values from the old Area of Study list still work, because
  each contains the surviving id. A URL with only a dropped duplicate id (e.g. `?department=51`)
  now returns nothing.
- **Delivery modes:** `DeliveryIDs` in program search results has the same values as before, but
  the order and repeats can differ (the field never had a guaranteed order).
- **Cleanup:**
  - `program_departments_bk` can be dropped once everything is confirmed on prod;
  - the 8 legacy "Interdisciplinary" / advising rows in `ic_departments` can be cleaned up from
    the admin screen when ready.
