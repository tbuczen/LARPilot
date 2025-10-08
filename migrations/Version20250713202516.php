<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713202516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_application_vote (id UUID NOT NULL, choice_id UUID NOT NULL, user_id UUID NOT NULL, vote INT NOT NULL, justification TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5785315E998666D1 ON larp_application_vote (choice_id)');
        $this->addSql('CREATE INDEX IDX_5785315EA76ED395 ON larp_application_vote (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5785315E998666D1A76ED395 ON larp_application_vote (choice_id, user_id)');
        $this->addSql('COMMENT ON COLUMN larp_application_vote.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application_vote.choice_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application_vote.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_application_vote ADD CONSTRAINT FK_5785315E998666D1 FOREIGN KEY (choice_id) REFERENCES larp_application_choice (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application_vote ADD CONSTRAINT FK_5785315EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_application_vote DROP CONSTRAINT FK_5785315E998666D1');
        $this->addSql('ALTER TABLE larp_application_vote DROP CONSTRAINT FK_5785315EA76ED395');
        $this->addSql('DROP TABLE larp_application_vote');
    }
}
