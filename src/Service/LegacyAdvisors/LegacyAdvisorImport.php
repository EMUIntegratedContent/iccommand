<?php

namespace App\Service\LegacyAdvisors;

use App\Entity\Ic\IcAdvisorType;

/**
 * Builds advisors from legacy advisor rows: one per distinct person or office, with each field's
 * most common value, the programs its legacy program names match, and its building.
 *
 * Rows of the same person share a last name and email (person|last|email, or person|last|first
 * without an email); rows of an office share its name. Close calls are settled by the overrides
 * (see LegacyAdvisorOverrides).
 */
final class LegacyAdvisorImport
{
    /**
     * @param array<string, string> $mergeInto group key => group key it merges into
     * @param array<string, string> $firstNames group key => first name
     * @param array<string, list<array{string, string}>> $programAliases legacy program name => extra [full_name, catalog] targets
     * @param array<string, string> $buildingAliases legacy building text => building name
     */
    public function __construct(
        private readonly array $mergeInto = LegacyAdvisorOverrides::MERGE_INTO,
        private readonly array $firstNames = LegacyAdvisorOverrides::FIRST_NAMES,
        private readonly array $programAliases = LegacyAdvisorOverrides::PROGRAM_ALIASES,
        private readonly array $buildingAliases = LegacyAdvisorOverrides::BUILDING_ALIASES,
    ) {
    }

    /**
     * @param list<array{source: string, program: ?string, advisor_name: ?string, advisor_address: ?string, advisor_phone: ?string, advisor_email: ?string, website: ?string, updated_at: string}> $legacyRows
     * @param list<array{full_name: string, catalog: string}> $programs
     * @param list<string> $buildings building names (map_item.name where discr = 'building')
     */
    public function build(array $legacyRows, array $programs, array $buildings): LegacyImportResult
    {
        $programsByKey = [];
        foreach ($programs as $program) {
            $fullName = trim($program['full_name']);
            $programsByKey[LegacyAdvisorParser::programKey($fullName)][] = [$fullName, $program['catalog']];
        }
        $aliasesByKey = [];
        foreach ($this->programAliases as $legacyName => $targets) {
            $aliasesByKey[LegacyAdvisorParser::programKey($legacyName)] = $targets;
        }

        [$groups, $skippedNames] = $this->group($legacyRows);

        $advisors = [];
        $unmatchedPrograms = [];
        $unmatchedAddresses = [];
        foreach ($groups as $key => $group) {
            $rows = $group['rows'];

            [$legacyPrograms, $links] = $this->programs($rows, $programsByKey, $aliasesByKey);
            foreach ($legacyPrograms as $name) {
                if (!isset($links[$name])) {
                    $unmatchedPrograms[$name][] = $key;
                }
            }

            $address = $this->pick($rows, fn (array $row) => LegacyAdvisorParser::cleanText($row['advisor_address']));
            [$room, $buildingName] = $this->location($address, $buildings);
            if ($address !== null && $buildingName === null && LegacyAdvisorParser::parseAddress($address)['building'] !== null) {
                $unmatchedAddresses[$address] = true;
            }

            $isPerson = $group['type'] === IcAdvisorType::PERSON;
            $advisors[] = new LegacyAdvisor(
                key: $key,
                type: $group['type'],
                firstName: $isPerson ? ($this->firstNames[$key] ?? $this->pick($rows, fn (array $row) => $row['_first'])) : null,
                lastName: $isPerson ? $this->pick($rows, fn (array $row) => $row['_last']) : null,
                organization: $isPerson ? null : $this->pick($rows, fn (array $row) => $row['_name']),
                email: $this->pick($rows, fn (array $row) => $row['_email']),
                phone: $this->pick($rows, fn (array $row) => LegacyAdvisorParser::cleanPhone($row['advisor_phone'])),
                room: $room,
                buildingName: $buildingName,
                url: $this->pick($rows, fn (array $row) => LegacyAdvisorParser::absoluteUrl($row['website'])),
                legacyPrograms: $legacyPrograms,
                programs: array_values(array_unique(array_merge([], ...array_values($links)), SORT_REGULAR)),
            );
        }

        usort($advisors, fn (LegacyAdvisor $a, LegacyAdvisor $b) => strcmp($a->key, $b->key));
        ksort($unmatchedPrograms, SORT_FLAG_CASE | SORT_STRING);

        return new LegacyImportResult($advisors, $skippedNames, $unmatchedPrograms, array_keys($unmatchedAddresses));
    }

