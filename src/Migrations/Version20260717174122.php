<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717174122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS social_media (
            id INT AUTO_INCREMENT NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            facebook_url VARCHAR(500) DEFAULT NULL, 
            x_url VARCHAR(500) DEFAULT NULL, 
            youtube_url VARCHAR(500) DEFAULT NULL, 
            instagram_url VARCHAR(500) DEFAULT NULL, 
            linkedin_url VARCHAR(500) DEFAULT NULL, 
            tiktok_url VARCHAR(500) DEFAULT NULL, 
            snapchat_url VARCHAR(500) DEFAULT NULL,
            created DATETIME NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            updated DATETIME NOT NULL,
            updated_by VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS social_media');
    }
}
