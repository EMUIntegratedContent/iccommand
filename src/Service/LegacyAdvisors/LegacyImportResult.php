<?php

namespace App\Service\LegacyAdvisors;

/**
 * The advisors built from the legacy tables, plus what didn't make it in, for the report.
 */
final readonly class LegacyImportResult
{
    /**
     * @param list<LegacyAdvisor> $advisors sorted by key
     * @param array<string, int> $skippedNames name => rows, for names that get no record
     * @param array<string, list<string>> $unmatchedPrograms legacy program name => keys of the advisors that list it
     * @param list<string> $unmatchedAddresses addresses whose building wasn't found
     */
    public function __construct(
        public array $advisors,
        public array $skippedNames,
        public array $unmatchedPrograms,
        public array $unmatchedAddresses,
    ) {
    }

    public function linkCount(): int
    {
        return array_sum(array_map(fn (LegacyAdvisor $advisor) => count($advisor->programs), $this->advisors));
    }
}
