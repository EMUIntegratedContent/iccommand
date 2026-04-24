<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_public column to gradcas_cycle table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradcas_cycle ADD is_public TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradcas_cycle DROP COLUMN is_public');
    }
}
