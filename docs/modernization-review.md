# ICCommand Modernization Review

**Date:** 2026-09-19, revised 2026-09-23 (final technical-debt audit added as section 7; production facts confirmed by the team) · **Branch reviewed:** `master` at `ef081e5` · **Status:** planning document, no code changed

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
| S1 | The 10 admin routes of the Redirect and Uncaught-URL APIs (incl. DELETE, PUT, CSV upload) have no authorization at all. The 3 write routes emich.edu's PHP 404 page uses to count visits and log bad links are open to anyone, not just emich.edu's server. | Anonymous |
| S2 | `POST /api/crimelog/upload` truncates the public Daily Crime Log table and has no authorization | Anonymous |
| S3 | `PUT /api/admin/users/{username}` lets any logged-in user set anyone's roles (including `ROLE_GLOBAL_ADMIN_SUPER`) and enabled flag | Any user |
| S4 | Photo request creation and directory search skip auth when *any* `X-API-Key` header is present or the User-Agent contains "API" (the key is never validated) | Anonymous |
| S5 | Image uploads keep the client filename and extension and store it under the web root; a `x.php` file starting with `GIF89a` passes the MIME check and is executable under the Docker Apache config | Any user (**needs confirmation** for the production web server) |

These are one-line to one-day fixes and should ship before any refactoring or build-tool work.

**The emich.edu 404 page needs a coordinated fix.** Its PHP calls five ICCommand routes server-side. The two lookups stay public. The three write routes need a shared token that emich.edu sends. The review also found that emich.edu's 404-visit counter had never worked because it called a URL that does not exist; this was fixed on emich.edu on 2026-09-23. An ICCommand outage can still hang every emich.edu 404 page, and each lookup is requested twice. The remaining changes ship as one small coordinated change (Phase 0a).

**The final audit (section 7) found one more High security issue and two High bugs.** An authenticated SQL injection exists in the Programs list endpoints (S21). An expired session makes every API save report success while silently saving nothing. The redirect visit counter may be failing on every call because of a Blameable listener. It also found that **production is serving a 15.7 MB development build of the frontend today**, because production deploys by pulling `master` and `master` has a dev build committed. Production error pages render blank, and staging runs in debug mode.

**The test suite cannot run.** Four independent blockers (PHPUnit 6-era runner, a config file for an uninstalled bundle, a removed framework option, a missing log channel in the test env) mean `php bin/phpunit` fails before any test executes. The existing tests also depend on a live production-like database.

**The Vite migration is moderate in size (about 3 to 4 days) and mostly mechanical**, but it forces a decision about the committed `public/build` directory, which already causes merge conflicts.

**The backend has a consistent but unhealthy shape**: controllers of 300 to 700 lines hold the business logic, services are thin permission-map and validate wrappers, and the same list/search/CRUD/CSV-import code is copied across every module. The frontend mirrors this: seven list components and seven form components reimplement the same fetch/paginate/submit/delete pattern, and two 557-line components differ only by a word.

Rough total effort to reach all four goals: **8 to 12 developer-weeks** (section 7 adds about a week of small fixes) depending on the option chosen (section 6).

---

## 1. Scope and method

- Codebase: Symfony 8.1 / PHP 8.5 (the Docker image uses `php:8.5-apache`; the review sandbox had PHP 8.4), Doctrine ORM 3.7, Vue 3.4 via Webpack Encore 7, 11 sub-applications, ~19k lines of PHP under `src/`, ~19.6k lines across 56 Vue single-file components.
- Four parallel reviews were run (frontend/build, Symfony practices, backend testability, security), then cross-checked by hand against the source.
- Commands run and their outcomes: `npm outdated`, `npm audit`, `composer audit --locked`, `composer show --locked --outdated`, `composer install` (with platform overrides), `bin/console about|lint:container|debug:router|debug:config security|doctrine:schema:validate|doctrine:mapping:info` against a throwaway copy, and `bin/phpunit` under three PHPUnit versions.
- Nothing was changed in tracked files. `vendor/` was installed locally for verification and is git-ignored.

---

## 2. Security findings (Goal 4)

> **Ops note: why unguarded `/api` routes are anonymous.** `config/packages/security.yaml` lists `access_control` rules only for page prefixes (`^/map`, `^/admin`, ...) plus `^/api/external` as public. There is no rule for `^/api`. Every environment's `main` firewall is `lazy: true`, which means Symfony does not force a login until something *asks* whether the user is authenticated. If a controller has no `#[IsGranted]`, nothing asks, and the request is served to anyone. Confirmed by running anonymous requests through the test kernel: `/api/redirects/list`, `/api/uncaughts/`, and `/api/crimelog/upload` all reached their controllers.

### 2.1 Critical

| ID | Finding | Evidence | Fix |
|---|---|---|---|
| S1 | Redirect and Uncaught-URL APIs are anonymous | `src/Controller/Api/Redirect/RedirectController.php` (9 routes, zero `IsGranted`), `src/Controller/Api/Redirect/UncaughtController.php` (6 routes, zero `IsGranted`). The routes split into two groups (see 2.1a). **Admin routes (10)**, called only by the ICCommand Vue UI: `DELETE /api/redirects/{id}` (:101), list, search, get, `POST/PUT /api/redirects/` (:173, :274), `POST /api/redirects/upload` (:468), and the uncaught DELETE/GET/PUT. **emich.edu 404-page routes (5)**, called by the script on emich.edu's PHP 404 page: `GET|PUT /api/redirects/external/redirect` and `GET|POST|PUT /api/uncaughts/external/uncaught`. Redirects feed the public emich.edu site. | Admin routes: add `IsGranted` with `ROLE_REDIRECT_USER`/`ROLE_REDIRECT_ADMIN`; no emich.edu change needed. The five emich.edu 404-page routes stay login-free: the two GETs fully public, the three writes protected by a shared token that emich.edu's PHP sends (see 2.1a). Add a catch-all `access_control` floor for `^/api` that lists these five paths explicitly as exceptions, so an unguarded route cannot recur. |
| S2 | Anonymous truncation and replacement of the Daily Crime Log | `src/Controller/Api/CrimeLog/CrimeLogController.php:56` has no guard; line 85 calls `truncateCrimeLogTable()` which runs `TRUNCATE TABLE dailylog` (`src/Repository/CrimeLog/CrimeLogRepository.php:31`) before any row is validated. This is a Clery-related public record. | Guard with `ROLE_CRIMELOG_USER`; validate and parse the whole CSV before touching the table. A transaction alone will not help: `TRUNCATE` is DDL in MySQL/MariaDB and commits immediately. Use `DELETE FROM dailylog` inside a transaction, or load into a staging table and swap with `RENAME TABLE`. Known crash paths after the truncate today: a blank Report Date passes `null` to a `string` setter, and an Excel byte-order mark on the first header makes every incident number null (see 7.1). Either leaves the public crime log empty. |
| S3 | Privilege escalation via profile update | `src/Controller/Api/Admin/UserController.php:74-99`: `PUT /api/admin/users/{username}` is `#[IsGranted('ROLE_USER')]`, takes any `{username}`, and calls `setRoles($data['roles'])` and `setEnabled($data['enabled'])` from the raw JSON body. `assets/js/components/Profile.vue` posts the whole user object to this endpoint. | Split into a self-service `/api/profile` endpoint that cannot touch roles or enabled, and an admin role-assignment endpoint. **Do not restrict role assignment to `ROLE_GLOBAL_ADMIN` only:** the Map, CAS, Scholarship and Social "Manage app" pages let `ROLE_X_ADMIN` users assign module roles through this same endpoint (`assets/js/components/admin/AppManage.vue:397-400`). The admin endpoint must let `ROLE_X_ADMIN` add or remove only `ROLE_X_*` roles, and only global admins grant `ROLE_GLOBAL_*`. Apply the same check to `GET /api/admin/appusers/{rolePrefix}`, which today lets any module admin list any module's users (`UserController.php:104-118`). |
| S4 | Fake API-key bypass | `src/Controller/Api/PhotoRequest/PhotoRequestController.php:198-210` and `src/Controller/Api/Directory/DirectoryController.php:94-107`: auth is skipped if `X-API-Key` is present (value never checked) or the User-Agent matches `/API/i`. | Validate a real secret from env with `hash_equals`, or move the public parts under `/api/external` deliberately. |
| S5 | Authenticated remote code execution via image upload | `src/Controller/Api/UserImageController.php:121` and `src/Controller/Api/Map/MapItemImageController.php:196` check only the sniffed MIME type and size. `src/Entity/Document.php:150-153` keeps the client filename (extension included) and line 165 moves it under `public/`. `public/.htaccess` serves existing files directly; `docker_vhost.conf` has `AllowOverride All`; `docker_postscript.sh:11` makes `public/` writable. Any `ROLE_USER` can reach the profile upload. | Generate random server-side filenames with an extension derived from the sniffed type; store uploads outside the web root or deny PHP execution under `public/uploads`; validate with `getimagesize()`. Random filenames also fix a data bug: two users uploading `photo.jpg` currently overwrite each other's profile image, and deleting one unlinks the other's file. Invalidate the Liip Imagine cache on replace and delete (7.4). **Needs confirmation** whether the production Apache config behaves like the Docker one. |

