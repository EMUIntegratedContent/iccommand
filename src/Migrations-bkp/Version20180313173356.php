<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180313173356 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_emergency ADD building_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE map_emergency ADD CONSTRAINT FK_83B556404D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_83B556404D2A7E12 ON map_emergency (building_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_emergency DROP FOREIGN KEY FK_83B556404D2A7E12');
        $this->addSql('DROP INDEX IDX_83B556404D2A7E12 ON map_emergency');
        $this->addSql('ALTER TABLE map_emergency DROP building_id');
    }
}
