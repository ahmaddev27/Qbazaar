<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Filament\Admin\Resources\SupportTicketResource\Pages;
use App\Filament\Admin\Resources\SupportTicketResource\RelationManagers\RepliesRelationManager;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Support ticket admin surface. Staff can:
 *  - Filter the queue by status / priority / category
 *  - Open a ticket and read the full thread (RepliesRelationManager)
 *  - Assign-to-me — claim a ticket so other agents see it's covered
 *  - Reply — appends a staff SupportReply and (when triaged) flips status to in_progress
 *  - Change status — pushes the ticket through the lifecycle (open → in_progress →
 *    waiting_user → resolved → closed); the public app's TicketReplyForm locks
 *    itself on terminal status.
 */
class SupportTicketResource extends Resource
{
    /** @var class-string|null */
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 25;

    protected static string|UnitEnum|null $navigationGroup = 'Communications';

    public static function getNavigationLabel(): string
    {
        return (string) __('admin.navigation.support_tickets');
    }

    public static function getModelLabel(): string
    {
        return (string) __('admin.resources.support_ticket.label');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.resources.support_ticket.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('status', [
                SupportTicketStatus::OPEN->value,
                SupportTicketStatus::IN_PROGRESS->value,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('subject')
                ->label(__('admin.fields.subject'))
                ->required()
                ->maxLength(160),

            Select::make('category')
                ->label(__('admin.fields.category'))
                ->options(self::enumOptions(SupportTicketCategory::class))
                ->required(),

            Textarea::make('body')
                ->label(__('admin.fields.body'))
                ->rows(6)
                ->disabled(),

            Select::make('status')
                ->label(__('admin.fields.status'))
                ->options(self::enumOptions(SupportTicketStatus::class))
                ->required(),

            Select::make('priority')
                ->label(__('admin.fields.priority'))
                ->options(self::enumOptions(SupportTicketPriority::class))
                ->required(),

            Select::make('assigned_to')
                ->label(__('admin.fields.assignee'))
                ->relationship('assignee', 'full_name')
                ->searchable()
                ->preload(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(static fn (Builder $query) => $query->withCount('replies'))
            ->columns([
                TextColumn::make('subject')
                    ->label(__('admin.fields.subject'))
                    ->searchable()
                    ->wrap()
                    ->limit(60),

                TextColumn::make('user.full_name')
                    ->label(__('admin.fields.reporter'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('category')
                    ->label(__('admin.fields.category'))
                    ->badge()
                    ->formatStateUsing(static fn (SupportTicketCategory $state): string => (string) __('admin.support.category.' . $state->value)),

                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->color(static fn (SupportTicketStatus $state): string => match ($state) {
                        SupportTicketStatus::OPEN => 'primary',
                        SupportTicketStatus::IN_PROGRESS => 'info',
                        SupportTicketStatus::WAITING_USER => 'warning',
                        SupportTicketStatus::RESOLVED => 'success',
                        SupportTicketStatus::CLOSED => 'gray',
                    })
                    ->formatStateUsing(static fn (SupportTicketStatus $state): string => (string) __('admin.support.status.' . $state->value)),

                TextColumn::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->badge()
                    ->color(static fn (SupportTicketPriority $state): string => match ($state) {
                        SupportTicketPriority::LOW => 'gray',
                        SupportTicketPriority::NORMAL => 'info',
                        SupportTicketPriority::HIGH => 'warning',
                        SupportTicketPriority::URGENT => 'danger',
                    })
                    ->formatStateUsing(static fn (SupportTicketPriority $state): string => (string) __('admin.support.priority.' . $state->value)),

                TextColumn::make('assignee.full_name')
                    ->label(__('admin.fields.assignee'))
                    ->placeholder('—'),

                TextColumn::make('replies_count')
                    ->label('Replies')
                    ->sortable(),

                TextColumn::make('last_replied_at')
                    ->label('Last reply')
                    ->since()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Opened')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(self::enumOptions(SupportTicketStatus::class))
                    ->default(SupportTicketStatus::OPEN->value),

                SelectFilter::make('priority')
                    ->label(__('admin.fields.priority'))
                    ->options(self::enumOptions(SupportTicketPriority::class)),

                SelectFilter::make('category')
                    ->label(__('admin.fields.category'))
                    ->options(self::enumOptions(SupportTicketCategory::class)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('assign_to_me')
                    ->label(__('admin.actions.assign_to_me'))
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(static fn (SupportTicket $record): bool => auth()->id() !== $record->assigned_to)
                    ->action(static function (SupportTicket $record): void {
                        $userId = auth()->id();
                        if (! is_string($userId)) {
                            return;
                        }
                        $patch = ['assigned_to' => $userId];
                        if ($record->status === SupportTicketStatus::OPEN) {
                            $patch['status'] = SupportTicketStatus::IN_PROGRESS->value;
                        }
                        $record->forceFill($patch)->save();

                        Notification::make()
                            ->title(__('admin.support.assigned_to_me'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }

    /**
     * Build a label-keyed dropdown for an enum that defines string `value`s.
     *
     * @param class-string<BackedEnum> $enumClass
     * @return array<string, string>
     */
    private static function enumOptions(string $enumClass): array
    {
        $out = [];
        /** @var array<int, BackedEnum> $cases */
        $cases = $enumClass::cases();
        foreach ($cases as $case) {
            $out[(string) $case->value] = (string) $case->value;
        }

        return $out;
    }
}
