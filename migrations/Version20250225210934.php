<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250225210934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, location VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN larp.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_character (id UUID NOT NULL, larp_id UUID NOT NULL, previous_character_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, post_larp_fate TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AFC950DF63FF2A01 ON larp_character (larp_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AFC950DFBCE3FC38 ON larp_character (previous_character_id)');
        $this->addSql('COMMENT ON COLUMN larp_character.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_character.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_character.previous_character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_character_submission (id UUID NOT NULL, larp_id UUID NOT NULL, user_id UUID NOT NULL, status VARCHAR(50) NOT NULL, notes TEXT DEFAULT NULL, contact_email VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FAF4708D63FF2A01 ON larp_character_submission (larp_id)');
        $this->addSql('CREATE INDEX IDX_FAF4708DA76ED395 ON larp_character_submission (user_id)');
        $this->addSql('COMMENT ON COLUMN larp_character_submission.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_character_submission.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_character_submission.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_faction (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN larp_faction.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_faction_larp (larp_faction_id UUID NOT NULL, larp_id UUID NOT NULL, PRIMARY KEY(larp_faction_id, larp_id))');
        $this->addSql('CREATE INDEX IDX_5CD0773A73AC70CA ON larp_faction_larp (larp_faction_id)');
        $this->addSql('CREATE INDEX IDX_5CD0773A63FF2A01 ON larp_faction_larp (larp_id)');
        $this->addSql('COMMENT ON COLUMN larp_faction_larp.larp_faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_faction_larp.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_participant (id UUID NOT NULL, user_id UUID NOT NULL, larp_id UUID NOT NULL, faction_id UUID DEFAULT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CA1F9FFDA76ED395 ON larp_participant (user_id)');
        $this->addSql('CREATE INDEX IDX_CA1F9FFD63FF2A01 ON larp_participant (larp_id)');
        $this->addSql('CREATE INDEX IDX_CA1F9FFD4448F8DA ON larp_participant (faction_id)');
        $this->addSql('COMMENT ON COLUMN larp_participant.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_participant.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_participant.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_participant.faction_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, username VARCHAR(180) NOT NULL, contact_email VARCHAR(180) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON "user" (username)');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE user_social_account (id UUID NOT NULL, user_id UUID NOT NULL, provider VARCHAR(255) NOT NULL, provider_user_id VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_99C85C60A76ED395 ON user_social_account (user_id)');
        $this->addSql('COMMENT ON COLUMN user_social_account.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_social_account.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_social_account.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DF63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_character ADD CONSTRAINT FK_AFC950DFBCE3FC38 FOREIGN KEY (previous_character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708D63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_character_submission ADD CONSTRAINT FK_FAF4708DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_faction_larp ADD CONSTRAINT FK_5CD0773A73AC70CA FOREIGN KEY (larp_faction_id) REFERENCES larp_faction (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_faction_larp ADD CONSTRAINT FK_5CD0773A63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFDA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFD63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_participant ADD CONSTRAINT FK_CA1F9FFD4448F8DA FOREIGN KEY (faction_id) REFERENCES larp_faction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_social_account ADD CONSTRAINT FK_99C85C60A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DF63FF2A01');
        $this->addSql('ALTER TABLE larp_character DROP CONSTRAINT FK_AFC950DFBCE3FC38');
        $this->addSql('ALTER TABLE larp_character_submission DROP CONSTRAINT FK_FAF4708D63FF2A01');
        $this->addSql('ALTER TABLE larp_character_submission DROP CONSTRAINT FK_FAF4708DA76ED395');
        $this->addSql('ALTER TABLE larp_faction_larp DROP CONSTRAINT FK_5CD0773A73AC70CA');
        $this->addSql('ALTER TABLE larp_faction_larp DROP CONSTRAINT FK_5CD0773A63FF2A01');
        $this->addSql('ALTER TABLE larp_participant DROP CONSTRAINT FK_CA1F9FFDA76ED395');
        $this->addSql('ALTER TABLE larp_participant DROP CONSTRAINT FK_CA1F9FFD63FF2A01');
        $this->addSql('ALTER TABLE larp_participant DROP CONSTRAINT FK_CA1F9FFD4448F8DA');
        $this->addSql('ALTER TABLE user_social_account DROP CONSTRAINT FK_99C85C60A76ED395');
        $this->addSql('DROP TABLE larp');
        $this->addSql('DROP TABLE larp_character');
        $this->addSql('DROP TABLE larp_character_submission');
        $this->addSql('DROP TABLE larp_faction');
        $this->addSql('DROP TABLE larp_faction_larp');
        $this->addSql('DROP TABLE larp_participant');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_social_account');
    }
}
