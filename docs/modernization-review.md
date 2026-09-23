# ICCommand Modernization Review

**Date:** 2026-09-19, revised 2026-09-23 · **Branch reviewed:** `master` at `ef081e5` · **Status:** planning document, no code changed

This document covers the four goals set for the review:

1. Replace Webpack Encore with Vite and retire outdated packages
2. Find violations of design patterns and Symfony best practices
3. Get the backend and frontend to a unit-testable state
4. Discover security concerns

Section 6 proposes three roadmap options and a recommended sequence. Everything in sections 2 to 5 was verified by reading the source or by running a command; where a claim could not be confirmed from the repository alone it is marked **needs confirmation**. Line numbers refer to the commit above.

> **Reading guide.** Boxes marked *Ops note* explain infrastructure concepts for developers who mostly work on application code.

---

## 0. Summary

**The most urgent problems are security, not tooling.** Five findings are exploitable today by an anonymous internet user or by any logged-in staff account:

| # | Finding | Who can exploit it |
|---|---|---|
| S1 | The 10 admin routes of the Redirect and Uncaught-URL APIs (incl. DELETE, PUT, CSV upload) have no authorization at all. The 3 write routes the emich.edu 404 page uses to count visits and log bad links are open to anyone, not just the 404 page. | Anonymous |
| S2 | `POST /api/crimelog/upload` truncates the public Daily Crime Log table and has no authorization | Anonymous |
| S3 | `PUT /api/admin/users/{username}` lets any logged-in user set anyone's roles (including `ROLE_GLOBAL_ADMIN_SUPER`) and enabled flag | Any user |
| S4 | Photo request creation and directory search skip auth when *any* `X-API-Key` header is present or the User-Agent contains "API" (the key is never validated) | Anonymous |
| S5 | Image uploads keep the client filename and extension and store it under the web root; a `x.php` file starting with `GIF89a` passes the MIME check and is executable under the Docker Apache config | Any user (**needs confirmation** for the production web server) |

These are one-line to one-day fixes and should ship before any refactoring or build-tool work.

**The test suite cannot run.** Four independent blockers (PHPUnit 6-era runner, a config file for an uninstalled bundle, a removed framework option, a missing log channel in the test env) mean `php bin/phpunit` fails before any test executes. The existing tests also depend on a live production-like database.

**The Vite migration is moderate in size (about 3 to 4 days) and mostly mechanical**, but it forces a decision about the committed `public/build` directory, which already causes merge conflicts.

**The backend has a consistent but unhealthy shape**: controllers of 300 to 700 lines hold the business logic, services are thin permission-map and validate wrappers, and the same list/search/CRUD/CSV-import code is copied across every module. The frontend mirrors this: seven list components and seven form components reimplement the same fetch/paginate/submit/delete pattern, and two 557-line components differ only by a word.

Rough total effort to reach all four goals: **8 to 12 developer-weeks** depending on the option chosen (section 6).

---

## 1. Scope and method

- Codebase: Symfony 8.1 / PHP 8.5 (container has PHP 8.4), Doctrine ORM 3.7, Vue 3.4 via Webpack Encore 7, 11 sub-applications, ~19k lines of PHP under `src/`, ~19.6k lines across 56 Vue single-file components.
- Four parallel reviews were run (frontend/build, Symfony practices, backend testability, security), then cross-checked by hand against the source.
- Commands run and their outcomes: `npm outdated`, `npm audit`, `composer audit --locked`, `composer show --locked --outdated`, `composer install` (with platform overrides), `bin/console about|lint:container|debug:router|debug:config security|doctrine:schema:validate|doctrine:mapping:info` against a throwaway copy, and `bin/phpunit` under three PHPUnit versions.
- Nothing was changed in tracked files. `vendor/` was installed locally for verification and is git-ignored.

---

## 2. Security findings (Goal 4)

> **Ops note: why unguarded `/api` routes are anonymous.** `config/packages/security.yaml` lists `access_control` rules only for page prefixes (`^/map`, `^/admin`, ...) plus `^/api/external` as public. There is no rule for `^/api`. Every environment's `main` firewall is `lazy: true`, which means Symfony does not force a login until something *asks* whether the user is authenticated. If a controller has no `#[IsGranted]`, nothing asks, and the request is served to anyone. Confirmed by running anonymous requests through the test kernel: `/api/redirects/list`, `/api/uncaughts/`, and `/api/crimelog/upload` all reached their controllers.

### 2.1 Critical

| ID | Finding | Evidence | Fix |
|---|---|---|---|
| S1 | Redirect and Uncaught-URL APIs are anonymous | `src/Controller/Api/Redirect/RedirectController.php` (9 routes, zero `IsGranted`), `src/Controller/Api/Redirect/UncaughtController.php` (6 routes, zero `IsGranted`). The routes split into two groups (see 2.1a). **Admin routes (10)**, called only by the ICCommand Vue UI: `DELETE /api/redirects/{id}` (:101), list, search, get, `POST/PUT /api/redirects/` (:173, :274), `POST /api/redirects/upload` (:468), and the uncaught DELETE/GET/PUT. **emich.edu 404-page routes (5)**, called by the script on emich.edu's PHP 404 page: `GET|PUT /api/redirects/external/redirect` and `GET|POST|PUT /api/uncaughts/external/uncaught`. Redirects feed the public emich.edu site. | Admin routes: add `IsGranted` with `ROLE_REDIRECT_USER`/`ROLE_REDIRECT_ADMIN`; no emich.edu change needed. The five emich.edu 404-page routes stay login-free: the two GETs fully public, the three writes rate-limited and validated or token-protected depending on where the 404 script runs (see 2.1a). Add a catch-all `access_control` floor for `^/api` that lists these five paths explicitly as exceptions, so an unguarded route cannot recur. |
| S2 | Anonymous truncation and replacement of the Daily Crime Log | `src/Controller/Api/CrimeLog/CrimeLogController.php:56` has no guard; line 85 calls `truncateCrimeLogTable()` which runs `TRUNCATE TABLE dailylog` (`src/Repository/CrimeLog/CrimeLogRepository.php:31`) before any row is validated. This is a Clery-related public record. | Guard with `ROLE_CRIMELOG_USER`; validate the whole CSV before truncating; wrap in a transaction. |
| S3 | Privilege escalation via profile update | `src/Controller/Api/Admin/UserController.php:74-99`: `PUT /api/admin/users/{username}` is `#[IsGranted('ROLE_USER')]`, takes any `{username}`, and calls `setRoles($data['roles'])` and `setEnabled($data['enabled'])` from the raw JSON body. `assets/js/components/Profile.vue` posts the whole user object to this endpoint. | Split into a self-service `/api/profile` endpoint that cannot touch roles or enabled, and an admin endpoint requiring `ROLE_GLOBAL_ADMIN` with an allow-list of grantable roles. |
| S4 | Fake API-key bypass | `src/Controller/Api/PhotoRequest/PhotoRequestController.php:198-210` and `src/Controller/Api/Directory/DirectoryController.php:94-107`: auth is skipped if `X-API-Key` is present (value never checked) or the User-Agent matches `/API/i`. | Validate a real secret from env with `hash_equals`, or move the public parts under `/api/external` deliberately. |
| S5 | Authenticated remote code execution via image upload | `src/Controller/Api/UserImageController.php:121` and `src/Controller/Api/Map/MapItemImageController.php:196` check only the sniffed MIME type and size. `src/Entity/Document.php:150-153` keeps the client filename (extension included) and line 165 moves it under `public/`. `public/.htaccess` serves existing files directly; `docker_vhost.conf` has `AllowOverride All`; `docker_postscript.sh:11` makes `public/` writable. Any `ROLE_USER` can reach the profile upload. | Generate random server-side filenames with an extension derived from the sniffed type; store uploads outside the web root or deny PHP execution under `public/uploads`; validate with `getimagesize()`. **Needs confirmation** whether the production Apache config behaves like the Docker one. |

