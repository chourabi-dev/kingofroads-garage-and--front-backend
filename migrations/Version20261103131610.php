<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103131610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bike_report (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, description VARCHAR(255) NOT NULL, bike_id INT DEFAULT NULL, INDEX IDX_A8804F70D5A4816F (bike_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE bike_report ADD CONSTRAINT FK_A8804F70D5A4816F FOREIGN KEY (bike_id) REFERENCES bike (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bike_report DROP FOREIGN KEY FK_A8804F70D5A4816F');
        $this->addSql('DROP TABLE bike_report');
    }
}
