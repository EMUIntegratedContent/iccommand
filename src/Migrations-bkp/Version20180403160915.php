<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180403160915 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE map_dining (id INT NOT NULL, building_id INT DEFAULT NULL, hours LONGTEXT DEFAULT NULL, INDEX IDX_4656E0A24D2A7E12 (building_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE map_dining ADD CONSTRAINT FK_4656E0A24D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE map_dining ADD CONSTRAINT FK_4656E0A2BF396750 FOREIGN KEY (id) REFERENCES map_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE map_dining');
    }
}
