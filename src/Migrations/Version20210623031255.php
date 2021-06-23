<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210623031255 extends AbstractMigration
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
        $this->addSql('ALTER TABLE directory_departments CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE updated updated DATE NOT NULL, CHANGE department department VARCHAR(255) DEFAULT NULL, CHANGE search_terms search_terms VARCHAR(255) DEFAULT NULL, CHANGE address1 address1 VARCHAR(255) DEFAULT NULL, CHANGE address2 address2 VARCHAR(255) DEFAULT NULL, CHANGE city city VARCHAR(255) DEFAULT NULL, CHANGE state state VARCHAR(255) DEFAULT NULL, CHANGE zip zip VARCHAR(255) DEFAULT NULL, CHANGE on_campus on_campus INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE phone_alt phone_alt VARCHAR(255) DEFAULT NULL, CHANGE fax fax VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE faculty_list faculty_list VARCHAR(255) DEFAULT NULL, CHANGE staff_list staff_list VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX unique_index ON ou_campus_events');
        $this->addSql('ALTER TABLE ou_campus_events CHANGE title title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ou_campus_signups CHANGE full_name full_name VARCHAR(500) NOT NULL, CHANGE emich_email emich_email VARCHAR(355) NOT NULL, CHANGE site site VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE graphic_request (id INT NOT NULL, completion_date DATE NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE graphic_request ADD CONSTRAINT FK_7A34E8F4BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE directory_departments CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE updated updated DATE DEFAULT NULL, CHANGE department department VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, CHANGE search_terms search_terms VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE address1 address1 VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE address2 address2 VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE city city VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE state state VARCHAR(2) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE zip zip VARCHAR(5) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE on_campus on_campus TINYINT(1) DEFAULT \'1\', CHANGE phone phone VARCHAR(12) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE phone_alt phone_alt VARCHAR(12) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE fax fax VARCHAR(12) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE email email VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE website website VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE faculty_list faculty_list VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`, CHANGE staff_list staff_list VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' COLLATE `latin1_swedish_ci`');
        $this->addSql('ALTER TABLE ou_campus_events CHANGE title title VARCHAR(255) CHARACTER SET latin1 DEFAULT \'OU Campus Training\' NOT NULL COLLATE `latin1_swedish_ci`');
        $this->addSql('CREATE UNIQUE INDEX unique_index ON ou_campus_events (date, location)');
        $this->addSql('ALTER TABLE ou_campus_signups CHANGE full_name full_name VARCHAR(500) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, CHANGE emich_email emich_email VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`, CHANGE site site VARCHAR(255) CHARACTER SET latin1 DEFAULT \'\' NOT NULL COLLATE `latin1_swedish_ci`');
    }
}
