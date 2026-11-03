<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103125357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bike (id INT AUTO_INCREMENT NOT NULL, model VARCHAR(100) NOT NULL, year INT NOT NULL, cc INT NOT NULL, plate VARCHAR(50) NOT NULL, mileage INT NOT NULL, vin VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL, client_id INT DEFAULT NULL, make_id INT DEFAULT NULL, INDEX IDX_4CBC378019EB6921 (client_id), INDEX IDX_4CBC3780CFBF73EB (make_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE bike ADD CONSTRAINT FK_4CBC378019EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE bike ADD CONSTRAINT FK_4CBC3780CFBF73EB FOREIGN KEY (make_id) REFERENCES bike_brand (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bike DROP FOREIGN KEY FK_4CBC378019EB6921');
        $this->addSql('ALTER TABLE bike DROP FOREIGN KEY FK_4CBC3780CFBF73EB');
        $this->addSql('DROP TABLE bike');
    }
}