### 2.1a The emich.edu 404-page routes

When a visitor requests a bad URL such as `emich.edu/badlink`, emich.edu's server renders its PHP 404 page, and a script on that page calls ICCommand to look up a redirect, count a visit, or log the 404. Five routes serve this:

| Route | What it does | Decision / harm if anyone can call it |
|---|---|---|
| `GET /api/redirects/external/redirect?url=` | returns the redirect target | **Stays fully public** so developers can test it from a browser or Postman. Low harm: emich.edu already performs the redirect for anyone. |
| `GET /api/uncaughts/external/uncaught?url=` | checks whether a 404 URL is already logged | **Stays fully public**, same reason. Low harm. |
| `PUT /api/redirects/external/redirect` | increments visit count, sets last-visit date | Medium. Anyone can inflate usage analytics that staff use to decide which redirects to keep. |
| `POST /api/uncaughts/external/uncaught` | inserts a new 404 row | Medium. Unlimited inserts with no length or dedupe check (S18) can flood the table and the admin suggestions list. |
| `PUT /api/uncaughts/external/uncaught` | increments a 404's visit count | Medium. Same analytics-poisoning risk. |

Note that the paths are `/api/redirects/external/...` and `/api/uncaughts/external/...`, not `/api/external/...`. They are outside the existing `^/api/external` public rule, so the new `^/api` floor needs explicit exceptions for these five paths (or the routes can move under `/api/external`, which requires changing the emich.edu 404 page).

The right protection for the three write routes depends on **where the 404 page's script runs**, which needs confirmation:

**Case 1: JavaScript that runs in the visitor's browser (likely).** The CORS config allows `www.emich.edu`, `webstage`, `wwwtmp` and `wwwcache` origins to send PUT and POST to `/api/`, which only matters for browser calls.

- A shared secret **cannot work**. Anything in the page's JavaScript is visible in view-source.
- CORS is not protection. It stops *other websites'* JavaScript from calling the API in a visitor's browser, but curl, Postman or a script ignore it.
- These write routes are therefore public by nature, like any client-side analytics beacon. The goal is to limit damage, not to block access:
  - **Per-IP rate limiting on the three write routes**, which works in this case because the client IP is the real visitor's. Keep GETs unlimited for developer testing.
  - Configure `trusted_proxies` if a load balancer sits in front of ICCommand; otherwise every visitor shares the proxy's IP.
  - Validate input: cap the URL length, require a path on an emich.edu host, dedupe on `link`.
  - Count at most one visit per IP per URL per time window, so replaying requests cannot inflate counts.
  - Accept that the counts are approximate and say so in the admin UI.

**Case 2: PHP code on emich.edu's server (for example `curl` or `file_get_contents`).** Then only emich.edu's server ever calls ICCommand:

- A shared secret works. Store a random token in both servers' config, send it in a header such as `X-ICCommand-Token`, and check it with `hash_equals()`. Require it on the three write routes only; GETs stay open.
- Optionally allow-list emich.edu's server IPs at the Apache or firewall level for the write routes.
- CORS is irrelevant, and per-IP rate limiting must **not** be applied, because every visitor arrives from emich.edu's one server IP (see S10).
- Roll out in log-only mode first: accept but don't require the token, update emich.edu, then enforce.

**If it is Case 1, consider moving to Case 2.** Having the 404 page's PHP call ICCommand server-side, instead of emitting JavaScript, is a small change on emich.edu. It makes the write routes properly protectable and hides ICCommand's API from visitors.

> **Ops note: CORS in one sentence.** CORS is a rule the *browser* enforces about which websites' JavaScript may read responses from another site; it has no effect on requests from servers, curl or Postman, so it is never an access control on its own.

### 2.2 High

| ID | Finding | Evidence | Fix |
|---|---|---|---|
| S6 | LDAP bind credentials cross the network in cleartext | `config/services.yaml`: `host: ad.emich.edu, port: 389, encryption: none`. Login is a simple bind with the user's AD password. | `encryption: tls` (StartTLS) or `ssl` on 636. Move host to env. |
| S7 | No login throttling | No `login_throttling` on any firewall. Online brute force against AD accounts is possible. | `login_throttling: { max_attempts: 5 }` on `main`. |
| S8 | Disabled accounts still log in on staging and prod | `App\Security\UserChecker` is wired only on the `dev` firewall (`security.yaml:93`). | Add `user_checker` to staging and prod firewalls. |
| S9 | CKEditor HTML is stored and served unsanitized to public emich.edu consumers | No `symfony/html-sanitizer` in `composer.json`, no sanitizer calls in `src`. Affected: emergency banner/notices (`GET /api/emergency/banner`), scholarship text fields (`/api/external/scholarships/*`), program overview, map hours (`/api/external/mapitems`). Also rendered in-app via `v-html`. `npm audit` reports the pinned CKEditor 5 v37 has a known XSS (GHSA-jrqm-vmqc-gm93, fixed in 47.6). | Sanitize on write with `symfony/html-sanitizer` allow-list; DOMPurify on render; upgrade CKEditor (licence review needed, see 3.3). |
| S10 | Rate limiter is effectively dead code, runs twice, and its design depends on how emich.edu calls it | `src/EventSubscriber/RateLimitSubscriber.php:43` uses `in_array($userAgent, [...])` (exact match; a real Googlebot UA such as `Mozilla/5.0 (compatible; Googlebot/2.1; ...)` never matches) and only for the GET redirect lookup. It keys the limit on `getClientIp()`. `config/services.yaml:43` registers the subscriber a second time on top of autoconfiguration, so each request would consume two tokens. | Delete the manual service definition. Then follow 2.1a: in Case 1 (browser script) apply a per-IP limit to the three write routes and set `trusted_proxies`; in Case 2 (server-side PHP) never rate-limit by IP, because every visitor arrives from emich.edu's server, and rely on the shared token instead. In both cases leave the GET routes unlimited so developers can test them. |
| S11 | Anonymous SSRF via redirect validation | `RedirectController.php:241, :348, :373` call `get_headers($fullToLink)` on a caller-supplied URL: arbitrary outbound requests from the server, no timeout, follows redirects, 404-or-not oracle. | After S1, use `HttpClientInterface` with allow-listed hosts, timeout, no redirects, ideally off the request path. |

> **Ops note: trusted proxies.** When the app sits behind a load balancer or reverse proxy, PHP sees the proxy's IP as the client. Symfony only reads the real client IP from `X-Forwarded-For` if `framework.trusted_proxies` names that proxy. Without it, any per-IP rate limit counts every user as one client. **Needs confirmation** of the production topology.

### 2.3 Medium

