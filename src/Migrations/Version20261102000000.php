<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates ic_contacts, the contacts shared across IC Command apps. Version20261102010000 adds
 * the contact_id columns that point at it.
 *
 * down() drops the table and its rows, so back up before rolling back. MariaDB refuses the
 * drop while any contact_id FK still references the table.
 */
final class Version20261102000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ic_contacts';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists()) {
            $this->warnIf(true, 'ic_contacts already exists, skipping');
            return;
        }

        $this->addSql("CREATE TABLE ic_contacts (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            type VARCHAR(20) NOT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            organization VARCHAR(255) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            room VARCHAR(255) DEFAULT NULL,
            area VARCHAR(255) DEFAULT NULL,
            url VARCHAR(1024) DEFAULT NULL,
            forager_url VARCHAR(1024) DEFAULT NULL,
            image_url VARCHAR(1024) DEFAULT NULL,
            created DATETIME NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            updated DATETIME NOT NULL,
            updated_by VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists()) {
            $this->warnIf(true, 'ic_contacts does not exist, skipping');
            return;
        }

        $this->addSql('DROP TABLE ic_contacts');
    }

    private function tableExists(): bool
    {
        return (bool)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ic_contacts'"
        );
    }
}
