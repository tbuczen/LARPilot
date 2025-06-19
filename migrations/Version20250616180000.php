<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250616180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add log table for story objects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE story_object_log_entry (id INT NOT NULL, action VARCHAR(8) NOT NULL, logged_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(191) NOT NULL, version INT NOT NULL, data JSON DEFAULT NULL, username VARCHAR(191) DEFAULT NULL, PRIMARY KEY(id))");
        $this->addSql("CREATE INDEX story_object_class_lookup_idx ON story_object_log_entry (object_class)");
        $this->addSql("CREATE INDEX story_object_date_lookup_idx ON story_object_log_entry (logged_at)");
        $this->addSql("CREATE INDEX story_object_user_lookup_idx ON story_object_log_entry (username)");
        $this->addSql("CREATE INDEX story_object_version_lookup_idx ON story_object_log_entry (object_id, object_class, version)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE story_object_log_entry');
    }
}
