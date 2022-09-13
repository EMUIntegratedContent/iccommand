<?php declare(strict_types = 1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180313173014 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE map_emergency (id INT NOT NULL, type_id INT DEFAULT NULL, INDEX IDX_83B55640C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_emergency_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE map_emergency ADD CONSTRAINT FK_83B55640C54C8C93 FOREIGN KEY (type_id) REFERENCES map_emergency_type (id)');
        $this->addSql('ALTER TABLE map_emergency ADD CONSTRAINT FK_83B55640BF396750 FOREIGN KEY (id) REFERENCES map_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_emergency DROP FOREIGN KEY FK_83B55640C54C8C93');
        $this->addSql('DROP TABLE map_emergency');
        $this->addSql('DROP TABLE map_emergency_type');
    }
}
