<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotCommand extends Model
{
    protected $fillable = [
        'telegraph_bot_id',
        'command',
        'description',
        'response_text',
        'response_type',
        'media_url',
        'is_active',
        'show_in_menu',
        'allowed_chat_ids',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegraphBot::class, 'telegraph_bot_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    public function isAllowedInChat(?int $chatId): bool
    {
        if (empty($this->allowed_chat_ids)) {
            return true;
        }

        return in_array($chatId, $this->allowed_chat_ids);
    }

    public function getFullCommandAttribute(): string
    {
        return '/'.$this->command;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'allowed_chat_ids' => 'array',
        ];
    }
}
