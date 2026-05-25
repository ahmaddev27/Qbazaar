<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HelpArticleResource\Pages;
use App\Models\HelpArticle;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

/**
 * Help article editor. Drives `/help/articles/{slug}` on the public site.
 * The same `flushCache()` busts both the category index (since article
 * counts change) and the per-article cache when implemented.
 */
class HelpArticleResource extends Resource
{
    /** @var class-string|null */
    protected static ?string $model = HelpArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 82;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function getNavigationLabel(): string
    {
        return (string) __('admin.navigation.help_articles');
    }

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.help_article.label');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.help_article.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label(__('admin.fields.category'))
                ->relationship('category', 'slug')
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('slug')
                ->label(__('admin.fields.slug'))
                ->required()
                ->maxLength(120)
                ->alphaDash()
                ->unique(ignoreRecord: true),

            KeyValue::make('title')
                ->label(__('admin.fields.title'))
                ->keyLabel('Locale')
                ->valueLabel('Translation')
                ->required()
                ->default(['ar' => '', 'en' => '']),

            KeyValue::make('excerpt')
                ->label('Excerpt')
                ->keyLabel('Locale')
                ->valueLabel('Short summary'),

            RichEditor::make('body.ar')
                ->label('Body (AR)')
                ->required()
                ->columnSpanFull(),

            RichEditor::make('body.en')
                ->label('Body (EN)')
                ->required()
                ->columnSpanFull(),

            Toggle::make('is_published')
                ->label(__('admin.fields.is_published'))
                ->default(true),

            TextInput::make('display_order')
                ->label(__('admin.fields.order'))
                ->numeric()
                ->default(0),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('display_order')
            ->defaultSort('display_order')
            ->columns([
                TextColumn::make('title.ar')
                    ->label('Title (AR)')
                    ->searchable(query: static fn ($query, string $search) => $query->where('title->ar', 'like', "%{$search}%"))
                    ->wrap(),

                TextColumn::make('category.slug')
                    ->label(__('admin.fields.category'))
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label(__('admin.fields.is_published'))
                    ->boolean(),

                TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable(),

                TextColumn::make('display_order')
                    ->label(__('admin.fields.order'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('admin.fields.category'))
                    ->relationship('category', 'slug'),

                TernaryFilter::make('is_published')->label(__('admin.fields.is_published')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->after(static fn () => self::flushCache()),
                DeleteAction::make()->after(static fn () => self::flushCache()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(static fn () => self::flushCache()),
                ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHelpArticles::route('/'),
            'create' => Pages\CreateHelpArticle::route('/create'),
            'view' => Pages\ViewHelpArticle::route('/{record}'),
            'edit' => Pages\EditHelpArticle::route('/{record}/edit'),
        ];
    }

    public static function flushCache(): void
    {
        Cache::forget('help.categories');
    }
}
