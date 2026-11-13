# Contacts application (design)

## Intent
Contacts (`ic_contacts`) is shared by Programs, Scholarships and Map, so its list and form should be an
app of their own, not part of /admin. Admin screens stay global-admin-only; Contacts gets its own roles,
menus, templates and Vue components, like Programs and Scholarships.

## Goals
- Own app at `/contacts`: controller `src/Controller/Contacts/`, API `src/Controller/Api/Contacts/` at
  `/api/contacts/`, templates `templates/contacts/` (with `nav_contacts.html.twig`), Vue `components/contacts/`.
- Menu entry "Contacts" under Maps & Directories (top menu in `base.html.twig`) and a tile in `Homepage.vue`.
- Roles `ROLE_CONTACTS_VIEW` < `EDIT` < `DELETE` < `ADMIN` (each inherits the one before; VIEW inherits
  ROLE_USER). ADMIN can also manage who has access to the app (UserController expressions, UserManage.vue).
- Every `#[IsGranted]` includes `ROLE_GLOBAL_ADMIN` via Expression.
- Remove the `/admin/contacts` pages, nav link, admin Vue components and `/api/admin/contacts` routes.

## Non-goals
- No change to the contact model, the public feeds, or the contact picker `/api/ic/contacts/options`
  in program/scholarship/map forms (its roles stay as they are).

## Permissions
VIEW: list and open contacts (read-only form). EDIT: add and edit. DELETE: delete (still refused with 409
while a contact is in use). ADMIN: all of the above plus manage app access (`/contacts/manage`).

## Approach
One sensible way: copy the Scholarships/Social Media wiring. Alternatives (keeping it under /admin with a
looser role, or a shared admin-style module) don't meet the request to separate it from admin.

## Decisions
- URLs `/contacts`, `/contacts/create`, `/contacts/{id}/edit`, `/contacts/manage`. Add `^/contacts` to
  access_control (ROLE_CONTACTS_VIEW, ROLE_GLOBAL_ADMIN) and register the API prefix in `config/routes.yaml`.
- Existing tests (`tests/Api/Admin/IcContactControllerTest.php`) move to the new paths and gain role checks.
- Update `docs/ic-contacts.md` and `CLAUDE.md` for the new location and roles.
- Unreleased feature, so the old routes are removed outright with no redirects.
