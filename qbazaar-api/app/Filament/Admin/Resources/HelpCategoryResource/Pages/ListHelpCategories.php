<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpCategoryResource\Pages;

use App\Filament\Admin\Resources\HelpCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpCategories extends ListRecords
{
    protected static string $resource = HelpCategoryResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
