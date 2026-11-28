<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames the Contacts app to Advisors in the database:
 *
 * - ic_contacts => ic_advisors
 * - program_contact, scholarship_contact, map_building_contact => program_advisor,
 *   scholarship_advisor, map_building_advisor, with contact_id => advisor_id
 * - their contact_id index and both foreign keys => the names Doctrine generates for the new
 *   mappings, so schema diffs stay quiet
 * - ROLE_CONTACTS_* => ROLE_ADVISORS_* in user.roles
 *
 * Rows, ids and links are kept. down() is the exact reverse, so nothing is lost either way.
 *
 * Each step checks information_schema first, so a run that stopped part way (MariaDB can't roll
 * back schema changes) can be re-run. Under --dry-run on a database that hasn't run
 * Version20261102000000/Version20261102010000 yet, the old tables don't exist and are skipped
 * with a warning.
 *
 * The column is renamed with CHANGE and the index with DROP/ADD (not RENAME COLUMN/RENAME INDEX)
 * so this also runs on MariaDB older than 10.5.
 */
final class Version20261115000000 extends AbstractMigration
{
    /** record => [record table, record id column] */
    private const RECORDS = [
        'program' => ['program_programs', 'program_id'],
        'scholarship' => ['scholarships_scholarship', 'scholarship_id'],
        'building' => ['map_building', 'building_id'],
    ];

    /** The names before the rename. links: record => [link table, link column index, record FK, link FK] */
    private const CONTACTS = [
        'table' => 'ic_contacts',
        'column' => 'contact_id',
        'rolePrefix' => 'ROLE_CONTACTS_',
        'links' => [
            'program' => ['program_contact', 'IDX_C7881EECE7A1254A', 'FK_C7881EEC3EB8070A', 'FK_C7881EECE7A1254A'],
            'scholarship' => ['scholarship_contact', 'IDX_DBA85242E7A1254A', 'FK_DBA8524228722836', 'FK_DBA85242E7A1254A'],
            'building' => ['map_building_contact', 'IDX_F8020C0AE7A1254A', 'FK_F8020C0A4D2A7E12', 'FK_F8020C0AE7A1254A'],
        ],
    ];

    /** The names after the rename, same shape as CONTACTS. */
    private const ADVISORS = [
        'table' => 'ic_advisors',
        'column' => 'advisor_id',
        'rolePrefix' => 'ROLE_ADVISORS_',
        'links' => [
            'program' => ['program_advisor', 'IDX_9247312066D3AD77', 'FK_924731203EB8070A', 'FK_9247312066D3AD77'],
            'scholarship' => ['scholarship_advisor', 'IDX_8E677D8E66D3AD77', 'FK_8E677D8E28722836', 'FK_8E677D8E66D3AD77'],
            'building' => ['map_building_advisor', 'IDX_ADCD23C666D3AD77', 'FK_ADCD23C64D2A7E12', 'FK_ADCD23C666D3AD77'],
        ],
    ];

    public function getDescription(): string
    {
        return 'Rename the Contacts tables, columns and roles to Advisors (ic_contacts => ic_advisors)';
    }

    public function up(Schema $schema): void
    {
        $this->rename(self::CONTACTS, self::ADVISORS);
    }

    public function down(Schema $schema): void
    {
        $this->rename(self::ADVISORS, self::CONTACTS);
    }

    /**
     * Moves the schema and roles from one set of names to the other. All checks read the state
     * before this migration runs, since none of its SQL has executed yet.
     */
    private function rename(array $from, array $to): void
    {
        // Each link table's current name, or no entry if it doesn't exist under either name.
        $links = [];
        foreach (array_keys(self::RECORDS) as $record) {
            $old = $from['links'][$record][0];
            $new = $to['links'][$record][0];
            $this->abortIf(
                $this->tableExists($old) && $this->tableExists($new),
                "Both $old and $new exist. Merge or drop one by hand, then re-run."
            );

            if ($this->tableExists($new)) {
                $links[$record] = $new;
            } elseif ($this->tableExists($old)) {
                $links[$record] = $old;
            } else {
                $this->warnIf(true, "Neither $old nor $new exists, skipping it");
            }
        }

        // 1. Drop the old foreign keys, so the tables can be renamed and re-pointed.
        foreach ($links as $record => $table) {
            [, , $recordFk, $linkFk] = $from['links'][$record];
            foreach ([$recordFk, $linkFk] as $foreignKey) {
                if ($this->foreignKeyExists($table, $foreignKey)) {
                    $this->addSql("ALTER TABLE $table DROP FOREIGN KEY $foreignKey");
                }
            }
        }

        // 2. The shared table.
        $this->abortIf(
            $this->tableExists($from['table']) && $this->tableExists($to['table']),
            "Both {$from['table']} and {$to['table']} exist. Merge or drop one by hand, then re-run."
        );
        if ($this->tableExists($from['table'])) {
            $this->addSql("RENAME TABLE {$from['table']} TO {$to['table']}");
        } elseif (!$this->tableExists($to['table'])) {
            $this->warnIf(true, "Neither {$from['table']} nor {$to['table']} exists, skipping it");
        }

        // 3. Each link table's name, column and index.
        foreach ($links as $record => $table) {
            [, $oldIndex] = $from['links'][$record];
            [$newTable, $newIndex] = $to['links'][$record];

            if ($table !== $newTable) {
                $this->addSql("RENAME TABLE $table TO $newTable");
            }
            if ($this->columnExists($table, $from['column'])) {
                $this->addSql("ALTER TABLE $newTable CHANGE {$from['column']} {$to['column']} INT UNSIGNED NOT NULL");
            }
            if ($this->indexExists($table, $oldIndex)) {
                $this->addSql("ALTER TABLE $newTable DROP INDEX $oldIndex, ADD INDEX $newIndex ({$to['column']})");
            } elseif (!$this->indexExists($table, $newIndex)) {
                $this->addSql("ALTER TABLE $newTable ADD INDEX $newIndex ({$to['column']})");
            }
        }

        // 4. The foreign keys under their new names. The record side cascades; the shared side
        //    has no ON DELETE action, so a linked row can't be deleted.
        foreach ($links as $record => $table) {
            [$recordTable, $recordColumn] = self::RECORDS[$record];
            [$newTable, , $recordFk, $linkFk] = $to['links'][$record];

            if (!$this->foreignKeyExists($table, $recordFk)) {
                $this->addSql("ALTER TABLE $newTable ADD CONSTRAINT $recordFk FOREIGN KEY ($recordColumn) REFERENCES $recordTable (id) ON DELETE CASCADE");
            }
            if (!$this->foreignKeyExists($table, $linkFk)) {
                $this->addSql("ALTER TABLE $newTable ADD CONSTRAINT $linkFk FOREIGN KEY ({$to['column']}) REFERENCES {$to['table']} (id)");
            }
        }

        // 5. Users' roles (a JSON list of strings). Matching the opening quote keeps the
        //    replacement to whole role names.
        $fromRole = '"' . $from['rolePrefix'];
        $this->addSql(
            'UPDATE `user` SET roles = REPLACE(roles, ?, ?) WHERE LOCATE(?, roles) > 0',
            [$fromRole, '"' . $to['rolePrefix'], $fromRole]
        );
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
