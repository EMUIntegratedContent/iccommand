<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180315184914 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mapparking_types (mapparking_id INT NOT NULL, type_id INT NOT NULL, INDEX IDX_522AD281E7E3549D (mapparking_id), INDEX IDX_522AD281C54C8C93 (type_id), PRIMARY KEY(mapparking_id, type_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_parking_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281E7E3549D FOREIGN KEY (mapparking_id) REFERENCES map_parking (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapparking_types ADD CONSTRAINT FK_522AD281C54C8C93 FOREIGN KEY (type_id) REFERENCES map_parking_type (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapparking_types DROP FOREIGN KEY FK_522AD281C54C8C93');
        $this->addSql('DROP TABLE mapparking_types');
        $this->addSql('DROP TABLE map_parking_type');
    }
}
