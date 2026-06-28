<?php

namespace App\Enum;

enum InvoiceType: string
{
    case INVOICE = 'invoice';          // Rechnung
    case CREDIT_NOTE = 'credit_note';  // Gutschrift
    case CANCELLATION = 'cancellation'; // Stornorechnung (storniert eine Rechnung)
}
