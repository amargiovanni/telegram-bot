<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache duration in seconds
     */
    protected const CACHE_DURATIONS = [
        'rss_feed' => 900, // 15 minutes
        'bot_stats' => 300, // 5 minutes
        'health_check' => 60, // 1 minute
        'rate_limit' => 60, // 1 minute
        'command_list' => 3600, // 1 hour
    ];

    public function rememberRssFeed(string $feedUrl, callable $callback): mixed
    {
        $key = 'rss_feed:'.md5($feedUrl);

        return Cache::remember($key, self::CACHE_DURATIONS['rss_feed'], $callback);
    }

    public function rememberBotStats(int $botId, callable $callback): mixed
    {
        $key = "bot_stats:{$botId}";

        return Cache::remember($key, self::CACHE_DURATIONS['bot_stats'], $callback);
    }

    public function rememberHealthCheck(callable $callback): mixed
    {
        $key = 'health_check:status';

        return Cache::remember($key, self::CACHE_DURATIONS['health_check'], $callback);
    }

    public function forgetRssFeed(string $feedUrl): bool
    {
        $key = 'rss_feed:'.md5($feedUrl);

        return Cache::forget($key);
    }

    public function forgetBotStats(int $botId): bool
    {
        $key = "bot_stats:{$botId}";

        return Cache::forget($key);
    }

    public function clearAll(): void
    {
        Cache::flush();
    }
}
