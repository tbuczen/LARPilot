<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503135733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE larp_participant ALTER roles TYPE JSONB USING roles::jsonb;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE larp_invitation ALTER accepted_by_user_ids TYPE JSONB USING accepted_by_user_ids::jsonb;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE saved_form_filter ALTER parameters TYPE JSONB USING parameters::jsonb;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE object_field_mapping ALTER mapping_configuration TYPE JSONB USING mapping_configuration::jsonb;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE object_field_mapping ALTER meta_configuration TYPE JSONB USING meta_configuration::jsonb;
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE shared_file ALTER metadata TYPE JSONB USING metadata::jsonb;
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
    }
}
