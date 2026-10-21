<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Map call recording speakers to their Discord account and LARP participant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording_track ADD discord_user_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording_track ADD participant_id UUID DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN call_recording_track.participant_id IS '(DC2Type:uuid)'");
        $this->addSql('ALTER TABLE call_recording_track ADD CONSTRAINT FK_CFDADF499D1C3019 FOREIGN KEY (participant_id) REFERENCES larp_participant (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_CFDADF499D1C3019 ON call_recording_track (participant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording_track DROP CONSTRAINT FK_CFDADF499D1C3019');
        $this->addSql('DROP INDEX IDX_CFDADF499D1C3019');
        $this->addSql('ALTER TABLE call_recording_track DROP discord_user_id');
        $this->addSql('ALTER TABLE call_recording_track DROP participant_id');
    }
}
