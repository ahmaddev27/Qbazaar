<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpCategoryResource\Pages;

use App\Filament\Admin\Resources\HelpCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpCategory extends CreateRecord
{
    protected static string $resource = HelpCategoryResource::class;

    protected function afterCreate(): void
    {
        HelpCategoryResource::flushCache();
    }
}