### 2.1a The emich.edu 404-page routes

When a visitor requests a bad URL such as `emich.edu/badlink`, emich.edu's server renders its PHP 404 page. **That PHP code calls ICCommand server-side** with `file_get_contents` and `curl` (confirmed from the emich.edu helper functions `fetchRedirect`, `updateRedirectCount`, `fetchUncaughtRedirect`, `addUncaughtRedirect`, `updateUncaughtCount`, `__testFor404`). The visitor's browser never talks to ICCommand. Five routes serve this:

| Route | Called by | Decision / harm if anyone can call it |
|---|---|---|
| `GET /api/redirects/external/redirect?url=` | `fetchRedirect` | **Stays fully public** so developers can test it from a browser or Postman. Low harm: emich.edu already performs the redirect for anyone. |
| `GET /api/uncaughts/external/uncaught?url=` | `fetchUncaughtRedirect` | **Stays fully public**, same reason. Low harm. |
| `PUT /api/redirects/external/redirect` | `updateRedirectCount` | Medium. Anyone can inflate usage analytics that staff use to decide which redirects to keep. |
| `POST /api/uncaughts/external/uncaught` | `addUncaughtRedirect` | Medium. Unlimited inserts with no length or dedupe check (S18) can flood the table and the admin suggestions list. |
| `PUT /api/uncaughts/external/uncaught` | *nothing* (see below) | Medium. Same analytics-poisoning risk. |

Note that the paths are `/api/redirects/external/...` and `/api/uncaughts/external/...`, not `/api/external/...`. They are outside the existing `^/api/external` public rule, so the new `^/api` floor needs explicit exceptions for these five paths.

**Protection for the three write routes: a shared token.**

- Generate a random token and store it in config on both servers, for example `ICCOMMAND_API_TOKEN` in ICCommand's env and a config constant on emich.edu. It never reaches a browser, so it stays secret.
- emich.edu adds one line to the stream context of the three write calls: `'header' => "X-ICCommand-Token: " . ICCOMMAND_API_TOKEN . "\r\n"`. The GET calls are unchanged.
- ICCommand checks it with `hash_equals()` in a small request subscriber scoped to those three routes, and returns 401 when it is missing or wrong.
- Roll out in log-only mode first: deploy ICCommand accepting but not requiring the token and logging when it is absent; update emich.edu; confirm the log goes quiet; then enforce.
- Optional extra layer: allow-list emich.edu's server IPs for these three routes at the Apache or firewall level.

**Per-IP rate limiting must not be applied to these routes.** Every visitor's request reaches ICCommand from emich.edu's server, so ICCommand sees one IP for the whole site (see S10). CORS is also irrelevant here: CORS is a rule browsers enforce, and these are server-to-server calls.

> **Ops note: CORS in one sentence.** CORS is a rule the *browser* enforces about which websites' JavaScript may read responses from another site; it has no effect on requests from servers, curl or Postman, so it is never an access control on its own. The `^/api/` CORS rule in `nelmio_cors.yaml` is not needed for these five routes. It may still be needed for other callers that run in a browser, such as an emergency-banner script embedded in emich.edu pages (**needs confirmation** before removing it).

**Problems found in the emich.edu helper code** (not in this repository, but they affect ICCommand data):

- **The uncaught visit counter had never incremented. Fixed on emich.edu on 2026-09-23.** `updateUncaughtCount` sends `PUT /api/uncaughts/external/uncaughtincrement`. That route does not exist (`router:match` returns "None of the routes match"); the real route is `PUT /api/uncaughts/external/uncaught`. Every uncaught URL stays at the 1 visit set when it was first logged, so the admin UI cannot rank 404s by traffic. A commented log line in `UncaughtController.php:99` mentions `/api/external/uncaughtincrement`, which suggests the URL changed during an earlier migration and the caller was not updated. Fix on the emich.edu side by changing the URL. All rows logged before that date show 1 visit. The fixed PUT is safe: the `Uncaught` entity has no Gedmo listeners, unlike `Redirect` (7.1).
- **No timeout on the lookup.** `__testFor404` uses `curl` with no `CURLOPT_TIMEOUT`, and `fetchRedirect` relies on PHP's default socket timeout (60 s unless changed). If ICCommand is slow or down, every emich.edu 404 page hangs. Set a short timeout (2 to 3 s) and fall through to the normal 404 page on failure.
- **Two requests per lookup.** `fetchRedirect` and `fetchUncaughtRedirect` call the same URL twice: once via `curl` to check for 404, then via `file_get_contents` to read the body. One `curl` call that reads both the status and the body halves the load on ICCommand and the latency of every 404 page.
- The write calls send form-encoded bodies without a `Content-Type` header. PHP adds `application/x-www-form-urlencoded` automatically (with a notice), and Symfony parses that for PUT, so it works today. Setting the header explicitly removes the notice.

### 2.2 High

