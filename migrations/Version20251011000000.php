<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename StoryObject entity tables by removing 'larp_' prefix
 */
final class Version20251011000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename larp_character, larp_character_item, larp_character_skill, and larp_faction tables to remove larp_ prefix';
    }

    public function up(Schema $schema): void
    {
        // Rename the main entity tables
        $this->addSql('ALTER TABLE larp_character RENAME TO character');
        $this->addSql('ALTER TABLE larp_character_item RENAME TO character_item');
        $this->addSql('ALTER TABLE larp_character_skill RENAME TO character_skill');
        $this->addSql('ALTER TABLE larp_faction RENAME TO faction');

        // Update foreign key constraints to reference the renamed tables
        // Note: PostgreSQL automatically updates constraint names when tables are renamed
        // but we need to ensure the references are correct

        // Update sequences if they exist
        $this->addSql('ALTER SEQUENCE IF EXISTS larp_character_id_seq RENAME TO character_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS larp_character_item_id_seq RENAME TO character_item_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS larp_character_skill_id_seq RENAME TO character_skill_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS larp_faction_id_seq RENAME TO faction_id_seq');
    }

    public function down(Schema $schema): void
    {
        // Revert the table renames
        $this->addSql('ALTER TABLE character RENAME TO larp_character');
        $this->addSql('ALTER TABLE character_item RENAME TO larp_character_item');
        $this->addSql('ALTER TABLE character_skill RENAME TO larp_character_skill');
        $this->addSql('ALTER TABLE faction RENAME TO larp_faction');

        // Revert sequence renames
        $this->addSql('ALTER SEQUENCE IF EXISTS character_id_seq RENAME TO larp_character_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS character_item_id_seq RENAME TO larp_character_item_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS character_skill_id_seq RENAME TO larp_character_skill_id_seq');
        $this->addSql('ALTER SEQUENCE IF EXISTS faction_id_seq RENAME TO larp_faction_id_seq');
    }
}
