<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add status field to user table for approval workflow
 */
final class Version20251109120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status field to user table with default value of pending, and set all existing users to approved';
    }

    public function up(Schema $schema): void
    {
        // Create the status enum type
        $this->addSql(<<<'SQL'
            CREATE TYPE user_status AS ENUM ('pending', 'approved', 'suspended', 'banned')
        SQL);

        // Add the status column with default value
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD status user_status NOT NULL DEFAULT 'pending'
        SQL);

        // Set all existing users to approved status
        $this->addSql(<<<'SQL'
            UPDATE "user" SET status = 'approved'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop the status column
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP status
        SQL);

        // Drop the enum type
        $this->addSql(<<<'SQL'
            DROP TYPE user_status
        SQL);
    }
}
