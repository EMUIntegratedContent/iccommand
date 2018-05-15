<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180515174346 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE photo_request DROP FOREIGN KEY FK_E779812C54C8C93');
        $this->addSql('DROP INDEX IDX_E779812C54C8C93 ON photo_request');
        $this->addSql('ALTER TABLE photo_request CHANGE type_id photo_request_type_id INT DEFAULT NULL, CHANGE start_date start_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE photo_request ADD CONSTRAINT FK_E779812FAAAE0FB FOREIGN KEY (photo_request_type_id) REFERENCES photo_request_type (id)');
        $this->addSql('CREATE INDEX IDX_E779812FAAAE0FB ON photo_request (photo_request_type_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE photo_request DROP FOREIGN KEY FK_E779812FAAAE0FB');
        $this->addSql('DROP INDEX IDX_E779812FAAAE0FB ON photo_request');
        $this->addSql('ALTER TABLE photo_request CHANGE photo_request_type_id type_id INT DEFAULT NULL, CHANGE start_time start_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE photo_request ADD CONSTRAINT FK_E779812C54C8C93 FOREIGN KEY (type_id) REFERENCES photo_request_type (id)');
        $this->addSql('CREATE INDEX IDX_E779812C54C8C93 ON photo_request (type_id)');
    }
}
