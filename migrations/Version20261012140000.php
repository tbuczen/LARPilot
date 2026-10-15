<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Map LarpIntegration access and refresh tokens to the encrypted_text type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("COMMENT ON COLUMN larp_integration.access_token IS '(DC2Type:encrypted_text)'");
        $this->addSql("COMMENT ON COLUMN larp_integration.refresh_token IS '(DC2Type:encrypted_text)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN larp_integration.access_token IS NULL');
        $this->addSql('COMMENT ON COLUMN larp_integration.refresh_token IS NULL');
    }
}
