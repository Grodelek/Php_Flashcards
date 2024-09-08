<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240908212610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE flashcards ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE flashcards ADD CONSTRAINT FK_62A226B5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_62A226B5A76ED395 ON flashcards (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE flashcards DROP FOREIGN KEY FK_62A226B5A76ED395');
        $this->addSql('DROP INDEX IDX_62A226B5A76ED395 ON flashcards');
        $this->addSql('ALTER TABLE flashcards DROP user_id');
    }
}
