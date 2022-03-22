<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180514213644 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE photo_request ADD type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE photo_request ADD CONSTRAINT FK_E779812C54C8C93 FOREIGN KEY (type_id) REFERENCES photo_request_type (id)');
        $this->addSql('CREATE INDEX IDX_E779812C54C8C93 ON photo_request (type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE photo_request DROP FOREIGN KEY FK_E779812C54C8C93');
        $this->addSql('DROP INDEX IDX_E779812C54C8C93 ON photo_request');
        $this->addSql('ALTER TABLE photo_request DROP type_id');
    }
}
