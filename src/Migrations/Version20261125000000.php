<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds ic_advisors.legacy_programs, a read-only list of the program names an advisor had in the
 * old advisor tables (advisor_programs, uacdc_advisor_programs). Only the legacy import SQL
 * (sql/legacy_advisors_import.sql) fills it, so the new links can be checked against the old.
 *
 * down() drops the column, so the lists are lost. Back up before rolling back.
 *
 * Each step checks information_schema first, so it is safe to re-run.
 */
final class Version20261125000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ic_advisors.legacy_programs';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists()) {
            $this->warnIf(true, 'ic_advisors does not exist, skipping legacy_programs');
            return;
        }
        if ($this->columnExists()) {
            $this->warnIf(true, 'ic_advisors.legacy_programs already exists, skipping');
            return;
        }

        $this->addSql('ALTER TABLE ic_advisors ADD legacy_programs TEXT DEFAULT NULL AFTER image_url');
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists() || !$this->columnExists()) {
            $this->warnIf(true, 'ic_advisors.legacy_programs does not exist, skipping');
            return;
        }

        $this->addSql('ALTER TABLE ic_advisors DROP COLUMN legacy_programs');
    }

    private function tableExists(): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ic_advisors'"
        );
    }

    private function columnExists(): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ic_advisors' AND COLUMN_NAME = 'legacy_programs'"
        );
    }
}
