<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180323211456 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user CHANGE firstname firstname VARCHAR(255) DEFAULT NULL, CHANGE lastname lastname VARCHAR(255) DEFAULT NULL, CHANGE jobtitle jobtitle VARCHAR(255) DEFAULT NULL, CHANGE department department VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user CHANGE firstname firstname VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE lastname lastname VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE jobtitle jobtitle VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE department department VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE phone phone VARCHAR(16) NOT NULL COLLATE utf8_unicode_ci');
    }
}
