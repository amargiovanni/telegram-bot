<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class BotRateLimiter
{
    /**
     * Rate limit tiers based on command complexity
     */
    private const LIMITS = [
        'heavy' => ['max' => 5, 'decay' => 60],      // 5 requests per minute (calc, qr, cf)
        'medium' => ['max' => 10, 'decay' => 60],    // 10 requests per minute (password, shorten)
        'light' => ['max' => 20, 'decay' => 60],     // 20 requests per minute (fun commands)
    ];

    /**
     * Generate rate limit key for bot command
     */
    public static function key(int $chatId, string $command): string
    {
        return "bot_command:{$chatId}:{$command}";
    }

    /**
     * Check if the request is allowed for the given key and tier
     */
    public function attempt(string $key, string $tier = 'medium'): bool
    {
        $limit = self::LIMITS[$tier] ?? self::LIMITS['medium'];

        return RateLimiter::attempt(
            $key,
            $limit['max'],
            function () {
                // This callback is executed if rate limit is not exceeded
            },
            $limit['decay']
        );
    }

    /**
     * Get remaining attempts for a key
     */
    public function remaining(string $key, string $tier = 'medium'): int
    {
        $limit = self::LIMITS[$tier] ?? self::LIMITS['medium'];

        return RateLimiter::remaining($key, $limit['max']);
    }

    /**
     * Get seconds until rate limit is reset
     */
    public function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /**
     * Clear rate limit for a key (for admin/testing)
     */
    public function clear(string $key): void
    {
        RateLimiter::clear($key);
    }
}
