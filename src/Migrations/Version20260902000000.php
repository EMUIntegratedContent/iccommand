<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Promotes scholarship keywords and organizations from free-text columns to managed-entity
 * M2M: creates scholarships_keyword + scholarships_organization masters and their pivot
 * tables (FKs ON DELETE CASCADE), then drops the old schlrshp_keywords / schlrshp_organizations
 * scalar columns. FK/index names match the Doctrine-derived names so schema:validate stays green.
 */
final class Version20260902000000 extends AbstractMigration
{
    private const SCHOLARSHIP = 'scholarships_scholarship';

    public function getDescription(): string
    {
        return 'Create scholarship keyword/organization managed-entity tables and drop the scalar columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_keyword (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_scholarships_keyword_name (keyword),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_organization (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            organization VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_scholarships_organization_name (organization),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_scholarship_keyword (
            scholarship_id INT UNSIGNED NOT NULL,
            keyword_id INT UNSIGNED NOT NULL,
            INDEX IDX_86D45BF928722836 (scholarship_id),
            INDEX IDX_86D45BF9115D4552 (keyword_id),
            PRIMARY KEY (scholarship_id, keyword_id),
            CONSTRAINT FK_86D45BF928722836 FOREIGN KEY (scholarship_id) REFERENCES scholarships_scholarship (id) ON DELETE CASCADE,
            CONSTRAINT FK_86D45BF9115D4552 FOREIGN KEY (keyword_id) REFERENCES scholarships_keyword (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_scholarship_organization (
            scholarship_id INT UNSIGNED NOT NULL,
            organization_id INT UNSIGNED NOT NULL,
            INDEX IDX_C5EC86CB28722836 (scholarship_id),
            INDEX IDX_C5EC86CB32C8A3DE (organization_id),
            PRIMARY KEY (scholarship_id, organization_id),
            CONSTRAINT FK_C5EC86CB28722836 FOREIGN KEY (scholarship_id) REFERENCES scholarships_scholarship (id) ON DELETE CASCADE,
            CONSTRAINT FK_C5EC86CB32C8A3DE FOREIGN KEY (organization_id) REFERENCES scholarships_organization (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        foreach (['schlrshp_keywords', 'schlrshp_organizations'] as $column) {
            if ($this->columnExists($column)) {
                $this->addSql('ALTER TABLE ' . self::SCHOLARSHIP . ' DROP COLUMN ' . $column);
            } else {
                $this->warnIf(true, sprintf('%s not found on %s, skipping drop', $column, self::SCHOLARSHIP));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS scholarships_scholarship_keyword');
        $this->addSql('DROP TABLE IF EXISTS scholarships_scholarship_organization');
        $this->addSql('DROP TABLE IF EXISTS scholarships_keyword');
        $this->addSql('DROP TABLE IF EXISTS scholarships_organization');

        foreach (['schlrshp_keywords', 'schlrshp_organizations'] as $column) {
            if (!$this->columnExists($column)) {
                $this->addSql('ALTER TABLE ' . self::SCHOLARSHIP . ' ADD ' . $column . ' VARCHAR(255) DEFAULT NULL');
            }
        }
    }

    private function columnExists(string $column): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [self::SCHOLARSHIP, $column]
        );
    }
}
