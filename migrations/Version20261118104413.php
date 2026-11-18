<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261118104413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GIN index on user roles JSON column for better query performance';
    }

    public function up(Schema $schema): void
    {
        // Create a GIN index on the roles JSON column cast to text
        $this->addSql('CREATE INDEX idx_user_roles ON "user" (CAST(roles AS TEXT)) WHERE roles IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_roles');
    }
}
