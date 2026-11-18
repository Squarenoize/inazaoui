<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261118114357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD deactivation_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD first_connection BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".deactivation_date IS \'(DC2Type:datetime_immutable)\'');
        
        // Set admin users to false (they don't need onboarding)
        $this->addSql('UPDATE "user" SET first_connection = false WHERE roles::text LIKE \'%ROLE_ADMIN%\'');
        
        $this->addSql('ALTER TABLE "user" ALTER first_connection DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP deactivation_date');
        $this->addSql('ALTER TABLE "user" DROP first_connection');
    }
}
