<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261101091101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Discounts per line item and per document; settle existing Storno invoices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice ADD discount_type VARCHAR(10) DEFAULT NULL, ADD discount_value NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, ADD discount_total INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE invoice_item ADD discount_percent NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE quote ADD discount_type VARCHAR(10) DEFAULT NULL, ADD discount_value NUMERIC(12, 2) DEFAULT \'0.00\' NOT NULL, ADD discount_total INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE quote_item ADD discount_percent NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL');
        // Storno invoices fully offset the original -> mark them as settled.
        $this->addSql("UPDATE invoice SET status = 'paid', paid_amount = total_gross WHERE type = 'cancellation' AND status <> 'paid'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invoice DROP discount_type, DROP discount_value, DROP discount_total');
        $this->addSql('ALTER TABLE invoice_item DROP discount_percent');
        $this->addSql('ALTER TABLE quote DROP discount_type, DROP discount_value, DROP discount_total');
        $this->addSql('ALTER TABLE quote_item DROP discount_percent');
    }
}
