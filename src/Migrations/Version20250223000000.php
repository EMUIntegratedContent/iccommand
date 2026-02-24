<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add program_id foreign key to program_websites (programs schema).
 * Run with: php bin/console doctrine:migrations:migrate --conn=programs
 */
final class Version20250223000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add program_id column and FK to program_websites';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programs.program_websites ADD program_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_program_websites_program_id ON programs.program_websites (program_id)');
        $this->addSql('ALTER TABLE programs.program_websites ADD CONSTRAINT FK_program_websites_program FOREIGN KEY (program_id) REFERENCES programs.program_programs (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE programs.program_websites DROP FOREIGN KEY FK_program_websites_program');
        $this->addSql('DROP INDEX UNIQ_program_websites_program_id ON programs.program_websites');
        $this->addSql('ALTER TABLE programs.program_websites DROP program_id');
    }
}
