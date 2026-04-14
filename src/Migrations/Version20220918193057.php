<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220918193057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS user (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, roles JSON NOT NULL, username JSON NOT NULL, password VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, email VARCHAR(50) NOT NULL, jobtitle VARCHAR(255) DEFAULT NULL, department VARCHAR(255) DEFAULT NULL, phone VARCHAR(16) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D6493DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $fkExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'FK_8D93D6493DA5256D' AND TABLE_NAME = 'user' AND TABLE_SCHEMA = DATABASE()"
        );
        if (!$fkExists) {
            $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6493DA5256D FOREIGN KEY (image_id) REFERENCES user_image (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY IF EXISTS FK_8D93D6493DA5256D');
        $this->addSql('DROP TABLE IF EXISTS user');
    }
}
