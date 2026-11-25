# Legacy Advisor Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the advisors in the old `advisor_programs` and `uacdc_advisor_programs` tables into
`ic_advisors` records linked to their `program_programs` through `program_advisor`. The result is a reviewed,
re-runnable SQL file, and every imported advisor keeps a read-only `legacy_programs` list for checking.

**Architecture:** A read-only console command (`app:advisors:legacy-sql`) reads the two legacy tables, the
programs and the buildings. Pure PHP classes clean, group and match the rows, apply the close-call answers
kept in one overrides class, validate every advisor against `IcAdvisor`'s constraints, and write
`sql/legacy_advisors_import.sql`. The SQL looks up buildings, programs and advisors by name, never by id,
so it runs the same on production as locally. A new migration adds `ic_advisors.legacy_programs`, which the
advisor form shows as a read-only textarea.

**Tech Stack:** Symfony 8 / PHP 8.5, Doctrine DBAL, PHPUnit 9.6, Vue 3, MariaDB.

**Spec:** the user's request of 2026-11-25 (summarized under Requirements). Close-call answers are in
`docs/superpowers/plans/2026-11-25-legacy-advisor-close-calls.md`.

## Requirements (from the request)

1. One `ic_advisors` record per distinct person or organization in either legacy table. Names that don't
   name a person or office ("Visit the CMTA website for advising information.") get no record.
2. A `program_advisor` link for each legacy `program` that matches a `program_programs` name.
3. `advisor_address`: the room goes in `ic_advisors.room`, and the building is looked up against
   `map_item.name` where `discr = 'building'`.
4. A migration adds `ic_advisors.legacy_programs TEXT`, holding the advisor's program names from the legacy
   tables, comma-separated. It's a read-only textarea on the Vue form.
5. Legacy `website` goes in `ic_advisors.url`.
6. The output is an SQL file with every statement. Close calls on names and programs are asked before the
   statements are written.

## Global Constraints

- The output is `sql/legacy_advisors_import.sql`, UTF-8, starting with `SET NAMES utf8mb4;`. Its data
  statements sit inside `START TRANSACTION;` … `COMMIT;`.
- Re-runnable: a second run adds 0 advisors and 0 links. Advisors are inserted with `INSERT … SELECT … WHERE NOT EXISTS`
  on the natural key, links with `INSERT IGNORE`, and `legacy_programs` is set by an `UPDATE … WHERE legacy_programs IS NULL`.
- No hard-coded ids. Buildings are looked up as `(SELECT id FROM map_item WHERE discr = 'building' AND name = …)`,
  programs as `TRIM(full_name) = … AND catalog = …`, and advisors by natural key into `@advisor`.
- Natural key. Person: `type = 'Person' AND last_name = … AND email <=> …` (with no email, `first_name = …`
  instead of the email). Organization: `type = 'Organization' AND organization = …`.
- `created_by` / `updated_by` = `'legacy_import'`, and `created` / `updated` = `NOW()`.
- `type` is `'Person'` or `'Organization'` (`IcAdvisorType`). `area`, `forager_url` and `image_url` stay NULL.
- `legacy_programs`: every legacy program name of the advisor's rows, matched or not. Cleaned, deduped
  case-insensitively, sorted A–Z and joined with `", "`. Some names contain commas, so a reader splits by eye.
- When a group's rows disagree on a field, the most common cleaned non-null value wins. A tie goes to the row
  with the latest `updated_at`.
- Relative legacy URLs (`/cob/faculty/…`) get the prefix `https://www.emich.edu`.
- Phones with exactly 10 digits become `734.487.1234`. Values with no digits (`N/A`, a name typed into the
  phone field) become NULL.
- `legacy_programs` isn't writable through the API and isn't added to `IcAdvisor::toPublicArray()`.
- Generate the final file from data that matches production. The legacy tables were edited as late as 2026-11-24.
  The command is read-only, so it can run on the server, or a fresh copy of the two tables can come first.
- Every migration follows CLAUDE.md: check `information_schema` first, and use `warnIf` / `abortIf`.

## Review Focus

1. **Production data differs from the data the file was made from.** The header records the source DB,
   the legacy row counts and the expected totals, and the file ends with `SELECT` checks to compare against them.
