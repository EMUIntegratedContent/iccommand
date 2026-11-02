<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds an optional contact_id (FK to ic_contacts, no ON DELETE action) to the five tables that
 * can have a contact. On scholarships_scholarship it replaces the unused schlrshp_contact_id
 * placeholder, which is renamed; the migration stops if that column holds any values, since
 * they can't point at ic_contacts.
 *
 * Index and FK names are the ones Doctrine generates for each mapping, so schema diffs stay
 * quiet. program_programs has no association (see Programs::$contact_id), so its names are ours.
 *
 * down() loses which contact each record had, except on scholarships_scholarship, where the
 * values stay in the renamed column. Back up before rolling back.
 */
final class Version20261102010000 extends AbstractMigration
{
    /** table => [index, foreign key] */
    private const TABLES = [
        'ic_colleges' => ['IDX_47AD834CE7A1254A', 'FK_47AD834CE7A1254A'],
        'ic_departments' => ['IDX_73CF737BE7A1254A', 'FK_73CF737BE7A1254A'],
        'scholarships_scholarship' => ['IDX_FB0CB827E7A1254A', 'FK_FB0CB827E7A1254A'],
        'program_programs' => ['IDX_program_programs_contact', 'FK_program_programs_contact'],
        'map_building' => ['IDX_839D57F3E7A1254A', 'FK_839D57F3E7A1254A'],
    ];

    private const SCHOLARSHIP_TABLE = 'scholarships_scholarship';
    private const SCHOLARSHIP_PLACEHOLDER = 'schlrshp_contact_id';

    public function getDescription(): string
    {
        return 'Add contact_id with an FK to ic_contacts to colleges, departments, scholarships, programs and buildings';
    }

    public function up(Schema $schema): void
    {
        if ($this->columnExists(self::SCHOLARSHIP_TABLE, self::SCHOLARSHIP_PLACEHOLDER)) {
            $values = (int)$this->connection->fetchOne(
                'SELECT COUNT(*) FROM ' . self::SCHOLARSHIP_TABLE . ' WHERE ' . self::SCHOLARSHIP_PLACEHOLDER . ' IS NOT NULL'
            );
            $this->abortIf($values > 0, sprintf('%d scholarships have a %s value that can\'t point at ic_contacts. Clear them first.', $values, self::SCHOLARSHIP_PLACEHOLDER));
        }

        foreach (self::TABLES as $table => [$index, $foreignKey]) {
            if ($this->columnExists($table, 'contact_id')) {
                $this->warnIf(true, "$table.contact_id already exists, skipping the column");
            } elseif ($table === self::SCHOLARSHIP_TABLE) {
                $this->addSql("ALTER TABLE $table CHANGE " . self::SCHOLARSHIP_PLACEHOLDER . ' contact_id INT UNSIGNED DEFAULT NULL');
            } else {
                $this->addSql("ALTER TABLE $table ADD contact_id INT UNSIGNED DEFAULT NULL");
            }

            if ($this->indexExists($table, $index)) {
                $this->warnIf(true, "$table index $index already exists, skipping");
            } else {
                $this->addSql("CREATE INDEX $index ON $table (contact_id)");
            }

            if ($this->foreignKeyExists($table, $foreignKey)) {
                $this->warnIf(true, "$table FK $foreignKey already exists, skipping");
            } else {
                $this->addSql("ALTER TABLE $table ADD CONSTRAINT $foreignKey FOREIGN KEY (contact_id) REFERENCES ic_contacts (id)");
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table => [$index, $foreignKey]) {
            if ($this->foreignKeyExists($table, $foreignKey)) {
                $this->addSql("ALTER TABLE $table DROP FOREIGN KEY $foreignKey");
            }
            if ($this->indexExists($table, $index)) {
                $this->addSql("DROP INDEX $index ON $table");
            }

            if (!$this->columnExists($table, 'contact_id')) {
                $this->warnIf(true, "$table.contact_id does not exist, skipping the column");
            } elseif ($table === self::SCHOLARSHIP_TABLE) {
                $this->addSql("ALTER TABLE $table CHANGE contact_id " . self::SCHOLARSHIP_PLACEHOLDER . ' INT DEFAULT NULL');
            } else {
                $this->addSql("ALTER TABLE $table DROP COLUMN contact_id");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $index]
        );
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $foreignKey]
        );
    }
}
