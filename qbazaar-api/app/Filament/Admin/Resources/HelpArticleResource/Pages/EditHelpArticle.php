<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpArticleResource\Pages;

use App\Filament\Admin\Resources\HelpArticleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHelpArticle extends EditRecord
{
    protected static string $resource = HelpArticleResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        HelpArticleResource::flushCache();
    }
}
