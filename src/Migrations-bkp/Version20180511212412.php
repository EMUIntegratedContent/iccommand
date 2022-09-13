<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180511212412 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE multimedia_request_status_note (id INT AUTO_INCREMENT NOT NULL, multimedia_request_id INT DEFAULT NULL, note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, content_changed DATETIME DEFAULT NULL, INDEX IDX_6747C5E791828F74 (multimedia_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE multimedia_request (id INT AUTO_INCREMENT NOT NULL, status_id INT DEFAULT NULL, assignee_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, department VARCHAR(255) DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, content_changed DATETIME DEFAULT NULL, INDEX IDX_869D34B46BF700BD (status_id), INDEX IDX_869D34B459EC7D60 (assignee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE multimedia_request_status (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, status_slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE multimedia_request_status_note ADD CONSTRAINT FK_6747C5E791828F74 FOREIGN KEY (multimedia_request_id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE multimedia_request ADD CONSTRAINT FK_869D34B46BF700BD FOREIGN KEY (status_id) REFERENCES multimedia_request_status (id)');
        $this->addSql('ALTER TABLE multimedia_request ADD CONSTRAINT FK_869D34B459EC7D60 FOREIGN KEY (assignee_id) REFERENCES multimedia_request_assignee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE multimedia_request_status_note DROP FOREIGN KEY FK_6747C5E791828F74');
        $this->addSql('ALTER TABLE multimedia_request DROP FOREIGN KEY FK_869D34B46BF700BD');
        $this->addSql('DROP TABLE multimedia_request_status_note');
        $this->addSql('DROP TABLE multimedia_request');
        $this->addSql('DROP TABLE multimedia_request_status');
    }
}
