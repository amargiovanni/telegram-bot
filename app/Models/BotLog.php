<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotLog extends Model
{
    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'telegraph_bot_id',
        'telegraph_chat_id',
        'type',
        'message',
        'data',
    ];

    public static function log(
        string $type,
        ?int $botId = null,
        ?int $chatId = null,
        ?string $message = null,
        ?array $data = null
    ): self {
        return self::create([
            'telegraph_bot_id' => $botId,
            'telegraph_chat_id' => $chatId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegraphBot::class, 'telegraph_bot_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForBot($query, int $botId)
    {
        return $query->where('telegraph_bot_id', $botId);
    }

    public function scopeForChat($query, int $chatId)
    {
        return $query->where('telegraph_chat_id', $chatId);
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
