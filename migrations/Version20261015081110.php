<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015081110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store imported call recordings and their speaker tracks (call transcripts)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE call_recording (id UUID NOT NULL, larp_id UUID NOT NULL, knowledge_document_id UUID DEFAULT NULL, created_by_id UUID NOT NULL, title VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, language VARCHAR(10) DEFAULT NULL, recording_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, notes JSON NOT NULL, failure_reason TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_90B117A03B5400DF ON call_recording (knowledge_document_id)');
        $this->addSql('CREATE INDEX IDX_90B117A0B03A8386 ON call_recording (created_by_id)');
        $this->addSql('CREATE INDEX IDX_90B117A063FF2A01 ON call_recording (larp_id)');
        $this->addSql("COMMENT ON COLUMN call_recording.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_recording.larp_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_recording.knowledge_document_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_recording.created_by_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_recording.recording_started_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('CREATE TABLE call_recording_track (id UUID NOT NULL, recording_id UUID NOT NULL, speaker VARCHAR(255) NOT NULL, audio_filename VARCHAR(255) DEFAULT NULL, transcript_filename VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, segments JSON NOT NULL, failure_reason TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CFDADF498CA9A845 ON call_recording_track (recording_id)');
        $this->addSql("COMMENT ON COLUMN call_recording_track.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN call_recording_track.recording_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE call_recording ADD CONSTRAINT FK_90B117A063FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE call_recording ADD CONSTRAINT FK_90B117A03B5400DF FOREIGN KEY (knowledge_document_id) REFERENCES knowledge_document (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE call_recording ADD CONSTRAINT FK_90B117A0B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE call_recording_track ADD CONSTRAINT FK_CFDADF498CA9A845 FOREIGN KEY (recording_id) REFERENCES call_recording (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording_track DROP CONSTRAINT FK_CFDADF498CA9A845');
        $this->addSql('ALTER TABLE call_recording DROP CONSTRAINT FK_90B117A063FF2A01');
        $this->addSql('ALTER TABLE call_recording DROP CONSTRAINT FK_90B117A03B5400DF');
        $this->addSql('ALTER TABLE call_recording DROP CONSTRAINT FK_90B117A0B03A8386');
        $this->addSql('DROP TABLE call_recording_track');
        $this->addSql('DROP TABLE call_recording');
    }
}
