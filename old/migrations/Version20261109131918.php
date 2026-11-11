<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109131918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE maintenance_line (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(150) NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 3) NOT NULL, maintenance_id INT NOT NULL, action_id INT DEFAULT NULL, INDEX IDX_1E07D43AF6C202BC (maintenance_id), INDEX IDX_1E07D43A9D32F035 (action_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE maintenance_line ADD CONSTRAINT FK_1E07D43AF6C202BC FOREIGN KEY (maintenance_id) REFERENCES maintenance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maintenance_line ADD CONSTRAINT FK_1E07D43A9D32F035 FOREIGN KEY (action_id) REFERENCES maintenance_action (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE maintenance_service DROP FOREIGN KEY FK_986BDF40F6C202BC');
        $this->addSql('ALTER TABLE maintenance_service DROP FOREIGN KEY FK_986BDF405FA33B08');
        $this->addSql('DROP TABLE job_type');
        $this->addSql('DROP TABLE maintenance_service');
        $this->addSql('ALTER TABLE maintenance DROP title, DROP mileage, DROP vat_rate, CHANGE description note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_B122168EA750E8 (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE maintenance_service (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, maintenance_id INT NOT NULL, job_type_id INT NOT NULL, unit_price NUMERIC(10, 3) DEFAULT \'0.000\' NOT NULL, INDEX IDX_986BDF40F6C202BC (maintenance_id), INDEX IDX_986BDF405FA33B08 (job_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE maintenance_service ADD CONSTRAINT FK_986BDF40F6C202BC FOREIGN KEY (maintenance_id) REFERENCES maintenance (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE maintenance_service ADD CONSTRAINT FK_986BDF405FA33B08 FOREIGN KEY (job_type_id) REFERENCES job_type (id)');
        $this->addSql('ALTER TABLE maintenance_line DROP FOREIGN KEY FK_1E07D43AF6C202BC');
        $this->addSql('ALTER TABLE maintenance_line DROP FOREIGN KEY FK_1E07D43A9D32F035');
        $this->addSql('DROP TABLE maintenance_line');
        $this->addSql('ALTER TABLE maintenance ADD title VARCHAR(255) NOT NULL, ADD mileage INT NOT NULL, ADD vat_rate NUMERIC(5, 2) DEFAULT \'19.00\' NOT NULL, CHANGE note description LONGTEXT DEFAULT NULL');
    }
}
