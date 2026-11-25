# Legacy advisor import: close calls

## Answers (2026-11-25)

Every recommendation below was accepted: N1, N2, N3, B1 and P1–P14 yes, and U1–U14 as recommended (so U13
and U14 stay unlinked). The legacy rows match production. The answers live in `LegacyAdvisorOverrides`,
each tagged with its ID. P2, P7, P10, P11, U5, U7 and U9 have no override because only skipped rows
("Visit the … website", "Contact the department …") list those programs. The regenerated report raised no
new close calls: 149 advisors, 392 links, every address has a building, and the 45 unlinked names are the
ones listed at the end, plus U13 and U14.

Questions for the import in `2026-11-25-legacy-advisor-import.md`. Each one has a recommendation. Reply
with the IDs you want changed (for example "U2 no, N3 Barb"). Anything you don't mention keeps its
recommendation. Task 6 turns your answers into `LegacyAdvisorOverrides`.

Source: local `ic` DB, 2026-11-25 (`advisor_programs` 197 rows, `uacdc_advisor_programs` 346 rows).
Legacy rows were edited as recently as 2026-11-24, so Task 6 reruns the report on current data and
asks again about any new close calls.

## Names

| ID | Close call | Recommendation |
|---|---|---|
| N1 | "Dan Brickner" (dbrickner@emich.edu, 1 row) and "Daniel Brickner, Ph.D." (daniel.brickner@emich.edu, 1 row) | Merge into one person, using the more recently updated row's email |
| N2 | "Matt Kautz" with coe_ldcn_k12_info@emich.edu (3 rows) and coe_ldcn_spa_phd@emich.edu (1 row) | Merge, keeping coe_ldcn_k12_info@emich.edu |
| N3 | Same email, different first names (merged automatically): Barb/Barbara Ross (1:1), Phil/Philip Lewis (1:3), Jackie/Jacquelyn McGinnis (3:2), Mary Elizabeth/Mary-Elizabeth Murphy (2:4) | Barbara, Philip, Jackie, Mary-Elizabeth (the most common one, and Barbara for the tie) |
| N4 | coe_ldcn_spa_phd@emich.edu is shared by Cierra Presberry, Rachel Radina and Matt Kautz | Separate people. Presberry and Radina keep the shared email |
| N5 | 19 names get no record (117 rows): every "Visit the … website for advising information.", every "Contact …"/"Please contact …", and "Program Advising" (Dietetics, 260 Rackham) | Skip them all. Their programs get no advisor from those rows |
| N6 | Organizations: College of Business Academic Services; College of Education Advising Center; College of Engineering and Technology Student Services; College of Health and Human Services Advising Center; University Advising & Career Development Center | All five as Organization. Every other name is a Person |
| N7 | Credentials and job titles after the name ("Ph.D.", "DNP, RN, FNP-BC, Associate Director of Graduate Nursing Studies", "Program Director", "Department Head", "RN-BSN Coordinator", "Undergraduate Program Coordinator") | Drop them, since there's no field for them. `area` isn't on the form, and `toPublicArray()` publishes it |
| N8 | Nicknames: `Renuku "Ray" Roche`, `Ildiko (Ildi) Porter-Szucs` | Keep them as-is in the first name |
| B1 | "251 Packham" (Ray Roche) has no building match | Treat it as a typo for Rackham Building |

## Programs: formatting differences only

Recommendation: link all of these.

| ID | Legacy name (table) | Program |
|---|---|---|
| P1 | Psychology-Clinical Behavioral [M.S.] (grad) | Psychology - Clinical Behavioral [M.S.] |
| P2 | Communication [BA +MA] (undergrad) | Communication [BA+MA], both catalogs |
| P3 | Business Administration (Graduate Certificate) | Business Administration [Graduate Certificate] |
| P4 | Executive, MBA and Executive - Master of Business Administration | Executive [MBA] |
| P5 | Management of Innovation and Strategy, MS | Management of Innovation and Strategy [M.S.] |
| P6 | Music Therapy Equivalency Post- Baccalaureate Certificate | Music Therapy Equivalency [Post-Baccalaureate Certificate] |
| P7 | Secondary Education Language Literature and Writing Minor | Secondary Education Language, Literature and Writing Minor |
| P8 | PK-12 French Teaching / German Language and Literature / Japanese Language and Culture Teaching / Spanish Teaching / Bilingual Education [BA] | The PreK-12 … [BA] program of the same name |
| P9 | Secondary Education Business Management Marketing and Technology [BBE] | Secondary Education Business, Management, Marketing, and Technology [BBE] |
| P10 | Communication Media and Theatre Arts Comprehensive [BA] | Communication, Media and Theatre Arts Comprehensive [BA] |
| P11 | Simulation Animation and Gaming Minor / [BS] | Simulation, Animation and Gaming Minor / [BS] |
| P12 | Accounting [BBA + MS] (grad table) | Accounting [BBA + M.S.] (graduate). It also matches undergraduate Accounting [BBA + MS] exactly |
| P13 | a Multicultural Society [Graduate Certificate] (name cut off in the legacy table) | Helping Interventions in a Multicultural Society [Graduate Certificate] |
| P14 | Individualized Studies [BGS] | Individualized Studies (General Studies) [BGS] |

