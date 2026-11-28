<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Promotes program_colleges to the shared ic_colleges table (the "ic_" prefix marks tables
 * used by more than one IC Command app). Ids are untouched and InnoDB repoints the
 * program_college_link FK on rename. The legacy latin1 table is converted to the utf8mb4
 * collation the newer tables use.
 */
final class Version20261008000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename program_colleges to the shared ic_colleges table';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('ic_colleges') || !$this->tableExists('program_colleges')) {
            $this->warnIf(true, 'ic_colleges already exists or program_colleges is missing, skipping');
            return;
        }
        $this->addSql('RENAME TABLE program_colleges TO ic_colleges');
        $this->addSql('ALTER TABLE ic_colleges CONVERT TO CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('program_colleges') || !$this->tableExists('ic_colleges')) {
            $this->warnIf(true, 'program_colleges already exists or ic_colleges is missing, skipping');
            return;
        }
        $this->addSql('RENAME TABLE ic_colleges TO program_colleges');
    }

    private function tableExists(string $table): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?",
            [$table]
        );
    }
}
