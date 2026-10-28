<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops scholarships_scholarship.schlrshp_app_proc. Its content was appended to
 * schlrshp_description beforehand (app:scholarship-append-app-proc); run that command first
 * or the data is lost.
 *
 * down() re-adds the column as an empty nullable MEDIUMTEXT. The dropped values are not
 * restored (they now live inside schlrshp_description), so back up before migrating.
 */
final class Version20261028000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop scholarships_scholarship.schlrshp_app_proc';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists()) {
            $this->warnIf(true, 'scholarships_scholarship.schlrshp_app_proc already dropped, skipping');
            return;
        }

        $this->addSql('ALTER TABLE scholarships_scholarship DROP COLUMN schlrshp_app_proc');
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists()) {
            $this->warnIf(true, 'scholarships_scholarship.schlrshp_app_proc already exists, skipping');
            return;
        }

        $this->addSql('ALTER TABLE scholarships_scholarship ADD schlrshp_app_proc MEDIUMTEXT DEFAULT NULL AFTER schlrshp_housing');
    }

    private function columnExists(): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'scholarships_scholarship'
               AND COLUMN_NAME = 'schlrshp_app_proc'"
        );
    }
}