## Programs: judgment calls

| ID | Legacy name | Candidate(s) | Recommendation |
|---|---|---|---|
| U1 | Educational Studies [Ph.D., Sp.A.] | Educational Studies [Ph.D.] and [Sp.A.] | Link both |
| U2 | Business Administration (MBA-BU); All Concentrations (Master of Business Administration) | Business Administration [MBA] / Full-Time / Part-Time / Online [MBA] | MBA-BU to Business Administration [MBA]; All Concentrations to all four |
| U3 | Language and International Trade - Spanish [M.A.] | Language and International Trade [M.A.] | Link |
| U4 | Geography [BS] … concentration (5 names), Geoscience [BS] … concentration (2) | Geography [BS], Geoscience [BS] | Link |
| U5 | English [BA] | English - Creative Writing / Linguistics / Literature / Professional Writing [BA] | Link all four |
| U6 | Science Literacy [BS]; Science Literacy Minor | The five "(for … Minors/Majors)" versions of each | Link all five of each |
| U7 | Musical Theatre Minor | Musical Theatre Minor (for Dance / Music / Theatre Arts Majors) | Link all three |
| U8 | Music Performance [B.M.] | Music Performance - Instrumental / - Vocal [B.M.] | Link both |
| U9 | Special Education [M.A.T.] | Special Education (for Certified / Non-Certified Teachers) [MAT] | Link both |
| U10 | Doctor of Nursing Practice (All Programs) | Nursing Practice (Post Masters) [DNP], Nursing Practice - Post-BSN Primary Care Nurse Practitioner [DNP] | Link both |
| U11 | Curriculum and Instruction [M.A.] | Curriculum and Instruction - Leadership / - Reading [M.A.] | Link both |
| U12 | Clinical Research Administration [M.S.] | Clinical Research Management [M.S.] | Link (looks like a rename) |
| U13 | Special Education Teaching Endorsement [Graduate Certificate] | Advanced Special Education Teaching Endorsement [Graduate Certificate] | Don't link (different program) |
| U14 | Public and Nonprofit Administration [BA + MPA]; Accounting Combined, MS; Interior Design [M.S.]; Human Nutrition [M.S.]; Finance [M.S.]; Hotel and Restaurant Management [Graduate Certificate] | Only the undergraduate or non-combined versions | Don't link |

## Not linked: no candidate

These names stay only in `legacy_programs`: Music [M.M.]; Central Office/Superintendent Endorsement;
Educational Leadership [Sp.A.] and both "Educational Leadership – …" M.A.s; Autism Spectrum Disorders [M.A.]/[MA];
Learning Disabilities [M.A.]; Learning, Motivation, and Creativity; Special Education [M.A.]; Long-Term Care
Administration; Public Land Planning and Development Management; the six Secondary … [MAT]; both Exercise
Science + Exercise Physiology combos; Accounting + Tax Consulting [BBA + MS]; Nurse Practitioner Programs
(All Programs); All Programs (Graduate Certificate, Master of Science in Nursing); All Nursing Programs; Nursing;
the Special Education K-12 … Endorsement [BS] variants; Elementary - Early Childhood Teaching and Learning;
Communication Sciences and Disorders [Post-Baccalaureate Certificate]; Undergraduate Certificate in Classroom
and Behavior Management Studies; Information Assurance & Cyber Defense [BS]; Electronic Engineering Technology
Minor; Polymers and Coatings Minor; Apparel Textiles and Merchandising Minor; Secondary Education Social
Studies [BA]; and the non-program labels Graduate Coordinator, COB Acad. Prog. Advisor, Admissions Coordinator
(Social Work).
