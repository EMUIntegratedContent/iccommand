# Scholarship Search API

Public, read-only endpoint for searching EMU scholarships. No authentication, no API key.

```
GET /api/external/scholarships/search
```

Returns a JSON array of scholarships. All parameters are optional — with none supplied you
get every scholarship currently on offer, the same as `GET /api/external/scholarships/all`.

Results are always sorted by `title`, ascending. There is no pagination; the full matching
set is returned in one response.

---

## Quick start

```
# Everything currently on offer
GET /api/external/scholarships/search

# A junior from Michigan with a 3.2 GPA
GET /api/external/scholarships/search?classStanding=Junior&state=MI&gpa=3.20

# Nursing awards that don't require a FAFSA
GET /api/external/scholarships/search?title=nursing&isFafsa=0
```

---

## Query parameters

Parameters come in three kinds:

- **Eligibility criteria** describe *the student*. A handful of scholarships are open to
  everyone and are returned regardless of these (see [Open-to-all scholarships](#open-to-all-scholarships)).
- **Filters** describe *the scholarship*. These always narrow the result.
- **Result scope** controls which scholarships are eligible to appear at all.

### Eligibility criteria

| Parameter | Type | Match | Accepted values |
|---|---|---|---|
| `gpa` | string | Returns awards the student qualifies for: minimum GPA at or below the value, plus awards with no GPA requirement | `2.50`–`4.00` in `0.10` steps, always two decimals |
| `classStanding` | string | Exact, against a multi-value field | `Entering Freshman`, `Freshman`, `Sophomore`, `Junior`, `Senior`, `Graduate` |
| `transfer` | string | Matches the value, and also awards open to both | `Yes`, `No`, `Both` |
| `gender` | string | Exact | `Male`, `Female` |
| `ethnicity` | string | Exact | `Black/African American`, `Hispanic/Latino`, `Asian/Pacific Islander`, `Native American`, `Chaldean/Arabic`, `Other` |
| `housing` | string | Exact | `Yes`, `No` |
| `state` | string | Exact | Two-letter US state code, plus `DC` and `VI` |
| `city` | string | Substring, case-insensitive | Free text |
| `county` | string | Substring, case-insensitive | Free text |
| `highSchool` | string | Substring, case-insensitive | Free text |
| `enrollment` | string | Substring, case-insensitive | Free text, e.g. `full` matches `full time`, `Full-Time`, `fulltime` |
| `major` | integer | Exact program ID | A program ID from the programs catalog |
| `college` | integer | Exact college ID | A college ID |
| `department` | integer | Exact department ID | A department ID |
| `organization` | string | Substring against any awarding organization linked to the scholarship | Free text |
| `keyword` | string | Comma-separated list; matches a scholarship carrying **any** of the terms as a substring | e.g. `nursing,transfer` |
| `isFafsa` | boolean | See below | `1` / `0` |
| `isParent` | boolean | See below | `1` / `0` |
| `isBilingual` | boolean | See below | `1` / `0` |

**How the three boolean criteria work.** They ask about the student, so they only ever
remove results:

- `isFafsa=0` — "I have not filed a FAFSA." Scholarships that require one are hidden.
- `isFafsa=1` — "I have filed a FAFSA." Nothing is excluded; the result is unchanged.

`isParent` and `isBilingual` behave the same way.

### Filters

These always narrow the result, including for open-to-all scholarships.

| Parameter | Type | Match |
|---|---|---|
| `title` | string | Substring, case-insensitive |
| `description` | string | Substring, case-insensitive |
| `overview` | string | Substring, case-insensitive |
| `appProc` | string | Substring against the application-process text |
| `amount` | string | Substring against the award amount, which is free text — `$500` also matches `$500 minimum` |
| `contact` | string | Substring against the contact block |
| `url` | string | Substring |
| `catchAll` | boolean | `0` excludes open-to-all scholarships; `1` returns only those |
| `applyDateFrom` | date | Applications open on or after this date |
| `applyDateTo` | date | Applications open on or before this date |
| `expDateFrom` | date | Deadline on or after this date |
| `expDateTo` | date | Deadline on or before this date |
| `openNow` | boolean | `1` returns only scholarships whose application period has opened |

Dates use `YYYY-MM-DD`. Ranges are inclusive on both ends.

A date range only matches scholarships that actually carry that date. A scholarship with no
deadline set is not returned by `expDateFrom` or `expDateTo` — a missing date cannot fall
inside a range. `openNow`, by contrast, does include scholarships with no opening date, since
those are open by default.

### Result scope

| Parameter | Type | Effect |
|---|---|---|
| `includeExpired` | boolean | `1` also returns scholarships whose deadline has passed. Off by default |

Scholarships that are not published are never returned, and this cannot be changed by any
parameter.

---

## Parameter value rules

**Booleans** accept `1`, `true`, `on`, `yes` and `0`, `false`, `off`, `no`, in any case.

**Ignored values.** A parameter is treated as "not supplied" when it is blank, omitted, or
set to the literal string `any`. This lets a form bind every field to a query parameter and
leave the unanswered ones empty.

**Invalid values are ignored, not rejected.** An unparseable date (`2026-13-45`), an
unrecognized boolean (`banana`), or an unknown parameter name is skipped and the rest of the
query still runs. The endpoint does not return a validation error for these — it returns the
results for the parameters it understood. Check your query string if a filter appears to have
no effect.

**Combining parameters.** Supplying several parameters narrows the result; they combine with
AND, not OR. The one exception is `keyword`, whose comma-separated terms combine with OR.

**Text matching** is substring-based and case-insensitive: a search for `full` matches
anywhere in the field.

**`%` and `_` are live wildcards.** Text parameters are passed to a SQL `LIKE`, so `%` matches
any run of characters and `_` matches exactly one — `title=%` returns everything. If you are
forwarding user input into a text parameter, escape both characters unless you intend that
behaviour.

### Open-to-all scholarships

A small number of scholarships are flagged as open to every student. They are returned
regardless of the eligibility criteria you send, so an applicant always sees them alongside
the awards they specifically qualify for.

They still respect filters and result scope: a `title` search or a date range applies to them
like any other scholarship, and an expired one stays hidden unless `includeExpired=1`.

Send `catchAll=0` to leave them out entirely, or `catchAll=1` to see only them.

---

## Response

`200 OK`, `Content-Type: application/json`, a JSON array. An empty result is `[]`, not an
error.

### Fields

| Field | Type | Notes |
|---|---|---|
| `id` | integer | |
| `title` | string | Always present |
| `active` | boolean | Always `true` in this feed |
| `catchAll` | boolean | Open to all students |
| `overview` | string \| null | HTML |
| `description` | string \| null | HTML |
| `appProc` | string \| null | HTML — how to apply |
| `contact` | string \| null | HTML — who to contact |
| `amount` | string \| null | Free text, e.g. `$1000`, `varies`, `160 awards at $500 each` |
| `url` | string \| null | |
| `gpa` | string \| null | Minimum GPA, two decimals, e.g. `"3.00"`. A string, not a number |
| `applyDate` | string \| null | ISO 8601, e.g. `2019-05-31T00:00:00+00:00`. Date only — time is always midnight |
| `expDate` | string \| null | ISO 8601. Application deadline |
| `standingClass` | string \| null | Comma-separated, e.g. `Freshman,Sophomore,Junior` |
| `enrollment` | string \| null | Free text |
| `transfer` | string \| null | `Yes`, `No`, or `Both` |
| `housing` | string \| null | `Yes` or `No` |
| `gender` | string \| null | |
| `ethnicity` | string \| null | |
| `state` | string \| null | |
| `city` | string \| null | |
| `county` | string \| null | |
| `highSchool` | string \| null | |
| `collegeId` | integer \| null | Awarding college |
| `departmentId` | integer \| null | Awarding department |
| `isFafsa` | boolean | A FAFSA is required |
| `isParent` | boolean | Applicant must be a parent |
| `isBilingual` | boolean | Applicant must be bilingual |
| `keywords` | array | See below |
| `organizations` | array | See below |
| `programLinks` | array | See below |

Every nullable field can be `null`. Do not assume a field is populated — `overview` and `url`,
for example, are empty across most of the current catalogue.

`transfer` returns `Yes`, `No`, or `Both`. Sending `transfer=Yes` already includes awards
marked `Both`, so query for `Both` only when you specifically want awards open to transfer and
non-transfer students alike.

### Nested objects

```jsonc
"keywords": [
  { "id": 40, "keyword": "alumni" }
],
"organizations": [
  { "id": 6, "organization": "Alumni Association" }
],
"programLinks": [
  { "programId": 18514014, "scholarshipId": 473, "notes": "whatever" }
]
```

All three are always arrays, empty (`[]`) when nothing is linked. `notes` on a program link
may be `null`.

### Example

```jsonc
{
  "id": 371,
  "title": "Alumni Legacy Endowed Scholarships",
  "active": true,
  "catchAll": false,
  "gpa": "3.00",
  "standingClass": "Entering Freshman,Freshman,Sophomore,Junior,Senior,Graduate",
  "enrollment": "full time",
  "transfer": "No",
  "housing": "No",
  "amount": "$1000",
  "appProc": "<p><a href=\"...\">Application Information</a></p>",
  "isFafsa": false,
  "isParent": false,
  "isBilingual": false,
  "overview": null,
  "description": null,
  "url": null,
  "applyDate": null,
  "expDate": null,
  "gender": null,
  "ethnicity": null,
  "state": null,
  "city": null,
  "county": null,
  "highSchool": null,
  "contact": null,
  "collegeId": null,
  "departmentId": null,
  "organizations": [ { "id": 6, "organization": "Alumni Association" } ],
  "keywords": [ { "id": 40, "keyword": "alumni" } ],
  "programLinks": []
}
```

---

## Related endpoints

| Endpoint | Returns |
|---|---|
| `GET /api/external/scholarships/all` | Every scholarship on offer. Same as a search with no parameters |
| `GET /api/external/scholarships/{id}` | One scholarship. `404` if it does not exist, is unpublished, or has expired |

---

## Notes for implementers

**HTML fields are raw.** `overview`, `description`, `appProc` and `contact` contain HTML
authored in a rich-text editor. Sanitize before rendering — do not bind them directly into
`v-html`, `dangerouslySetInnerHTML`, or equivalent, without passing them through a sanitizer
first.

**No pagination.** The full result set comes back in one response; the unfiltered feed is
roughly 600 scholarships and about 750 KB. Cache it rather than re-fetching per keystroke,
and debounce any search-as-you-type UI.

**`gpa` is a string.** Compare and display it as text, or parse it explicitly. `"3.00"` will
not equal `3` in a strict comparison.
