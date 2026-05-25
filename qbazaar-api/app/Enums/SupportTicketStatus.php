<?php

declare(strict_types=1);

namespace App\Enums;

enum SupportTicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING_USER = 'waiting_user';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function isTerminal(): bool
    {
        return $this === self::RESOLVED || $this === self::CLOSED;
    }
}
