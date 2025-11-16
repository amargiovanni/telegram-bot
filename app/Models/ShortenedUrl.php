<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShortenedUrl extends Model
{
    protected $fillable = [
        'telegraph_bot_id',
        'telegraph_chat_id',
        'original_url',
        'short_code',
        'click_count',
        'expires_at',
        'is_active',
    ];

    public static function generateUniqueCode(int $length = 6): string
    {
        do {
            $code = Str::random($length);
        } while (self::where('short_code', $code)->exists());

        return $code;
    }

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
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByBot($query, int $botId)
    {
        return $query->where('telegraph_bot_id', $botId);
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getShortUrl(): string
    {
        return url("/s/{$this->short_code}");
    }

    protected function casts(): array
    {
        return [
            'click_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
