<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Post call transcript summaries to a chosen Discord channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE call_recording ADD discord_status VARCHAR(20) DEFAULT 'skipped' NOT NULL");
        $this->addSql('ALTER TABLE call_recording ADD discord_channel_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD discord_thread_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD discord_thread_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD discord_failure_reason TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording DROP discord_status');
        $this->addSql('ALTER TABLE call_recording DROP discord_channel_id');
        $this->addSql('ALTER TABLE call_recording DROP discord_thread_id');
        $this->addSql('ALTER TABLE call_recording DROP discord_thread_url');
        $this->addSql('ALTER TABLE call_recording DROP discord_failure_reason');
    }
}
