<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Converts the FAFSA, parent and bilingual criteria to booleans and renames
 * schlrshp_fafsa to schlrshp_is_fafsa.
 */
final class Version20260830000000 extends AbstractMigration
{
    private const TABLE = 'scholarships_scholarship';

    public function getDescription(): string
    {
        return 'Convert schlrshp_fafsa, schlrshp_is_parent and schlrshp_is_bilingual to booleans';
    }

    public function up(Schema $schema): void
    {
        // The columns are nullable text, and converting NULL straight to a NOT NULL
        // boolean is rejected, so normalize the values to '0' or '1' first.
        if ($this->columnExists('schlrshp_fafsa')) {
            $this->addSql($this->normalizeSql('schlrshp_fafsa'));
            $this->addSql('ALTER TABLE ' . self::TABLE . ' CHANGE schlrshp_fafsa schlrshp_is_fafsa TINYINT(1) NOT NULL DEFAULT 0');
        } else {
            $this->warnIf(true, 'schlrshp_fafsa not found, skipping rename');
        }

        foreach (['schlrshp_is_parent', 'schlrshp_is_bilingual'] as $column) {
            if ($this->columnExists($column)) {
                $this->addSql($this->normalizeSql($column));
                $this->addSql('ALTER TABLE ' . self::TABLE . ' MODIFY ' . $column . ' TINYINT(1) NOT NULL DEFAULT 0');
            } else {
                $this->warnIf(true, sprintf('%s not found, skipping', $column));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . self::TABLE . ' CHANGE schlrshp_is_fafsa schlrshp_fafsa VARCHAR(15) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . self::TABLE . ' MODIFY schlrshp_is_parent VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ' . self::TABLE . ' MODIFY schlrshp_is_bilingual VARCHAR(255) DEFAULT NULL');
    }

    /**
     * Anything that reads as a yes becomes '1', everything else including NULL becomes '0'.
     * @param string $column
     * @return string
     */
    private function normalizeSql(string $column): string
    {
        return sprintf(
            "UPDATE %s SET %s = CASE WHEN %s IN ('1', 'Y', 'y', 'Yes', 'yes', 'true') THEN '1' ELSE '0' END",
            self::TABLE,
            $column,
            $column
        );
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
