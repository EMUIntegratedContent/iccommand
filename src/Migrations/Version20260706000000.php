<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create Scholarships module tables: scholarships_scholarship and
 * scholarships_scholarship_program (many-to-many link to program_programs).
 */
final class Version20260706000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create scholarships_scholarship and scholarships_scholarship_program tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_scholarship (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            schlrshp_title VARCHAR(255) NOT NULL,
            schlrshp_active TINYINT(1) NOT NULL DEFAULT 0,
            schlrshp_gpa DECIMAL(3,2) DEFAULT NULL,
            schlrshp_url VARCHAR(255) DEFAULT NULL,
            schlrshp_description TEXT DEFAULT NULL,
            schlrshp_apply_date DATE DEFAULT NULL,
            schlrshp_exp_date DATE DEFAULT NULL,
            schlrshp_county VARCHAR(160) DEFAULT NULL,
            schlrshp_city VARCHAR(160) DEFAULT NULL,
            schlrshp_state VARCHAR(160) DEFAULT NULL,
            schlrshp_high_school VARCHAR(255) DEFAULT NULL,
            schlrshp_standing_class VARCHAR(255) DEFAULT NULL,
            schlrshp_enrollment VARCHAR(255) DEFAULT NULL,
            schlrshp_gender VARCHAR(10) DEFAULT NULL,
            schlrshp_ethnicity VARCHAR(255) DEFAULT NULL,
            schlrshp_fafsa VARCHAR(15) DEFAULT NULL,
            schlrshp_is_parent VARCHAR(255) DEFAULT NULL,
            schlrshp_is_bilingual VARCHAR(255) DEFAULT NULL,
            schlrshp_organizations VARCHAR(255) DEFAULT NULL,
            schlrshp_keywords VARCHAR(255) DEFAULT NULL,
            schlrshp_housing VARCHAR(4) DEFAULT NULL,
            schlrshp_app_proc MEDIUMTEXT DEFAULT NULL,
            schlrshp_amount VARCHAR(255) DEFAULT NULL,
            schlrshp_contact MEDIUMTEXT DEFAULT NULL,
            schlrshp_contact_id INT DEFAULT NULL,
            created DATETIME NOT NULL,
            created_by VARCHAR(255) NOT NULL,
            updated DATETIME NOT NULL,
            updated_by VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS scholarships_scholarship_program (
            scholarship_id INT UNSIGNED NOT NULL,
            program_id INT UNSIGNED NOT NULL,
            notes VARCHAR(255) DEFAULT NULL,
            INDEX IDX_ssp_program (program_id),
            PRIMARY KEY(scholarship_id, program_id),
            CONSTRAINT FK_ssp_scholarship FOREIGN KEY (scholarship_id) REFERENCES scholarships_scholarship (id) ON DELETE CASCADE,
            CONSTRAINT FK_ssp_program FOREIGN KEY (program_id) REFERENCES program_programs (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE scholarships_scholarship_program');
        $this->addSql('DROP TABLE scholarships_scholarship');
    }
}
