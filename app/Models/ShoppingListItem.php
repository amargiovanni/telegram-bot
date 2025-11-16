<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivityAllDirty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShoppingListItem extends Model
{
    use LogsActivityAllDirty;

    protected $fillable = [
        'shopping_list_id',
        'name',
        'quantity',
        'unit',
        'is_checked',
        'position',
    ];

    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function toggleCheck(): void
    {
        $this->update(['is_checked' => ! $this->is_checked]);
    }

    public function scopeUnchecked($query)
    {
        return $query->where('is_checked', false);
    }

    public function scopeChecked($query)
    {
        return $query->where('is_checked', true);
    }

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'quantity' => 'integer',
            'position' => 'integer',
        ];
    }
}
