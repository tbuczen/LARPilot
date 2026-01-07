<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224083715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create staff_position table for tracking staff member positions on maps';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE staff_position (id UUID NOT NULL, participant_id UUID NOT NULL, map_id UUID NOT NULL, grid_cell VARCHAR(10) NOT NULL, status_note VARCHAR(255) DEFAULT NULL, position_updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6472445B9D1C3019 ON staff_position (participant_id)');
        $this->addSql('CREATE INDEX IDX_6472445B53C55F64 ON staff_position (map_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_participant_map ON staff_position (participant_id, map_id)');
        $this->addSql('COMMENT ON COLUMN staff_position.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN staff_position.participant_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN staff_position.map_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE staff_position ADD CONSTRAINT FK_6472445B9D1C3019 FOREIGN KEY (participant_id) REFERENCES larp_participant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE staff_position ADD CONSTRAINT FK_6472445B53C55F64 FOREIGN KEY (map_id) REFERENCES game_map (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff_position DROP CONSTRAINT FK_6472445B9D1C3019');
        $this->addSql('ALTER TABLE staff_position DROP CONSTRAINT FK_6472445B53C55F64');
        $this->addSql('DROP TABLE staff_position');
    }
}