- **S12 IDOR on profile images.** `UserImageController.php:30-48` takes `user_id` from the body and accepts any HTTP method; `:53-67` deletes any user's image by id. Use `$this->getUser()`.
- **S13 Profile disclosure.** `GET /api/admin/users/{username}` (`ROLE_USER`) returns any user's roles, email, phone, department. `PhotoRequestController.php:177-188` serializes without groups and embeds the assigned User. Password is `#[Ignore]`d everywhere (the earlier "remove password hash" commit is complete).
- **S14 Web Profiler enabled on staging.** `config/packages/web_profiler.yaml:9` (`when@staging`) plus `config/routes/web_profiler.yaml` mount `/_profiler` and the `dev` firewall has `security: false` for it. Anyone reaching staging can browse request bodies, SQL, and session data.
- **S15 Login and logout without CSRF.** `form_login`/`form_login_ldap` lack `enable_csrf: true`; logout has none, so a cross-site `GET /logout` works.
- **S16 API relies on SameSite=Lax alone.** `assets/js/bootstrap.js:39-45` looks for a `csrf-token` meta tag that no template emits, and no API controller validates one. Modern browsers block cross-site POST via Lax; legacy or embedded clients are not covered.
- **S17 No security headers anywhere** (no CSP, `X-Frame-Options`, HSTS, `X-Content-Type-Options`, `Referrer-Policy`) in Apache config, Nelmio, or a subscriber.
- **S18 Unbounded anonymous DB writes.** `POST /api/uncaughts/external/uncaught` inserts a row per request with no validation, dedupe, length cap, or limit. Add a length cap, a host check and dedupe on `link` in every case, since even legitimate traffic includes junk URLs from scanners probing emich.edu. Rate limiting or a token (2.1a) limits who can write.
- **S19 Stray scripts in the web root.** `public/check.php` (requirements checker, exposes php.ini details) and `public/tile.php` (raw PHP outside the kernel building a path from `$_GET`).
- **S20 Bulk-upload result messages are HTML built from CSV values** and rendered with `v-html` (`RedirectController.php:519-524`, `CrimeLogController.php:116`).

### 2.4 Low

- CORS: `nelmio_cors.yaml` nests `dev:`/`prod:` keys under `allow_origin`, which Nelmio treats as a plain list, so both regexes are active in every environment; dots are unescaped; the dev pattern ends `emich.edu*$`. `allow_credentials` is off so impact is limited.
- `config/packages/swiftmailer.yaml` contains commented Mailtrap credentials; `aliases` embeds Homestead DB credentials; `docker-compose.yml` publishes MariaDB on `3306` with `root/secret` (dev only).
- Google Maps key is rendered on every page including `/login` (`templates/base.html.twig:143`). It must be HTTP-referrer restricted in Google Cloud (**needs confirmation**).
- `docker_postscript.sh:23-25` runs `composer self-update` and `composer update` (not `install`) on container start, so dependencies drift from the lock file.
- Role-name drift silently breaks features: `ROLE_MAP_UPLOAD` (`MapItemImageController.php:38`) vs the defined `ROLE_MAP_IMAGE_UPLOAD`; `ROLE_DIRECTORY_*` (`DirectoryService.php:58,64`) vs the defined `ROLE_DEPARTMENTS_*`.
- Password policy: minimum 6 characters, compromised-password check disabled (`validator.yaml`). Only affects dev/test form accounts.
- `DefaultController.php:36` exposes a `/unittest` route in production.

### 2.5 Dependency audit results

| Tool | Result |
|---|---|
| `composer audit --locked` | No advisories. One abandoned package: `composer/package-versions-deprecated`. |
| `npm audit` | 25 moderate, three root causes: `ckeditor5 >=29 <47.6.0` (XSS, GHSA-jrqm-vmqc-gm93), `vue-template-compiler <3` (XSS, GHSA-g3ch-rx76-35fx; this is a Vue 2 package that is not actually used), `uuid <11.1.1` (transitive). |

---

## 3. Build pipeline and dependencies (Goal 1)

### 3.1 Current pipeline facts

- Single entry `assets/js/app.js` → `public/build/` via Webpack Encore 7, Babel 8 with `preset-env` and **no browserslist targets**, so the output is transpiled to ES5 and ships hand-written IE polyfills (`app.js:23-44`) plus `es6-promise`.
- `app.js` mixes 40 CommonJS `require()` calls (38 of them global component registrations of the form `require('./components/X.vue').default`) with ESM `import`. Webpack tolerates this; Vite/Rollup will not.
- `assets/js/bootstrap.js` is a 2018 Laravel template: sets `window._` (lodash, not in `package.json`, never used), `window.Popper`, `window.$`/`window.jQuery`, `window.axios`, and a CSRF lookup that fails on every page load.
- **43 Vue files use the bare global `axios`** with zero `import axios` statements. 9 files use global `$` for Bootstrap 4 modal control. `GoogleMap.vue` uses a bare `google` global from a `<script>` tag.
- Vue is mounted with **in-DOM templates**: 57 Twig files contain `<div id="app">` with components as custom elements and props from server data. This requires the runtime compiler build (`vue$` alias to `vue.esm-bundler.js`) and must be preserved.
- SCSS uses the webpack-only `~` prefix (`assets/css/app.scss:8,10`), deprecated `@import`, and imports all of Bootstrap **three times** (`app.scss`, `_map.scss`, `_override.scss`). `Heading.vue:7` imports the entire `app.scss` into a component style block.
- `templates/base.html.twig` calls `encore_entry_link_tags('app')` twice (lines 7-8) and `encore_entry_script_tags('app')` twice (lines 141-142). Encore's helper de-duplicates, so it is harmless today, but the Vite helper may not. Eight page templates also call the script helper inside the body.
- **Built assets are committed.** `git ls-files public/build` shows 10 tracked files including a 2.37 MB `app.js`. `.gitignore` has both a commented-out and an active `/public/build/` rule; the active one has no effect on already-tracked files. `git log -- public/build` shows 60 commits and at least one merge done only to resolve conflicts in built assets. The Docker image installs no Node, so Docker and production get JS only via these committed files.
- Versioning (`enableVersioning()`) is commented out, so there is no cache-busting today.
- `package-lock.bkp.json` (488 KB) is a tracked snapshot of the pre-Encore-7 tree.
- No `.nvmrc`, ESLint, Prettier, JS test runner, or CI workflow.

### 3.2 Package audit

Live registry check on 2026-09-19. **Bold** = action recommended.

