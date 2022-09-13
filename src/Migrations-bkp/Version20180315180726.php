<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180315180726 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_building CHANGE hours hours VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE mapbuilding_types DROP FOREIGN KEY FK_DDEA82763B1ECB64');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA82763B1ECB64 FOREIGN KEY (mapbuilding_id) REFERENCES map_building (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_building CHANGE hours hours VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE mapbuilding_types DROP FOREIGN KEY FK_DDEA82763B1ECB64');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA82763B1ECB64 FOREIGN KEY (mapbuilding_id) REFERENCES map_item (id) ON DELETE CASCADE');
    }
}
