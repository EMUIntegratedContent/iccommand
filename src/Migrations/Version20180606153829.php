<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180606153829 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE publication_request (id INT NOT NULL, completion_date DATE NOT NULL, intended_use VARCHAR(20) NOT NULL, is_photography_required TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE publication_request ADD CONSTRAINT FK_331E59A3BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE graphic_request');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE graphic_request (id INT NOT NULL, completion_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE graphic_request ADD CONSTRAINT FK_7A34E8F4BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE publication_request');
    }
}