| Package | Installed | Used? | Verdict |
|---|---|---|---|
| `@babel/core`, `@babel/preset-env`, `babel-loader` | 8.0.6 / 10.1.1 | build only, listed under `dependencies` | **Remove with Vite** (esbuild replaces them) |
| `@symfony/webpack-encore`, `webpack`, `webpack-cli`, `webpack-notifier`, `sass-loader`, `vue-loader`, `cssnano` | current | build only | **Remove with Vite** |
| `vue-template-compiler` ^2.7 | 2.7.16 | **No** (Vue 2 compiler) | **Remove** (also clears one npm advisory) |
| `@vue/compiler-sfc` | 3.x | bundled inside `vue` since 3.2.13 | Remove |
| `vue` ^3.2 | 3.4.35 | yes | **Move to `dependencies`**, bump to ^3.5 |
| `jquery` ^3.3 | 3.7.1 | yes (BS4 modals, navbar) | Move to `dependencies` while Bootstrap 4 remains |
| `bootstrap` ^4.6 | 4.6.2 (EOL) | yes | Keep for this migration. **Bootstrap 5 is a separate 1 to 2 week job**: 174 `form-group`, 43 `badge-*`, 29+ `data-toggle`, 28 `data-dismiss`, 16 hand-rolled modals, `twig.yaml` form theme. BS5 would also remove jQuery and Popper 1. |
| `bootstrap-sass` ^3.3 | 3.4.3 | **No** (one commented require, one dead SCSS variable) | **Remove** |
| `popper.js` 1.x | 1.16.1 (EOL) | required by BS4 | Keep until BS5 |
| `@ckeditor/ckeditor5-build-classic` ^37 | 37.1.0 | yes (4 forms) | **Decide separately.** Prebuilt bundles are discontinued; the `ckeditor5` package is at 48.x. From v44 an explicit `licenseKey` (`'GPL'` or commercial) is required at construction. The app is `UNLICENSED`/proprietary, so shipping under GPL terms needs a licence review with EMU. Staying on v37 means no security fixes (see S9). |
| `@ckeditor/ckeditor5-vue` ^4 | 4.0.1 | yes | Pair with the CKEditor decision (v8 needs the new package) |
| `@fortawesome/fontawesome` ^1.1 | 1.1.8 | **No** (abandoned FA5-era core) | **Remove** |
| `@fortawesome/fontawesome-svg-core`, `free-solid-svg-icons`, `vue-fontawesome` | 6.7 / 3.3 | one icon (`faPenToSquare`) in 8 files | Keep or consolidate |
| `font-awesome` 4.7 | 4.7.0 | yes: 129 `fa fa-*` usages in Vue, 12 in Twig | **Two icon systems.** Either drop the four `@fortawesome/*` packages by swapping the one icon to `fa fa-pencil-square-o`, or replace FA4 with `@fortawesome/fontawesome-free` (ships v4 shims). Not both. |
| `ajv` ^8 | 8.20.0 | **No** (Dependabot promoted a transitive dep) | **Remove** |
| `axios` ^1.15 | 1.20.0 | yes (global) | Keep |
| `es6-promise` | 4.2.8 | polyfill | **Remove** with the IE polyfills |
| `load-google-maps-api` | 1.3.3 | **No** (maps loaded via `<script>`) | **Remove** |
| `moment` ^2.29 | 2.30.1 | only in `CalendarEventPicker.vue`, which nothing imports | **Remove** with the dead component |
| `momentjs` ^2.0 | 2.0.0 | **No** (unrelated squatted package, not Moment.js) | **Remove** |
| `vee-validate` ^4.7 | 4.13.2 | yes (8 forms) | Keep, bump to 4.15 |
| `vue-axios` | 2.1.5 | **No** | **Remove** |
| `vue-flatpickr-component` | 11.0.5 | **No** | **Remove** |
| `vue-multiselect` `"next"` | 3.0.0-beta.3 | yes (8 files) | **Re-pin to ^3.5.0**; the `next` tag still points at a 2-year-old beta |
| `vue-router` ^3.6 | 3.6.5 | **No** (multi-page app, no router; v3 is the Vue 2 line anyway) | **Remove** |
| `vue-slicksort` | 1.2.0 | **No** (superseded by vuedraggable) | **Remove** |
| `vuedraggable` ^4.1 | 4.1.0 | yes | Keep the explicit `^4.1.0` (npm `latest` is the Vue 2 build) |
| `yup` 0.32 | 0.32.11 | yes (3 forms) | Optional bump to ^1.7 (small API change) |
| `sass` ^1.77 | 1.77.8 | yes | Keep on 1.x with `quietDeps`; Bootstrap 4 SCSS will break on Dart Sass 3 |
| `lodash` | transitive | `bootstrap.js:4` only, never used | Delete the line |

Composer side: `jms/serializer` (**zero usages**; CLAUDE.md is wrong on this point), `willdurand/hateoas` (one inert attribute on `MapItem`), `composer/package-versions-deprecated` (abandoned), `symfony/web-link`, `nesbot/carbon`, `symfony/process`, `symfony/lock` (unused but `lock.yaml` demands a `LOCK_DSN`), `phpdocumentor/reflection-docblock` (misused as an attribute in `CrimeLogService.php:9`). `symfony/webpack-encore-bundle` goes with the migration.

### 3.3 Frontend code health

