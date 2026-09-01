<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the overview and catch-all columns to scholarships_scholarship.
 */
final class Version20260901000000 extends AbstractMigration
{
    private const TABLE = 'scholarships_scholarship';

    private const COLUMNS = [
        'schlrshp_overview' => 'VARCHAR(200) DEFAULT NULL',
        'schlrshp_catch_all' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ];

    public function getDescription(): string
    {
        return 'Add schlrshp_overview and schlrshp_catch_all to scholarships_scholarship';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $column => $definition) {
            if ($this->columnExists($column)) {
                $this->warnIf(true, sprintf('%s column already exists on %s, skipping', $column, self::TABLE));
                continue;
            }
            $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', self::TABLE, $column, $definition));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::COLUMNS) as $column) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN IF EXISTS %s', self::TABLE, $column));
        }
    }

    private function columnExists(string $column): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [self::TABLE, $column]
        );
    }
}
