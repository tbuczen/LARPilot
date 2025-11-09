<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add plan table and link users to plans for tier management
 */
final class Version20251109130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plan table for user tier/subscription management and link users to plans';
    }

    public function up(Schema $schema): void
    {
        // Create plan table
        $this->addSql(<<<'SQL'
            CREATE TABLE plan (
                id UUID NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                max_larps INT DEFAULT NULL,
                max_participants_per_larp INT DEFAULT NULL,
                storage_limit_mb INT DEFAULT NULL,
                has_google_integrations BOOLEAN NOT NULL DEFAULT FALSE,
                has_custom_branding BOOLEAN NOT NULL DEFAULT FALSE,
                price_in_cents INT NOT NULL DEFAULT 0,
                is_free BOOLEAN NOT NULL DEFAULT TRUE,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        // Add unique constraint on plan name
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DD5A5B7D5E237E06 ON plan (name)
        SQL);

        // Add plan_id column to user table
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD plan_id UUID DEFAULT NULL
        SQL);

        // Add foreign key constraint
        $this->addSql(<<<'SQL'
            ALTER TABLE "user"
            ADD CONSTRAINT FK_8D93D649E899029B
            FOREIGN KEY (plan_id) REFERENCES plan (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);

        // Create index on plan_id
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649E899029B ON "user" (plan_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649E899029B
        SQL);

        // Drop plan_id column from user
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP plan_id
        SQL);

        // Drop plan table
        $this->addSql(<<<'SQL'
            DROP TABLE plan
        SQL);
    }
}
