<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180514212516 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE photo_request (id INT NOT NULL, start_date DATETIME NOT NULL, end_time DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, intended_use VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE video_request (id INT NOT NULL, completion_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE photo_request ADD CONSTRAINT FK_E779812BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_request ADD CONSTRAINT FK_DE7A6632BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multimedia_request ADD discr VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE photo_request');
        $this->addSql('DROP TABLE video_request');
        $this->addSql('ALTER TABLE multimedia_request DROP discr');
    }
}
