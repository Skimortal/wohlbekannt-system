<?php

namespace App\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';                  // Entwurf (noch keine Nummer vergeben)
    case SENT = 'sent';                    // versendet / offen
    case PARTIALLY_PAID = 'partially_paid'; // teilbezahlt
    case PAID = 'paid';                    // bezahlt
    case OVERDUE = 'overdue';              // überfällig
    case CANCELLED = 'cancelled';          // storniert
}
