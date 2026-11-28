<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces the building-to-advisor link table with one building per advisor:
 *
 * - drops map_building_advisor (buildings no longer list advisors)
 * - adds ic_advisors.building_id, a nullable foreign key to map_building. Deleting a building
 *   sets it back to NULL, so the advisor stays.
 *
 * The old links aren't copied into building_id: "this building lists this advisor" isn't the
 * same fact as "this advisor is in this building", and an advisor could have several. The
 * feature never reached production, so only development databases have links to lose.
 *
 * Index and FK names are the ones Doctrine generates for the IcAdvisor::$building mapping, so
 * schema diffs stay quiet.
 *
 * down() drops building_id (which building each advisor was in is lost) and recreates
 * map_building_advisor empty, as Version20261115000000 left it. Back up before rolling back.
 *
 * Each step checks information_schema first, so it is safe to re-run. Under --dry-run on a
 * database where ic_advisors doesn't exist yet, the column steps are skipped with a warning.
 */
final class Version20261115010000 extends AbstractMigration
{
    private const ADVISORS = 'ic_advisors';
    private const COLUMN = 'building_id';
    private const INDEX = 'IDX_13C0A32B4D2A7E12';
    private const FOREIGN_KEY = 'FK_13C0A32B4D2A7E12';

    /** The link table as Version20261115000000 named it. */
    private const LINK_TABLE = 'map_building_advisor';
    private const LINK_INDEX = 'IDX_ADCD23C666D3AD77';
    private const LINK_BUILDING_FK = 'FK_ADCD23C64D2A7E12';
    private const LINK_ADVISOR_FK = 'FK_ADCD23C666D3AD77';

    public function getDescription(): string
    {
        return 'Drop map_building_advisor and add ic_advisors.building_id (one building per advisor)';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists(self::LINK_TABLE)) {
            $links = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM ' . self::LINK_TABLE);
            $this->warnIf($links > 0, "Dropping $links building-advisor links from " . self::LINK_TABLE);
            $this->addSql('DROP TABLE ' . self::LINK_TABLE);
        }

        if (!$this->tableExists(self::ADVISORS)) {
            $this->warnIf(true, self::ADVISORS . ' does not exist, skipping building_id');
            return;
        }

        if (!$this->columnExists(self::ADVISORS, self::COLUMN)) {
            $this->addSql('ALTER TABLE ' . self::ADVISORS . ' ADD ' . self::COLUMN . ' INT DEFAULT NULL AFTER area');
        }
        if (!$this->indexExists(self::ADVISORS, self::INDEX)) {
            $this->addSql('CREATE INDEX ' . self::INDEX . ' ON ' . self::ADVISORS . ' (' . self::COLUMN . ')');
        }
        if (!$this->foreignKeyExists(self::ADVISORS, self::FOREIGN_KEY)) {
            $this->addSql('ALTER TABLE ' . self::ADVISORS . ' ADD CONSTRAINT ' . self::FOREIGN_KEY
                . ' FOREIGN KEY (' . self::COLUMN . ') REFERENCES map_building (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists(self::ADVISORS)) {
            if ($this->foreignKeyExists(self::ADVISORS, self::FOREIGN_KEY)) {
                $this->addSql('ALTER TABLE ' . self::ADVISORS . ' DROP FOREIGN KEY ' . self::FOREIGN_KEY);
            }
            if ($this->indexExists(self::ADVISORS, self::INDEX)) {
                $this->addSql('DROP INDEX ' . self::INDEX . ' ON ' . self::ADVISORS);
            }
            if ($this->columnExists(self::ADVISORS, self::COLUMN)) {
                $this->addSql('ALTER TABLE ' . self::ADVISORS . ' DROP COLUMN ' . self::COLUMN);
            }
        } else {
            $this->warnIf(true, self::ADVISORS . ' does not exist, skipping building_id');
        }

        if ($this->tableExists(self::LINK_TABLE)) {
            $this->warnIf(true, self::LINK_TABLE . ' already exists, skipping');
            return;
        }

        $this->addSql('CREATE TABLE ' . self::LINK_TABLE . ' (
            building_id INT NOT NULL,
            advisor_id INT UNSIGNED NOT NULL,
            INDEX ' . self::LINK_INDEX . ' (advisor_id),
            PRIMARY KEY (building_id, advisor_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ' . self::LINK_TABLE . ' ADD CONSTRAINT ' . self::LINK_BUILDING_FK
            . ' FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ' . self::LINK_TABLE . ' ADD CONSTRAINT ' . self::LINK_ADVISOR_FK
            . ' FOREIGN KEY (advisor_id) REFERENCES ' . self::ADVISORS . ' (id)');
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
