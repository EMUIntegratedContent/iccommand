<?php declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180706142321 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
//
//        $this->addSql('ALTER TABLE map_item ADD alias LONGTEXT DEFAULT NULL');
//        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C33828BE16C6B94 ON map_item (alias)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
//        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
//
//        $this->addSql('DROP INDEX UNIQ_8C33828BE16C6B94 ON map_item');
//        $this->addSql('ALTER TABLE map_item DROP alias');
    }
}
