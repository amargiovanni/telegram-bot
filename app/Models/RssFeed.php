<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssFeed extends Model
{
    protected $fillable = [
        'telegraph_bot_id',
        'telegraph_chat_id',
        'name',
        'url',
        'check_interval',
        'last_checked_at',
        'last_entry_date',
        'is_active',
        'filters',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegraphBot::class, 'telegraph_bot_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForCheck($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_checked_at')
                ->orWhereRaw('last_checked_at < datetime("now", "-" || check_interval || " minutes")');
        });
    }

    protected function casts(): array
    {
        return [
            'check_interval' => 'integer',
            'last_checked_at' => 'datetime',
            'last_entry_date' => 'datetime',
            'is_active' => 'boolean',
            'filters' => 'array',
        ];
    }
}
