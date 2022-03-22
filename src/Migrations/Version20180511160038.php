<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180511160038 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE multimedia_request_assignee ADD status_id INT DEFAULT NULL, DROP status');
        $this->addSql('ALTER TABLE multimedia_request_assignee ADD CONSTRAINT FK_A495436BF700BD FOREIGN KEY (status_id) REFERENCES multimedia_request_assignee_status (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A495436BF700BD ON multimedia_request_assignee (status_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE multimedia_request_assignee DROP FOREIGN KEY FK_A495436BF700BD');
        $this->addSql('DROP INDEX IDX_A495436BF700BD ON multimedia_request_assignee');
        $this->addSql('ALTER TABLE multimedia_request_assignee ADD status VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, DROP status_id');
    }
}
