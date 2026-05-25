<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $email
 * @property string $subject
 * @property SupportTicketCategory $category
 * @property string $body
 * @property SupportTicketStatus $status
 * @property SupportTicketPriority $priority
 * @property string|null $assigned_to
 * @property Carbon|null $last_replied_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property User|null $user
 * @property User|null $assignee
 */
class SupportTicket extends Model
{
    use HasUlids;

    protected $table = 'support_tickets';

    /** @var string */
    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'email',
        'subject',
        'category',
        'body',
        'status',
        'priority',
        'assigned_to',
        'last_replied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SupportTicketCategory::class,
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'last_replied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<SupportReply, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class, 'ticket_id');
    }

    /**
     * @param Builder<SupportTicket> $query
     * @return Builder<SupportTicket>
     */
    public function scopeOpenOnly(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SupportTicketStatus::OPEN->value,
            SupportTicketStatus::IN_PROGRESS->value,
            SupportTicketStatus::WAITING_USER->value,
        ]);
    }

    /**
     * @param Builder<SupportTicket> $query
     * @return Builder<SupportTicket>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
