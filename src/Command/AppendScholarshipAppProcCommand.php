<?php

namespace App\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * One-off: append schlrshp_app_proc to schlrshp_description for every scholarship
 * where schlrshp_app_proc is not null. NOT idempotent: running twice appends twice.
 * Uses raw DBAL so Gedmo timestamps/blame and the rich-text sanitizer are untouched.
 */
#[AsCommand(name: 'app:scholarship-append-app-proc', description: 'Append schlrshp_app_proc to schlrshp_description')]
class AppendScholarshipAppProcCommand extends Command
{
    /** TEXT column limit; schlrshp_app_proc is MEDIUMTEXT so the combined value can overflow. */
    private const DESCRIPTION_MAX_BYTES = 65535;

    public function __construct(private ManagerRegistry $doctrine)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report changes without saving them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $conn = $this->doctrine->getConnection();

        $rows = $conn->fetchAllAssociative(
            'SELECT id, schlrshp_description, schlrshp_app_proc FROM scholarships_scholarship WHERE schlrshp_app_proc IS NOT NULL'
        );

        $updated = 0;
        $skipped = 0;

        if (!$dryRun) {
            $conn->beginTransaction();
        }
        try {
            foreach ($rows as $row) {
                $combined = ($row['schlrshp_description'] ?? '') . $row['schlrshp_app_proc'];

                if (strlen($combined) > self::DESCRIPTION_MAX_BYTES) {
                    $io->warning(sprintf('Scholarship #%d skipped: combined length %d exceeds TEXT limit.', $row['id'], strlen($combined)));
                    ++$skipped;
                    continue;
                }

                if (!$dryRun) {
                    $conn->update('scholarships_scholarship', ['schlrshp_description' => $combined], ['id' => $row['id']]);
                }
                ++$updated;
            }
            if (!$dryRun) {
                $conn->commit();
            }
        } catch (\Throwable $e) {
            if (!$dryRun) {
                $conn->rollBack();
            }
            throw $e;
        }

        $io->table(
            ['Matched', $dryRun ? 'Would update' : 'Updated', 'Skipped (too long)'],
            [[count($rows), $updated, $skipped]]
        );
        $dryRun
            ? $io->note('Dry run. Nothing was saved.')
            : $io->success('schlrshp_app_proc appended to schlrshp_description.');

        return Command::SUCCESS;
    }
}
