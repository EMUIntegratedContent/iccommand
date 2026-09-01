<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Aligns the program_id index name on scholarships_scholarship_program with the name
 * Doctrine derives for the new ManyToOne(Programs) association (was a hand-named index
 * from when the program side was a loose scalar FK). Purely a rename — no structural
 * change; the underlying index and FK are unchanged.
 */
final class Version20260901130000 extends AbstractMigration
{
    private const TABLE = 'scholarships_scholarship_program';
    private const OLD_INDEX = 'idx_ssp_program';
    private const NEW_INDEX = 'IDX_4EAA5D463EB8070A';

    public function getDescription(): string
    {
        return 'Rename scholarships_scholarship_program.program_id index to the Doctrine-derived name';
    }

    public function up(Schema $schema): void
    {
        if ($this->indexExists(self::OLD_INDEX) && !$this->indexExists(self::NEW_INDEX)) {
            $this->addSql(sprintf(
                'ALTER TABLE %s RENAME INDEX %s TO %s',
                self::TABLE,
                self::OLD_INDEX,
                self::NEW_INDEX
            ));
        } else {
            $this->warnIf(true, sprintf('%s index not in expected state, skipping rename', self::OLD_INDEX));
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->indexExists(self::NEW_INDEX) && !$this->indexExists(self::OLD_INDEX)) {
            $this->addSql(sprintf(
                'ALTER TABLE %s RENAME INDEX %s TO %s',
                self::TABLE,
                self::NEW_INDEX,
                self::OLD_INDEX
            ));
        }
    }

    private function indexExists(string $index): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?",
            [self::TABLE, $index]
        );
    }
}