2. **A program or building name exists locally but not on production.** A subquery that finds nothing would
   drop that link silently. The trailing checks (link count against expected) catch it. Task 5 tests that the
   expected link count appears in the header.
3. **Re-saving an imported advisor on the form fails validation** (bad email or URL, a person missing a first
   name). The command validates every advisor with Symfony's validator and refuses to write the file on any
   violation (Task 5 test).
4. **Mojibake.** `advisor_programs` is latin1 with UTF-8 bytes in it (`MÃ³nica`, `â€‹`). Task 3 tests repair it,
   and Task 7 checks that no imported text contains `Ã`, `Â` or `â€`.
5. **Running the file twice or after a partial run.** Task 5 tests the idempotent statement shapes, and Task 7
   runs the file twice and compares counts.

---

### Task 1: `ic_advisors.legacy_programs` column, read-only in the API

**Files:**
- Create: `src/Migrations/Version20261125000000.php`
- Modify: `src/Entity/Ic/IcAdvisor.php`
- Modify: `docs/ic-advisors.md` (column table, migrations table)
- Test: `tests/Api/Advisors/AdvisorControllerTest.php`

**Interfaces:**
- Produces: column `ic_advisors.legacy_programs TEXT DEFAULT NULL AFTER image_url`. `IcAdvisor::getLegacyPrograms(): ?string`
  (mapped `#[ORM\Column(name: 'legacy_programs', type: 'text', nullable: true)]`, `#[Groups("ic")]`, no setter).
  `GET /api/advisors/{id}` returns `legacy_programs` (it selects `c.*`).

- [ ] **Step 0: Sync local migration bookkeeping.** Run `docker exec iccommand-web-1 php bin/console doctrine:migrations:list`.
  If `Version20261102000000`, `Version20261102010000`, `Version20261115000000` and `Version20261115010000` show
  "not migrated" while `ic_advisors` exists, mark each one `doctrine:migrations:version 'App\Migrations\<name>' --add -n`.
  Running them again would create `ic_contacts` beside `ic_advisors`, and `Version20261115000000` would then abort.
- [ ] **Step 1: Write the failing test** `testLegacyProgramsIsReadOnly`: create an organization (`createOrganization()`),
  then set `legacy_programs = 'Biology Minor, Chemistry [BS]'` with DBAL. `GET /api/advisors/{id}` returns
  `legacy_programs === 'Biology Minor, Chemistry [BS]'`. `PUT` with the full payload plus `legacyPrograms: 'changed'`
  and `legacy_programs: 'changed'` returns 200, and a second GET still returns the original value.
  `IcAdvisor::toPublicArray()` for it has no `legacyPrograms` / `legacy_programs` key.
- [ ] **Step 2: Run it and check that it fails.** `docker exec iccommand-web-1 php bin/phpunit --filter testLegacyProgramsIsReadOnly`
  should fail with an unknown column `legacy_programs`.
- [ ] **Step 3: Write the migration.** `up()` adds the column if `information_schema.COLUMNS` doesn't have it,
  and otherwise warns and skips. `down()` drops it if present, and otherwise warns. Its docblock says `down()` loses the lists.
  Add the entity property and getter.
- [ ] **Step 4: Migrate and run the test until it passes.** Run `doctrine:migrations:migrate -n`, then the filter above, then
  `php bin/phpunit --testsuite advisors`. Everything should PASS, and `doctrine:schema:update --dump-sql | grep -i legacy` should print nothing.
- [ ] **Step 5: Update the docs.** In `docs/ic-advisors.md`, add a `legacy_programs` row to the `ic_advisors` table
  ("Read-only list of the advisor's programs in the old advisor tables, from the 2026-11 import; not published")
  and a `Version20261125000000` row to the migrations table.
- [ ] **Step 6: Commit:** `git add src/Migrations/Version20261125000000.php src/Entity/Ic/IcAdvisor.php tests/Api/Advisors/AdvisorControllerTest.php docs/ic-advisors.md && git commit -m "Add read-only ic_advisors.legacy_programs"`

### Task 2: Read-only Legacy Programs textarea on the advisor form

**Files:**
- Modify: `assets/js/components/advisors/AdvisorForm.vue` (data `record`, the template after the Campus Location row)
- Modify: `public/build/*` (rebuilt)

