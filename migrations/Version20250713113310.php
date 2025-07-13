<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713113310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE larp_character ADD larp_participant_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN larp_character.larp_participant_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFA08CBE59 FOREIGN KEY (larp_participant_id) REFERENCES larp_participant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_AFC950DFA08CBE59 ON larp_character (larp_participant_id)');
        $this->addSql('ALTER TABLE larp_participant DROP CONSTRAINT fk_ca1f9ffdb89790d2');
        $this->addSql('DROP INDEX idx_ca1f9ffdb89790d2');
        $this->addSql('ALTER TABLE larp_participant DROP larp_character_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_participant ADD larp_character_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN larp_participant.larp_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_participant ADD CONSTRAINT fk_ca1f9ffdb89790d2 FOREIGN KEY (larp_character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_ca1f9ffdb89790d2 ON larp_participant (larp_character_id)');
        $this->addSql('ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFA08CBE59');
        $this->addSql('DROP INDEX IDX_AFC950DFA08CBE59');
        $this->addSql('ALTER TABLE larp_character DROP larp_participant_id');
    }
}
