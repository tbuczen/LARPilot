<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Per-LARP defaults for call transcripts (summary, Discord channel)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE call_transcript_settings (id UUID NOT NULL, larp_id UUID NOT NULL, summary_enabled BOOLEAN DEFAULT true NOT NULL, discord_channel_id VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F161355C63FF2A01 ON call_transcript_settings (larp_id)');
        $this->addSql("COMMENT ON COLUMN call_transcript_settings.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_transcript_settings.larp_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE call_transcript_settings ADD CONSTRAINT FK_F161355C63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_transcript_settings DROP CONSTRAINT FK_F161355C63FF2A01');
        $this->addSql('DROP TABLE call_transcript_settings');
    }
}
