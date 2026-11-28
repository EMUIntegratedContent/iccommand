<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Widens ic_colleges.college from the legacy VARCHAR(50) to VARCHAR(100) so longer college
 * names can be entered from the admin Manage Colleges screen.
 */
final class Version20261009000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen ic_colleges.college to VARCHAR(100)';
    }

    public function up(Schema $schema): void
    {
        if ($this->collegeLength() === 100) {
            $this->warnIf(true, 'ic_colleges.college is already VARCHAR(100), skipping');
            return;
        }
        $this->addSql("ALTER TABLE ic_colleges MODIFY college VARCHAR(100) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        if ($this->collegeLength() === 50) {
            $this->warnIf(true, 'ic_colleges.college is already VARCHAR(50), skipping');
            return;
        }
        $this->addSql("ALTER TABLE ic_colleges MODIFY college VARCHAR(50) NOT NULL DEFAULT ''");
    }

    private function collegeLength(): ?int
    {
        $length = $this->connection->fetchOne(
            "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ic_colleges'
               AND COLUMN_NAME = 'college'"
        );
        return $length === false ? null : (int)$length;
    }
}
