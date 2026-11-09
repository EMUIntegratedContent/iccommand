<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the three tables that join programs, scholarships and buildings to their contacts
 * (many contacts each, many records per contact).
 *
 * Each table is (record id, contact_id) with a composite primary key. The record side cascades
 * on delete, so deleting a program, scholarship or building drops its links. The contact side
 * has no ON DELETE action, so a contact that is still linked can't be deleted.
 *
 * Index and FK names are the ones Doctrine generates for the mappings, so schema diffs stay
 * quiet. program_contact has no entity (see ProgramsRepository::updateProgramContacts()), but
 * follows the same naming.
 *
 * An earlier version of this migration added a contact_id column to ic_colleges, ic_departments,
 * program_programs, scholarships_scholarship (renaming schlrshp_contact_id) and map_building.
 * That version never reached production. Both up() and down() remove those columns if a
 * development database still has them, so rolling this migration back and re-applying it
 * cleans up after the old one. Their values are discarded. The scholarship column goes back to
 * its original name, schlrshp_contact_id, which the current migration leaves alone.
 *
 * down() drops the link tables, so which contacts each record had is lost. Back up before
 * rolling back.
 */
final class Version20261102010000 extends AbstractMigration
{
    /** link table => [record table, record id column, record id type, contact index, record FK, contact FK] */
    private const LINK_TABLES = [
        'program_contact' => ['program_programs', 'program_id', 'INT UNSIGNED', 'IDX_C7881EECE7A1254A', 'FK_C7881EEC3EB8070A', 'FK_C7881EECE7A1254A'],
        'scholarship_contact' => ['scholarships_scholarship', 'scholarship_id', 'INT UNSIGNED', 'IDX_DBA85242E7A1254A', 'FK_DBA8524228722836', 'FK_DBA85242E7A1254A'],
        'map_building_contact' => ['map_building', 'building_id', 'INT', 'IDX_F8020C0AE7A1254A', 'FK_F8020C0A4D2A7E12', 'FK_F8020C0AE7A1254A'],
    ];

    /** Left by the earlier version of this migration: table => [index, foreign key] */
    private const LEGACY_COLUMNS = [
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
        return 'Create the program_contact, scholarship_contact and map_building_contact link tables to ic_contacts';
    }

    public function up(Schema $schema): void
    {
        $this->removeLegacyColumns();

        foreach (self::LINK_TABLES as $table => [$recordTable, $recordColumn, $recordType, $contactIndex, $recordFk, $contactFk]) {
            if ($this->tableExists($table)) {
                $this->warnIf(true, "$table already exists, skipping");
                continue;
            }

            $this->addSql("CREATE TABLE $table (
                $recordColumn $recordType NOT NULL,
                contact_id INT UNSIGNED NOT NULL,
                INDEX $contactIndex (contact_id),
                PRIMARY KEY ($recordColumn, contact_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
            $this->addSql("ALTER TABLE $table ADD CONSTRAINT $recordFk FOREIGN KEY ($recordColumn) REFERENCES $recordTable (id) ON DELETE CASCADE");
            $this->addSql("ALTER TABLE $table ADD CONSTRAINT $contactFk FOREIGN KEY (contact_id) REFERENCES ic_contacts (id)");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::LINK_TABLES) as $table) {
            if ($this->tableExists($table)) {
                $this->addSql("DROP TABLE $table");
            } else {
                $this->warnIf(true, "$table does not exist, skipping");
            }
        }

        $this->removeLegacyColumns();
    }

    /**
     * Drops the contact_id columns the earlier version of this migration added.
     */
    private function removeLegacyColumns(): void
    {
        foreach (self::LEGACY_COLUMNS as $table => [$index, $foreignKey]) {
            if (!$this->columnExists($table, 'contact_id')) {
                continue;
            }

            $this->warnIf(true, "$table.contact_id is left over from an earlier version of this migration, removing it");
            if ($this->foreignKeyExists($table, $foreignKey)) {
                $this->addSql("ALTER TABLE $table DROP FOREIGN KEY $foreignKey");
            }
            if ($this->indexExists($table, $index)) {
                $this->addSql("DROP INDEX $index ON $table");
            }

            if ($table === self::SCHOLARSHIP_TABLE) {
                $this->addSql("ALTER TABLE $table CHANGE contact_id " . self::SCHOLARSHIP_PLACEHOLDER . ' INT DEFAULT NULL');
            } else {
                $this->addSql("ALTER TABLE $table DROP COLUMN contact_id");
            }
        }
    }

    private function tableExists(string $table): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );
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
