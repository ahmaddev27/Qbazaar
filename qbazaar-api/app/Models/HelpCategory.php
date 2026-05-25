<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $slug
 * @property array<string, string> $name
 * @property array<string, string>|null $description
 * @property string|null $icon
 * @property int $display_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class HelpCategory extends Model
{
    use HasUlids;

    protected $table = 'help_categories';

    /** @var string */
    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'display_order' => 'integer',
        ];
    }

    /** @return HasMany<HelpArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class, 'category_id');
    }

    /**
     * @param Builder<HelpCategory> $query
     * @return Builder<HelpCategory>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
