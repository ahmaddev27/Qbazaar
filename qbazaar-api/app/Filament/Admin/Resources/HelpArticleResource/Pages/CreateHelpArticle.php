<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HelpArticleResource\Pages;

use App\Filament\Admin\Resources\HelpArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHelpArticle extends CreateRecord
{
    protected static string $resource = HelpArticleResource::class;

    protected function afterCreate(): void
    {
        HelpArticleResource::flushCache();
    }
}
