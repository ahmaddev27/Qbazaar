<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpCategoryResource\Pages;

use App\Filament\Admin\Resources\HelpCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpCategory extends EditRecord
{
    protected static string $resource = HelpCategoryResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        HelpCategoryResource::flushCache();
    }
}
