<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HelpCategoryResource\Pages;
use App\Models\HelpCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

/**
 * Help center category editor. Drives `/help/c/{slug}` on the public site.
 */
class HelpCategoryResource extends Resource
{
    /** @var class-string|null */
    protected static ?string $model = HelpCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 81;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function getNavigationLabel(): string
    {
        return (string) __('admin.navigation.help_categories');
    }

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.help_category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.help_category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slug')
                ->label(__('admin.fields.slug'))
                ->required()
                ->maxLength(64)
                ->alphaDash()
                ->unique(ignoreRecord: true),

            KeyValue::make('name')
                ->label(__('admin.fields.name'))
                ->keyLabel('Locale')
                ->valueLabel('Translation')
                ->required()
                ->default(['ar' => '', 'en' => '']),

            KeyValue::make('description')
                ->label(__('admin.fields.description'))
                ->keyLabel('Locale')
                ->valueLabel('Translation'),

            TextInput::make('icon')
                ->label(__('admin.fields.icon'))
                ->helperText(__('admin.helpers.lucide_icon'))
                ->maxLength(64),

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
                TextColumn::make('name.ar')
                    ->label('Name (AR)')
                    ->searchable(query: static fn ($query, string $search) => $query->where('name->ar', 'like', "%{$search}%")),

                TextColumn::make('name.en')
                    ->label('Name (EN)')
                    ->searchable(query: static fn ($query, string $search) => $query->where('name->en', 'like', "%{$search}%"))
                    ->toggleable(),

                TextColumn::make('slug')->label(__('admin.fields.slug'))->searchable(),

                TextColumn::make('icon')->label(__('admin.fields.icon'))->toggleable(),

                TextColumn::make('articles_count')
                    ->label('Articles')
                    ->counts('articles')
                    ->sortable(),

                TextColumn::make('display_order')
                    ->label(__('admin.fields.order'))
                    ->sortable(),
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
            'index' => Pages\ListHelpCategories::route('/'),
            'create' => Pages\CreateHelpCategory::route('/create'),
            'view' => Pages\ViewHelpCategory::route('/{record}'),
            'edit' => Pages\EditHelpCategory::route('/{record}/edit'),
        ];
    }

    public static function flushCache(): void
    {
        Cache::forget('help.categories');
    }
}
