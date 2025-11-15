<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Location approval system migration
 */
final class Version20251114230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add approval status fields to location table';
    }

    public function up(Schema $schema): void
    {
        // Add approval_status column with default 'approved' for existing locations
        $this->addSql('ALTER TABLE location ADD approval_status VARCHAR(255) DEFAULT \'approved\' NOT NULL');

        // Add approved_by_id column (references user table)
        $this->addSql('ALTER TABLE location ADD approved_by_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN location.approved_by_id IS \'(DC2Type:uuid)\'');

        // Add approved_at column
        $this->addSql('ALTER TABLE location ADD approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Add rejection_reason column
        $this->addSql('ALTER TABLE location ADD rejection_reason TEXT DEFAULT NULL');

        // Add foreign key constraint for approved_by_id
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB2D1630AB FOREIGN KEY (approved_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5E9E89CB2D1630AB ON location (approved_by_id)');

        // Set approved_at for all existing locations to their created_at date
        $this->addSql('UPDATE location SET approved_at = created_at WHERE approval_status = \'approved\'');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key and index
        $this->addSql('ALTER TABLE location DROP CONSTRAINT FK_5E9E89CB2D1630AB');
        $this->addSql('DROP INDEX IDX_5E9E89CB2D1630AB');

        // Remove columns
        $this->addSql('ALTER TABLE location DROP approval_status');
        $this->addSql('ALTER TABLE location DROP approved_by_id');
        $this->addSql('ALTER TABLE location DROP approved_at');
        $this->addSql('ALTER TABLE location DROP rejection_reason');
    }
}
