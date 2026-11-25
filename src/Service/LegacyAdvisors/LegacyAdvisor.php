<?php

namespace App\Service\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;

/**
 * One advisor built from the legacy advisor tables, ready to be written as SQL.
 */
final readonly class LegacyAdvisor
{
    /**
     * @param string $key the group key (person|last|email or organization|name)
     * @param list<string> $legacyPrograms every legacy program name of its rows, deduped, A–Z
     * @param list<array{string, string}> $programs the program_programs rows it links to, as [full_name, catalog]
     */
    public function __construct(
        public string $key,
        public IcAdvisorType $type,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $organization,
        public ?string $email,
        public ?string $phone,
        public ?string $room,
        public ?string $buildingName,
        public ?string $url,
        public array $legacyPrograms,
        public array $programs,
    ) {
    }

    /** What ic_advisors.legacy_programs holds. */
    public function legacyProgramsText(): string
    {
        return implode(', ', $this->legacyPrograms);
    }
}
