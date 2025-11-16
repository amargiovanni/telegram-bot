<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\LogsActivityAllDirty;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoResponse extends Model
{
    use LogsActivityAllDirty;

    protected $fillable = [
        'telegraph_bot_id',
        'name',
        'keywords',
        'match_type',
        'case_sensitive',
        'response_text',
        'response_type',
        'media_url',
        'is_active',
        'priority',
        'delete_trigger_message',
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function matches(string $text): bool
    {
        $keywords = $this->keywords ?? [];

        foreach ($keywords as $keyword) {
            $matched = match ($this->match_type) {
                'exact' => $this->case_sensitive
                    ? $text === $keyword
                    : strcasecmp($text, $keyword) === 0,
                'contains' => $this->case_sensitive
                    ? str_contains($text, $keyword)
                    : str_contains(strtolower($text), strtolower($keyword)),
                'starts_with' => $this->case_sensitive
                    ? str_starts_with($text, $keyword)
                    : str_starts_with(strtolower($text), strtolower($keyword)),
                'ends_with' => $this->case_sensitive
                    ? str_ends_with($text, $keyword)
                    : str_ends_with(strtolower($text), strtolower($keyword)),
                'regex' => @preg_match($keyword, $text),
                default => false,
            };

            if ($matched) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedInChat(?int $chatId): bool
    {
        if (empty($this->allowed_chat_ids)) {
            return true;
        }

        return in_array($chatId, $this->allowed_chat_ids);
    }

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'case_sensitive' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'integer',
            'delete_trigger_message' => 'boolean',
            'allowed_chat_ids' => 'array',
        ];
    }
}
