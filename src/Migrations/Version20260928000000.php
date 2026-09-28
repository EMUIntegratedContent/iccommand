<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds is_active to program_programs so a program can be put on hold (hidden from the
 * public feeds and the Scholarship/CAS pickers) without being deleted. Existing programs
 * are all active.
 */
final class Version20260928000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active column to program_programs';
    }

    public function up(Schema $schema): void
    {
        $colExists = $this->connection->fetchOne(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'program_programs'
           AND COLUMN_NAME = 'is_active'"
        );
        if ($colExists) {
            $this->warnIf(true, 'is_active column already exists on program_programs, skipping');
            return;
        }
        $this->addSql('ALTER TABLE program_programs ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('UPDATE program_programs SET is_active = 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE program_programs DROP COLUMN IF EXISTS is_active');
    }
}
