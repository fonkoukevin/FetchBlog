<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240702204625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD id_role_id INT NOT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E989E8BDC FOREIGN KEY (id_role_id) REFERENCES role (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E989E8BDC ON users (id_role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E989E8BDC');
        $this->addSql('DROP INDEX IDX_1483A5E989E8BDC ON users');
        $this->addSql('ALTER TABLE users DROP id_role_id');
    }
}
