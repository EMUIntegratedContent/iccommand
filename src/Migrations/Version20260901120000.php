<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Widens schlrshp_overview to TEXT so it can hold CKEditor rich-text (HTML) content.
 */
final class Version20260901120000 extends AbstractMigration
{
    private const TABLE = 'scholarships_scholarship';

    public function getDescription(): string
    {
        return 'Widen schlrshp_overview to TEXT on scholarships_scholarship';
    }

    public function up(Schema $schema): void
    {
        if ($this->columnExists('schlrshp_overview')) {
            $this->addSql('ALTER TABLE ' . self::TABLE . ' MODIFY schlrshp_overview TEXT DEFAULT NULL');
        } else {
            $this->warnIf(true, 'schlrshp_overview not found, skipping widen to TEXT');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ' . self::TABLE . ' MODIFY schlrshp_overview VARCHAR(200) DEFAULT NULL');
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
