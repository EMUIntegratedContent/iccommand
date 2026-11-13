# Contacts Application Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the contacts list/form out of `/admin` into a standalone Contacts app with its own roles, menus, templates and Vue components.

**Architecture:** Copy the Scholarships wiring: page controller `App\Controller\Contacts\ContactController` (`/contacts`), JSON API `App\Controller\Api\Contacts\ContactController` (`/api/contacts/`), templates `templates/contacts/`, Vue `assets/js/components/contacts/`. Four hierarchical roles; every `#[IsGranted]` also admits `ROLE_GLOBAL_ADMIN`. The old admin routes/files are deleted outright (unreleased feature).

**Tech Stack:** Symfony 8 / PHP 8.5, Twig, Vue 3 + Webpack Encore, PHPUnit (WebTestCase).

**Spec:** `docs/superpowers/specs/2026-11-13-contacts-app-design.md`

## Global Constraints

- Roles, exactly: `ROLE_CONTACTS_VIEW: ROLE_USER`, `ROLE_CONTACTS_EDIT: ROLE_CONTACTS_VIEW`, `ROLE_CONTACTS_DELETE: ROLE_CONTACTS_EDIT`, `ROLE_CONTACTS_ADMIN: ROLE_CONTACTS_DELETE`. EDIT covers add and edit (no separate CREATE).
- Every `#[IsGranted]` uses an Expression including `is_granted("ROLE_GLOBAL_ADMIN")` (CLAUDE.md: global admin doesn't inherit module roles).
- New API controller must be registered in `config/routes.yaml` with prefix `/api/contacts/`, or `attributes.yaml` serves it outside the `^/api` firewall.
- Page access rule `^/contacts` -> `[ROLE_CONTACTS_VIEW, ROLE_GLOBAL_ADMIN]` in `security.yaml`.
- Nothing contacts-related may remain under `admin/` (controllers, templates, nav, Vue, `/api/admin/contacts`).
- Unchanged: contact model, public feeds (`IcContact::toPublicArray()`), picker `/api/ic/contacts/options` and its roles.
- Tests hit the real `ic` DB: use the `ZZ` name prefix and clean up (see CLAUDE.md "Tests" and memory `test-env-broken.md` for how to run phpunit). Run PHP/console in the container: `docker exec iccommand-web-1 php bin/console ...`.
- `public/build` is committed: rebuild with `npm run build` and pass `bin/check-frontend-build` before committing it.

## Review Focus

- A `ROLE_CONTACTS_VIEW`-only user POSTing/PUTting/DELETEing via the API, or opening `/contacts/create`, gets 403 (never 500, never silent success).
- Deleting a contact still in use returns 409 with the reason for a `ROLE_CONTACTS_DELETE` user (not 500).
- A global admin holding no `ROLE_CONTACTS_*` role can use every contacts page and API route, including `/contacts/manage`.
- `/contacts/create` and `/contacts/manage` are not swallowed by the `/contacts/{id}` route; `/contacts/9999999/edit` is 404.
- Old `/admin/contacts`, `/api/admin/contacts` are gone (404), and unauthenticated `/api/contacts` is 401 JSON (firewall applies), not a redirect or 200.
- A `ROLE_CONTACTS_ADMIN` user can list only Contacts users via `/api/admin/appusers/ROLE_CONTACTS_` and is refused (403) for `ROLE_MAP_`.
- Picker-only users (`ROLE_MAP_CREATE`, `ROLE_PROGRAMS_CREATE`, `ROLE_SCHOLARSHIP_CREATE`) still get 200 from `/api/ic/contacts/options` and 403 from `/api/contacts`.

---

### Task 1: Roles and access management wiring

**Files:**
- Modify: `config/packages/security.yaml` (access_control after `^/scholarships`; hierarchy after Scholarships block)
- Modify: `src/Controller/Api/Admin/UserController.php` (the `IsGranted` Expression on `getRolesAction`, `getAppusersAction`, `getAppusersNotAction`: append `or is_granted("ROLE_CONTACTS_ADMIN")`)
- Modify: `assets/js/components/admin/UserManage.vue` (add `rolesContacts: []`, push keys starting `ROLE_CONTACTS_` next to the `ROLE_SCHOLARSHIP_` branch, and a "Contacts" card mirroring the Scholarships card, key prefix `user-contacts-`)
- Test: `tests/Api/Contacts/ContactAccessTest.php` (new)

**Interfaces:**
- Produces: roles above, usable by Tasks 2-5. Test helper `persistUser(EntityManagerInterface $em, string $suffix, array $roles): User` (copy from `tests/Api/Admin/IcContactControllerTest.php`, prefix `zz_ictest_`, tearDown deleting those users).

- [ ] **Step 1: Write failing tests** in `ContactAccessTest`: `testRoleHierarchy` (container `security.role_hierarchy` -> `getReachableRoleNames(['ROLE_CONTACTS_ADMIN'])` contains `ROLE_CONTACTS_DELETE`, `_EDIT`, `_VIEW`, `ROLE_USER`; for `['ROLE_CONTACTS_VIEW']` it does not contain `ROLE_CONTACTS_EDIT`); `testRolesEndpointListsContactsRoles` (login `ROLE_CONTACTS_ADMIN`, `GET /api/admin/roles` -> 200, JSON keys include the four roles); `testAdminManagesOnlyContactsUsers` (`GET /api/admin/appusers/ROLE_CONTACTS_` -> 200; `/api/admin/appusers/ROLE_MAP_` -> 403; a `ROLE_CONTACTS_VIEW` user gets 403 on the first).
- [ ] **Step 2: Run** `docker exec iccommand-web-1 php bin/phpunit tests/Api/Contacts/ContactAccessTest.php` — expect FAIL (unknown roles / 403).
- [ ] **Step 3: Implement** the security.yaml, UserController and UserManage.vue changes listed above.
- [ ] **Step 4: Run the test again** — expect PASS.
- [ ] **Step 5: Commit** `git add -A config src assets tests && git commit -m "Add ROLE_CONTACTS_* roles and access management"` (build output is rebuilt in Task 4).

### Task 2: Move the API to /api/contacts with per-action roles

**Files:**
- Move: `src/Controller/Api/Admin/IcContactController.php` -> `src/Controller/Api/Contacts/ContactController.php` (`git mv`; namespace `App\Controller\Api\Contacts`, class `ContactController`; fix the docblock: prefix `/api/contacts/`)
- Modify: `config/routes.yaml` (replace `admin_ic_contacts` with `contacts: resource App\Controller\Api\Contacts\ContactController, prefix: /api/contacts/`)
- Move + modify: `tests/Api/Admin/IcContactControllerTest.php` -> `tests/Api/Contacts/ContactControllerTest.php` (namespace `App\Tests\Api\Contacts`, URLs `/api/contacts`)
- Modify: `tests/Api/Ic/IcContactFeedsTest.php:223` (URL `/api/contacts`)

**Interfaces:**
- Consumes: roles from Task 1; `IcService` methods unchanged.
- Produces: routes `GET /api/contacts` (list, `ROLE_CONTACTS_VIEW`), `GET /api/contacts/{id}` (VIEW), `POST /api/contacts` and `PUT /api/contacts/{id}` (`ROLE_CONTACTS_EDIT`), `DELETE /api/contacts/{id}` (`ROLE_CONTACTS_DELETE`); each Expression also admits `ROLE_GLOBAL_ADMIN`.

- [ ] **Step 1: Write failing tests** in `ContactControllerTest` (after moving, URL changes only): `testRoleMatrix` — for each of `ROLE_CONTACTS_VIEW`, `_EDIT`, `_DELETE`, `_ADMIN`, `ROLE_GLOBAL_ADMIN` (alone), `ROLE_MAP_ADMIN` (no contacts role): expected status of list/get (200 or 403), POST (201 or 403), PUT (200 or 403), DELETE (204 or 403) per the Interfaces block; `testDeleteInUseIs409ForDeleteRole` (existing in-use test, run as `ROLE_CONTACTS_DELETE`, expects 409); `testOldAdminApiIsGone` (`GET /api/admin/contacts` -> 404); `testUnauthenticatedIs401` (`GET /api/contacts` without login -> 401, JSON). In the picker test, the loop's `/api/admin/contacts` 403 check becomes `/api/contacts`.
- [ ] **Step 2: Run** `docker exec iccommand-web-1 php bin/phpunit tests/Api/Contacts tests/Api/Ic` — expect FAIL.
- [ ] **Step 3: Implement** the move, the five `#[IsGranted(new Expression(...))]` changes, and the routes.yaml change; run `docker exec iccommand-web-1 php bin/console cache:clear` and `debug:router | grep contacts` to confirm `/api/contacts` routes exist and `/api/admin/contacts` does not.
- [ ] **Step 4: Run the same command** — expect PASS (clean up any `ZZ` rows left by failures).
- [ ] **Step 5: Commit** `git add -A src config tests && git commit -m "Move contacts API to /api/contacts with per-action roles"`.

### Task 3: Contacts pages, templates and nav

**Files:**
- Create: `src/Controller/Contacts/ContactController.php`, `templates/contacts/{index,create,edit,show,manage}.html.twig`, `templates/contacts/nav_contacts.html.twig`
- Modify: `src/Service/IcService.php` (add `getContactPermissions(): array`, same keys/inheritance as `ScholarshipService::getScholarshipPermissions()` but with `view/edit/delete/admin` only, using `ROLE_CONTACTS_*`; global admin gets all)
- Delete: `src/Controller/Admin/IcContactController.php`, `templates/admin/contacts/`; remove the "Manage Contacts" link from `templates/admin/nav_admin.html.twig`
- Test: `tests/Controller/Contacts/ContactPagesTest.php` (new)

**Interfaces:**
- Produces routes (declare `manage` before `{id}`; `{id}` requires `\d+`): `/contacts` `contacts_index` (VIEW), `/contacts/create` `contacts_create` (EDIT), `/contacts/manage` `contacts_manage` (ADMIN), `/contacts/{id}/edit` `contacts_edit` (EDIT, 404 if `IcService::getContact($id)` is null), `/contacts/{id}` `contacts_show` (VIEW, 404 likewise). Every template receives `permissions` = `json_encode($service->getContactPermissions())`, which becomes the Vue prop `:permissions="[{{ permissions|raw }}]"` as in `templates/scholarship/index.html.twig`. Templates mount `<contacts-list>` and `<contacts-form>` (`:item-exists`, `start-mode` "edit" or "view", `item-id`); manage mounts `<app-manage role-prefix="ROLE_CONTACTS_" app-slug="contacts" app-name="Contacts App" current-user="{{ app.user.username }}">`. Nav heading "Contacts Application" with links: list, "Add new contact" (EDIT or admin only), "Manage app" (ADMIN only).

- [ ] **Step 1: Write failing tests**: `testPageAccessMatrix` (VIEW: `/contacts` 200, `/contacts/create` 403, `/contacts/manage` 403; EDIT: create 200, manage 403; ADMIN: manage 200; `ROLE_GLOBAL_ADMIN` alone: all 200), `testRoutesNotCapturedByIdRoute` (admin `/contacts/create` and `/contacts/manage` are 200, `/contacts/9999999/edit` and `/contacts/9999999` are 404), `testOldAdminPagesAreGone` (`/admin/contacts` -> 404), `testNavHidesAddAndManageFromViewers` (VIEW user's `/contacts` body lacks `/contacts/create` and `/contacts/manage`; ADMIN's has both).
- [ ] **Step 2: Run** `docker exec iccommand-web-1 php bin/phpunit tests/Controller/Contacts` — expect FAIL.
- [ ] **Step 3: Implement** the controller, templates, nav, `getContactPermissions()`, and the deletions above.
- [ ] **Step 4: Run** the command — expect PASS.
- [ ] **Step 5: Commit** `git add -A src templates tests && git commit -m "Add Contacts pages and remove admin contact pages"`.

### Task 4: Vue components

**Files:**
- Move (`git mv`): `assets/js/components/admin/ContactList.vue` -> `components/contacts/ContactList.vue`; `admin/ContactForm.vue` -> `contacts/ContactForm.vue`; `admin/IcDeleteModal.vue` and `admin/IcUsageLists.vue` -> `components/ic/` (shared with the College/Department forms; update the imports in those files, in `ContactForm.vue`, and anywhere else `git grep -n "IcDeleteModal\|IcUsageLists"` finds)
- Modify: `assets/js/app.js` (drop `admin-contact-list/form` at lines 73-74; add a "Contacts Application" block registering `contacts-list` and `contacts-form`; fix the shared-component registrations if any exist)
- Modify (both moved components): URLs `/admin/contacts...` -> `/contacts...`, `/api/admin/contacts...` -> `/api/contacts...`; add `permissions` prop (Array, same shape as `ScholarshipList.vue`); show "Add contact" only when `permissions[0].edit`, edit links only when `edit` (else link to `/contacts/{id}` show page), Save only in edit mode, Delete button only when `permissions[0].delete`.
- Modify: `public/build/*` (rebuild)

**Interfaces:**
- Consumes: template props from Task 3 (`permissions`, `item-exists`, `start-mode`, `item-id`); API from Task 2.

- [ ] **Step 1: Make the changes above**, then `git grep -n "admin/contacts\|admin-contact" -- assets src templates` returns nothing.
- [ ] **Step 2: Build** `npm run build` — expect success; `bin/check-frontend-build` prints OK.
- [ ] **Step 3: Verify in the browser** (docker app on :8080, `/run` skill or Chrome tools) as three users — `ROLE_CONTACTS_VIEW`: list shows no Add button, rows open the read-only form, no Delete; `ROLE_CONTACTS_EDIT`: can add and edit, no Delete; `ROLE_CONTACTS_DELETE`: Delete works and an in-use contact shows the 409 message. Check the History date format and First/Last name order still hold.
- [ ] **Step 4: Commit** `git add -A assets public/build && git commit -m "Move contact Vue components out of admin and gate by role"`.

### Task 5: Menu, homepage tile and docs

**Files:**
- Modify: `templates/base.html.twig` (Maps & Directories: add `or is_granted('ROLE_CONTACTS_VIEW')` to the outer `if` at line ~34 and an item `<a class="dropdown-item" href="/contacts">Contacts</a>` guarded by `ROLE_GLOBAL_ADMIN or ROLE_CONTACTS_VIEW`, after Social Media Directory)
- Modify: `assets/js/components/Homepage.vue` (new `contacts` entry in `userModules`: title "Contacts", `buttonText` "Manage Contacts", `buttonLink` "/contacts", `category` "Maps & Directories", `display: false`, description of shared contacts for programs, scholarships and buildings; show when a role includes `ROLE_CONTACTS_` or `ROLE_GLOBAL_ADMIN`)
- Modify: `CLAUDE.md` (the `ic_contacts` paragraph: managed at /contacts, roles `ROLE_CONTACTS_*`), `docs/ic-contacts.md` (admin screens section and the API table: new URLs and roles), `docs/ic-contacts-plan.md` (the `/admin/contacts` mentions)
- Test: `tests/Controller/Contacts/ContactMenuTest.php` (new)

**Interfaces:**
- Consumes: routes and roles from Tasks 1-3.

- [ ] **Step 1: Write failing tests**: `testMenuShowsContactsToViewers` (login `ROLE_CONTACTS_VIEW` only; `GET /applinks` or any page using `base.html.twig` contains `href="/contacts"` inside the Maps & Directories group), `testMenuHidesContactsFromOthers` (user with only `ROLE_MAP_VIEW` has no `href="/contacts"`; `ROLE_GLOBAL_ADMIN` has it).
- [ ] **Step 2: Run** `docker exec iccommand-web-1 php bin/phpunit tests/Controller/Contacts/ContactMenuTest.php` — expect FAIL.
- [ ] **Step 3: Implement** the twig, `Homepage.vue` and docs changes above.
- [ ] **Step 4: Run** the test — expect PASS; then `npm run build`, `bin/check-frontend-build`, and confirm in the browser that the homepage shows the Contacts tile under Maps & Directories for a `ROLE_CONTACTS_VIEW` user.
- [ ] **Step 5: Run the whole contacts suite** `docker exec iccommand-web-1 php bin/phpunit tests/Api/Contacts tests/Api/Ic tests/Controller/Contacts` — expect all PASS, and no `ZZ`/`zz_ictest_` rows left.
- [ ] **Step 6: Commit** `git add -A && git commit -m "Add Contacts to menu and homepage; update docs"`.
