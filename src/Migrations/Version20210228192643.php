<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210228192643 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE graphic_request');
        $this->addSql('DROP TABLE ou_campus_events');
        $this->addSql('ALTER TABLE ou_campus_signups CHANGE full_name full_name VARCHAR(500) NOT NULL, CHANGE emich_email emich_email VARCHAR(355) NOT NULL, CHANGE site site VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE graphic_request (id INT NOT NULL, completion_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE ou_campus_events (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, location VARCHAR(128) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, title VARCHAR(255) CHARACTER SET latin1 DEFAULT \'OU Campus Training\' NOT NULL COLLATE `latin1_swedish_ci`, UNIQUE INDEX unique_index (date, location), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE graphic_request ADD CONSTRAINT FK_7A34E8F4BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ou_campus_signups CHANGE full_name full_name VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, CHANGE emich_email emich_email VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, CHANGE site site VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`');
    }
}
