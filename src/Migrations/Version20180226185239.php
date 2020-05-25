<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180226185239 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_item CHANGE latitude_illustration latitude_illustration NUMERIC(10, 7) DEFAULT NULL, CHANGE longitude_illustration longitude_illustration NUMERIC(10, 7) DEFAULT NULL, CHANGE latitude_satellite latitude_satellite NUMERIC(10, 7) DEFAULT NULL, CHANGE longitude_satellite longitude_satellite NUMERIC(10, 7) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_item CHANGE latitude_illustration latitude_illustration NUMERIC(10, 7) NOT NULL, CHANGE longitude_illustration longitude_illustration NUMERIC(10, 7) NOT NULL, CHANGE latitude_satellite latitude_satellite NUMERIC(10, 7) NOT NULL, CHANGE longitude_satellite longitude_satellite NUMERIC(10, 7) NOT NULL');
    }
}
