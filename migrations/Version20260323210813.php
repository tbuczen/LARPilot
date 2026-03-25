<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323210813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE external_reference ALTER provider SET NOT NULL');
        $this->addSql('ALTER TABLE external_reference ALTER external_id SET NOT NULL');
        $this->addSql('ALTER TABLE larp ADD enable_wip_stage BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE larp ADD enable_negotiation_stage BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE larp ADD enable_costume_check_stage BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE larp ADD application_turns SMALLINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE larp ADD enabled_modules JSON DEFAULT \'[]\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE larp DROP enable_wip_stage');
        $this->addSql('ALTER TABLE larp DROP enable_negotiation_stage');
        $this->addSql('ALTER TABLE larp DROP enable_costume_check_stage');
        $this->addSql('ALTER TABLE larp DROP application_turns');
        $this->addSql('ALTER TABLE larp DROP enabled_modules');
        $this->addSql('ALTER TABLE external_reference ALTER provider DROP NOT NULL');
        $this->addSql('ALTER TABLE external_reference ALTER external_id DROP NOT NULL');
    }
}
