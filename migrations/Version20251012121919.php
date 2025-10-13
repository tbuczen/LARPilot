<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012121919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE planning_resource (id UUID NOT NULL, larp_id UUID NOT NULL, character_id UUID DEFAULT NULL, item_id UUID DEFAULT NULL, participant_id UUID DEFAULT NULL, created_by_id UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, description TEXT DEFAULT NULL, quantity SMALLINT DEFAULT 1 NOT NULL, shareable BOOLEAN DEFAULT false NOT NULL, available_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, available_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8B94A2BB63FF2A01 ON planning_resource (larp_id)');
        $this->addSql('CREATE INDEX IDX_8B94A2BB1136BE75 ON planning_resource (character_id)');
        $this->addSql('CREATE INDEX IDX_8B94A2BB126F525E ON planning_resource (item_id)');
        $this->addSql('CREATE INDEX IDX_8B94A2BB9D1C3019 ON planning_resource (participant_id)');
        $this->addSql('CREATE INDEX IDX_8B94A2BBB03A8386 ON planning_resource (created_by_id)');
        $this->addSql('COMMENT ON COLUMN planning_resource.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN planning_resource.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN planning_resource.character_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN planning_resource.item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN planning_resource.participant_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN planning_resource.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE resource_booking (id UUID NOT NULL, scheduled_event_id UUID NOT NULL, resource_id UUID NOT NULL, quantity_needed SMALLINT DEFAULT 1 NOT NULL, required BOOLEAN DEFAULT true NOT NULL, notes TEXT DEFAULT NULL, status VARCHAR(50) DEFAULT \'pending\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BDAC0A3622AA54 ON resource_booking (scheduled_event_id)');
        $this->addSql('CREATE INDEX IDX_BDAC0A3689329D25 ON resource_booking (resource_id)');
        $this->addSql('COMMENT ON COLUMN resource_booking.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resource_booking.scheduled_event_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resource_booking.resource_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE scheduled_event (id UUID NOT NULL, larp_id UUID NOT NULL, event_id UUID DEFAULT NULL, quest_id UUID DEFAULT NULL, thread_id UUID DEFAULT NULL, location_id UUID DEFAULT NULL, created_by_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, setup_minutes SMALLINT DEFAULT 0 NOT NULL, cleanup_minutes SMALLINT DEFAULT 0 NOT NULL, status VARCHAR(50) DEFAULT \'draft\' NOT NULL, organizer_notes TEXT DEFAULT NULL, visible_to_players BOOLEAN DEFAULT true NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_79D3538C63FF2A01 ON scheduled_event (larp_id)');
        $this->addSql('CREATE INDEX IDX_79D3538C71F7E88B ON scheduled_event (event_id)');
        $this->addSql('CREATE INDEX IDX_79D3538C209E9EF4 ON scheduled_event (quest_id)');
        $this->addSql('CREATE INDEX IDX_79D3538CE2904019 ON scheduled_event (thread_id)');
        $this->addSql('CREATE INDEX IDX_79D3538C64D218E ON scheduled_event (location_id)');
        $this->addSql('CREATE INDEX IDX_79D3538CB03A8386 ON scheduled_event (created_by_id)');
        $this->addSql('COMMENT ON COLUMN scheduled_event.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.event_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.quest_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.thread_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.location_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE scheduled_event_conflict (id UUID NOT NULL, event1_id UUID NOT NULL, event2_id UUID NOT NULL, resolved_by_id UUID DEFAULT NULL, type VARCHAR(50) NOT NULL, severity VARCHAR(50) NOT NULL, description TEXT NOT NULL, resolution TEXT DEFAULT NULL, resolved BOOLEAN DEFAULT false NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8E749A60AC7780D3 ON scheduled_event_conflict (event1_id)');
        $this->addSql('CREATE INDEX IDX_8E749A60BEC22F3D ON scheduled_event_conflict (event2_id)');
        $this->addSql('CREATE INDEX IDX_8E749A606713A32B ON scheduled_event_conflict (resolved_by_id)');
        $this->addSql('COMMENT ON COLUMN scheduled_event_conflict.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event_conflict.event1_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event_conflict.event2_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN scheduled_event_conflict.resolved_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT FK_8B94A2BB63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT FK_8B94A2BB1136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT FK_8B94A2BB126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT FK_8B94A2BB9D1C3019 FOREIGN KEY (participant_id) REFERENCES larp_participant (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE planning_resource ADD CONSTRAINT FK_8B94A2BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resource_booking ADD CONSTRAINT FK_BDAC0A3622AA54 FOREIGN KEY (scheduled_event_id) REFERENCES scheduled_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resource_booking ADD CONSTRAINT FK_BDAC0A3689329D25 FOREIGN KEY (resource_id) REFERENCES planning_resource (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538C63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538C209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538CE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538C64D218E FOREIGN KEY (location_id) REFERENCES map_location (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event ADD CONSTRAINT FK_79D3538CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event_conflict ADD CONSTRAINT FK_8E749A60AC7780D3 FOREIGN KEY (event1_id) REFERENCES scheduled_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event_conflict ADD CONSTRAINT FK_8E749A60BEC22F3D FOREIGN KEY (event2_id) REFERENCES scheduled_event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE scheduled_event_conflict ADD CONSTRAINT FK_8E749A606713A32B FOREIGN KEY (resolved_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT FK_8B94A2BB63FF2A01');
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT FK_8B94A2BB1136BE75');
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT FK_8B94A2BB126F525E');
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT FK_8B94A2BB9D1C3019');
        $this->addSql('ALTER TABLE planning_resource DROP CONSTRAINT FK_8B94A2BBB03A8386');
        $this->addSql('ALTER TABLE resource_booking DROP CONSTRAINT FK_BDAC0A3622AA54');
        $this->addSql('ALTER TABLE resource_booking DROP CONSTRAINT FK_BDAC0A3689329D25');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538C63FF2A01');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538C71F7E88B');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538C209E9EF4');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538CE2904019');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538C64D218E');
        $this->addSql('ALTER TABLE scheduled_event DROP CONSTRAINT FK_79D3538CB03A8386');
        $this->addSql('ALTER TABLE scheduled_event_conflict DROP CONSTRAINT FK_8E749A60AC7780D3');
        $this->addSql('ALTER TABLE scheduled_event_conflict DROP CONSTRAINT FK_8E749A60BEC22F3D');
        $this->addSql('ALTER TABLE scheduled_event_conflict DROP CONSTRAINT FK_8E749A606713A32B');
        $this->addSql('DROP TABLE planning_resource');
        $this->addSql('DROP TABLE resource_booking');
        $this->addSql('DROP TABLE scheduled_event');
        $this->addSql('DROP TABLE scheduled_event_conflict');
    }
}
