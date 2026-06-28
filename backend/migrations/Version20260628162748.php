<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628162748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, unit VARCHAR(30) NOT NULL, unit_price INT NOT NULL, vat_rate NUMERIC(5, 2) NOT NULL, tax_category VARCHAR(20) NOT NULL, category VARCHAR(80) DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE company_settings (id INT AUTO_INCREMENT NOT NULL, legal_name VARCHAR(200) NOT NULL, phone VARCHAR(60) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, web VARCHAR(180) DEFAULT NULL, company_register_number VARCHAR(40) DEFAULT NULL, vat_id VARCHAR(20) DEFAULT NULL, tax_number VARCHAR(40) DEFAULT NULL, managing_director VARCHAR(200) DEFAULT NULL, bank_name VARCHAR(120) DEFAULT NULL, iban VARCHAR(40) DEFAULT NULL, bic VARCHAR(20) DEFAULT NULL, logo_path VARCHAR(255) DEFAULT NULL, default_currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, default_vat_rate NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL, default_payment_terms_days SMALLINT DEFAULT 14 NOT NULL, default_quote_validity_days SMALLINT DEFAULT 30 NOT NULL, quote_intro_text LONGTEXT DEFAULT NULL, quote_outro_text LONGTEXT DEFAULT NULL, invoice_intro_text LONGTEXT DEFAULT NULL, invoice_outro_text LONGTEXT DEFAULT NULL, address_street VARCHAR(200) DEFAULT NULL, address_address_line2 VARCHAR(200) DEFAULT NULL, address_postal_code VARCHAR(20) DEFAULT NULL, address_city VARCHAR(120) DEFAULT NULL, address_country_code VARCHAR(2) DEFAULT \'AT\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, customer_number VARCHAR(30) DEFAULT NULL, type VARCHAR(20) NOT NULL, company_name VARCHAR(200) DEFAULT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, contact_person VARCHAR(200) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(60) DEFAULT NULL, vat_id VARCHAR(20) DEFAULT NULL, payment_terms_days SMALLINT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, address_street VARCHAR(200) DEFAULT NULL, address_address_line2 VARCHAR(200) DEFAULT NULL, address_postal_code VARCHAR(20) DEFAULT NULL, address_city VARCHAR(120) DEFAULT NULL, address_country_code VARCHAR(2) DEFAULT \'AT\' NOT NULL, UNIQUE INDEX UNIQ_81398E092755C305 (customer_number), INDEX idx_customer_number (customer_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(30) DEFAULT NULL, recipient_name VARCHAR(200) NOT NULL, recipient_contact VARCHAR(200) DEFAULT NULL, recipient_customer_number VARCHAR(30) DEFAULT NULL, recipient_vat_id VARCHAR(20) DEFAULT NULL, contact_person VARCHAR(200) DEFAULT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, prices_include_vat TINYINT DEFAULT 1 NOT NULL, issue_date DATE NOT NULL, intro_text LONGTEXT DEFAULT NULL, outro_text LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, total_net INT DEFAULT 0 NOT NULL, total_tax INT DEFAULT 0 NOT NULL, total_gross INT DEFAULT 0 NOT NULL, optional_total_gross INT DEFAULT 0 NOT NULL, tax_breakdown JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(20) NOT NULL, due_date DATE DEFAULT NULL, service_period_start DATE DEFAULT NULL, service_period_end DATE DEFAULT NULL, paid_amount INT DEFAULT 0 NOT NULL, sent_at DATETIME DEFAULT NULL, recipient_address_street VARCHAR(200) DEFAULT NULL, recipient_address_address_line2 VARCHAR(200) DEFAULT NULL, recipient_address_postal_code VARCHAR(20) DEFAULT NULL, recipient_address_city VARCHAR(120) DEFAULT NULL, recipient_address_country_code VARCHAR(2) DEFAULT \'AT\' NOT NULL, customer_id INT DEFAULT NULL, related_quote_id INT DEFAULT NULL, cancels_invoice_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9065174496901F54 (number), INDEX IDX_906517449395C3F3 (customer_id), INDEX IDX_906517447D031FD5 (related_quote_id), INDEX IDX_906517443A337308 (cancels_invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice_item (id INT AUTO_INCREMENT NOT NULL, position SMALLINT DEFAULT 0 NOT NULL, optional TINYINT DEFAULT 0 NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantity NUMERIC(12, 3) DEFAULT \'1.000\' NOT NULL, unit VARCHAR(30) NOT NULL, unit_price INT DEFAULT 0 NOT NULL, vat_rate NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL, tax_category VARCHAR(20) NOT NULL, line_net INT DEFAULT 0 NOT NULL, line_tax INT DEFAULT 0 NOT NULL, line_gross INT DEFAULT 0 NOT NULL, invoice_id INT NOT NULL, INDEX IDX_1DDE477B2989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE number_range (id INT AUTO_INCREMENT NOT NULL, range_key VARCHAR(40) NOT NULL, prefix VARCHAR(10) NOT NULL, next_value INT DEFAULT 1 NOT NULL, padding SMALLINT DEFAULT 4 NOT NULL, yearly_reset TINYINT DEFAULT 0 NOT NULL, current_year SMALLINT DEFAULT NULL, include_year TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_28CED9815D08C2F0 (range_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, paid_at DATE NOT NULL, amount INT NOT NULL, method VARCHAR(40) DEFAULT NULL, reference VARCHAR(120) DEFAULT NULL, invoice_id INT NOT NULL, INDEX IDX_6D28840D2989F1FD (invoice_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(30) DEFAULT NULL, recipient_name VARCHAR(200) NOT NULL, recipient_contact VARCHAR(200) DEFAULT NULL, recipient_customer_number VARCHAR(30) DEFAULT NULL, recipient_vat_id VARCHAR(20) DEFAULT NULL, contact_person VARCHAR(200) DEFAULT NULL, currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL, prices_include_vat TINYINT DEFAULT 1 NOT NULL, issue_date DATE NOT NULL, intro_text LONGTEXT DEFAULT NULL, outro_text LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, total_net INT DEFAULT 0 NOT NULL, total_tax INT DEFAULT 0 NOT NULL, total_gross INT DEFAULT 0 NOT NULL, optional_total_gross INT DEFAULT 0 NOT NULL, tax_breakdown JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, valid_until DATE DEFAULT NULL, sent_at DATETIME DEFAULT NULL, decided_at DATETIME DEFAULT NULL, recipient_address_street VARCHAR(200) DEFAULT NULL, recipient_address_address_line2 VARCHAR(200) DEFAULT NULL, recipient_address_postal_code VARCHAR(20) DEFAULT NULL, recipient_address_city VARCHAR(120) DEFAULT NULL, recipient_address_country_code VARCHAR(2) DEFAULT \'AT\' NOT NULL, customer_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6B71CBF496901F54 (number), INDEX IDX_6B71CBF49395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quote_item (id INT AUTO_INCREMENT NOT NULL, position SMALLINT DEFAULT 0 NOT NULL, optional TINYINT DEFAULT 0 NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantity NUMERIC(12, 3) DEFAULT \'1.000\' NOT NULL, unit VARCHAR(30) NOT NULL, unit_price INT DEFAULT 0 NOT NULL, vat_rate NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL, tax_category VARCHAR(20) NOT NULL, line_net INT DEFAULT 0 NOT NULL, line_tax INT DEFAULT 0 NOT NULL, line_gross INT DEFAULT 0 NOT NULL, quote_id INT NOT NULL, INDEX IDX_8DFC7A94DB805178 (quote_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517449395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517447D031FD5 FOREIGN KEY (related_quote_id) REFERENCES quote (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517443A337308 FOREIGN KEY (cancels_invoice_id) REFERENCES invoice (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice_item ADD CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF49395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE quote_item ADD CONSTRAINT FK_8DFC7A94DB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449395C3F3');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517447D031FD5');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517443A337308');
        $this->addSql('ALTER TABLE invoice_item DROP FOREIGN KEY FK_1DDE477B2989F1FD');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D2989F1FD');
        $this->addSql('ALTER TABLE quote DROP FOREIGN KEY FK_6B71CBF49395C3F3');
        $this->addSql('ALTER TABLE quote_item DROP FOREIGN KEY FK_8DFC7A94DB805178');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE company_settings');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE number_range');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_item');
    }
}
