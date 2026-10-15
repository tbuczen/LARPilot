<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261014093853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store link suggestions for external items (integration inbox)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE external_item_suggestion (id UUID NOT NULL, item_id UUID NOT NULL, story_object_id UUID DEFAULT NULL, tag_id UUID DEFAULT NULL, source VARCHAR(30) NOT NULL, score DOUBLE PRECISION NOT NULL, target_type VARCHAR(30) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2949F544126F525E ON external_item_suggestion (item_id)');
        $this->addSql('CREATE INDEX IDX_2949F544DA976C5A ON external_item_suggestion (story_object_id)');
        $this->addSql('CREATE INDEX IDX_2949F544BAD26311 ON external_item_suggestion (tag_id)');
        $this->addSql('CREATE INDEX IDX_2949F544126F525E32993751 ON external_item_suggestion (item_id, score)');
        $this->addSql("COMMENT ON COLUMN external_item_suggestion.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN external_item_suggestion.item_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN external_item_suggestion.story_object_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN external_item_suggestion.tag_id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN external_item_suggestion.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE external_item_suggestion ADD CONSTRAINT FK_2949F544126F525E FOREIGN KEY (item_id) REFERENCES external_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_item_suggestion ADD CONSTRAINT FK_2949F544DA976C5A FOREIGN KEY (story_object_id) REFERENCES story_object (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_item_suggestion ADD CONSTRAINT FK_2949F544BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE external_item ADD suggestions_refreshed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN external_item.suggestions_refreshed_at IS '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE external_item_suggestion');
        $this->addSql('ALTER TABLE external_item DROP suggestions_refreshed_at');
    }
}
