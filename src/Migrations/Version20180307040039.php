<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180307040039 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapitems_images DROP FOREIGN KEY FK_5B8E8183DA5256D');
        $this->addSql('ALTER TABLE mapitems_images DROP FOREIGN KEY FK_5B8E818AFD86EE1');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E8183DA5256D FOREIGN KEY (image_id) REFERENCES mapitem_image (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E818AFD86EE1 FOREIGN KEY (mapitem_id) REFERENCES map_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mapitems_images DROP FOREIGN KEY FK_5B8E818AFD86EE1');
        $this->addSql('ALTER TABLE mapitems_images DROP FOREIGN KEY FK_5B8E8183DA5256D');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E818AFD86EE1 FOREIGN KEY (mapitem_id) REFERENCES map_item (id)');
        $this->addSql('ALTER TABLE mapitems_images ADD CONSTRAINT FK_5B8E8183DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
    }
}
