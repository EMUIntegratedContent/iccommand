<?php

namespace App\Command;

use App\Entity\Ic\IcAdvisor;
use App\Service\LegacyAdvisors\LegacyAdvisor;
use App\Service\LegacyAdvisors\LegacyAdvisorImport;
use App\Service\LegacyAdvisors\LegacyAdvisorSqlWriter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * One-off: reads the legacy advisor tables (advisor_programs, uacdc_advisor_programs) and writes
 * the SQL that imports them into ic_advisors and program_advisor. It only reads the database.
 * The close calls it reports are settled in LegacyAdvisorOverrides. See docs/ic-advisors.md.
 */
#[AsCommand(name: 'app:advisors:legacy-sql', description: 'Write the SQL that imports the legacy advisor tables into ic_advisors')]
class LegacyAdvisorSqlCommand extends Command
{
    private const LEGACY_TABLES = ['advisor_programs', 'uacdc_advisor_programs'];

    public function __construct(
        private readonly Connection $connection,
        private readonly ValidatorInterface $validator,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Where to write the SQL (relative to the project)', 'sql/legacy_advisors_import.sql');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = [];
        $counts = [];
        foreach (self::LEGACY_TABLES as $table) {
            $tableRows = $this->connection->fetchAllAssociative(
                "SELECT '$table' AS source, program, advisor_name, advisor_address, advisor_phone, advisor_email, website,
                        CAST(updated_at AS CHAR) AS updated_at
                 FROM $table ORDER BY id"
            );
            $counts[] = sprintf('%s %d rows', $table, count($tableRows));
            $rows = array_merge($rows, $tableRows);
        }
        $programs = $this->connection->fetchAllAssociative('SELECT TRIM(full_name) AS full_name, catalog FROM program_programs');
        $buildings = $this->connection->fetchFirstColumn("SELECT name FROM map_item WHERE discr = 'building'");

        $result = (new LegacyAdvisorImport())->build($rows, $programs, $buildings);

        $problems = [];
        foreach ($result->advisors as $advisor) {
            foreach ($this->validator->validate($this->toEntity($advisor)) as $violation) {
                $problems[] = sprintf('%s: %s %s', $advisor->key, $violation->getPropertyPath(), $violation->getMessage());
            }
        }
        if ($problems) {
            $io->error('Some advisors would not pass IcAdvisor validation, so no SQL was written.');
            $io->listing($problems);
            return Command::FAILURE;
        }

        $source = sprintf('%s (%s)', $this->connection->getDatabase(), implode(', ', $counts));
        $path = $input->getOption('output');
        if (!str_starts_with($path, '/')) {
            $path = $this->projectDir . '/' . $path;
        }
        file_put_contents($path, (new LegacyAdvisorSqlWriter())->write($result, $source));

        $io->success(sprintf('Wrote %s: %d advisors, %d links.', $path, count($result->advisors), $result->linkCount()));
        $io->section(sprintf('Skipped names (%d)', count($result->skippedNames)));
        $io->listing(array_map(fn ($name, $rowCount) => "$name ($rowCount rows)", array_keys($result->skippedNames), $result->skippedNames));
        $io->section(sprintf('Legacy programs with no program (%d)', count($result->unmatchedPrograms)));
        $io->listing(array_map(fn ($name, $keys) => "$name (" . implode(', ', $keys) . ')', array_keys($result->unmatchedPrograms), $result->unmatchedPrograms));
        $io->section(sprintf('Addresses with no building (%d)', count($result->unmatchedAddresses)));
        $io->listing($result->unmatchedAddresses ?: ['none']);

        return Command::SUCCESS;
    }

    /** An unsaved IcAdvisor with the fields the form validates. */
    private function toEntity(LegacyAdvisor $advisor): IcAdvisor
    {
        return (new IcAdvisor())
            ->setType($advisor->type)
            ->setFirstName($advisor->firstName)
            ->setLastName($advisor->lastName)
            ->setOrganization($advisor->organization)
            ->setEmail($advisor->email)
            ->setPhone($advisor->phone)
            ->setRoom($advisor->room)
            ->setUrl($advisor->url);
    }
}
