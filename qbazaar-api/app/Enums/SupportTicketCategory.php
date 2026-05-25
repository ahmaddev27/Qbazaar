<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportTicketCategory: string
{
    case GENERAL = 'general';
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case ABUSE = 'abuse';
    case FEEDBACK = 'feedback';
    case OTHER = 'other';
}
