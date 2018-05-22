<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180313161050 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mapitems_images (mapitem_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_5B8E818AFD86EE1 (mapitem_id), INDEX IDX_5B8E8183DA5256D (image_id), PRIMARY KEY(mapitem_id, image_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_building (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E818AFD86EE1 FOREIGN KEY (mapitem_id) REFERENCES map_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E8183DA5256D FOREIGN KEY (image_id) REFERENCES mapitem_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_building ADD CONSTRAINT FK_839D57F3BF396750 FOREIGN KEY (id) REFERENCES map_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FBF396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_item ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, ADD content_changed DATETIME DEFAULT NULL, CHANGE slug slug VARCHAR(128) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C33828B989D9B62 ON map_item (slug)');
        $this->addSql('ALTER TABLE mapitem_image ADD CONSTRAINT FK_76EF9CFBF396750 FOREIGN KEY (id) REFERENCES document (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE map_bathroom ADD building_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE map_bathroom ADD CONSTRAINT FK_D2F5F4054D2A7E12 FOREIGN KEY (building_id) REFERENCES map_building (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D2F5F4054D2A7E12 ON map_bathroom (building_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE map_bathroom DROP FOREIGN KEY FK_D2F5F4054D2A7E12');
        $this->addSql('DROP TABLE mapitems_images');
        $this->addSql('DROP TABLE map_building');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP INDEX IDX_D2F5F4054D2A7E12 ON map_bathroom');
        $this->addSql('ALTER TABLE map_bathroom DROP building_id');
        $this->addSql('DROP INDEX UNIQ_8C33828B989D9B62 ON map_item');
        $this->addSql('ALTER TABLE map_item DROP created, DROP updated, DROP content_changed, CHANGE slug slug VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, CHANGE description description LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE mapitem_image DROP FOREIGN KEY FK_76EF9CFBF396750');
    }
}
