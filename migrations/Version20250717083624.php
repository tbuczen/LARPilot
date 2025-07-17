<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250717083624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_designation_story_object (item_id UUID NOT NULL, story_object_id UUID NOT NULL, PRIMARY KEY(item_id, story_object_id))');
        $this->addSql('CREATE INDEX IDX_EC537105126F525E ON item_designation_story_object (item_id)');
        $this->addSql('CREATE INDEX IDX_EC537105DA976C5A ON item_designation_story_object (story_object_id)');
        $this->addSql('COMMENT ON COLUMN item_designation_story_object.item_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN item_designation_story_object.story_object_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE item_designation_story_object ADD CONSTRAINT FK_EC537105126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE item_designation_story_object ADD CONSTRAINT FK_EC537105DA976C5A FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE item_designation_story_object DROP CONSTRAINT FK_EC537105126F525E');
        $this->addSql('ALTER TABLE item_designation_story_object DROP CONSTRAINT FK_EC537105DA976C5A');
        $this->addSql('DROP TABLE item_designation_story_object');
    }
}
