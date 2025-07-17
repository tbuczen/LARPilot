<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250717083826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_story_object DROP CONSTRAINT fk_28f4e4df126f525e');
        $this->addSql('ALTER TABLE item_story_object DROP CONSTRAINT fk_28f4e4dfda976c5a');
        $this->addSql('ALTER TABLE item_designation_story_object DROP CONSTRAINT fk_ec537105126f525e');
        $this->addSql('ALTER TABLE item_designation_story_object DROP CONSTRAINT fk_ec537105da976c5a');
        $this->addSql('DROP TABLE item_designation_story_object');
        $this->addSql('ALTER TABLE item ADD designation_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN item.designation_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EFAC7D83F FOREIGN KEY (designation_id) REFERENCES story_object (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1F1B251EFAC7D83F ON item (designation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_story_object (item_id UUID NOT NULL, story_object_id UUID NOT NULL, PRIMARY KEY(item_id, story_object_id))');
        $this->addSql('CREATE INDEX idx_28f4e4df126f525e ON item_story_object (item_id)');
        $this->addSql('CREATE INDEX idx_28f4e4dfda976c5a ON item_story_object (story_object_id)');
        $this->addSql('COMMENT ON COLUMN item_story_object.item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN item_story_object.story_object_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE item_designation_story_object (item_id UUID NOT NULL, story_object_id UUID NOT NULL, PRIMARY KEY(item_id, story_object_id))');
        $this->addSql('CREATE INDEX idx_ec537105126f525e ON item_designation_story_object (item_id)');
        $this->addSql('CREATE INDEX idx_ec537105da976c5a ON item_designation_story_object (story_object_id)');
        $this->addSql('COMMENT ON COLUMN item_designation_story_object.item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN item_designation_story_object.story_object_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE item_story_object ADD CONSTRAINT fk_28f4e4df126f525e FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_story_object ADD CONSTRAINT fk_28f4e4dfda976c5a FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_designation_story_object ADD CONSTRAINT fk_ec537105126f525e FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_designation_story_object ADD CONSTRAINT fk_ec537105da976c5a FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item DROP CONSTRAINT FK_1F1B251EFAC7D83F');
        $this->addSql('DROP INDEX IDX_1F1B251EFAC7D83F');
        $this->addSql('ALTER TABLE item DROP designation_id');
    }
}
