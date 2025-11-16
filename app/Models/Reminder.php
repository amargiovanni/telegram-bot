<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivityAllDirty;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use LogsActivityAllDirty;

    protected $fillable = [
        'telegraph_bot_id',
        'telegraph_chat_id',
        'message',
        'remind_at',
        'is_sent',
        'sent_at',
    ];

    protected $attributes = [
        'is_sent' => false,
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegraphBot::class, 'telegraph_bot_id');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false);
    }

    public function scopeDue($query)
    {
        return $query->where('remind_at', '<=', now());
    }

    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'sent_at' => 'datetime',
            'is_sent' => 'boolean',
        ];
    }
}