- 100% Options API (56/56), zero `<script setup>`. No event bus, no `$root`/`$parent` abuse, props-down/emit-up throughout. That is a decent base.
- **Zero `emits:` declarations** across 36 `$emit` calls (Vue 3 warnings; mechanical fix).
- Vue 1/2 fossils that Vue 3 silently ignores: `filters: {}` in 16 files, `ready()` hook in 5 delete modals, `events: {}` in 8 files, legacy `slot="title"` attribute syntax in 6 files.
- Five largest components: `map/MapItemForm.vue` 1,972 lines, `programs/ProgramForm.vue` 1,201, `scholarship/ScholarshipForm.vue` 1,127, `directory/DepartmentForm.vue` 987, `photorequest/PhotoRequestForm.vue` 827.
- **Duplication.** Seven `*List.vue` components reimplement the same `data()`, `handleSearchInput`, `fetchX` with an identical 403/404/500 switch, `handlePageChanged`, and `window.location.href` navigation. Seven `*Form.vue` components reimplement `fetchX`, `submitX`, `afterSubmitSucceeds`, `markItemDeleted`, `toggleEdit`, `goBack`. Eight delete modals are near-identical. `ScholarshipKeywordsList.vue` and `ScholarshipOrganizationsList.vue` are both exactly 557 lines and were produced with `sed`. Two paginators (`Paginator.vue`, `ExternalPaginator.vue`) coexist. Two modal paradigms coexist (jQuery `.modal()` vs the CAS module's state-driven `v-if`).
- Dead files: `assets/app.js`, `assets/styles/app.css` (Flex recipe leftovers), `assets/js/utils/timeSlots.js`, `assets/js/components/utils/CalendarEventPicker.vue`, three tracked `.DS_Store` files.
- Bugs noticed in passing: `DepartmentList.vue:266-301` clears the loading flag synchronously so the spinner never shows; `.catch` reads `error.response.status` unguarded (network error → TypeError); `userCanCreate`/`userCanEdit` are identical. The same shapes are copied into the other lists.

### 3.4 Vite migration outline

**Recommended stack:** `vite` + `@vitejs/plugin-vue` + `vite-plugin-symfony` (npm) with `pentatrion/vite-bundle` (Composer, v8.2.4 supports Symfony 8 / PHP 8.5). The bundle is a like-for-like replacement for `webpack_encore.yaml`: it reads Vite's manifest, provides `vite_entry_link_tags`/`vite_entry_script_tags`, and switches to the dev server automatically.

> **Ops note: manifest and dev server.** A production Vite build writes hashed files (`app.abc123.js`) plus a `manifest.json` mapping entry names to those files. Twig asks the bundle for the current filename, so cache-busting is free. In development, the bundle instead emits `<script type="module" src="http://localhost:5173/...">`, and the **browser** talks to the Vite dev server directly for hot module replacement (HMR). Apache does not need to proxy anything; it only needs to read the small `entrypoints.json` file that Vite writes into `public/build`, which works through the existing bind mount.

Steps:

1. **Hygiene first (independent of Vite, ~0.5 day):** remove the 11 unused/duplicate packages, delete `package-lock.bkp.json`, dead assets and `.DS_Store` files, move `vue`/`jquery` to `dependencies`, re-pin `vue-multiselect`, drop the `lodash` line, remove the Vue 1/2 fossils, add `emits:` declarations, add `.nvmrc`.
2. **Swap (~1 to 1.5 days):** `composer remove symfony/webpack-encore-bundle && composer require pentatrion/vite-bundle`; delete `webpack.config.js`, `.babelrc`, `config/packages/webpack_encore.yaml`, the `WebpackEncoreBundle` line in `bundles.php`, and `framework.assets.json_manifest_path`; write `vite.config.js` (vue plugin, `vue` alias to `vue.esm-bundler.js`, `@` alias, `build.outDir: 'public/build'`, `manifest: true`, single `app` input, `scss.quietDeps`, `__VUE_PROD_DEVTOOLS__: 'false'`); rewrite `app.js` to static imports; split `bootstrap.js` into a `globals.js` (jQuery/Popper on `window`) that is imported *before* `import 'bootstrap'` (ESM import hoisting otherwise breaks Bootstrap 4's UMD wrapper); strip the `~` prefix and the triple Bootstrap import from SCSS; fix `Heading.vue` to import only variables; replace the four Twig helper calls with one link and one script call.
3. **Verify (~1 day):** click through the 57 mount pages: navbar dropdowns and collapse (jQuery load order), 16 modals, CKEditor in 4 forms, Google map in `MapItemForm`, image uploads, vee-validate forms, FA4 fonts from hashed paths.
4. **Decide on `public/build` (~0.5 day + ops):** hashed filenames make committed builds change on every build, so conflicts get worse. Recommended: untrack `public/build`, add a Node build stage to the `Dockerfile` (or a deploy-pipeline step). Fallback if the deploy cannot run Node: pin stable output names in `rollupOptions.output`, which keeps today's no-cache-busting behaviour.

Do not add `@vitejs/plugin-legacy` unless there is a documented old-browser requirement; this is an internal staff app and legacy mode doubles output and reintroduces Babel.

Separate, later decisions: Bootstrap 4→5 (1 to 2 weeks, removes jQuery), icon-system consolidation, CKEditor ≥44 (needs a licence decision).

---

## 4. Symfony best practices and design (Goal 2)

### 4.1 Authorization model

Covered in section 2. The structural fix is a `^/api` catch-all in `access_control` plus centralised role constants so the `ROLE_MAP_UPLOAD`/`ROLE_DIRECTORY_*` drift cannot happen. Every `IsGranted` expression is a copy-pasted string; only the Scholarship keyword/organization controllers extract them to constants.

### 4.2 Fat controllers (High)

API controller sizes: `ProgramsController` 686 lines, `MapItemController` 678, `RedirectController` 611, `CasController` 457, `ScholarshipController` 393, `PhotoRequestController` 346, `CrimeLogController` 337.

- **Business logic and persistence live in actions.** `MapItemController::postMapitemAction` (:170) and `putMapitemAction` (:395) are ~220 and ~265 lines: the type switch, child-collection reconciliation, per-child validation and `persist()`/`flush()` all inline. Every API controller injects `ManagerRegistry` and/or `EntityManagerInterface` and calls `find/persist/remove/flush` directly. `CasController` runs raw SQL against the `programs` connection inside an action.
- **Manual hydration.** Field-to-setter cascades are written by hand and duplicated between the POST and PUT actions of the same controller (Programs, PhotoRequest, Directory, Scholarship). Several actions `json_decode($request->getContent())` a second time even though `JsonRequestSubscriber` already decoded it (because `ParameterBag::get()` rejects arrays).
- **Hand-rolled validation and blocking I/O.** `RedirectController` calls `get_headers()` on user URLs inside the request (S11). Required-field, duplicate and id-existence checks are re-implemented per controller.
- **~110 occurrences** of `new Response($this->serializer->serialize(...), N, ['Content-Type' => 'application/json'])`. No `JsonResponse`, no `$this->json()`.
- **Five CSV importers** (`RedirectController`, `CrimeLogController`, `ProgramsController`, `CasController`, `ScholarshipKeywordController`) each re-implement `file()/str_getcsv/array_combine`, batching, `em->clear()`, and then return an HTML `<ul>` under `Content-Type: application/json`.
- **URL normalisation is copied four times** inside `RedirectController` and once more in `ProgramsService`.
- Dead or unrouted: `RedirectController::putRedirectsAction` (:390, no `#[Route]`, flushes in a loop), `EmailController` (no actions), `DefaultController::unitTest`.

**Direction:** per-module handlers or application services taking DTOs bound with `#[MapRequestPayload]`; controllers become 5 to 10 line adapters using `$this->json()`; one shared CSV importer and one shared list/search/paginate helper.

### 4.3 Service layer (High)

- Services are mostly pass-throughs: each has (a) a `validate()` wrapper around `ValidatorInterface` (10 identical copies), (b) a copy-pasted `getXPermissions()` role-to-bool map (10 copies), and (c) one-line `getRepository()->method()` delegations. The real rules live in the controllers.
- `ProgramsService` (825 lines) mixes permissions, pagination, a hand-built SQL `WHERE` string assembler for the public degree search, slug generation, URL normalisation, CSV import and pivot updates. `ScholarshipService` (582) mixes DQL, raw SQL on the `programs` connection, link syncing and two importers.
- `flush()` inside loops in `ScholarshipService`, `MapItemService::deleteMapItem` (called per item from `mapItemCollectionCompare`), and `RedirectController::putRedirectsAction`. Multi-step writes have no transaction: `ProgramsController::postProgramAction` flushes the program then runs four pivot updates in separate transactions; a mid-way failure leaves a half-saved program and returns 500.
- `EmergencyService::updateBanner` catches `\Exception` and returns `['success' => false]`; hard-codes `setUpdatedBy(1)`/`setCreatedBy(1)` with TODOs (lines 133, 205, 209). `getNotices()` is a stub returning `[]`.
- `CrimeLogService.php:9` imports `phpDocumentor\Reflection\PseudoTypes\ArrayShape` and uses it as an attribute; three other services import `JetBrains\PhpStorm\ArrayShape`, which is not installed. Harmless at runtime, misleading to readers.
- Unused: `UserService` (empty class), `MapItemService::mapBuildingTypeCompare`, `RedirectService::deleteRedirect`, `DirectoryService::deleteDepartment`, `SocialMediaService::deleteSocialMedia`. `DirectoryService::getDepartmentsByName` runs the same query twice.

### 4.4 Dependency injection and configuration (Medium)

- Constructor injection is used everywhere; no service locators or `$this->get()`. Good.
- 17 classes inject both `ManagerRegistry` and `EntityManagerInterface`, and resolve repositories at call time via `getRepository()`. Injecting the concrete `*Repository` services (already autowirable) is the single change that makes services mockable (section 5).
- `config/services.yaml:43` registers `ratelimit_subscriber` manually on top of autoconfiguration; the `kernel.request` tag with `connection: default` is not a real tag. Result: the subscriber runs twice per request (verified via `debug:event-dispatcher`).
- `config/services.yaml:50` injects `@monolog.logger.crime_log`, but the `crime_log` channel is declared only in the dev/staging/prod monolog files, so the `test` container cannot compile. Use `#[WithMonologChannel]` and declare the channel in the base file.
- `AbstractController::getParameter()` hides dependencies for upload directories and the role hierarchy; prefer `#[Autowire('%param%')]`.
- Routing is defined twice: `config/routes/attributes.yaml` imports all of `src/Controller/` and `config/routes.yaml` re-imports each API controller with a prefix. The prefixed versions win only because they load last and the route names collide. Several attributes lack a leading slash and depend on the prefix's trailing slash. Put `#[Route('/api/...')]` on the classes and delete the `routes.yaml` entries.
- `twig.yaml:5` sets `cache: false`, disabling compiled-template caching in production. `bundles.php` enables `WebProfilerBundle` and `DebugBundle` in staging (S14).
- `nelmio_cors.yaml` nests env names inside a list (section 2.4); use `when@prod`.
- `platform-check: false` in `composer.json` hides the missing `ext-ldap`/`ext-gd`/`pdo_mysql` declarations; `composer install` fails on a machine without `ext-ldap` unless overridden.

### 4.5 Doctrine (High)

- **Entities are mapped in two entity managers.** `config/packages/doctrine.yaml` maps `dir: src/Entity` for the default EM with no `exclude`, and then maps `Programs/` and `CrimeLog/` again for the `programs` and `dps` EMs. `doctrine:mapping:info --em=default` reports 42 entities including all Programs and CrimeLog classes. CLAUDE.md's statement that default excludes them is wrong. Consequences: every repository for those entities overrides `getEntityManager()` to pick the right EM by hand; `getManagerForClass()` returns the wrong EM; `doctrine:schema:create` on the default EM tries to create `dps.dailylog`; the `programs` connection is now a pure duplicate of `default` (the yaml comment admits its env vars are unused). Fix: `exclude` those directories from the default mapping, or retire the `programs` EM entirely now that the schema is shared.
- **Mapping is invalid.** `doctrine:schema:validate --skip-sync` fails on the default EM (4 errors: `ProgramKeywordLinks#keyword` inverse side does not exist; `MapExhibit#building` inverse targets `MapBuilding#emergencyDevices`; `MapBuilding#directoryDepartments` and `MapParkingType#parkingLots` lack `inversedBy`) and on the programs EM (1). Any CI step that builds the schema will fail until these are fixed.
- **Migrations do not manage the schema.** `doctrine_migrations.yaml` points at `src/Migrations`; the root `migrations/` directory is empty. Only 10 tables have `CREATE TABLE IF NOT EXISTS` in any migration; the other ~34 (`map_*`, `program_*`, `redirect`, `photo_request`, `emergency_*`, ...) exist only as ORM metadata. Migrations are hand-written idempotent patches that query `information_schema` inside `up()`; one is entirely commented out. `em: default` means the `dps` schema is never migrated.
- `MapItem` uses JOINED inheritance with 10 subclasses, so every `findBy` on `MapItem` joins 10 tables; the public `/api/external/mapitems` feed then lazy-loads six child collections per building (N+1). Consider SINGLE_TABLE or a DTO query for the public feed.
- `src/Entity/Document.php` does filesystem moves and unlinks in lifecycle callbacks and derives the upload root from `__DIR__ . '/../../public/'`. `setPath()` is a no-op (`$path = $path;`). `MapItem` and `Document` declare `Timestampable(on: change, field: [title, body])` for fields that do not exist.
- 132 untyped entity properties, 144 methods without return types, 196 `array(` literals. Enum candidates: `Redirect.itemType` (six magic strings compared 12+ times), `PhotoRequest.status`, `MapItem` type strings.
- `ProgramsRepository` is ~90% raw SQL with `LIMIT $offset, $pageSize` interpolated from uncast query params (parameters elsewhere are bound, so injection risk is low, but a non-integer page value is a 500). Repositories return arrays or entities inconsistently.
- `ProgramKeywordLinks` declares `repositoryClass: ProgramKeywordsRepository`, whose constructor binds to a different entity.

### 4.6 Serialization, validation, HTTP contract (Medium)

- JMS Serializer is in `composer.json` and CLAUDE.md but has zero usages. All 21 API controllers use the Symfony Serializer with `#[Groups]`. Groups are ad hoc and often omitted (S13).
- `JsonRequestSubscriber` replaces `request->request` with the decoded JSON body. 147 `$request->request->get()` calls depend on it; 11 calls bypass it. `#[MapRequestPayload]` DTOs would replace both paths and give validation for free.
- Validator is applied to entities after manual hydration. Entity constraints are uneven: `CrimeLog` is fully annotated, `Department` and `Programs` have none.
- Response bodies are inconsistent: JSON-encoded strings, plain text under a JSON content type, HTML fragments, `ConstraintViolationList` dumps for 422, ad hoc `{error: ...}` objects. PUT returns 201 in seven controllers and 200 in two; DELETE returns 204 *with* a body or 200; bulk uploads return 201 on total failure; missing null checks before `remove()` produce 500 instead of 404. Direction: one response factory plus a `kernel.exception` listener that emits JSON problem details.

### 4.7 Dead code and repository hygiene (Medium)

Delete list (all verified unused or leftover): root files `User`, `UserImage` (0 bytes), `notes.txt`, `aliases` (Homestead), `after.sh`, `package-lock.bkp.json`; a `CREATE TABLE programs.program_programs` DDL statement pasted at the end of `.gitignore`; `.claude/settings.json` committing a developer's absolute `/Users/...` paths; `config/packages/swiftmailer.yaml`, `test/swiftmailer.yaml` (breaks the test container), `test/framework.yaml` (removed `storage_id` option), `ldap_tools.yaml`, `twig_extensions.yaml`, duplicate `imagine.yaml`/`liip_imagine.yaml` and `routes/imagine.yaml`/`routes/liip_imagine.yaml`; the unused Postgres `database` service in `docker-compose.yml`; `public/check.php`, `public/tile.php`; `src/Entity/Group.php` (namespace only), `src/Service/UserService.php`, `src/Controller/Api/EmailController.php`, `src/Repository/MapDispenserRepository.php` (references a non-existent entity; duplicate of `Repository/Map/MapDispenserRepository.php`); `mailer.yaml` still uses the SwiftMailer-era `MAILER_URL` name.

---

## 5. Testability (Goal 3)

### 5.1 Backend: current state

Seven test classes, 14 methods, all `WebTestCase` against a live pre-populated MariaDB. Five legacy tests use the removed `session` service and hand-rolled tokens and can never pass. The two SocialMedia tests are modern (`loginUser`, `getContainer()`) but require a pre-seeded real user and clean up by name prefix. Zero unit tests for any service, repository, or subscriber. Zero coverage of Programs, Scholarship, Redirect, CrimeLog, Directory, Emergency, Cas, and uploads.

**`php bin/phpunit` cannot run.** Verified blockers, in order encountered:

1. `bin/phpunit` hard-codes `SYMFONY_PHPUNIT_VERSION=6.5`; `phpunit/phpunit` is not in the lock. The bridge's `simple-phpunit` caps at PHPUnit 9.
2. `config/packages/test/swiftmailer.yaml` configures a bundle that is not installed: `There is no extension able to load the configuration for "swiftmailer"`.
3. `config/packages/test/framework.yaml` uses `session.storage_id`, removed in Symfony 6. The root `framework.yaml` already has a correct `when@test` block, so the file is redundant.
4. `monolog.logger.crime_log` does not exist in the test env.
5. Four legacy tests declare `setUp()` without `: void` (fatal under PHPUnit 9+).
6. `.env.test` is empty and `.env` is git-ignored, so no database is configured. Two tests import non-existent entity classes.

With blockers 2 to 4 patched in a throwaway copy, the test kernel boots and `lint:container` passes. With 5 patched, 13 tests run: 12 errors (removed session service, no DB), 1 failure.

### 5.2 Backend: what blocks unit tests per service

Good news: no service takes a `Request`, reaches into the container, calls LDAP/HTTP/mailer, or uses `date()`/filesystem. All resolve repositories through `ManagerRegistry::getRepository()` at call time, which is the friction.

| Service | Lines | Rating today | What makes it easy |
|---|---|---|---|
| MapItemService | 184 | Easy | inject repositories; 5-role permission matrix is a pure data-provider test |
| RedirectService | 109 | Easy | inject repository |
| DirectoryService | 173 | Easy | `normalizeSearchTerms()` is pure |
| SocialMediaService | 103 | Easy | inject repository |
| PhotoRequestService | 158 | Easy | inject repository |
| CrimeLogService | 101 | Easy | inject the dps repositories explicitly |
| CasService | 87 | Easy/Medium | move one raw SQL call into a repository |
| EmergencyService | 218 | Medium | stop swallowing `\Exception`; inject `Security` for the hard-coded user id 1; extract `handleEmergencyNotices()` |
| ProgramsService | 825 | Medium | extract a `DegreeSearchCriteria` value object so the WHERE/binds/ORDER BY of the public search can be asserted without a DB; the slug, catalog-id, URL-stripping and id-normaliser helpers are pure today |
| ScholarshipService | 582 | Medium | move 7 inline SQL/DQL calls into repositories; then the three `sync*Links` reconciliations and bulk importers are testable with an EM mock |

Cross-cutting enablers: inject concrete repositories; inject `ClockInterface` (`symfony/clock` is already in the lock) where `new \DateTime('today')` decides behaviour (`ScholarshipRepository`, `ScholarshipExternalController::hasExpired`, `RateLimitSubscriber`); replace `Document` lifecycle callbacks with a `FileUploader` service so upload flows do not write into `public/uploads`; move controller logic (CSV parsing, `new \DateTime($request->...)` ×6 in PhotoRequest) into services.

### 5.3 Backend: recommended strategy

**Database choice: one MariaDB with transaction rollback, not SQLite.** Verified MySQL-only SQL in `ProgramsRepository` (20 raw calls), `ProgramKeywordsRepository` (8), `ScholarshipService` (7), the CrimeLog repositories and Cas: `GROUP_CONCAT ... SEPARATOR`, `IF(...)`, comma-form `LIMIT`, `TRUNCATE`, backtick quoting, and the `schema: 'dps'` table attribute, which SQLite cannot create at all.

> **Ops note: transactional tests.** `dama/doctrine-test-bundle` opens a database transaction before each test and rolls it back afterwards, so tests never see each other's rows and the database never needs cleaning. It supports the three connections here. CI then needs only a throwaway MariaDB container with the schema created once.

Step 0, one PR, prerequisite for everything else:

1. `composer require --dev phpunit/phpunit:^11`; keep `symfony/phpunit-bridge` for deprecation reporting; point `bin/phpunit` at `vendor/bin/phpunit`; regenerate `phpunit.xml.dist` with `--migrate-configuration`.
2. Delete `config/packages/test/swiftmailer.yaml` and `test/framework.yaml`; declare the `crime_log` channel in the base `monolog.yaml`.
3. Fix or delete the five legacy tests; fix the two wrong imports.
4. Commit a real `.env.test` and add `server_version` to all three connections so schema tools do not need a live connection at compile time.
5. Fix the five `doctrine:schema:validate` mapping errors.
6. Add `dama/doctrine-test-bundle`, `zenstruck/foundry` + `fakerphp/faker` for factories.
7. Add `.github/workflows/ci.yml`: MariaDB 11 service, `shivammathur/setup-php` with `ldap, gd, pdo_mysql`, `composer install`, `doctrine:database:create` for `ic_test` and `dps`, `doctrine:schema:create --em=default` and `--em=dps`, `phpunit --coverage-clover`, and a `phpstan` job.

Pyramid, in order of introduction:

1. Pure unit tests (`TestCase`, no kernel): permission matrices of all 10 services, `DirectoryService::normalizeSearchTerms`, the `ProgramsService` helpers and search-criteria builder, `ScholarshipService` sync logic, `JsonRequestSubscriber`, `RateLimitSubscriber` with a `RateLimiterFactory` mock (this test would have caught the exact-match UA bug), `UserChecker`.
2. Repository integration tests (`KernelTestCase` + dama): `ScholarshipRepository::searchPublicScholarships` (146 lines of criteria, changed six times recently, date-sensitive), `ProgramsRepository::searchDegreePrograms`, `RedirectRepository` pagination/search, `UserRepository::findByLikeRole`.
3. `WebTestCase` smoke tests, one class per API controller: anonymous → 302/401, wrong role → 403, right role → 200 + JSON shape. **Start with the 20 currently unguarded routes so the security fix is regression-guarded.**
4. Upload flows last, after the `FileUploader` refactor.

Static analysis: `phpstan` + `phpstan-symfony` + `phpstan-doctrine` at level 5 with a baseline, raising over time; `php-cs-fixer` `@Symfony` (the repo mixes tabs, 2-space and 4-space indentation).

Module order by churn and risk (from `git log --name-only`): Programs → Scholarships → Redirects/Uncaught/CrimeLog (auth tests) → Admin/UserController (negative tests) → SocialMedia/Cas (repair existing) → Map/PhotoRequest/Directory/Emergency.

### 5.4 Frontend: current state and strategy

No test runner, no lint, no CI. What stops mounting a component in a test today:

1. Bare globals: `axios` (43 files), `$` (9 files), `google` (1 file) → `ReferenceError` under `@vue/test-utils`.
2. `font-awesome-icon` and `ckeditor` are registered only on the app instance → need `global.stubs`.
3. jQuery modal calls need a real jQuery + Bootstrap + DOM.
4. `window.location.href` assignments in 27 places.
5. CKEditor build touches `window` at import; Google Maps constructed in `mounted`.
6. `Heading.vue` imports all of `app.scss`, so any test mounting it compiles Bootstrap unless `css: false`.

Setup: `vitest` + `@vue/test-utils` + `happy-dom`, `css: false`, a `tests/js/setup.js` stubbing `font-awesome-icon`, `ckeditor`, `google`, and `$` until the refactors land; `vi.mock('axios')` or `msw` for HTTP. Add `"test": "vitest"` and ESLint with `eslint-plugin-vue` (catches the `filters`/`ready`/`slot=` fossils automatically).

Prerequisite refactors, by payoff:

1. `assets/js/http.js` exporting the configured axios instance; replace bare `axios` with an import (mechanical, 43 files; keep `window.axios` temporarily).
2. Replace the 22 jQuery modal lines with state-driven modals (the CAS module already does this) or a 20-line `useBootstrapModal()` wrapper.
3. Extract `useListPage(endpoint)` / `useResourceForm(endpoint)` composables and a single `ConfirmDeleteModal` and `Paginator`, so tests target one implementation instead of seven copies. This removes several thousand lines.
4. Add `emits:` declarations so `wrapper.emitted()` assertions are meaningful.
5. A tiny `navigate()` wrapper around `window.location.href`.

---

## 6. Roadmap options

All options start with the same **Phase 0: security hotfix** because S1 to S5 are exploitable today and their fixes are small, local, and independent of everything else. Estimates are developer-days for one experienced Symfony/Vue developer and should be treated as ±50%.

### Phase 0 (all options): security hotfix — 3 to 5 days

- Add `IsGranted` to the 10 Redirect/Uncaught admin routes and the CrimeLog upload; add the `^/api` catch-all `access_control` floor with explicit exceptions for the five emich.edu 404-page routes.
- Protect the three emich.edu write routes per 2.1a: rate limit and validate if the 404 script runs in the browser, or add a shared token (log-only first) if it runs in PHP on the server. Keep the two GETs public.
- Split the user PUT into self-service and admin endpoints; allow-list grantable roles.
- Remove the `X-API-Key`/User-Agent bypass.
- Random upload filenames with sniffed-type extensions; deny PHP execution under `public/uploads`.
- Wire `UserChecker` on staging/prod; `login_throttling`; login/logout CSRF; LDAP StartTLS (needs an AD-side check that 389+StartTLS or 636 is offered).
- Disable the profiler on staging; delete `public/check.php` and `tile.php`; add basic security headers.
- Fix the rate limiter: remove the duplicate registration and apply it per 2.1a.
- Sanitize CKEditor HTML on write with `symfony/html-sanitizer`.

Each item is a candidate for its own small PR. Without a working test suite these ship on manual verification, which is acceptable for guards this simple; the regression tests come in the next phase.

### Option A: Security, then foundations, then incremental refactor (recommended)

| Phase | Content | Effort |
|---|---|---|
| 0 | Security hotfix (above) | 3 to 5 d |
| 1 | Test harness and CI (section 5.3 step 0), plus auth smoke tests for the 20 formerly unguarded routes and negative tests for role assignment | 4 to 6 d |
| 2 | Hygiene PR: dead files, dead Composer/npm packages, duplicate configs, Doctrine mapping fixes, default-EM `exclude`, routing cleanup, `.env.test`, CLAUDE.md corrections | 3 to 4 d |
| 3 | Vite migration (section 3.4) including the `public/build` decision | 3 to 4 d |
| 4 | Backend refactor, one module at a time, tests first: inject repositories, DTOs with `MapRequestPayload`, handlers, shared CSV importer, response factory + exception listener, transactions. Order: Programs, Scholarships, Redirects, then the rest | 15 to 20 d |
| 5 | Frontend refactor: `http.js`, state-driven modals, `useListPage`/`useResourceForm`, single paginator and delete modal, Vitest coverage of the composables and one list/form per module | 8 to 12 d |
| Later | Bootstrap 5 (removes jQuery), CKEditor ≥44 licence decision, Composition API migration, SINGLE_TABLE or DTO feed for the map | separate |

Total: roughly **36 to 51 days**. Every phase leaves `master` deployable. Risk is low because each phase is independently reviewable and the test suite exists before the big refactors.

### Option B: Foundations first, security fixes ride on tests

Same phases as A but Phase 1 (test harness) comes before Phase 0, and each security fix lands with its regression test.

- Pro: every security change is proven by a failing-then-passing test; no manual verification.
- Con: the anonymous DELETE/TRUNCATE/role-escalation routes stay open for another one to two weeks while the harness is built. Given that the fixes are one-line attributes, this trade is not worth it. Reasonable only if a WAF or network rule can block `/api/redirects`, `/api/uncaughts`, and `/api/crimelog/upload` from outside in the meantime.

Total: same as A, **36 to 51 days**.

### Option C: Parallel full modernization

Phase 0 first, then three workstreams in parallel: (1) test harness + backend refactor, (2) Vite + Bootstrap 5 + Composition API + frontend composables, (3) Doctrine cleanup (retire the `programs` EM, baseline migration, SINGLE_TABLE map, enums, typed properties).

- Pro: reaches the end state fastest in calendar time, about 5 to 6 weeks with two or three developers.
- Con: three streams touch the same controllers, templates and entities; the Bootstrap 5 rewrite alone touches every Vue and Twig file, so merge conflicts and regressions are near-certain without a test suite in place first. The committed `public/build` directory makes parallel frontend branches especially painful until Phase 3 of Option A has landed.
- Total: roughly **45 to 60 days** of effort, compressed into fewer weeks, with materially higher risk. Only sensible if there is a team of two or more and a hard deadline.

### Comparison

| | A: Incremental | B: Foundations first | C: Parallel |
|---|---|---|---|
| Time until S1 to S5 are closed | days | 1 to 2 weeks | days |
| Total effort | 36 to 51 d | 36 to 51 d | 45 to 60 d |
| Calendar time (1 dev) | 8 to 10 wk | 8 to 10 wk | n/a |
| Calendar time (2 to 3 devs) | 5 to 6 wk | 5 to 6 wk | 4 to 5 wk |
| Regression risk | low | low | high |
| `master` always deployable | yes | yes | not guaranteed |

**Recommendation: Option A.** Ship the security fixes this week as small PRs, then build the test harness, then do the hygiene PR (which is where most of the "outdated packages" goal is met), then Vite, then refactor module by module with tests leading.

### Decisions needed from the team before starting

1. **`public/build`:** untrack and build in the deploy pipeline (needs Node on the build host or a Docker build stage), or keep committing with stable filenames.
2. **CKEditor licence:** stay on v37 with a known XSS, or move to ≥44 under GPL terms or a commercial key.
3. **LDAP transport:** confirm AD offers StartTLS on 389 or LDAPS on 636.
4. **Production web server:** confirm whether the upload directory can execute PHP (S5) and whether a reverse proxy sits in front (trusted proxies).
5. **`programs` entity manager:** retire it now that it points at the same database, or keep it and add the `exclude`.
6. **Bootstrap 5 and Composition API:** in scope for this effort or deferred.
7. **emich.edu 404 page:** does its script call ICCommand from the visitor's browser (JavaScript) or from emich.edu's server (PHP)? Who owns that code, and could it move server-side? This decides how the three write routes are protected (2.1a).

---

## Appendix A: API routes with no `#[IsGranted]` (verified against `debug:router`)

Under `/api/external/*` and intentionally public (10): Cas, Programs, Scholarship, SocialMedia externals, `/api/external/mapitems`.

Not under `/api/external` and therefore anonymous today (20):

- `RedirectController`, called by the emich.edu 404 page (GET stays public; PUT needs rate limiting or a token, not a login): `GET|PUT /api/redirects/external/redirect`
- `RedirectController`, called by the ICCommand UI (need `IsGranted`): `DELETE /api/redirects/{id}`, `GET /api/redirects/list`, `GET /api/redirects/search`, `GET /api/redirects/{id}`, `POST /api/redirects/`, `PUT /api/redirects/`, `POST /api/redirects/upload`
- `UncaughtController`, called by the emich.edu 404 page (GET stays public): `GET|POST|PUT /api/uncaughts/external/uncaught`
- `UncaughtController`, called by the ICCommand UI: `DELETE /api/uncaughts/{id}`, `GET /api/uncaughts/`, `PUT /api/uncaughts/`
- `POST /api/crimelog/upload`
- `POST /api/photorequests/` (create; header bypass)
- `GET /api/directory/search` (header bypass), `GET /api/directory/bldgnames`
- `GET /api/emergency/banner` (public consumer; should live under `/api/external`)
- `GET /api/programs/keywords` (guard commented out)

## Appendix B: Environment variables the config resolves (none documented today)

`APP_SECRET`, `DATABASE_HOST|PORT|NAME|USER|PASSWORD`, `DATABASE_HOST|PORT|NAME|USER|PASSWORD_DPS`, `MAILER_URL`, `LOCK_DSN`, `GOOGLE_MAPS_API_KEY`, `LDAP_USER`, `LDAP_PASSWORD`, `CORS_ALLOW_ORIGIN`, `VAR_DUMPER_SERVER` (dev). `.env` is git-ignored and absent, `.env.test` is empty, and `bin/console` fails without a `.env` file. Commit a `.env` with safe defaults per the Symfony Flex convention.

## Appendix C: Documentation drift in `CLAUDE.md`

- "Serialization uses JMS Serializer": the Symfony Serializer is used everywhere; JMS has zero usages.
- "`default` connection excludes Programs, CrimeLog": it does not; all entities are mapped in the default EM.
- "Dev allows `*.emich.edu`; production restricts": both CORS regexes are active in every environment.
- "`php bin/phpunit` runs all tests": the runner cannot start.
- "Frontend HTTP requests use Axios with CSRF token configured in `bootstrap.js`": the token lookup fails because no template emits the meta tag, and the API does not check it.
