<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326192924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contract_reports (id UUID NOT NULL, public_id VARCHAR(32) NOT NULL, file_name VARCHAR(255) NOT NULL, file_hash VARCHAR(64) NOT NULL, document_type VARCHAR(32) NOT NULL, language VARCHAR(32) NOT NULL, risk_score INT DEFAULT NULL, overall_risk VARCHAR(16) DEFAULT NULL, status VARCHAR(16) NOT NULL, report_payload JSON DEFAULT NULL, report_html TEXT DEFAULT NULL, is_locked BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F94EDA8DB5B48B91 ON contract_reports (public_id)');
        $this->addSql('CREATE TABLE report_payments (id UUID NOT NULL, payment_method VARCHAR(32) NOT NULL, wallet_network VARCHAR(16) NOT NULL, wallet_address VARCHAR(255) NOT NULL, expected_amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, tx_hash VARCHAR(128) DEFAULT NULL, payer_address VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, raw_verification_payload JSON DEFAULT NULL, confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, report_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_65B2024F4BD2A4C0 ON report_payments (report_id)');
        $this->addSql('ALTER TABLE report_payments ADD CONSTRAINT FK_65B2024F4BD2A4C0 FOREIGN KEY (report_id) REFERENCES contract_reports (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report_payments DROP CONSTRAINT FK_65B2024F4BD2A4C0');
        $this->addSql('DROP TABLE contract_reports');
        $this->addSql('DROP TABLE report_payments');
    }
}
