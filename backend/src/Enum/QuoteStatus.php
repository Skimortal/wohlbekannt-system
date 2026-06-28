<?php

namespace App\Enum;

enum QuoteStatus: string
{
    case DRAFT = 'draft';        // Entwurf
    case SENT = 'sent';          // versendet
    case ACCEPTED = 'accepted';  // angenommen
    case DECLINED = 'declined';  // abgelehnt
    case EXPIRED = 'expired';    // abgelaufen (gültig bis überschritten)
}
