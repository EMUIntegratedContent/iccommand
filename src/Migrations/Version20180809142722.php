<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180809142722 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE map_service_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_service (id INT NOT NULL, type_id INT DEFAULT NULL, building_id INT DEFAULT NULL, INDEX IDX_CEB6628EC54C8C93 (type_id), INDEX IDX_CEB6628E4D2A7E12 (building_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE map_service ADD CONSTRAINT FK_CEB6628EC54C8C93 FOREIGN KEY (type_id) REFERENCES map_service_type (id)');
        $this->addSql('ALTER TABLE map_service ADD CONSTRAINT FK_CEB6628E4D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE map_service ADD CONSTRAINT FK_CEB6628EBF396750 FOREIGN KEY (id) REFERENCES map_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_service DROP FOREIGN KEY FK_CEB6628EC54C8C93');
        $this->addSql('DROP TABLE map_service_type');
        $this->addSql('DROP TABLE map_service');
    }
}
