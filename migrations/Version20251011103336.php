<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011103336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_map (id UUID NOT NULL, larp_id UUID NOT NULL, created_by_id UUID NOT NULL, name VARCHAR(255) NOT NULL, image_file VARCHAR(255) DEFAULT NULL, grid_rows SMALLINT DEFAULT 10 NOT NULL, grid_columns SMALLINT DEFAULT 10 NOT NULL, grid_opacity NUMERIC(3, 2) DEFAULT \'0.50\' NOT NULL, grid_visible BOOLEAN DEFAULT true NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_88F7B97E63FF2A01 ON game_map (larp_id)');
        $this->addSql('CREATE INDEX IDX_88F7B97EB03A8386 ON game_map (created_by_id)');
        $this->addSql('COMMENT ON COLUMN game_map.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_map.larp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game_map.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE map_location (id UUID NOT NULL, map_id UUID NOT NULL, place_id UUID DEFAULT NULL, created_by_id UUID NOT NULL, name VARCHAR(255) NOT NULL, grid_coordinates JSON NOT NULL, color VARCHAR(7) DEFAULT NULL, capacity SMALLINT DEFAULT NULL, description TEXT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3C6CBFEC53C55F64 ON map_location (map_id)');
        $this->addSql('CREATE INDEX IDX_3C6CBFECDA6A219 ON map_location (place_id)');
        $this->addSql('CREATE INDEX IDX_3C6CBFECB03A8386 ON map_location (created_by_id)');
        $this->addSql('COMMENT ON COLUMN map_location.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN map_location.map_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN map_location.place_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN map_location.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE game_map ADD CONSTRAINT FK_88F7B97E63FF2A01 FOREIGN KEY (larp_id) REFERENCES larp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_map ADD CONSTRAINT FK_88F7B97EB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE map_location ADD CONSTRAINT FK_3C6CBFEC53C55F64 FOREIGN KEY (map_id) REFERENCES game_map (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE map_location ADD CONSTRAINT FK_3C6CBFECDA6A219 FOREIGN KEY (place_id) REFERENCES place (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE map_location ADD CONSTRAINT FK_3C6CBFECB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game_map DROP CONSTRAINT FK_88F7B97E63FF2A01');
        $this->addSql('ALTER TABLE game_map DROP CONSTRAINT FK_88F7B97EB03A8386');
        $this->addSql('ALTER TABLE map_location DROP CONSTRAINT FK_3C6CBFEC53C55F64');
        $this->addSql('ALTER TABLE map_location DROP CONSTRAINT FK_3C6CBFECDA6A219');
        $this->addSql('ALTER TABLE map_location DROP CONSTRAINT FK_3C6CBFECB03A8386');
        $this->addSql('DROP TABLE game_map');
        $this->addSql('DROP TABLE map_location');
    }
}