    /**
     * @return array{0: array<string, array{type: IcAdvisorType, rows: list<array>}>, 1: array<string, int>}
     */
    private function group(array $legacyRows): array
    {
        $groups = [];
        $skipped = [];
        foreach ($legacyRows as $row) {
            $name = LegacyAdvisorParser::cleanText($row['advisor_name']);
            $type = $name === null ? null : LegacyAdvisorParser::classifyName($name);
            if ($type === null) {
                $label = $name ?? '(no name)';
                $skipped[$label] = ($skipped[$label] ?? 0) + 1;
                continue;
            }

            $row['_name'] = $name;
            $row['_email'] = LegacyAdvisorParser::cleanEmail($row['advisor_email']);
            if ($type === IcAdvisorType::PERSON) {
                ['first' => $row['_first'], 'last' => $row['_last']] = LegacyAdvisorParser::splitPersonName($name);
                $key = 'person|' . mb_strtolower($row['_last']) . '|' . ($row['_email'] ?? mb_strtolower($row['_first']));
            } else {
                $key = 'organization|' . mb_strtolower($name);
            }

            $seen = [];
            while (isset($this->mergeInto[$key]) && !isset($seen[$key])) {
                $seen[$key] = true;
                $key = $this->mergeInto[$key];
            }

            $groups[$key]['type'] ??= $type;
            $groups[$key]['rows'][] = $row;
        }

        return [$groups, $skipped];
    }

    /**
     * The group's legacy program names (first spelling of each, A–Z) and, per name, the
     * programs it links to.
     *
     * @return array{0: list<string>, 1: array<string, list<array{string, string}>>}
     */
    private function programs(array $rows, array $programsByKey, array $aliasesByKey): array
    {
        $names = [];
        foreach ($rows as $row) {
            $name = LegacyAdvisorParser::cleanText($row['program']);
            if ($name !== null) {
                $names[LegacyAdvisorParser::programKey($name)] ??= $name;
            }
        }

        $links = [];
        foreach ($names as $key => $name) {
            $targets = array_merge($programsByKey[$key] ?? [], $aliasesByKey[$key] ?? []);
            if ($targets) {
                $links[$name] = array_values(array_unique($targets, SORT_REGULAR));
            }
        }

        $names = array_values($names);
        usort($names, 'strcasecmp');

        return [$names, $links];
    }

    /**
     * The room and building name for an address. A building that can't be found leaves the
     * whole address as the room, so nothing is lost.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function location(?string $address, array $buildings): array
    {
        ['room' => $room, 'building' => $text] = LegacyAdvisorParser::parseAddress($address);
        if ($text === null) {
            return [$room, null];
        }

        $key = LegacyAdvisorParser::buildingKey($text);
        $exact = array_values(array_filter($buildings, fn (string $name) => LegacyAdvisorParser::buildingKey($name) === $key));
        $prefix = array_values(array_filter(
            $buildings,
            fn (string $name) => $key !== '' && str_starts_with(LegacyAdvisorParser::buildingKey($name) . ' ', $key . ' ')
        ));

        $building = match (true) {
            count($exact) === 1 => $exact[0],
            count($exact) === 0 && count($prefix) === 1 => $prefix[0],
            default => $this->buildingAliases[$text] ?? null,
        };

        return $building === null ? [$address, null] : [$room, $building];
    }

    /**
     * The most common non-null value among the rows; a tie goes to the value on the most
     * recently updated row.
     */
    private function pick(array $rows, callable $value): ?string
    {
        $counts = [];
        $latest = [];
        foreach ($rows as $row) {
            $v = $value($row);
            if ($v === null) {
                continue;
            }
            $counts[$v] = ($counts[$v] ?? 0) + 1;
            $latest[$v] = max($latest[$v] ?? '', $row['updated_at']);
        }
        if (!$counts) {
            return null;
        }

        $values = array_keys($counts);
        usort($values, fn ($a, $b) => [$counts[$b], $latest[$b]] <=> [$counts[$a], $latest[$a]]);

        return (string) $values[0];
    }
}
