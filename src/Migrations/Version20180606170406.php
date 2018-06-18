<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180606170406 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE publication_request_type (id INT AUTO_INCREMENT NOT NULL, request_type VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE publication_request ADD publication_request_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE publication_request ADD CONSTRAINT FK_331E59A3FCD68058 FOREIGN KEY (publication_request_type_id) REFERENCES publication_request_type (id)');
        $this->addSql('CREATE INDEX IDX_331E59A3FCD68058 ON publication_request (publication_request_type_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE publication_request DROP FOREIGN KEY FK_331E59A3FCD68058');
        $this->addSql('DROP TABLE publication_request_type');
        $this->addSql('DROP INDEX IDX_331E59A3FCD68058 ON publication_request');
        $this->addSql('ALTER TABLE publication_request DROP publication_request_type_id');
    }
}
