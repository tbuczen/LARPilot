<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the AI summary of imported call recordings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE call_recording ADD summary_status VARCHAR(20) DEFAULT 'skipped' NOT NULL");
        $this->addSql('ALTER TABLE call_recording ADD summary JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE call_recording ADD summary_failure_reason TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording DROP summary_status');
        $this->addSql('ALTER TABLE call_recording DROP summary');
        $this->addSql('ALTER TABLE call_recording DROP summary_failure_reason');
    }
}