| ID | Finding | Evidence | Fix |
|---|---|---|---|
| S6 | LDAP bind credentials cross the network in cleartext | `config/services.yaml`: `host: ad.emich.edu, port: 389, encryption: none`. Login is a simple bind with the user's AD password. | `encryption: tls` (StartTLS) or `ssl` on 636. Move host to env. |
| S7 | No login throttling | No `login_throttling` on any firewall. Online brute force against AD accounts is possible. | `login_throttling: { max_attempts: 5 }` on `main`. |
| S8 | Disabled accounts still log in on staging and prod | `App\Security\UserChecker` is wired only on the `dev` firewall (`security.yaml:93`). | Add `user_checker` to staging and prod firewalls. |
| S9 | CKEditor HTML is stored and served unsanitized to public emich.edu consumers | No `symfony/html-sanitizer` in `composer.json`, no sanitizer calls in `src`. Affected: emergency banner/notices (`GET /api/emergency/banner`), scholarship text fields (`/api/external/scholarships/*`), program overview, map hours (`/api/external/mapitems`). Also rendered in-app via `v-html`. `npm audit` reports the pinned CKEditor 5 v37 has a known XSS (GHSA-jrqm-vmqc-gm93, fixed in 47.6). | Sanitize on write with `symfony/html-sanitizer` allow-list; DOMPurify on render; upgrade CKEditor (licence review needed, see 3.3). |
| S10 | Rate limiter is dead code, runs twice, and would throttle the whole site if it ever fired | `src/EventSubscriber/RateLimitSubscriber.php:43` limits the GET redirect lookup only when the User-Agent exactly equals a bot name, keyed on `getClientIp()`. The only caller is emich.edu's PHP (2.1a), which sends PHP's or curl's default User-Agent, not the visitor's, so the check never matches. If it did, the key would be emich.edu's server IP, so all visitors to the site would share one 50-per-hour bucket. `config/services.yaml:43` registers the subscriber a second time on top of autoconfiguration. | Delete the subscriber, its `rate_limiter.yaml` entry, the manual service definition, and the `_defaults.bind` for `$externalRedirectsLimiter` in `services.yaml:18` (the container fails to compile if the bind outlives the limiter). The shared token (2.1a) replaces it for the write routes, and the GET lookups are meant to be public. Bot throttling belongs on emich.edu, which sees the real visitor. If a limit on token-less requests is still wanted, add one with a fresh design rather than repairing this one. |
| S11 | Anonymous SSRF via redirect validation | `RedirectController.php:241, :348, :373` call `get_headers($fullToLink)` on a caller-supplied URL: arbitrary outbound requests from the server, no timeout, follows redirects, 404-or-not oracle. | After S1, use `HttpClientInterface` with allow-listed hosts, timeout, no redirects, ideally off the request path. |
| S21 | SQL injection in two Programs admin list endpoints | `src/Repository/Programs/ProgramsRepository.php:87` and `ProgramWebsitesRepository.php:61` interpolate `LIMIT $offset, $pageSize`, where `$pageSize` is the raw `?limit=` string from `ProgramsController.php:60,99`. In production, PHP only warns on a leading-numeric string in the offset arithmetic, and the string then reaches SQL verbatim: `limit=10 PROCEDURE ANALYSE(1)` becomes `LIMIT 0, 10 PROCEDURE ANALYSE(1)`. Reachable by any `ROLE_PROGRAMS_VIEW` user. Impact after `LIMIT` depends on MariaDB version and DB privileges (**needs confirmation**). | Type the repository parameters as `int` and cast in the controller, as `ProgramKeywordsRepository` already does; clamp `limit` to a maximum (7.3). |

> **Ops note: trusted proxies.** When the app sits behind a load balancer or reverse proxy, PHP sees the proxy's IP as the client. Symfony only reads the real client IP from `X-Forwarded-For` if `framework.trusted_proxies` names that proxy. Without it, any per-IP rate limit counts every user as one client. **Needs confirmation** of the production topology.

### 2.3 Medium

- **S12 IDOR on profile images.** `UserImageController.php:30-48` takes `user_id` from the body and accepts any HTTP method; `:53-67` deletes any user's image by id. Use `$this->getUser()`.
- **S13 Profile disclosure.** `GET /api/admin/users/{username}` (`ROLE_USER`) returns any user's roles, email, phone, department. `PhotoRequestController.php:177-188` serializes without groups and embeds the assigned User. Password is `#[Ignore]`d everywhere (the earlier "remove password hash" commit is complete).
- **S14 Web Profiler enabled on staging.** `config/packages/web_profiler.yaml:9` (`when@staging`) plus `config/routes/web_profiler.yaml` mount `/_profiler` and the `dev` firewall has `security: false` for it. Anyone reaching staging can browse request bodies, SQL, and session data.
- **S15 Login and logout without CSRF.** `form_login`/`form_login_ldap` lack `enable_csrf: true`; logout has none, so a cross-site `GET /logout` works.
- **S16 API relies on SameSite=Lax alone.** `assets/js/bootstrap.js:39-45` looks for a `csrf-token` meta tag that no template emits, and no API controller validates one. Modern browsers block cross-site POST via Lax; legacy or embedded clients are not covered.
- **S17 No security headers anywhere** (no CSP, `X-Frame-Options`, HSTS, `X-Content-Type-Options`, `Referrer-Policy`) in Apache config, Nelmio, or a subscriber.
- **S18 Unbounded anonymous DB writes.** `POST /api/uncaughts/external/uncaught` inserts a row per request with no validation, length cap, or limit. The entity does declare `unique: true, length: 191` on `link` (`Uncaught.php:40`), so a duplicate or over-long URL produces a 500 rather than a second row (**needs confirmation** that the live table has the index, since no migration creates it). The shared token (2.1a) limits who can write. Replace insert-then-fail with `INSERT ... ON DUPLICATE KEY UPDATE visits = visits + 1`, cap the length, and use atomic `visits = visits + 1` updates in both counter PUTs so concurrent requests stop overwriting each other.
- **S19 Stray scripts in the web root.** `public/check.php` (requirements checker, exposes php.ini details) and `public/tile.php` (raw PHP outside the kernel building a path from `$_GET`). `check.php` is recreated by the `symfony/requirements-checker` Composer auto-script, so also run `composer remove symfony/requirements-checker`. `tile.php` has no live caller (the only one is commented out in `GoogleMap.vue:50`; tiles are served statically), so deleting it is safe.
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
- **Built assets are committed.** `git ls-files public/build` shows 10 tracked files. At the reviewed commit `app.js` was a 2.37 MB production build, but `master` is now at `36c562f` with a **15.7 MB unminified development build** (Vue dev mode and devtools on). Most commits since 2026-08-13 shipped dev builds; which one is committed depends on whoever ran the build last. Dependabot PRs change only `package.json`/`package-lock.json`, so npm fixes never reach the shipped bundle until someone rebuilds by hand. The bundle's banner references `app.js.LICENSE.txt`, which is not committed, so third-party licence notices (including CKEditor's) are not shipped. History weight: 53 versions of `app.js` account for about 65 MB of a 91 MB pack in this shallow clone. `.gitignore` has both a commented-out and an active `/public/build/` rule; the active one has no effect on already-tracked files. `git log -- public/build` shows 60 commits and at least one merge done only to resolve conflicts in built assets. The Docker image installs no Node, so Docker and production get JS only via these committed files.
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
| `@fortawesome/fontawesome-svg-core`, `free-solid-svg-icons`, `vue-fontawesome` | 6.7 / 3.3 | one icon (`faPenToSquare`) in 11 files | Keep or consolidate |
| `font-awesome` 4.7 | 4.7.0 | yes: 129 `fa fa-*` usages in Vue, 12 in Twig | **Two icon systems.** Either drop the four `@fortawesome/*` packages by swapping the one icon to `fa fa-pencil-square-o`, or replace FA4 with `@fortawesome/fontawesome-free` (ships v4 shims). Not both. |
| `ajv` ^8 | 8.20.0 | **No** (Dependabot promoted a transitive dep) | **Remove** |
| `axios` ^1.15 | 1.20.0 | yes (global) | Keep |
| `es6-promise` | 4.2.8 | polyfill | **Remove** with the IE polyfills |
| `load-google-maps-api` | 1.3.3 | **No** (maps loaded via `<script>`) | **Remove** |
| `moment` ^2.29 | 2.30.1 | only in `CalendarEventPicker.vue`, which nothing imports | **Remove** with the dead component |
| `momentjs` ^2.0 | 2.0.0 | **No** (unrelated squatted package, not Moment.js) | **Remove** |
| `vee-validate` ^4.7 | 4.13.2 | yes (13 forms) | Keep, bump to 4.15 |
| `vue-axios` | 2.1.5 | **No** | **Remove** |
| `vue-flatpickr-component` | 11.0.5 | **No** | **Remove** |
| `vue-multiselect` `"next"` | 3.0.0-beta.3 | yes (19 files) | **Re-pin to ^3.5.0**; the `next` tag still points at a 2-year-old beta |
| `vue-router` ^3.6 | 3.6.5 | **No** (multi-page app, no router; v3 is the Vue 2 line anyway) | **Remove** |
| `vue-slicksort` | 1.2.0 | **No** (superseded by vuedraggable) | **Remove** |
| `vuedraggable` ^4.1 | 4.1.0 | yes | Keep the explicit `^4.1.0` (npm `latest` is the Vue 2 build) |
| `yup` 0.32 | 0.32.11 | yes (13 forms) | Optional bump to ^1.7 (small API change, but touches 13 forms) |
| `sass` ^1.77 | 1.77.8 | yes | Keep on 1.x with `quietDeps`; Bootstrap 4 SCSS will break on Dart Sass 3 |
| `lodash` | transitive | `bootstrap.js:4` only, never used | Delete the line |

