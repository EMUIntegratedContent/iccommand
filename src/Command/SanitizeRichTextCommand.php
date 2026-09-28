<?php

namespace App\Command;

use App\Entity\Emergency\EmergencyBanner;
use App\Entity\Map\MapBuilding;
use App\Entity\Map\MapParking;
use App\Entity\Programs\Programs;
use App\Entity\Scholarship\Scholarship;
use App\Service\RichTextSanitizer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * One-off cleanup: run existing rich-text values through the same sanitizer
 * the API now applies on write.
 */
#[AsCommand(name: 'app:sanitize-rich-text', description: 'Sanitize stored rich-text HTML fields')]
class SanitizeRichTextCommand extends Command
{
    private const BATCH_SIZE = 100;

    /** entity class => list of fields (getter/setter suffixes) */
    private const TARGETS = [
        Programs::class => ['ProgramOverview'],
        Scholarship::class => ['Overview', 'Contact', 'AppProc', 'Description'],
        EmergencyBanner::class => ['BannerMessage'],
        MapBuilding::class => ['Hours'],
        MapParking::class => ['Hours'],
    ];

    public function __construct(
        private ManagerRegistry $doctrine,
        private RichTextSanitizer $richText,
    ) {
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
        $rows = [];

        foreach (self::TARGETS as $class => $fields) {
            $em = $this->doctrine->getManagerForClass($class);
            $changed = 0;
            $pending = 0;

            foreach ($em->getRepository($class)->createQueryBuilder('e')->getQuery()->toIterable() as $entity) {
                $entityChanged = false;
                foreach ($fields as $field) {
                    $current = $entity->{'get' . $field}();
                    if ($current === null || $current === '') {
                        continue;
                    }
                    $clean = $this->richText->sanitize($current);
                    if ($clean !== $current) {
                        $entityChanged = true;
                        if ($output->isVerbose()) {
                            $io->writeln(sprintf('%s #%s %s', $class, $entity->getId(), $field));
                        }
                        if (!$dryRun) {
                            $entity->{'set' . $field}($clean);
                        }
                    }
                }

                if ($entityChanged) {
                    ++$changed;
                    ++$pending;
                }
                if (!$dryRun && $pending >= self::BATCH_SIZE) {
                    $em->flush();
                    $em->clear();
                    $pending = 0;
                }
            }

            if (!$dryRun) {
                $em->flush();
            }
            $em->clear();
            $rows[] = [$class, implode(', ', $fields), $changed];
        }

        $io->table(['Entity', 'Fields', $dryRun ? 'Rows that would change' : 'Rows changed'], $rows);
        $dryRun
            ? $io->note('Dry run. Nothing was saved. Use -v to list each changed row.')
            : $io->success('Rich-text fields sanitized.');

        return Command::SUCCESS;
    }
}
