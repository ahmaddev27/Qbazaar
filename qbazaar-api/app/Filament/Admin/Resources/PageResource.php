<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PageResource\Pages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

/**
 * CMS page editor — drives the `/p/{slug}` public surface (About, Terms,
 * Privacy, Contact, etc.). Slug is the URL key; title/body/meta_description
 * are bilingual JSON columns rendered through two RichEditors so admins can
 * paste copy without context-switching to a Markdown editor.
 *
 * `flushCache()` busts both the index cache and the per-slug cache so the
 * public app picks up changes immediately.
 */
class PageResource extends Resource
{
    /** @var class-string|null */
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 80;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    public static function getNavigationLabel(): string
    {
        return (string) __('admin.navigation.pages');
    }

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.page.label');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.page.plural');
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

            KeyValue::make('title')
                ->label(__('admin.fields.title'))
                ->keyLabel('Locale')
                ->valueLabel('Translation')
                ->required()
                ->default(['ar' => '', 'en' => '']),

            RichEditor::make('body.ar')
                ->label('Body (AR)')
                ->required()
                ->columnSpanFull(),

            RichEditor::make('body.en')
                ->label('Body (EN)')
                ->required()
                ->columnSpanFull(),

            KeyValue::make('meta_description')
                ->label('Meta description')
                ->keyLabel('Locale')
                ->valueLabel('Translation'),

            Toggle::make('is_published')
                ->label(__('admin.fields.is_published'))
                ->default(true),

            DateTimePicker::make('published_at')
                ->label('Published at')
                ->seconds(false),

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
                TextColumn::make('slug')
                    ->label(__('admin.fields.slug'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title.ar')
                    ->label('Title (AR)')
                    ->searchable(query: static fn ($query, string $search) => $query->where('title->ar', 'like', "%{$search}%")),

                TextColumn::make('title.en')
                    ->label('Title (EN)')
                    ->searchable(query: static fn ($query, string $search) => $query->where('title->en', 'like', "%{$search}%"))
                    ->toggleable(),

                IconColumn::make('is_published')
                    ->label(__('admin.fields.is_published'))
                    ->boolean(),

                TextColumn::make('display_order')
                    ->label(__('admin.fields.order'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->since(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label(__('admin.fields.is_published')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->after(static fn (Page $record) => self::flushCache($record->slug)),
                DeleteAction::make()->after(static fn (Page $record) => self::flushCache($record->slug)),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function flushCache(?string $slug = null): void
    {
        Cache::forget('pages.list');
        if ($slug !== null) {
            Cache::forget("pages.show.{$slug}");
        }
    }
}
