<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Convert users from admin boolean to password + roles JSON
 */
final class Version20261117104300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert users from admin boolean to password + roles JSON system';
    }

    public function up(Schema $schema): void
    {
        // Add new columns if they don't exist yet
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE "user" ADD roles JSON NOT NULL DEFAULT \'[]\'');

        // Convert existing users: admin=true -> ROLE_ADMIN, admin=false -> ROLE_USER
        // Using a default hashed password (you should change this manually or via CLI)
        $this->addSql('
            UPDATE "user"
            SET roles = CASE
                WHEN admin = true THEN \'["ROLE_ADMIN"]\'::json
                ELSE \'["ROLE_USER"]\'::json
            END,
            password = \'$2y$13$7JS0ehfU8vZhB3Q8o1sPGuoQxkiPGXRGgrAizmNfI5Sgy.Dqt9xoW\'
        ');

        // Drop the old admin column
        $this->addSql('ALTER TABLE "user" DROP admin');

        // Rename the index for consistency
        $this->addSql('ALTER INDEX uniq_8d93d649e7927c74 RENAME TO UNIQ_IDENTIFIER_EMAIL');

        // Remove defaults now that data is populated
        $this->addSql('ALTER TABLE "user" ALTER password DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER roles DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // Recreate the admin column
        $this->addSql('ALTER TABLE "user" ADD admin BOOLEAN NOT NULL DEFAULT false');

        // Revert roles to admin boolean
        $this->addSql('
            UPDATE "user"
            SET admin = true
            WHERE roles::text LIKE \'%ROLE_ADMIN%\'
        ');

        // Drop new columns
        $this->addSql('ALTER TABLE "user" DROP password');
        $this->addSql('ALTER TABLE "user" DROP roles');

        // Rename index back
        $this->addSql('ALTER INDEX uniq_identifier_email RENAME TO uniq_8d93d649e7927c74');
    }
}
