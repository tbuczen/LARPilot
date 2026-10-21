<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Count speech-to-text attempts per call track so provider outages are retried';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording_track ADD attempts INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE call_recording_track DROP attempts');
    }
}
