<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713185044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE larp_application_preferred_tags (larp_application_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(larp_application_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_3CBC93BF393848D ON larp_application_preferred_tags (larp_application_id)');
        $this->addSql('CREATE INDEX IDX_3CBC93BFBAD26311 ON larp_application_preferred_tags (tag_id)');
        $this->addSql('COMMENT ON COLUMN larp_application_preferred_tags.larp_application_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application_preferred_tags.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE larp_application_unwanted_tags (larp_application_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(larp_application_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_6AD63E1B393848D ON larp_application_unwanted_tags (larp_application_id)');
        $this->addSql('CREATE INDEX IDX_6AD63E1BBAD26311 ON larp_application_unwanted_tags (tag_id)');
        $this->addSql('COMMENT ON COLUMN larp_application_unwanted_tags.larp_application_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application_unwanted_tags.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_application_preferred_tags ADD CONSTRAINT FK_3CBC93BF393848D FOREIGN KEY (larp_application_id) REFERENCES larp_application (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application_preferred_tags ADD CONSTRAINT FK_3CBC93BFBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application_unwanted_tags ADD CONSTRAINT FK_6AD63E1B393848D FOREIGN KEY (larp_application_id) REFERENCES larp_application (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application_unwanted_tags ADD CONSTRAINT FK_6AD63E1BBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application DROP CONSTRAINT fk_7a0ee7542788656b');
        $this->addSql('ALTER TABLE larp_application DROP CONSTRAINT fk_7a0ee7549fc692c9');
        $this->addSql('DROP INDEX idx_b9db292d2788656b');
        $this->addSql('DROP INDEX idx_b9db292d9fc692c9');
        $this->addSql('ALTER TABLE larp_application DROP preferred_tags_id');
        $this->addSql('ALTER TABLE larp_application DROP unwanted_tags_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp_application_preferred_tags DROP CONSTRAINT FK_3CBC93BF393848D');
        $this->addSql('ALTER TABLE larp_application_preferred_tags DROP CONSTRAINT FK_3CBC93BFBAD26311');
        $this->addSql('ALTER TABLE larp_application_unwanted_tags DROP CONSTRAINT FK_6AD63E1B393848D');
        $this->addSql('ALTER TABLE larp_application_unwanted_tags DROP CONSTRAINT FK_6AD63E1BBAD26311');
        $this->addSql('DROP TABLE larp_application_preferred_tags');
        $this->addSql('DROP TABLE larp_application_unwanted_tags');
        $this->addSql('ALTER TABLE larp_application ADD preferred_tags_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE larp_application ADD unwanted_tags_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN larp_application.preferred_tags_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN larp_application.unwanted_tags_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE larp_application ADD CONSTRAINT fk_7a0ee7542788656b FOREIGN KEY (preferred_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE larp_application ADD CONSTRAINT fk_7a0ee7549fc692c9 FOREIGN KEY (unwanted_tags_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b9db292d2788656b ON larp_application (preferred_tags_id)');
        $this->addSql('CREATE INDEX idx_b9db292d9fc692c9 ON larp_application (unwanted_tags_id)');
    }
}
