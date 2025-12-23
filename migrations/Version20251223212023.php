<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223212023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add map_location_tags join table for ManyToMany with Tag, add position/shape to MapLocation, add created_by to Comment';
    }

    public function up(Schema $schema): void
    {
        // Create join table for MapLocation <-> Tag ManyToMany relationship
        $this->addSql('CREATE TABLE map_location_tags (map_location_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(map_location_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_A892DB94E1E073CC ON map_location_tags (map_location_id)');
        $this->addSql('CREATE INDEX IDX_A892DB94BAD26311 ON map_location_tags (tag_id)');
        $this->addSql('COMMENT ON COLUMN map_location_tags.map_location_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN map_location_tags.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE map_location_tags ADD CONSTRAINT FK_A892DB94E1E073CC FOREIGN KEY (map_location_id) REFERENCES map_location (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE map_location_tags ADD CONSTRAINT FK_A892DB94BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add created_by_id to comment as nullable first
        $this->addSql('ALTER TABLE comment ADD created_by_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN comment.created_by_id IS \'(DC2Type:uuid)\'');
        // Set existing comments' created_by to the author
        $this->addSql('UPDATE comment SET created_by_id = author_id WHERE created_by_id IS NULL');
        // Now make it NOT NULL
        $this->addSql('ALTER TABLE comment ALTER created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_9474526CB03A8386 ON comment (created_by_id)');

        $this->addSql('ALTER TABLE larp_application_choice ALTER character_id DROP NOT NULL');

        // Add position and shape columns to map_location as nullable first
        $this->addSql('ALTER TABLE map_location ADD position_x NUMERIC(8, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE map_location ADD position_y NUMERIC(8, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE map_location ADD shape VARCHAR(50) DEFAULT NULL');
        // Set default values for existing records
        $this->addSql("UPDATE map_location SET position_x = 50.0000, position_y = 50.0000, shape = 'dot' WHERE position_x IS NULL");
        // Now make columns NOT NULL
        $this->addSql('ALTER TABLE map_location ALTER position_x SET NOT NULL');
        $this->addSql('ALTER TABLE map_location ALTER position_y SET NOT NULL');
        $this->addSql('ALTER TABLE map_location ALTER shape SET NOT NULL');
        $this->addSql('ALTER TABLE map_location DROP grid_coordinates');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_location_tags DROP CONSTRAINT IF EXISTS FK_A892DB94E1E073CC');
        $this->addSql('ALTER TABLE map_location_tags DROP CONSTRAINT IF EXISTS FK_A892DB94BAD26311');
        $this->addSql('DROP TABLE IF EXISTS map_location_tags');

        $this->addSql('ALTER TABLE comment DROP CONSTRAINT IF EXISTS FK_9474526CB03A8386');
        $this->addSql('DROP INDEX IF EXISTS IDX_9474526CB03A8386');
        $this->addSql('ALTER TABLE comment DROP COLUMN IF EXISTS created_by_id');

        // Add grid_coordinates back as nullable, populate, then make NOT NULL
        $this->addSql('ALTER TABLE map_location ADD grid_coordinates JSON DEFAULT NULL');
        $this->addSql("UPDATE map_location SET grid_coordinates = '[]'::json WHERE grid_coordinates IS NULL");
        $this->addSql('ALTER TABLE map_location ALTER grid_coordinates SET NOT NULL');

        $this->addSql('ALTER TABLE map_location DROP COLUMN IF EXISTS position_x');
        $this->addSql('ALTER TABLE map_location DROP COLUMN IF EXISTS position_y');
        $this->addSql('ALTER TABLE map_location DROP COLUMN IF EXISTS shape');

        $this->addSql('ALTER TABLE larp_application_choice ALTER character_id SET NOT NULL');
    }
}
