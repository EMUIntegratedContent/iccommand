<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180404192747 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapparking_types DROP FOREIGN KEY FK_522AD281C54C8C93');
        $this->addSql('ALTER TABLE mapparking_types DROP FOREIGN KEY FK_522AD281E7E3549D');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281C54C8C93 FOREIGN KEY (type_id) REFERENCES map_parking_type (id)');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281E7E3549D FOREIGN KEY (mapparking_id) REFERENCES map_parking (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapparking_types DROP FOREIGN KEY FK_522AD281E7E3549D');
        $this->addSql('ALTER TABLE mapparking_types DROP FOREIGN KEY FK_522AD281C54C8C93');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281E7E3549D FOREIGN KEY (mapparking_id) REFERENCES map_parking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281C54C8C93 FOREIGN KEY (type_id) REFERENCES map_parking_type (id) ON DELETE CASCADE');
    }
}