Composer side: `jms/serializer` (**zero usages**; CLAUDE.md is wrong on this point), `willdurand/hateoas` (one inert attribute on `MapItem`), `composer/package-versions-deprecated` (abandoned), `symfony/web-link`, `nesbot/carbon`, `symfony/process`, `symfony/lock` (unused but `lock.yaml` demands a `LOCK_DSN`), `phpdocumentor/reflection-docblock` (misused as an attribute in `CrimeLogService.php:9`). `symfony/webpack-encore-bundle` goes with the migration.

### 3.3 Frontend code health

- 100% Options API (56/56), zero `<script setup>`. No event bus, no `$root`/`$parent` abuse, props-down/emit-up throughout. That is a decent base.
- **Zero `emits:` declarations** across 36 `$emit` calls (Vue 3 warnings; mechanical fix).
- Vue 1/2 fossils that Vue 3 silently ignores: `filters: {}` in 16 files, `ready()` hook in 5 delete modals, `events: {}` in 8 files, legacy `slot="title"` attribute syntax in 6 files.
- Five largest components: `map/MapItemForm.vue` 1,972 lines, `programs/ProgramForm.vue` 1,201, `scholarship/ScholarshipForm.vue` 1,127, `directory/DepartmentForm.vue` 987, `photorequest/PhotoRequestForm.vue` 827.
- **Duplication.** Seven `*List.vue` components reimplement the same `data()`, `handleSearchInput`, `fetchX` with an identical 403/404/500 switch, `handlePageChanged`, and `window.location.href` navigation. Seven `*Form.vue` components reimplement `fetchX`, `submitX`, `afterSubmitSucceeds`, `markItemDeleted`, `toggleEdit`, `goBack`. Eight delete modals are near-identical. `ScholarshipKeywordsList.vue` and `ScholarshipOrganizationsList.vue` are both exactly 557 lines and were produced with `sed`. Two paginators (`Paginator.vue`, `ExternalPaginator.vue`) coexist. Two modal paradigms coexist (jQuery `.modal()` vs the CAS module's state-driven `v-if`).
- Dead files: `assets/app.js`, `assets/styles/app.css` (Flex recipe leftovers), `assets/js/utils/timeSlots.js`, `assets/js/components/utils/CalendarEventPicker.vue`, `assets/js/components/map/MapIndexBuildingTable.vue` (never registered or imported), `assets/images/loading.gif` (duplicate of `public/images/loading.gif`), five tracked `.DS_Store` files (two under `src/`), and three PNGs under `src/public/images/map/uploads/`.
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
- **Six CSV importers** (`RedirectController`, `CrimeLogController`, `ProgramsController`, `CasController`, `ScholarshipKeywordController`, `ScholarshipOrganizationController`) each re-implement `file()/str_getcsv/array_combine`, batching, `em->clear()`, and then return an HTML `<ul>` under `Content-Type: application/json`.
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
- `ProgramsRepository` is ~90% raw SQL with `LIMIT $offset, $pageSize` interpolated from uncast query params (this is an injection vector, see S21; parameters elsewhere are bound). Repositories return arrays or entities inconsistently.
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

1. `composer require --dev phpunit/phpunit:^11`; keep `symfony/phpunit-bridge` for deprecation reporting and remove `SYMFONY_DEPRECATIONS_HELPER=disabled` from `phpunit.xml.dist`; set `bootstrap="tests/bootstrap.php"` (today it is `vendor/autoload.php`, so `tests/bootstrap.php`, the only code that loads `.env` files, never runs); point `bin/phpunit` at `vendor/bin/phpunit`; regenerate `phpunit.xml.dist` with `--migrate-configuration`.
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
2. Replace the 22 jQuery modal lines with one accessible state-driven `Modal` component (native `<dialog>` with `showModal()` gives focus trapping and Escape for free). The CAS module's state-driven modals are a starting point but lack `aria-modal`, initial focus, focus trap and focus return (7.6), so do not copy them as-is.
3. Extract `useListPage(endpoint)` / `useResourceForm(endpoint)` composables and a single `ConfirmDeleteModal` and `Paginator`, so tests target one implementation instead of seven copies. This removes several thousand lines.
4. Add `emits:` declarations so `wrapper.emitted()` assertions are meaningful.
5. A tiny `navigate()` wrapper around `window.location.href`.

---

## 6. Roadmap options

All options start with the same **Phase 0: security hotfix** because S1 to S5 are exploitable today and their fixes are small, local, and independent of everything else. Estimates are developer-days for one experienced Symfony/Vue developer and should be treated as ±50%.

### Phase 0 (all options): security hotfix — 4 to 6 days

- Add `IsGranted` to the 10 Redirect/Uncaught admin routes and the CrimeLog upload; add the `^/api` catch-all `access_control` floor with explicit exceptions for the five emich.edu 404-page routes.
- Protect the three emich.edu write routes with a shared token and keep the two GETs public. This needs coordinated changes on both servers; see Phase 0a.
- Split the user PUT into self-service and admin endpoints; allow-list grantable roles.
- Remove the `X-API-Key`/User-Agent bypass.
- Random upload filenames with sniffed-type extensions; deny PHP execution under `public/uploads`.
- Wire `UserChecker` on staging/prod; `login_throttling`; login/logout CSRF; LDAP StartTLS (needs an AD-side check that 389+StartTLS or 636 is offered).
- Disable the profiler on staging; delete `public/check.php` and `tile.php`; add basic security headers.
- Remove the rate limiter subscriber (S10); it cannot work for server-to-server calls.
- Cast and clamp `limit`/`page` in the Programs list endpoints (S21).
- Add `IsGranted(ROLE_GLOBAL_ADMIN or ROLE_X_ADMIN)` to the six unguarded "Manage app" pages (7.5).
- Make unauthenticated `/api` requests return a JSON 401 instead of redirecting to the login page, and add an axios interceptor that sends the user to `/login` (7.5). Today an expired session makes saves report success while nothing is saved.
- **Production is serving a development build today** (production pulls `master`, and `master` has the 15.7 MB dev build committed). Set `__VUE_PROD_DEVTOOLS__: false`, run `npm run build`, and commit the production build to `master`. Until Vite and a scripted deploy land, add a guard that rejects a non-production `public/build/app.js` (a size or `NODE_ENV` check in a pre-commit hook or CI) (7.6, 3.1).
- Sanitize CKEditor HTML on write with `symfony/html-sanitizer`.

Each item is a candidate for its own small PR. Without a working test suite these ship on manual verification, which is acceptable for guards this simple; the regression tests come in the next phase.

### Phase 0a (all options): emich.edu 404-page integration — about 1 day plus coordination

These changes span two codebases, so they need to go out in a fixed order. Details and evidence are in section 2.1a.

| Step | Where | Change |
|---|---|---|
| 1 | ICCommand | Generate a random token and store it as an env var. Add a request subscriber that checks `X-ICCommand-Token` with `hash_equals()` on the three write routes (redirect PUT, uncaught POST, uncaught PUT). Start in **log-only** mode: accept requests without the token and log a warning. Add explicit `access_control` exceptions for all five routes, with the two GETs fully public. |
| 2 | ICCommand | Make both counters atomic and listener-free: `UPDATE ... SET visits = visits + 1` through DBAL, and `INSERT ... ON DUPLICATE KEY UPDATE` for the uncaught POST with a length cap (S18). This also sidesteps the Gedmo Blameable problem that may be breaking the redirect counter today (7.1). Remove the rate limiter subscriber, its `rate_limiter.yaml` entry, its manual service definition and its `bind` (S10). |
| 3 | emich.edu | Store the same token in emich.edu's config and send it as a header on the three write calls (`updateRedirectCount`, `addUncaughtRedirect`, `updateUncaughtCount`). |
| 4 | emich.edu | ~~Fix `updateUncaughtCount` to call `PUT /api/uncaughts/external/uncaught` instead of the non-existent `.../uncaughtincrement`.~~ **Done 2026-09-23.** 404 visit counts are accurate from that date. |
| 5 | emich.edu | Add a 2 to 3 second timeout to every ICCommand call, falling through to the normal 404 page on failure, so an ICCommand outage cannot hang emich.edu 404 pages. |
| 6 | emich.edu | Replace the `__testFor404` + `file_get_contents` pair in `fetchRedirect` and `fetchUncaughtRedirect` with a single `curl` call that reads both status and body. This halves the requests per 404. Optionally set `Content-Type: application/x-www-form-urlencoded` explicitly on the write calls. |
| 7 | ICCommand | Once the warning log shows no token-less requests for a few days, switch the subscriber to **enforce** (return 401 without a valid token). |

Steps 3, 5 and 6 can ship in one emich.edu deploy. Uncaught rows logged before 2026-09-23 show 1 visit; consider noting the date counting started in the admin UI, or resetting counts.

> **Ops note: why log-only first.** If ICCommand started rejecting token-less requests before emich.edu was sending the token, every visit count and 404 log would fail silently. Deploying the check in log-only mode first means nothing breaks at any step, and the log tells you when it is safe to enforce.

### Option A: Security, then foundations, then incremental refactor (recommended)

| Phase | Content | Effort |
|---|---|---|
| 0 | Security hotfix (above) | 4 to 6 d |
| 1 | Test harness and CI (section 5.3 step 0), plus auth smoke tests for the 20 formerly unguarded routes and negative tests for role assignment | 4 to 6 d |
| 2 | Hygiene PR: dead files, dead Composer/npm packages, duplicate configs, Doctrine mapping fixes, retire the `programs` EM, routing cleanup, `.env.test`, CLAUDE.md/README corrections, plus the quick fixes in section 7 marked *hygiene* (timezone, logging, error page, `bin/console`, prod debug on staging, deprecations, `.gitattributes`, dependabot config, stale branches) | 4 to 6 d |
| 3 | Vite migration (section 3.4) including the `public/build` decision | 3 to 4 d |
| 4 | Backend refactor, one module at a time, tests first: inject repositories, DTOs with `MapRequestPayload`, handlers, shared CSV importer, response factory + exception listener, transactions. Order: Programs, Scholarships, Redirects, then the rest | 15 to 20 d |
| 5 | Frontend refactor: `http.js`, state-driven modals, `useListPage`/`useResourceForm`, single paginator and delete modal, Vitest coverage of the composables and one list/form per module | 8 to 12 d |
| Later | Bootstrap 5 (removes jQuery), CKEditor ≥44 licence decision, Composition API migration, SINGLE_TABLE or DTO feed for the map | separate |

Total: roughly **40 to 56 days** including the section 7 items. Every phase leaves `master` deployable. Risk is low because each phase is independently reviewable and the test suite exists before the big refactors.

### Option B: Foundations first, security fixes ride on tests

Same phases as A but Phase 1 (test harness) comes before Phase 0, and each security fix lands with its regression test.

- Pro: every security change is proven by a failing-then-passing test; no manual verification.
- Con: the anonymous DELETE/TRUNCATE/role-escalation routes stay open for another one to two weeks while the harness is built. Given that the fixes are one-line attributes, this trade is not worth it. Reasonable only if a WAF or network rule can block `/api/redirects`, `/api/uncaughts`, and `/api/crimelog/upload` from outside in the meantime.

Total: same as A, **40 to 56 days**.

### Option C: Parallel full modernization

Phase 0 first, then three workstreams in parallel: (1) test harness + backend refactor, (2) Vite + Bootstrap 5 + Composition API + frontend composables, (3) Doctrine cleanup (retire the `programs` EM, baseline migration, SINGLE_TABLE map, enums, typed properties).

- Pro: reaches the end state fastest in calendar time, about 5 to 6 weeks with two or three developers.
- Con: three streams touch the same controllers, templates and entities; the Bootstrap 5 rewrite alone touches every Vue and Twig file, so merge conflicts and regressions are near-certain without a test suite in place first. The committed `public/build` directory makes parallel frontend branches especially painful until Phase 3 of Option A has landed.
- Total: roughly **49 to 65 days** of effort, compressed into fewer weeks, with materially higher risk. Only sensible if there is a team of two or more and a hard deadline.

### Comparison

| | A: Incremental | B: Foundations first | C: Parallel |
|---|---|---|---|
| Time until S1 to S5 are closed | days | 1 to 2 weeks | days |
| emich.edu 404 integration (Phase 0a) | first week | after the test harness | first week |
| Total effort | 40 to 56 d | 40 to 56 d | 49 to 65 d |
| Calendar time (1 dev) | 8 to 11 wk | 8 to 11 wk | n/a |
| Calendar time (2 to 3 devs) | 5 to 6 wk | 5 to 6 wk | 4 to 5 wk |
| Regression risk | low | low | high |
| `master` always deployable | yes | yes | not guaranteed |

**Recommendation: Option A.** Ship the security fixes this week as small PRs, then build the test harness, then do the hygiene PR (which is where most of the "outdated packages" goal is met), then Vite, then refactor module by module with tests leading.

### Decisions needed from the team before starting

1. **`public/build`:** production deploys by `git pull`, so today the committed build *is* the production frontend. Options: (a) keep committing, but enforce production builds with a check and use stable filenames under Vite; or (b) untrack it and add a build step to the deploy script (Node on the server) or to CI with a release archive. (b) removes the merge conflicts and the dev-build risk; (a) needs no server change.
2. **CKEditor licence:** stay on v37 with a known XSS, or move to ≥44 under GPL terms or a commercial key.
3. **LDAP transport:** confirm AD offers StartTLS on 389 or LDAPS on 636.
4. **Production web server:** confirm whether the upload directory can execute PHP (S5) and whether a reverse proxy sits in front (trusted proxies).
5. **`programs` entity manager:** retire it now that it points at the same database. The alternative (keep it and `exclude` Programs from the default EM) is not viable: `ScholarshipProgram.php:33` maps a `ManyToOne` to `Programs`, and Doctrine cannot map associations across entity managers.
6. **Bootstrap 5 and Composition API:** in scope for this effort or deferred.
7. **emich.edu 404 page:** who owns the PHP helper code, so the token header, the `uncaughtincrement` URL fix and a request timeout can be deployed together (2.1a); and which browser-side callers, if any, still need the `^/api/` CORS rule.
8. **Operations:** adopt the deploy script, scheduled backups of both databases and the uploads directories, and a runbook (7.8). Confirm whether a restore has been tested, how logs are rotated, and whether staging should keep a debug mode behind access control. Resolved 2026-09-23: production timezone is `America/Detroit`; staging runs `APP_ENV=staging` in debug; production deploys `master` via `git pull`; deploys and backups are manual; the uncaught counter URL is fixed.

---

## 7. Additional technical debt (final audit, 2026-09-23)

A second pass compared the whole codebase against sections 2 to 6 and recorded only what they missed. Corrections it found have already been applied in place (S2, S3, S5, S10, S18, S19, S21, section 3 counts and dead files, section 5.3 bootstrap, decision 5, appendices). Items tagged *Phase 0* are in the Phase 0 list; items tagged *hygiene* belong in Option A Phase 2; untagged items go into the module-by-module refactor (Phases 4 and 5).

### 7.1 Backend bugs

| Sev | Finding | Evidence | Fix |
|---|---|---|---|
| High | **The redirect visit counter may fail on every call** (**needs confirmation** of the live column and `sql_mode`). | `Redirect.php:76` puts `Gedmo\Blameable(on: "update")` on `contentChanged`, a NOT NULL string column. The anonymous `PUT external/redirect` has no security token, so Gedmo writes NULL on every increment: a 500 in strict mode, or a wiped "last edited by" otherwise. `updated` is also bumped on every visit. The Phase 0a shared token is not a Symfony user, so it does not fix this. | DBAL `UPDATE redirect SET visits = visits + 1, last_visit = NOW()` bypassing ORM listeners (Phase 0a step 2). |
| Med | Crime-log import can empty the public log and store wrong dates. | Blank Report Date passes `null` to `setReportDate(string)` (`CrimeLogController.php:133`, `CrimeLog.php:358`). No UTF-8 BOM stripping, unlike the CAS and Scholarship importers. `date('Y-m-d', strtotime(...))` stores unparseable dates as `1970-01-01` (`:133,240`). Status columns are `length: 10` but validated with `max: 30` (`CrimeLog.php:113/116`, `FireLog.php:105/108`). | Parse with `DateTime::createFromFormat` and reject bad rows; strip the BOM; align lengths; see S2 for the truncate. |
| Med | Fire-log dedupe depends on batch position. | `CrimeLogController.php:251-261` deletes existing rows with immediate SQL while inserts flush every 50 rows (`:100`); duplicates inside one batch both survive. `firelog.crnnumber` has no index. | Dedupe within the CSV, delete by incident number in one transaction, add an index. |
| Med | Emergency notices can be wiped by accident. | `EmergencyService.php:149` treats a missing `notices` key as `[]`, deleting every notice; the bulk DELETE (`EmergencyNoticeRepository.php:57-62`) runs before validation and flush. `EmergencySeverity::from()` (`:127`) throws a `ValueError` that `catch (\Exception)` misses. Public `GET /api/emergency/banner` exposes `updatedByUsername`. | Reconcile notices only when the key is present, in a transaction; `tryFrom()`; add a public serialization group. |
| Med | Redirect URL handling crashes and mis-validates. | `RedirectController.php:183,189,292,549` call `array_key_exists` on `parse_url()` output, which is `false` for malformed URLs (TypeError; bulk upload aborts halfway). `get_headers(...)[0]` checks only the first hop, so a 301 to a 404 passes, and a DNS failure returns `false` and is treated as valid. | Guard `parse_url`; replace with `HttpClient` checking the final status (also fixes S11). |
| Low | Directory "IT" special case only matches lowercase `it`. | `DirectoryController.php:111`: the `== 'it'` comparison sits inside the `preg_replace` call. This is the public emich.edu search. | `strtolower(preg_replace(..., $searchTerm)) === 'it'`. |
| Low | Photo-request status filters build DQL parameter names from input. | `PhotoRequestRepository.php:56,102,149`: `':status_' . $status`; any punctuation gives a 500. | Allow-list statuses (enum) and bind an `IN` list. |
| Low | "Invalid" and "expired" redirects (**needs confirmation** of intent). | The public lookup (`RedirectController.php:55`) serves redirects marked invalid. Nothing ever sets "expired", yet `RedirectList.vue:265` shows an expired tab. | Decide the intended behaviour; filter by `itemType` in the lookup. |

### 7.2 Schema, migrations and data

- **Med: `redirect.from_link` has no index or unique constraint.** Every emich.edu 404 page looks it up, so each lookup scans the table, and duplicates are prevented only by a `UniqueEntity` validator. Add a unique index. **Needs confirmation** of live indexes.
- **Med: `schema: 'dps'` is hard-coded** on `CrimeLog.php:16` and `FireLog.php:16`, while the raw SQL in the CrimeLog repositories uses unqualified table names on `DATABASE_NAME_DPS`. With a test database the ORM writes to the real `dps` database while truncates hit the test one. Drop the attribute.
- **Med: possible data loss in `Version20260902000000`.** It drops `schlrshp_keywords` and `schlrshp_organizations` without copying their data, and its `down()` recreates empty columns. **Needs confirmation** whether the data was re-imported. Mark the migration irreversible.
- **Low: `user.username` and `user.email` have no unique index.** `/register` derives the username from the email prefix, which can collide. The security provider loads by `email` while `getUserIdentifier()` returns `username` and the LDAP bind uses the username; pick one identifier (7.5).
- **Low:** `transactional: true` in `doctrine_migrations.yaml` gives no atomicity for MySQL DDL and triggers a deprecation; set it to `false`. `sql/seed_pivot_tables.sql` is an untracked manual migration against the retired `programs.` schema. *hygiene*

### 7.3 Performance and limits

- **Med: the public scholarship feed has N+1 queries and no pagination.** `/api/external/scholarships/all` lazily loads program links, keywords and organizations per scholarship (`Scholarship.php:323,688,704`). Fetch-join them in `searchPublicScholarships`.
- **Low: no upper cap on `limit`** in any list endpoint (Redirect, Directory, Programs, PhotoRequest, SocialMedia, Cas, Scholarship). `GET /api/uncaughts/` returns the whole table. Clamp to 1 to 100.
- **Low: no persistent Doctrine query cache in prod.** `config/packages/prod/doctrine.yaml` is commented out, so DQL is re-parsed each request. Restore the recipe's `when@prod` query cache. *hygiene*
- **Low:** `config/preload.php` targets only the prod container and nothing sets `opcache.preload`. Configure it or delete the file. *hygiene*

### 7.4 Environment, logging and CLI *hygiene*

- **Low: the timezone is set only on the production server.** Production php.ini sets `America/Detroit` (confirmed 2026-09-23), so scholarship expiry and date display are correct there. The repository configures no timezone: the Docker image has no php.ini, so local development runs in UTC, where `new \DateTime('today')` (`ScholarshipRepository.php:115,278`, `ScholarshipExternalController.php:84`) expires scholarships at about 8 pm the day before and DATE columns can display as the previous day. Tests will hit the same drift. Add `date.timezone=America/Detroit` to a php.ini in the Docker image and to `phpunit.xml.dist`. Independently, serialize DATE columns as `Y-m-d` and TIME as `H:i`, and use one shared date formatter in the frontend, so display does not depend on server or browser timezone.
- **Med: staging runs in debug mode (confirmed 2026-09-23, `APP_ENV=staging`).** Symfony treats only `prod` as a production environment and `composer.json` sets no `extra.runtime.prod_envs`, so staging defaults to `APP_DEBUG=1`: it shows stack traces to anyone who reaches it, logs SQL, and renders with `strict_variables`, so it behaves differently from production. Together with the profiler (S14), staging leaks request data. Add `"runtime": {"prod_envs": ["prod","staging"]}` to `composer.json` or set `APP_DEBUG=0` in staging's env. Keep a separate debug-enabled environment only if the team needs one, behind a login or IP allow-list.
- **Med: `bin/console` is a Symfony 4 file.** It loads only `.env` (`bin/console:22`), unlike the web front controller, so migrations can run with different credentials than the site; it references the removed `Symfony\Component\Debug\Debug`; and `umask(0000)` in debug makes cache files world-writable. Restore the runtime-based file from the console recipe.
- **Med: prod and staging log every request at debug level with no rotation.** `prod/monolog.yaml` uses `fingers_crossed` with `action_level: info`, and route matching logs at info on every request, so the whole debug buffer (including SQL) is flushed each time to one unrotated file. `crime_log` records are written twice. Use `action_level: error`, `buffer_size: 50`, rotation or stderr, and exclude `crime_log` from the main handler.
- **Med: every production error page renders an empty body.** `templates/bundles/TwigBundle/Exception/error.html.twig` was copied from the Symfony demo: it fills `main`/`sidebar` blocks that `base.html.twig` never renders and calls `path('blog_index')`, a route that does not exist. Verified: a 404 renders the title "Welcome!" and an empty `<main>`. Same for 403 and 500. Rewrite with `{% block body %}`.
- **Low: Liip Imagine cache is never invalidated.** Nothing in `src/` uses `CacheManager`, and `Document::removeUpload()` deletes only the original, so deleted or replaced images remain publicly served from `public/media/cache/`. Call `CacheManager::remove()` on update and delete (pairs with S5).
- **Low: Flex recipes have drifted** (16 of 26 have updates) and config mixes per-environment folders with `when@` blocks. Run `composer recipes:update` one at a time and collapse to `when@`.
- **Low: dead config.** `^/resetting` access rule with no route; Stof `softdeleteable` enabled but unused; `env(DATABASE_URL)` fallback unused; the whole mailer stack (config, mailhog, the override's mailcatcher) serves only the dead `EmailController`.

### 7.5 Security and auth configuration

- **High: an expired session makes saves report success while saving nothing.** The only entry point is form login, so an unauthenticated `/api` call gets `302 → /login`, and `/login` answers any method with 200 (`LoginController.php:12` has no `methods:`). Axios follows the redirect, receives the login HTML with a 200, and forms call their success handler (e.g. `RedirectItemForm.vue:604-622`). Once S1 adds guards, this affects every module. Add a JSON 401 entry point for `^/api`, an axios 401 interceptor that redirects to `/login`, and restrict `/login` to GET and POST. *Phase 0*
- **Med: six "Manage app" pages have no admin guard.** Crimelog, directory, emergency, photorequests, programs and redirects (e.g. `src/Controller/Redirect/RedirectController.php:76`) let any module user open the role-assignment UI; the navbar only hides the link. Map, CAS, Scholarship and Social already check `ROLE_X_ADMIN`. *Phase 0*
- **Low: two user identifiers.** The provider loads by `email` (`security.yaml:6`) while `getUserIdentifier()` returns `username` and LDAP binds with it; the login label says "Email". Remember-me, impersonation or login links would break. Choose one.
- **Low: the role-to-module matrix exists in three places.** `base.html.twig`, `Homepage.vue:207-260` ("Keep them in sync") and `UserManage.vue:526-560`. They have already drifted: the navbar hides Social Media from `ROLE_SOCIAL_USER`-only users (`base.html.twig:35`), and `Homepage.vue:224` checks a non-existent `ROLE_DIRECTORY_`. The server should compute visible modules once and pass them to both.

### 7.6 Frontend bugs, accessibility and UX

Bugs:

- **Med: search-as-you-type has no debounce or cancellation and does not URL-encode the term** in eight list components (e.g. `DepartmentList.vue:227-231,312`). Late responses overwrite newer results, and terms like `R&D` or `C++` break the query. Only the three keyword/organization lists do it right. Build this into the planned `useListPage` composable.
- **Med: vuedraggable 4 is configured with the Vue 2 API** in `MapItemForm.vue:1071-1081`: `item-key="images"` makes every key `undefined`, `:options` is ignored so dragging is never disabled, and a global click listener is never removed.
- **Med: no double-submit guard** on 12 of 14 forms; a double-click creates duplicate records. Return the submit promise and bind `:disabled="isSubmitting"`.
- **Med: `GoogleMap.vue` Reset throws** after a marker is deleted (`:176` deletes, `:204` dereferences). Google objects are stored in reactive state (`:146`); use `markRaw`. `google.maps.Marker` is deprecated.
- **Low:** `ProgramForm.vue:964` and `WebsiteForm.vue:306` write `self.currentStatus` in an arrow function where `self` is `window.self`. Upload errors print the whole array once per error (`Profile.vue:158`, `MapItemForm.vue:1140`). The crime-log upload uses a relative URL (`CrimeLogItem.vue:171`). The jQuery nav highlighter and popover init in `app.js:6-21` match nothing useful.
- **Low: Twig mount-point typos.** `photorequests/edit.html.twig:11` closes `<photorequests-form>` with `</photorequests-show>` and `photorequests/index.html.twig:11` closes with `</programs-index>`; they work only because the browser recovers.
- **Low/Med: Vue devtools are enabled in production** (`__VUE_PROD_DEVTOOLS__: true` in `webpack.config.js`), letting anyone with the extension inspect and edit component state. *Phase 0*

Accessibility (no current baseline; worth a WCAG 2.1 AA target in Phase 5):

- About 60 icon-only controls (lock toggles, save buttons, edit links, remove buttons) have no accessible name.
- About 100 form controls have no associated `<label for>`; validation messages are not linked with `aria-describedby`.
- None of the 16 modal dialogs sets `aria-modal`, `aria-labelledby`, focus trap, Escape or focus return.
- Mouse-only interactions: clickable `<div>` cards in `MapItemForm`, right-click-only marker delete, drag-only image sort.
- Status shown by colour alone; the `.red` colour (about 4.0:1) fails AA contrast; no `aria-live` on save results. Duplicate `id="name"` in `MapitemImageEditModal.vue`.
- `base.html.twig` has no `<html lang>` and no `<meta name="viewport">`, so the collapsible navbar does not work on phones.

UX and hygiene:

- A third confirmation style: native `confirm()` is used seven times, including the critical force-emergency switch (`EmergencyBanner.vue:572`).
- Six lists show a header-only table when empty; option-loading failures only `console.log` (16 `console.*` calls remain).
- No `beforeunload` guard despite `formDirty` tracking; after delete, forms stay editable for 3 s before redirecting.
- 46 `v-for` loops without `:key`; 8 components with array-style `props`; a prop mutated via `v-model` in `MapitemImageEditModal.vue:15`; 22 `data-vv-as` attributes (a vee-validate 2 fossil) to add to the section 3.3 list.
- 33 of 49 `<style>` blocks are unscoped; `EmergencyBanner.vue` overrides `.bg-danger` globally with `!important`.
- A 62-line commented-out Emergency Notices UI (`EmergencyBanner.vue:249-310`) sits next to live notices logic.
- Hard-coded hosts and paths (`https://www.emich.edu` in `RedirectList.vue`, support URLs, Liip cache paths duplicating `services.yaml`). Pass them in as config.
- `applinks/index.html.twig` hard-codes the external admin link list, so editing a link needs a code deploy and rebuild. Move it to data.

### 7.7 Templates and public assets *hygiene*

- 62 hard-coded `href="/..."` links and one `path()` call; 12 near-identical `nav_*.html.twig` partials, some showing "Create" links without a role check (`redirect/nav_redirect.html.twig:9-11`, `programs/nav_programs.html.twig:46-48`). One nav macro driven by the module map in 7.5.
- Orphan or copy-paste templates: `crimelog/crimelog-item.html.twig` (never rendered, mounts an unregistered component), misnamed `applinks/nav_redirect.html.twig`, "Programs App Management" heading on the photo-requests page, unused `app-slug` prop, `/admin` and `/admin/users` rendering the same template.
- The navbar avatar uses a hard-coded `/media/cache/resolve/...` URL, costing a PHP redirect on every page view; Google Maps JS loads synchronously on every page.
- About 2.9 MB of unreferenced images in `public/images` (`illustrated_map_screenshot.png` 2.2 MB, the `*-front` screenshots, `multimedia-front.jpg` from a removed module). `public/dailylog_template.csv` is never linked and contains a real-looking incident row; replace it with a synthetic example.
- 388 tracked files under `public/` have the executable bit set.

### 7.8 Docker, local setup and operations

- **Med: a new developer cannot bootstrap from the README.** `docker-compose.yml:19` sets `MYSQL_USER: 'root'`, which very likely fails MariaDB first-time init (**needs confirmation** with `docker compose down -v && up`). No `dps` database is created, `.env` is absent, and there is no way to create the first admin (`/register` requires `ROLE_GLOBAL_ADMIN` and there is no console command). Fix: drop `MYSQL_USER`, add an initdb script for both databases, add an `app:create-admin` command, and rewrite the setup steps. *hygiene*
- **Low: image debt.** Floating tags (`php:8.5-apache`, `mariadb`), Composer installed via `curl | php`, no php.ini (so `display_errors` on, no timezone, no opcache tuning), unused packages and `ENV`, no `.dockerignore`, no healthchecks. The image holds no code and relies on a bind mount, so there is no production artefact at all.
- **Low: `docker_postscript.sh`** uses `mkdir` without `-p`, re-owns the bind-mounted working tree to `www-data` on Linux hosts, and has copy-pasted "EMU Today" text. Add `.gitattributes` with `*.sh text eol=lf` (the CRLF problem is documented only in `notes.txt`). *hygiene*
- **Low: `docker-compose.override.yml`** adds a second mail catcher and a Postgres port. *hygiene*
- **Low: no `.github/dependabot.yml`,** so updates are ungrouped and composer updates never appear. *hygiene*
- **Low: 9 of 11 remote branches are fully merged** (e.g. `gradcas`, `social-media`, `scholarships`). Delete them. *hygiene*
- **Low: no health-check endpoint.** `/unittest` returns "Hello World"; confirm no uptime monitor uses it before deleting it (section 2.4), and add a `/healthz` that pings the database.
- **Low: history weight.** After untracking `public/build`, plan a `git filter-repo` purge with the team; untracking alone does not shrink clones.

**Deploys and backups are manual (confirmed 2026-09-23).** Production and staging are updated with `git pull` of `master`, and the database is backed up with `mysqldump` by hand. Consequences:

- **Whatever is committed to `master` is what runs**, including `public/build`. That is how the dev build reached production (Phase 0). It also means the Vite decision below needs a build step somewhere.
- **Steps are easy to skip.** After a pull, `composer install --no-dev`, `doctrine:migrations:migrate` and `cache:clear` must be run by hand; forgetting any of them breaks the site in ways that look like code bugs (for example "Field does not exist", which `notes.txt` says to fix by deleting `var/cache`).
- **Backups depend on someone remembering.** `mysqldump` covers the database only. Uploaded images live in `public/uploads` and `public/media` (git-ignored, so `git pull` leaves them alone), but nothing backs them up. The `dps` database needs its own dump.
- **Restores have never been tested** (**needs confirmation**).

Recommended, in order of payoff:

1. **A deploy script in the repo** (`bin/deploy.sh`) that runs the same steps every time: `git pull --ff-only`, `composer install --no-dev --optimize-autoloader`, `doctrine:migrations:migrate --no-interaction`, `cache:clear`, and a smoke check against `/healthz`. It is still run by hand, but it cannot skip a step. *hygiene*
2. **Scheduled backups** via cron on the server: nightly `mysqldump --single-transaction` of both the `ic` and `dps` databases plus an `rsync`/`tar` of `public/uploads`, copied off the server and kept for a set period. Test a restore once to a scratch database.
3. **A short runbook** in `docs/` covering deploy, rollback (`git checkout <previous commit>` then the deploy script), backup, and restore.
4. Later, with Vite: either install Node on the servers and have the deploy script run `npm ci && npm run build`, or build in GitHub Actions and publish a release archive that the deploy script downloads.

> **Ops note: what `--single-transaction` does.** A plain `mysqldump` of a live database can capture tables at slightly different moments, so related rows may not match. `--single-transaction` takes a consistent snapshot of InnoDB tables without locking the site.

Still unknown: log rotation on the servers, session storage if there is ever more than one web node (native file sessions do not share across servers), and secrets handling (the Symfony secrets vault is unused).

### 7.9 Deprecations *hygiene*

- PHP 8.4: `str_getcsv()` without `$escape` fires once per CSV row in four importers; null passed to `strtolower`/`strlen`/`preg_replace`/`substr` in the Programs, Directory and Redirect controllers; `FireLog::setArson`/`getArson` use an undeclared dynamic property (an Error in PHP 9).
- Doctrine ORM 3 (errors in ORM 4): `nullable` on identifier join columns in `ScholarshipKeywordLink` and `ScholarshipOrganizationLink`; string `"ASC"` in `OrderBy` on `MapItem.php:73` and `MapParking.php:41`.
- Symfony config: `framework.profiler.collect_serializer_data`, `doctrine.orm.enable_native_lazy_objects`, and Liip `twig.mode` should be `lazy`.
- These surface automatically once the deprecation helper is re-enabled in the test suite (section 5.3).

---

## Appendix A: API routes with no `#[IsGranted]` (verified against `debug:router`)

Under `/api/external/*` and intentionally public (10): Cas, Programs, Scholarship, SocialMedia externals, `/api/external/mapitems`.

Not under `/api/external` and therefore anonymous today (20):

- `RedirectController`, called by the emich.edu 404 page (GET stays public; PUT needs the shared token, not a login): `GET|PUT /api/redirects/external/redirect`
- `RedirectController`, called by the ICCommand UI (need `IsGranted`): `DELETE /api/redirects/{id}`, `GET /api/redirects/list`, `GET /api/redirects/search`, `GET /api/redirects/{id}`, `POST /api/redirects/`, `PUT /api/redirects/`, `POST /api/redirects/upload`
- `UncaughtController`, called by the emich.edu 404 page (GET stays public; POST and PUT need the shared token): `GET|POST|PUT /api/uncaughts/external/uncaught`. The PUT was never reached until emich.edu's call was fixed on 2026-09-23.
- `UncaughtController`, called by the ICCommand UI: `DELETE /api/uncaughts/{id}`, `GET /api/uncaughts/`, `PUT /api/uncaughts/`
- `POST /api/crimelog/upload`
- `POST /api/photorequests/` (create; header bypass)
- `GET /api/directory/search` (header bypass), `GET /api/directory/bldgnames`
- `GET /api/emergency/banner` (public consumer; should live under `/api/external`)
- `GET /api/programs/keywords` (guard commented out)

## Appendix B: Environment variables the config resolves (none documented today)

`APP_SECRET`, `DATABASE_HOST|PORT|NAME|USER|PASSWORD`, `DATABASE_HOST|PORT|NAME|USER|PASSWORD_DPS`, `MAILER_URL`, `LOCK_DSN`, `GOOGLE_MAPS_API_KEY`, `VAR_DUMPER_SERVER` (dev). `MAILER_URL` is needed only by a mailer nothing uses. (`LDAP_USER`, `LDAP_PASSWORD` and `CORS_ALLOW_ORIGIN` appear only in commented-out lines.) `.env` is git-ignored and absent, `.env.test` is empty, and `bin/console` fails without a `.env` file. Commit a `.env` with safe defaults per the Symfony Flex convention.

## Appendix C: Documentation drift in `CLAUDE.md`

- "Serialization uses JMS Serializer": the Symfony Serializer is used everywhere; JMS has zero usages.
- "`default` connection excludes Programs, CrimeLog": it does not; all entities are mapped in the default EM.
- "Dev allows `*.emich.edu`; production restricts": both CORS regexes are active in every environment.
- "`php bin/phpunit` runs all tests": the runner cannot start.
- The module list omits Scholarships, CAS App Links, Social Media and App Links.
- The rate limiter is described as working; it is dead code (S10).
- `README.md` has the same drift, plus "LDAP when on the EMU network" (auth is chosen by `APP_ENV`), Symfony 8.0 / Vue 3.2 badges, and "Axios with CSRF protection". Fold both files into one correction PR.
- "Frontend HTTP requests use Axios with CSRF token configured in `bootstrap.js`": the token lookup fails because no template emits the meta tag, and the API does not check it.
