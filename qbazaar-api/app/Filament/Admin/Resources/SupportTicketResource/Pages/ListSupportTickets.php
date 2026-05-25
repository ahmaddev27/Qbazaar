<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SupportTicketResource\Pages;

use App\Filament\Admin\Resources\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
}
