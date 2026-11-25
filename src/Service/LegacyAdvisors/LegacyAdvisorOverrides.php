<?php

namespace App\Service\LegacyAdvisors;

/**
 * The answers to the import's close calls (docs/superpowers/plans/2026-11-25-legacy-advisor-close-calls.md),
 * each tagged with its ID there. Group keys are person|<lowercase last name>|<email> or
 * organization|<lowercase name>.
 */
final class LegacyAdvisorOverrides
{
    /** Group key => the group key it merges into. */
    public const MERGE_INTO = [
        'person|brickner|dbrickner@emich.edu' => 'person|brickner|daniel.brickner@emich.edu', // N1
        'person|kautz|coe_ldcn_spa_phd@emich.edu' => 'person|kautz|coe_ldcn_k12_info@emich.edu', // N2
    ];

    /** Group key => the first name to use. */
    public const FIRST_NAMES = [
        'person|ross|bwadding@emich.edu' => 'Barbara', // N3
        'person|lewis|plewis4@emich.edu' => 'Philip', // N3
        'person|mcginnis|jmcginnis@emich.edu' => 'Jackie', // N3
        'person|murphy|mmurph54@emich.edu' => 'Mary-Elizabeth', // N3
    ];

    /**
     * Legacy program name (cleaned) => the programs it also links to, as [full_name, catalog].
     * Only names that a kept advisor lists are here: P2, P7, P10, P11, U5, U7 and U9 belong
     * only to skipped rows ("Visit the … website", "Contact the department …").
     */
    public const PROGRAM_ALIASES = [
        'Psychology-Clinical Behavioral [M.S.]' => [['Psychology - Clinical Behavioral [M.S.]', 'graduate']], // P1
        'Business Administration (Graduate Certificate)' => [['Business Administration [Graduate Certificate]', 'graduate']], // P3
        'Executive, MBA' => [['Executive [MBA]', 'graduate']], // P4
        'Executive - Master of Business Administration' => [['Executive [MBA]', 'graduate']], // P4
        'Management of Innovation and Strategy, MS' => [['Management of Innovation and Strategy [M.S.]', 'graduate']], // P5
        'Music Therapy Equivalency Post- Baccalaureate Certificate' => [['Music Therapy Equivalency [Post-Baccalaureate Certificate]', 'undergraduate']], // P6
        'PK-12 Bilingual Education [BA]' => [['PreK-12 Bilingual Education [BA]', 'undergraduate']], // P8
        'PK-12 French Teaching [BA]' => [['PreK-12 French Teaching [BA]', 'undergraduate']], // P8
        'PK-12 German Language and Literature [BA]' => [['PreK-12 German Language and Literature [BA]', 'undergraduate']], // P8
        'PK-12 Japanese Language and Culture Teaching [BA]' => [['PreK-12 Japanese Language and Culture Teaching [BA]', 'undergraduate']], // P8
        'PK-12 Spanish Teaching [BA]' => [['PreK-12 Spanish Teaching [BA]', 'undergraduate']], // P8
        'Secondary Education Business Management Marketing and Technology [BBE]' => [['Secondary Education Business, Management, Marketing, and Technology [BBE]', 'undergraduate']], // P9
        'Accounting [BBA + MS]' => [['Accounting [BBA + M.S.]', 'graduate']], // P12
        'a Multicultural Society [Graduate Certificate]' => [['Helping Interventions in a Multicultural Society [Graduate Certificate]', 'graduate']], // P13
        'Individualized Studies [BGS]' => [['Individualized Studies (General Studies) [BGS]', 'undergraduate']], // P14
        'Educational Studies [Ph.D., Sp.A.]' => [['Educational Studies [Ph.D.]', 'graduate'], ['Educational Studies [Sp.A.]', 'graduate']], // U1
        'Business Administration (MBA-BU)' => [['Business Administration [MBA]', 'graduate']], // U2
        'All Concentrations (Master of Business Administration)' => [ // U2
            ['Business Administration [MBA]', 'graduate'],
            ['Business Administration Full-Time [MBA]', 'graduate'],
            ['Business Administration Part-Time [MBA]', 'graduate'],
            ['Business Administration Online [MBA]', 'graduate'],
        ],
        'Language and International Trade - Spanish [M.A.]' => [['Language and International Trade [M.A.]', 'graduate']], // U3
        'Geography [BS] Cultural/Human Geography concentration' => [['Geography [BS]', 'undergraduate']], // U4
        'Geography [BS] General advising' => [['Geography [BS]', 'undergraduate']], // U4
        'Geography [BS] Geospatial Techniques concentration' => [['Geography [BS]', 'undergraduate']], // U4
        'Geography [BS] Globalization, Diversity, and World Regions concentration' => [['Geography [BS]', 'undergraduate']], // U4
        'Geography [BS] Physical Geography concentration' => [['Geography [BS]', 'undergraduate']], // U4
        'Geoscience [BS] Earth Science concentration' => [['Geoscience [BS]', 'undergraduate']], // U4
        'Geoscience [BS] Geology concentration' => [['Geoscience [BS]', 'undergraduate']], // U4
        'Science Literacy [BS]' => [ // U6
            ['Science Literacy [BS] (for Biology Minors)', 'undergraduate'],
            ['Science Literacy [BS] (for Chemistry Minors)', 'undergraduate'],
            ['Science Literacy [BS] (for Earth Science Minors)', 'undergraduate'],
            ['Science Literacy [BS] (for Math Minors)', 'undergraduate'],
            ['Science Literacy [BS] (for Physics Minors)', 'undergraduate'],
        ],
        'Science Literacy Minor' => [ // U6
            ['Science Literacy Minor (for Biology Majors)', 'undergraduate'],
            ['Science Literacy Minor (for Chemistry Majors)', 'undergraduate'],
            ['Science Literacy Minor (for Earth Science Majors)', 'undergraduate'],
            ['Science Literacy Minor (for Math Majors)', 'undergraduate'],
            ['Science Literacy Minor (for Physics Majors)', 'undergraduate'],
        ],
        'Music Performance [B.M.]' => [['Music Performance - Instrumental [B.M.]', 'undergraduate'], ['Music Performance - Vocal [B.M.]', 'undergraduate']], // U8
        'Doctor of Nursing Practice (All Programs)' => [ // U10
            ['Nursing Practice (Post Masters) [DNP]', 'graduate'],
            ['Nursing Practice - Post-BSN Primary Care Nurse Practitioner [DNP]', 'graduate'],
        ],
        'Curriculum and Instruction [M.A.]' => [['Curriculum and Instruction - Leadership [M.A.]', 'graduate'], ['Curriculum and Instruction - Reading [M.A.]', 'graduate']], // U11
        'Clinical Research Administration [M.S.]' => [['Clinical Research Management [M.S.]', 'graduate']], // U12
    ];

    /** Legacy building text => map_item.name. */
    public const BUILDING_ALIASES = [
        'Packham' => 'Rackham Building', // B1
    ];
}
