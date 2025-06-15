<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250615120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add story recruitment and proposal tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE story_recruitment (id UUID NOT NULL, story_object_id UUID NOT NULL, required_number INT NOT NULL, type VARCHAR(255) NOT NULL, notes TEXT DEFAULT NULL, created_by_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EF6E7DBF6CDE5A ON story_recruitment (story_object_id)');
        $this->addSql('COMMENT ON COLUMN story_recruitment.id IS \"(DC2Type:uuid)\"');
        $this->addSql('COMMENT ON COLUMN story_recruitment.story_object_id IS \"(DC2Type:uuid)\"');
        $this->addSql('COMMENT ON COLUMN story_recruitment.created_by_id IS \"(DC2Type:uuid)\"');

        $this->addSql('CREATE TABLE recruitment_proposal (id UUID NOT NULL, recruitment_id UUID NOT NULL, character_id UUID NOT NULL, status VARCHAR(255) NOT NULL, comment TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8DCE6F4BA0D0F5AA ON recruitment_proposal (recruitment_id)');
        $this->addSql('CREATE INDEX IDX_8DCE6F4B1138C7E1 ON recruitment_proposal (character_id)');
        $this->addSql('COMMENT ON COLUMN recruitment_proposal.id IS \"(DC2Type:uuid)\"');
        $this->addSql('COMMENT ON COLUMN recruitment_proposal.recruitment_id IS \"(DC2Type:uuid)\"');
        $this->addSql('COMMENT ON COLUMN recruitment_proposal.character_id IS \"(DC2Type:uuid)\"');

        $this->addSql('ALTER TABLE story_recruitment ADD CONSTRAINT FK_EF6E7DBF6CDE5A FOREIGN KEY (story_object_id) REFERENCES story_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE story_recruitment ADD CONSTRAINT FK_EF6E7DBB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE recruitment_proposal ADD CONSTRAINT FK_8DCE6F4BA0D0F5AA FOREIGN KEY (recruitment_id) REFERENCES story_recruitment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE recruitment_proposal ADD CONSTRAINT FK_8DCE6F4B1138C7E1 FOREIGN KEY (character_id) REFERENCES larp_character (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruitment_proposal DROP CONSTRAINT FK_8DCE6F4BA0D0F5AA');
        $this->addSql('ALTER TABLE recruitment_proposal DROP CONSTRAINT FK_8DCE6F4B1138C7E1');
        $this->addSql('ALTER TABLE story_recruitment DROP CONSTRAINT FK_EF6E7DBF6CDE5A');
        $this->addSql('ALTER TABLE story_recruitment DROP CONSTRAINT FK_EF6E7DBB03A8386');
        $this->addSql('DROP TABLE recruitment_proposal');
        $this->addSql('DROP TABLE story_recruitment');
    }
}
