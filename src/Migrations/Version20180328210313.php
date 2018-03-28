<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180328210313 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE mapbuilding_types');
        $this->addSql('ALTER TABLE map_building ADD buildingtype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE map_building ADD CONSTRAINT FK_839D57F32CC9B1FB FOREIGN KEY (buildingtype_id) REFERENCES map_building_type (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_839D57F32CC9B1FB ON map_building (buildingtype_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mapbuilding_types (mapbuilding_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_DDEA82763B1ECB64 (mapbuilding_id), INDEX IDX_DDEA8276C54C8C93 (type_id), PRIMARY KEY(mapbuilding_id, type_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA82763B1ECB64 FOREIGN KEY (mapbuilding_id) REFERENCES map_building (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA8276C54C8C93 FOREIGN KEY (type_id) REFERENCES map_building_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_building DROP FOREIGN KEY FK_839D57F32CC9B1FB');
        $this->addSql('DROP INDEX IDX_839D57F32CC9B1FB ON map_building');
        $this->addSql('ALTER TABLE map_building DROP buildingtype_id');
    }
}
