<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpArticleResource\Pages;

use App\Filament\Admin\Resources\HelpArticleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHelpArticles extends ListRecords
{
    protected static string $resource = HelpArticleResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
