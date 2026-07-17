<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_public column to cas_cycle table';
    }

    public function up(Schema $schema): void
    {
        $colExists = $this->connection->fetchOne(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'cas_cycle'
           AND COLUMN_NAME = 'is_public'"
        );
        if ($colExists) {
            $this->warnIf(true, 'is_public column already exists on cas_cycle, skipping');
            return;
        }
        $this->addSql('ALTER TABLE cas_cycle ADD is_public TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas_cycle DROP COLUMN IF EXISTS is_public');
    }
}
