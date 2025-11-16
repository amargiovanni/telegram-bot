<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivityAllDirty;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShoppingList extends Model
{
    use LogsActivityAllDirty;

    protected $fillable = [
        'telegraph_bot_id',
        'telegraph_chat_id',
        'name',
        'description',
        'is_active',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegraphBot::class, 'telegraph_bot_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('position');
    }

    public function uncheckedItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)
            ->where('is_checked', false)
            ->orderBy('position');
    }

    public function checkedItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)
            ->where('is_checked', true)
            ->orderBy('position');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForChat($query, int $chatId)
    {
        return $query->where('telegraph_chat_id', $chatId);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