**Interfaces:**
- Consumes: `legacy_programs` from `GET /api/advisors/{id}` (Task 1).

- [ ] **Step 1: Add the field.** Add `legacy_programs: ""` to `record`. After the Campus Location `form-row`, add a
  `form-group` shown with `v-if="record.legacy_programs"`: label "Legacy Programs", a `<textarea class="form-control" rows="4" readonly>`
  bound with `:value` (not `v-model`, so it never marks the form dirty), and `<small class="text-muted">` reading
  "From the old advisor tables, for checking against Associated Programs. Not editable." Leave it out of `submitAdvisor()`'s payload.
- [ ] **Step 2: Build:** `npm run build && bin/check-frontend-build`. Expected: build succeeds and the check passes.
- [ ] **Step 3: Check it in the browser.** On an advisor with `legacy_programs` set (from Task 1's DBAL update or by hand), the textarea
  shows in both read-only and edit mode, can't be typed in, and Save leaves the value unchanged. An advisor without it shows no textarea.
- [ ] **Step 4: Commit:** `git add assets/js/components/advisors/AdvisorForm.vue public/build && git commit -m "Show legacy programs read-only on the advisor form"`

### Task 3: `LegacyAdvisorParser`: cleaning and parsing one value

**Files:**
- Create: `src/Service/LegacyAdvisors/LegacyAdvisorParser.php`
- Test: `tests/Unit/LegacyAdvisors/LegacyAdvisorParserTest.php` (plain `TestCase`, no DB)

**Interfaces:**
- Produces (all `public static`):
  - `cleanText(?string $value): ?string`: repairs UTF-8 read as cp1252 (only when the string has a mojibake
    pair and the repair is valid UTF-8), removes U+200B/U+FEFF, turns NBSP and tabs into spaces, collapses
    whitespace and trims. Empty becomes null.
  - `classifyName(string $name): ?IcAdvisorType`: null means skip. Skip when the name matches `/^(visit|contact|please)\b/i`,
    `/(advising|advisor) information/i` or `/^program advising$/i`. Organization when the text before the first
    comma has `College|Center|Services|Office|University|Department|Institute`. Otherwise Person.
  - `splitPersonName(string $name): array{first: string, last: string}`: takes the text before the first
    comma, strips trailing credential tokens (`Ph.D.`, `PhD`, `Ph. D.`, `Ed.D`, `EdS`, `Ed. S.`, `DMA`, `MHS`, `OTRL`,
    `MBA`, `CCC-A`, `MT`, `(ASCP)`), then uses the last word as the last name and the rest as the first name.
  - `parseAddress(?string $address): array{room: ?string, building: ?string}`. The room is the leading token
    `\d+ [A-Z]` (with the space removed), or `[A-Z]?\d+(-?[A-Z])?`. The building is the rest.
  - `cleanEmail(?string): ?string` (lowercase; null unless `FILTER_VALIDATE_EMAIL`), `cleanPhone(?string): ?string`,
    `absoluteUrl(?string): ?string`.
  - `programKey(string $name): string` (cleanText, curly quotes to straight, lowercase) and `buildingKey(string $name): string`
    (lowercase, `-` to a space, drop the words `building`/`hall`, collapse spaces).

- [ ] **Step 1: Write the failing tests.** Use one data-provider row per case:
  - `cleanText`: `"MÃ³nica MillÃ¡n, PhD"` → `"Mónica Millán, PhD"`; `"Aaron Strumingerâ€‹, Ph.D."` → `"Aaron Struminger, Ph.D."`;
    `"RebeccaÂ Spragg"` → `"Rebecca Spragg"`; `"jdesider@emich.edu\t"` → `"jdesider@emich.edu"`; `"   "` → null; `"Mónica"` → `"Mónica"` (already fine, unchanged)
  - `classifyName`: null for `"Visit the CMTA website for advising information."`, `"Contact department for information"`,
    `"Please contact the school for advisor information."`, `"Program Advising"`, `"Contact Program for Advising"`.
    ORGANIZATION for `"College of Business Academic Services"`, `"University Advising & Career Development Center"`.
    PERSON for `"Joy Beatty, Ph.D., Department Head"`, `"Hannah Seidel, Ph.D."`
  - `splitPersonName`: `"Alice Jo Rainville Ph.D., RD, CHE, SNS, FAND"` → `Alice Jo` / `Rainville`;
    `"Lynne M. Shetron-Rama PhD MT (ASCP)"` → `Lynne M.` / `Shetron-Rama`; `"Sharon Holt MHS OTRL"` → `Sharon` / `Holt`;
    `"Jennifer Desiderio Ed. S."` → `Jennifer` / `Desiderio`; `"Lidia Lee Ph.D. CCC-A"` → `Lidia` / `Lee`;
    `"Ildiko (Ildi) Porter-Szucs, PhD"` → `Ildiko (Ildi)` / `Porter-Szucs`
  - `parseAddress`: `"319-M Porter"` → `319-M` / `Porter`; `"206 J Marshall"` → `206J` / `Marshall`; `"N101 Alexander"` → `N101` / `Alexander`;
    `"1 Hill Hall"` → `1` / `Hill Hall`; `"319N Porter Building"` → `319N` / `Porter Building`; `"Hill Hall"` → null / `Hill Hall`; null → null / null
  - `cleanPhone`: `"734-487-3300"` and `" 734.487.3300"` → `"734.487.3300"`; `"N/A"` → null; `"Stephanie Newell, Ph.D"` → null
  - `cleanEmail`: `" ccroxall@emich.edu"` → `"ccroxall@emich.edu"`; `"COE_advising@emich.edu"` → `"coe_advising@emich.edu"`; `"-"` → null; `"Contact department for advisor information"` → null
  - `absoluteUrl`: `"/cob/faculty/b_ross.php"` → `"https://www.emich.edu/cob/faculty/b_ross.php"`; `"\t/history-philosophy/x.php"` → `"https://www.emich.edu/history-philosophy/x.php"`; `"https://example.edu/a"` unchanged; null → null
  - `programKey("Children’s and Young Adult Literature [BA] ")` === `programKey("Children's and Young Adult Literature [BA]")`
  - `buildingKey("Pray Harrold")` === `buildingKey("Pray-Harrold")`; `buildingKey("Alexander Hall")` === `"alexander"`
- [ ] **Step 2: Run them and check that they fail.** `docker exec iccommand-web-1 php bin/phpunit tests/Unit/LegacyAdvisors/LegacyAdvisorParserTest.php`
  should fail with the class not found.
- [ ] **Step 3: Implement `LegacyAdvisorParser`.**
- [ ] **Step 4: Run the tests until they pass.** Same command. Expected: PASS.
- [ ] **Step 5: Commit:** `git add src/Service/LegacyAdvisors/LegacyAdvisorParser.php tests/Unit/LegacyAdvisors/LegacyAdvisorParserTest.php && git commit -m "Add legacy advisor value parser"`

### Task 4: `LegacyAdvisorImport`: grouping, picking values, matching programs and buildings

**Files:**
- Create: `src/Service/LegacyAdvisors/LegacyAdvisorImport.php`, `LegacyAdvisor.php`, `LegacyImportResult.php`, `LegacyAdvisorOverrides.php`
- Test: `tests/Unit/LegacyAdvisors/LegacyAdvisorImportTest.php`

**Interfaces:**
- Consumes: `LegacyAdvisorParser` (Task 3).
- Produces:
  - `LegacyAdvisorOverrides` holds only constants, empty until Task 6:
    `MERGE_INTO` (`array<string, string>`: group key → group key it merges into), `FIRST_NAMES` (group key → first name),
    `PROGRAM_ALIASES` (`array<string, list<array{string, string}>>`: legacy program name as cleaned → list of `[full_name, catalog]`),
    `BUILDING_ALIASES` (legacy building text → `map_item.name`).
  - Group keys: `person|<lowercase last>|<email, or lowercase first when there's no email>` and `organization|<lowercase name>`.
  - `new LegacyAdvisorImport(array $mergeInto = LegacyAdvisorOverrides::MERGE_INTO, array $firstNames = …FIRST_NAMES, array $programAliases = …PROGRAM_ALIASES, array $buildingAliases = …BUILDING_ALIASES)`
  - `build(array $legacyRows, array $programs, array $buildings): LegacyImportResult`
    - `$legacyRows`: `list<array{source: string, program: ?string, advisor_name: ?string, advisor_address: ?string, advisor_phone: ?string, advisor_email: ?string, website: ?string, updated_at: string}>`
    - `$programs`: `list<array{full_name: string, catalog: string}>`. `$buildings`: `list<string>` (building names)
  - `final readonly class LegacyAdvisor`: `key`, `type` (IcAdvisorType), `firstName`, `lastName`, `organization`, `email`, `phone`, `room`,
    `buildingName`, `url` (all `?string` except key/type), `legacyPrograms` (`list<string>`), `programs` (`list<array{string, string}>`),
    and `legacyProgramsText(): string`.
  - `final readonly class LegacyImportResult`: `advisors` (`list<LegacyAdvisor>`, sorted by key), `skippedNames` (`array<string, int>` name → row count),
    `unmatchedPrograms` (`array<string, list<string>>` legacy name → advisor keys), `unmatchedAddresses` (`list<string>`).
- Matching rules:
  - A program matches when `programKey` is equal, against every catalog.
  - `PROGRAM_ALIASES` adds targets on top of exact matches.
  - A building matches on an exact `buildingKey`. Failing that, a unique word-prefix match (`"mark jefferson"` → Mark Jefferson
    Science Complex, `"alexander"` → Alexander Music Building), then `BUILDING_ALIASES`. If nothing matches, the building is null,
    the room is the whole cleaned address, and the address is reported.

- [ ] **Step 1: Write the failing tests.** Use small fixture arrays (copy the values below):
  - `testSkippedNamesMakeNoAdvisor`: a "Visit the English website…" row → no advisors, `skippedNames` has it with count 1
  - `testVariantsOfOnePersonMerge`: "Herman Tang Ph.D." and "Herman Tang, Ph.D." (both htang2@emich.edu) → 1 advisor `Herman` / `Tang`
  - `testMostCommonValueWinsAndTieGoesToLatest`: 3 rows for one email with rooms `712` ×2 and `713B` ×1 → `712`. Two rows with phones
    `734.487.0012` (2025-08-12) and `734.487.0013` (2026-11-24) → `734.487.0013`
  - `testSharedMailboxStaysSeparate`: Presberry and Radina with coe_ldcn_spa_phd@emich.edu → 2 advisors
  - `testMergeIntoOverride`: Kautz rows with two emails and `mergeInto: ['person|kautz|coe_ldcn_spa_phd@emich.edu' => 'person|kautz|coe_ldcn_k12_info@emich.edu']` → 1 advisor
    with legacy programs from both rows. The first-names override `['person|ross|bwadding@emich.edu' => 'Barbara']` → `Barbara`
  - `testProgramsMatchAcrossCatalogs`: program "Dietetics [BS + MS] " in both catalogs, legacy "Dietetics [BS + MS]" → `programs` has both catalog pairs
  - `testProgramAliasAddsTargets`: legacy "PK-12 French Teaching [BA]" with alias `[['PreK-12 French Teaching [BA]','undergraduate']]` → linked, and not in `unmatchedPrograms`
  - `testLegacyProgramsListsEveryNameOnce`: rows "Spanish Minor", "spanish minor ", "Graduate Coordinator" → `legacyProgramsText() === 'Graduate Coordinator, Spanish Minor'`.
    "Graduate Coordinator" is in `unmatchedPrograms`
  - `testBuildingAndRoom`: "541C Mark Jefferson" → room `541C`, building `Mark Jefferson Science Complex`. "251 Packham" → room `251 Packham`,
    building null, address reported. With `buildingAliases: ['Packham' => 'Rackham Building']` → room `251`, building `Rackham Building`
  - `testOrganization`: "College of Business Academic Services" → ORGANIZATION, organization set, first/last null
- [ ] **Step 2: Run them and check that they fail.** `docker exec iccommand-web-1 php bin/phpunit tests/Unit/LegacyAdvisors/LegacyAdvisorImportTest.php` should fail.
- [ ] **Step 3: Implement the four classes.** Group, apply `MERGE_INTO`, pick each field by the tie rule, then match programs and the building.
- [ ] **Step 4: Run the tests until they pass.** Expected: PASS, and the Task 3 tests still pass.
- [ ] **Step 5: Commit:** `git add src/Service/LegacyAdvisors tests/Unit/LegacyAdvisors && git commit -m "Group and match legacy advisors"`

### Task 5: SQL writer and the `app:advisors:legacy-sql` command

**Files:**
- Create: `src/Service/LegacyAdvisors/LegacyAdvisorSqlWriter.php`, `src/Command/LegacyAdvisorSqlCommand.php`
- Test: `tests/Unit/LegacyAdvisors/LegacyAdvisorSqlWriterTest.php`

**Interfaces:**
- Consumes: `LegacyAdvisorImport::build()`, `LegacyImportResult`, `LegacyAdvisor` (Task 4).
- Produces:
  - `LegacyAdvisorSqlWriter::write(LegacyImportResult $result, string $source): string`. String literals escape `\` → `\\` and `'` → `''`.
    NULL is bare `NULL`.
  - Command `app:advisors:legacy-sql`, option `--output` (default `sql/legacy_advisors_import.sql`). It reads with the default DBAL
    connection: both legacy tables (`UNION ALL`, with a `source` column), `SELECT TRIM(full_name) AS full_name, catalog FROM program_programs`, and
    `SELECT name FROM map_item WHERE discr = 'building'`. It builds an `IcAdvisor` (not persisted) from each `LegacyAdvisor` and runs
    `ValidatorInterface::validate()`. On any violation it prints them, writes nothing, and exits 1. Otherwise it writes the file and prints the report:
    advisor count, link count, skipped names, unmatched programs, unmatched addresses.
- File layout, per advisor in key order:
  1. A comment line `-- <key>`.
  2. The `INSERT INTO ic_advisors (type, first_name, last_name, organization, email, phone, room, building_id, url, created, created_by, updated, updated_by) SELECT … FROM DUAL WHERE NOT EXISTS (<natural key>)`.
  3. `SET @advisor = (SELECT id FROM ic_advisors WHERE <natural key> ORDER BY id LIMIT 1);`
  4. `UPDATE ic_advisors SET legacy_programs = '…' WHERE id = @advisor AND legacy_programs IS NULL;`
  5. When it has programs, `INSERT IGNORE INTO program_advisor (program_id, advisor_id) SELECT p.id, @advisor FROM program_programs p WHERE (TRIM(p.full_name), p.catalog) IN ((…), …);`

  The header comment holds the source, the generated time, the legacy row counts, `Expected: N advisors, M links`, and how to run the file.
  After `COMMIT;` come `SELECT COUNT(*) AS imported_advisors FROM ic_advisors WHERE created_by = 'legacy_import';` and the matching link count.

- [ ] **Step 1: Write the failing tests** (build a `LegacyImportResult` by hand):
  - `testStartsWithCharsetAndTransaction`: output starts with `SET NAMES utf8mb4;`, contains `START TRANSACTION;`, and `COMMIT;` comes before the final `SELECT COUNT(*)`
  - `testInsertIsGuardedByNaturalKey`: a person with an email gives `WHERE NOT EXISTS (SELECT 1 FROM ic_advisors WHERE type = 'Person' AND last_name = 'Tang' AND email <=> 'htang2@emich.edu')`.
    With no email it uses `first_name = 'Herman'` instead
  - `testBuildingIsLookedUpByName`: contains `(SELECT id FROM map_item WHERE discr = 'building' AND name = 'Sill Hall')`. A null building gives `NULL`
  - `testQuoting`: organization `O'Neil \ Center` is written as `'O''Neil \\ Center'`
  - `testLinksAndLegacyPrograms`: contains `INSERT IGNORE INTO program_advisor`, `('Dietetics [BS + MS]', 'graduate')`, and
    `UPDATE ic_advisors SET legacy_programs = 'Dietetics [BS + MS], Nutrition Minor' WHERE id = @advisor AND legacy_programs IS NULL;`
  - `testHeaderHasExpectedCounts`: 2 advisors with 3 links → the header contains `Expected: 2 advisors, 3 links`
  - `testNoLinkStatementWithoutPrograms`: an advisor with no programs gets no `INSERT IGNORE`
- [ ] **Step 2: Run them and check that they fail.** `docker exec iccommand-web-1 php bin/phpunit tests/Unit/LegacyAdvisors/LegacyAdvisorSqlWriterTest.php` should fail.
- [ ] **Step 3: Implement the writer and the command.**
- [ ] **Step 4: Run the tests until they pass, then run the command.** `php bin/phpunit tests/Unit/LegacyAdvisors` should PASS. Then
  `docker exec iccommand-web-1 php bin/console app:advisors:legacy-sql --output=var/legacy_advisors_draft.sql` should exit 0 and report
  about 150 advisors. The draft stays in `var/` (gitignored) until Task 6.
- [ ] **Step 5: Commit:** `git add src/Service/LegacyAdvisors/LegacyAdvisorSqlWriter.php src/Command/LegacyAdvisorSqlCommand.php tests/Unit/LegacyAdvisors/LegacyAdvisorSqlWriterTest.php && git commit -m "Write legacy advisor import SQL"`

### Task 6: Close calls → overrides (needs the user's answers)

**Files:**
- Modify: `src/Service/LegacyAdvisors/LegacyAdvisorOverrides.php`
- Modify: `docs/superpowers/plans/2026-11-25-legacy-advisor-close-calls.md` (record the answers)

- [ ] **Step 1: Make the data current.** Confirm with the user that local `advisor_programs` / `uacdc_advisor_programs` match production,
  or have them load a fresh copy. Rerun the command and compare its report with the close-calls file.
- [ ] **Step 2: STOP and ask the user** about each close call in the file they haven't answered yet, plus any new name pair, unmatched program
  with a likely candidate, or unmatched address the report shows. Don't continue without the answers.
- [ ] **Step 3: Encode the answers** in `LegacyAdvisorOverrides`. Each constant gets a one-line comment naming its close-call ID
  (`// N2`, `// U4`). Write the answers into the close-calls file.
- [ ] **Step 4: Regenerate:** `php bin/console app:advisors:legacy-sql`. It should exit 0. Every Yes in the close-calls file should be out of
  "unmatched programs", the only unmatched address left should be one the user chose to leave, and the advisor count should drop by the merges.
- [ ] **Step 5: Commit:** `git add src/Service/LegacyAdvisors/LegacyAdvisorOverrides.php docs/superpowers/plans/2026-11-25-legacy-advisor-close-calls.md sql/legacy_advisors_import.sql && git commit -m "Add legacy advisor import SQL with close-call overrides"`

### Task 7: Check the SQL on a scratch copy, then document it

**Files:**
- Modify: `docs/ic-advisors.md` (a "Legacy import (November 2026)" section)

- [ ] **Step 1: Make a scratch copy.** Copy `ic` to `ic_legacytest` with `mariadb-dump --single-transaction ic | mariadb ic_legacytest`
  inside `iccommand-db-1`. Leave out `--routines`: the local `mysql.proc` is out of date and the dump fails. Then run
  `DATABASE_NAME=ic_legacytest php bin/console doctrine:migrations:migrate -n` in the web container.
- [ ] **Step 2: Run the file:** `docker exec -i iccommand-db-1 mariadb -uroot ic_legacytest < sql/legacy_advisors_import.sql`, with the password
  from `MYSQL_PWD`. The two trailing counts should equal the header's `Expected:` line (plus any `legacy_import` rows already there, which should be none).
- [ ] **Step 3: Run it again.** Both counts should be unchanged.
- [ ] **Step 4: Spot-check.** `SELECT COUNT(*) FROM ic_advisors WHERE CONCAT_WS(' ', first_name, last_name, organization, legacy_programs) REGEXP 'Ã|Â|â€'` should be 0.
  `SELECT first_name, last_name, room, building_id, url, legacy_programs FROM ic_advisors WHERE last_name IN ('Millán','Kautz','Roche')` should
  show clean names, one Kautz, and Roche's building set if B1 was a yes. `doctrine:schema:validate` should show no new drift on `ic_advisors`.
- [ ] **Step 5: Drop the scratch DB:** `DROP DATABASE ic_legacytest`.
- [ ] **Step 6: Document it.** In `docs/ic-advisors.md`, cover how the file is regenerated (`app:advisors:legacy-sql`), where the overrides live,
  how to run it on production (back up, migrate, then `mariadb ic < sql/legacy_advisors_import.sql`, then compare the counts), that it's
  re-runnable, and that `created_by = 'legacy_import'` marks the imported rows.
- [ ] **Step 7: Commit:** `git add docs/ic-advisors.md && git commit -m "Document the legacy advisor import"`
