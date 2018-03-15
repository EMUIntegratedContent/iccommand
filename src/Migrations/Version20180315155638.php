<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180315155638 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mapbuilding_types (mapbuilding_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_DDEA82763B1ECB64 (mapbuilding_id), INDEX IDX_DDEA8276C54C8C93 (type_id), PRIMARY KEY(mapbuilding_id, type_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_building_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA82763B1ECB64 FOREIGN KEY (mapbuilding_id) REFERENCES map_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapbuilding_types ADD CONSTRAINT FK_DDEA8276C54C8C93 FOREIGN KEY (type_id) REFERENCES map_building_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_building ADD hours VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapbuilding_types DROP FOREIGN KEY FK_DDEA8276C54C8C93');
        $this->addSql('DROP TABLE mapbuilding_types');
        $this->addSql('DROP TABLE map_building_type');
        $this->addSql('ALTER TABLE map_building DROP hours');
    }
}
