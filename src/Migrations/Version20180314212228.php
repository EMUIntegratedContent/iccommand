<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180314212228 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_exhibit DROP FOREIGN KEY FK_CBD0354C4D2A7E12');
        $this->addSql('ALTER TABLE map_exhibit ADD CONSTRAINT FK_CBD0354C4D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE map_emergency DROP FOREIGN KEY FK_83B556404D2A7E12');
        $this->addSql('ALTER TABLE map_emergency ADD CONSTRAINT FK_83B556404D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_emergency DROP FOREIGN KEY FK_83B556404D2A7E12');
        $this->addSql('ALTER TABLE map_emergency ADD CONSTRAINT FK_83B556404D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_exhibit DROP FOREIGN KEY FK_CBD0354C4D2A7E12');
        $this->addSql('ALTER TABLE map_exhibit ADD CONSTRAINT FK_CBD0354C4D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE CASCADE');
    }
}
