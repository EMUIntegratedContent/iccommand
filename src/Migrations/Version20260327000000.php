<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create CAS application tables: cas_cycle and cas_link.
 */
final class Version20260327000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cas_cycle and cas_link tables for CAS App Links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS cas_cycle (
            id INT AUTO_INCREMENT NOT NULL,
            cycle_name VARCHAR(255) NOT NULL,
            current TINYINT(1) NOT NULL DEFAULT 0,
            created DATETIME NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            updated DATETIME NOT NULL,
            updated_by VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS cas_link (
            id INT AUTO_INCREMENT NOT NULL,
            cycle_id INT NOT NULL,
            program_id INT DEFAULT NULL,
            degree_name VARCHAR(255) NOT NULL,
            link VARCHAR(500) NOT NULL,
            created DATETIME NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            updated DATETIME NOT NULL,
            updated_by VARCHAR(255) NOT NULL,
            INDEX IDX_cas_link_cycle (cycle_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_cas_link_cycle FOREIGN KEY (cycle_id) REFERENCES cas_cycle (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cas_link');
        $this->addSql('DROP TABLE cas_cycle');
    }
}
