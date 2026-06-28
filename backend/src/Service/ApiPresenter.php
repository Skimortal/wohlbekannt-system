<?php

namespace App\Service;

use App\Entity\AbstractLineItem;
use App\Entity\Article;
use App\Entity\CompanySettings;
use App\Entity\Customer;
use App\Entity\Embeddable\Address;
use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Quote;

/**
 * Converts entities to plain arrays for the JSON API. Monetary values are
 * integer cents; the frontend formats them. Dates are ISO (Y-m-d), timestamps
 * are ATOM.
 */
class ApiPresenter
{
    public function address(Address $a): array
    {
        return [
            'street' => $a->street,
            'addressLine2' => $a->addressLine2,
            'postalCode' => $a->postalCode,
            'city' => $a->city,
            'countryCode' => $a->countryCode,
        ];
    }

    public function customer(Customer $c): array
    {
        return [
            'id' => $c->getId(),
            'customerNumber' => $c->getCustomerNumber(),
            'type' => $c->getType()->value,
            'companyName' => $c->getCompanyName(),
            'firstName' => $c->getFirstName(),
            'lastName' => $c->getLastName(),
            'displayName' => $c->getDisplayName(),
            'contactPerson' => $c->getContactPerson(),
            'email' => $c->getEmail(),
            'phone' => $c->getPhone(),
            'vatId' => $c->getVatId(),
            'address' => $this->address($c->getAddress()),
            'paymentTermsDays' => $c->getPaymentTermsDays(),
            'notes' => $c->getNotes(),
            'createdAt' => $c->getCreatedAt()->format(\DATE_ATOM),
        ];
    }

    public function article(Article $a): array
    {
        return [
            'id' => $a->getId(),
            'name' => $a->getName(),
            'description' => $a->getDescription(),
            'unit' => $a->getUnit(),
            'unitPrice' => $a->getUnitPrice(),
            'vatRate' => $a->getVatRate(),
            'taxCategory' => $a->getTaxCategory()->value,
            'category' => $a->getCategory(),
            'active' => $a->isActive(),
        ];
    }

    public function lineItem(AbstractLineItem $i): array
    {
        return [
            'id' => $i->getId(),
            'position' => $i->getPosition(),
            'optional' => $i->isOptional(),
            'title' => $i->getTitle(),
            'description' => $i->getDescription(),
            'quantity' => $i->getQuantity(),
            'unit' => $i->getUnit(),
            'unitPrice' => $i->getUnitPrice(),
            'vatRate' => $i->getVatRate(),
            'taxCategory' => $i->getTaxCategory()->value,
            'lineNet' => $i->getLineNet(),
            'lineTax' => $i->getLineTax(),
            'lineGross' => $i->getLineGross(),
        ];
    }

    private function documentBase(\App\Entity\AbstractDocument $d): array
    {
        return [
            'id' => $d->getId(),
            'number' => $d->getNumber(),
            'customerId' => $d->getCustomer()?->getId(),
            'recipientName' => $d->getRecipientName(),
            'recipientContact' => $d->getRecipientContact(),
            'recipientCustomerNumber' => $d->getRecipientCustomerNumber(),
            'recipientVatId' => $d->getRecipientVatId(),
            'recipientAddress' => $this->address($d->getRecipientAddress()),
            'contactPerson' => $d->getContactPerson(),
            'currency' => $d->getCurrency(),
            'pricesIncludeVat' => $d->isPricesIncludeVat(),
            'issueDate' => $d->getIssueDate()->format('Y-m-d'),
            'introText' => $d->getIntroText(),
            'outroText' => $d->getOutroText(),
            'internalNotes' => $d->getInternalNotes(),
            'totalNet' => $d->getTotalNet(),
            'totalTax' => $d->getTotalTax(),
            'totalGross' => $d->getTotalGross(),
            'optionalTotalGross' => $d->getOptionalTotalGross(),
            'taxBreakdown' => $d->getTaxBreakdown(),
            'createdAt' => $d->getCreatedAt()->format(\DATE_ATOM),
            'updatedAt' => $d->getUpdatedAt()->format(\DATE_ATOM),
        ];
    }

    public function quote(Quote $q, bool $withItems = true): array
    {
        $data = $this->documentBase($q) + [
            'status' => $q->getStatus()->value,
            'validUntil' => $q->getValidUntil()?->format('Y-m-d'),
            'sentAt' => $q->getSentAt()?->format(\DATE_ATOM),
            'decidedAt' => $q->getDecidedAt()?->format(\DATE_ATOM),
        ];
        if ($withItems) {
            $data['items'] = array_map([$this, 'lineItem'], $q->getItems()->toArray());
        }

        return $data;
    }

    public function invoice(Invoice $inv, bool $withItems = true): array
    {
        $data = $this->documentBase($inv) + [
            'status' => $inv->getStatus()->value,
            'type' => $inv->getType()->value,
            'dueDate' => $inv->getDueDate()?->format('Y-m-d'),
            'servicePeriodStart' => $inv->getServicePeriodStart()?->format('Y-m-d'),
            'servicePeriodEnd' => $inv->getServicePeriodEnd()?->format('Y-m-d'),
            'paidAmount' => $inv->getPaidAmount(),
            'openAmount' => $inv->getOpenAmount(),
            'relatedQuoteId' => $inv->getRelatedQuote()?->getId(),
            'cancelsInvoiceId' => $inv->getCancelsInvoice()?->getId(),
            'sentAt' => $inv->getSentAt()?->format(\DATE_ATOM),
        ];
        if ($withItems) {
            $data['items'] = array_map([$this, 'lineItem'], $inv->getItems()->toArray());
            $data['payments'] = array_map([$this, 'payment'], $inv->getPayments()->toArray());
        }

        return $data;
    }

    public function payment(Payment $p): array
    {
        return [
            'id' => $p->getId(),
            'paidAt' => $p->getPaidAt()->format('Y-m-d'),
            'amount' => $p->getAmount(),
            'method' => $p->getMethod(),
            'reference' => $p->getReference(),
        ];
    }

    public function companySettings(CompanySettings $c): array
    {
        return [
            'id' => $c->getId(),
            'legalName' => $c->getLegalName(),
            'address' => $this->address($c->getAddress()),
            'phone' => $c->getPhone(),
            'email' => $c->getEmail(),
            'web' => $c->getWeb(),
            'companyRegisterNumber' => $c->getCompanyRegisterNumber(),
            'vatId' => $c->getVatId(),
            'taxNumber' => $c->getTaxNumber(),
            'managingDirector' => $c->getManagingDirector(),
            'bankName' => $c->getBankName(),
            'iban' => $c->getIban(),
            'bic' => $c->getBic(),
            'logoPath' => $c->getLogoPath(),
            'defaultCurrency' => $c->getDefaultCurrency(),
            'defaultVatRate' => $c->getDefaultVatRate(),
            'defaultPaymentTermsDays' => $c->getDefaultPaymentTermsDays(),
            'defaultQuoteValidityDays' => $c->getDefaultQuoteValidityDays(),
            'quoteIntroText' => $c->getQuoteIntroText(),
            'quoteOutroText' => $c->getQuoteOutroText(),
            'invoiceIntroText' => $c->getInvoiceIntroText(),
            'invoiceOutroText' => $c->getInvoiceOutroText(),
        ];
    }
}
