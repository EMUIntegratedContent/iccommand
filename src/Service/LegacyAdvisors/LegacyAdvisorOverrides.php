<?php

namespace App\Service\LegacyAdvisors;

/**
 * The answers to the import's close calls (docs/superpowers/plans/2026-11-25-legacy-advisor-close-calls.md).
 * Group keys are person|<lowercase last name>|<email> or organization|<lowercase name>.
 */
final class LegacyAdvisorOverrides
{
    /** Group key => the group key it merges into. */
    public const MERGE_INTO = [];

    /** Group key => the first name to use. */
    public const FIRST_NAMES = [];

    /** Legacy program name (cleaned) => the programs it also links to, as [full_name, catalog]. */
    public const PROGRAM_ALIASES = [];

    /** Legacy building text => map_item.name. */
    public const BUILDING_ALIASES = [];
}
