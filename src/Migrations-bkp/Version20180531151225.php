<?php declare(strict_types=1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180531151225 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE headshot_request (id INT NOT NULL, time_slot_id INT DEFAULT NULL, INDEX IDX_165524B4D62B0FA (time_slot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE headshot_request ADD CONSTRAINT FK_165524B4D62B0FA FOREIGN KEY (time_slot_id) REFERENCES photo_headshot_date (id)');
        $this->addSql('ALTER TABLE headshot_request ADD CONSTRAINT FK_165524B4BF396750 FOREIGN KEY (id) REFERENCES multimedia_request (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE headshot_request');
    }
}
