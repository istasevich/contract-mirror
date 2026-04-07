<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize empty tx_hash values and add partial unique index for non-empty transaction hashes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE report_payments
            SET tx_hash = NULL
            WHERE tx_hash IS NOT NULL
              AND BTRIM(tx_hash) = ''
        ");

        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_REPORT_PAYMENTS_TX_HASH
            ON report_payments (tx_hash)
            WHERE tx_hash IS NOT NULL
              AND BTRIM(tx_hash) <> ''
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_REPORT_PAYMENTS_TX_HASH');
    }
}
