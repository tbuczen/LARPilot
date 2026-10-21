<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Copy call transcripts to a Google Doc in a chosen Drive folder';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE call_recording ADD google_doc_status VARCHAR(20) DEFAULT 'skipped' NOT NULL");
        $this->addSql('ALTER TABLE call_recording ADD google_doc_folder_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD google_doc_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD google_doc_url VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD google_doc_failure_reason TEXT DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN call_recording.google_doc_folder_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE call_recording ADD CONSTRAINT FK_90B117A0B5A7BD27 FOREIGN KEY (google_doc_folder_id) REFERENCES shared_file (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_90B117A0B5A7BD27 ON call_recording (google_doc_folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording DROP CONSTRAINT FK_90B117A0B5A7BD27');
        $this->addSql('DROP INDEX IDX_90B117A0B5A7BD27');
        $this->addSql('ALTER TABLE call_recording DROP google_doc_status');
        $this->addSql('ALTER TABLE call_recording DROP google_doc_folder_id');
        $this->addSql('ALTER TABLE call_recording DROP google_doc_id');
        $this->addSql('ALTER TABLE call_recording DROP google_doc_url');
        $this->addSql('ALTER TABLE call_recording DROP google_doc_failure_reason');
    }
}
