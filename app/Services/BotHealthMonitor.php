<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BotLog;
use DefStudio\Telegraph\Models\TelegraphBot;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BotHealthMonitor
{
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'bots' => $this->checkBots(),
            'scheduled_tasks' => $this->checkScheduledTasks(),
            'disk_space' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
        ];

        $overallStatus = collect($checks)->every(fn ($check) => $check['status'] === 'healthy')
            ? 'healthy'
            : 'unhealthy';

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $logsCount = BotLog::count();

            return [
                'status' => 'healthy',
                'message' => "Database connected. {$logsCount} logs in database.",
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_'.uniqid();
            $testValue = 'test_'.time();

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working correctly.',
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Cache read/write test failed.',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache error: '.$e->getMessage(),
            ];
        }
    }

    protected function checkBots(): array
    {
        try {
            $bots = TelegraphBot::all();

            if ($bots->isEmpty()) {
                return [
                    'status' => 'warning',
                    'message' => 'No bots configured.',
                ];
            }

            $unhealthyBots = [];

            foreach ($bots as $bot) {
                try {
                    // Test webhook by calling Telegram API
                    $response = Http::get("https://api.telegram.org/bot{$bot->token}/getWebhookInfo");

                    if (! $response->successful()) {
                        $unhealthyBots[] = $bot->name ?? $bot->id;
                    }
                } catch (Exception $e) {
                    $unhealthyBots[] = $bot->name ?? $bot->id;
                }
            }

            if (empty($unhealthyBots)) {
                return [
                    'status' => 'healthy',
                    'message' => count($bots).' bot(s) are healthy.',
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Unhealthy bots: '.implode(', ', $unhealthyBots),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Bot check failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkScheduledTasks(): array
    {
        try {
            // Check if reminders command has run recently (should run every minute)
            $lastReminderLog = BotLog::where('type', 'reminder_sent')
                ->orWhere(function ($query) {
                    $query->where('message', 'like', '%No due reminders found%');
                })
                ->orderByDesc('created_at')
                ->first();

            $reminderStatus = 'healthy';
            $reminderMessage = 'Reminder task appears to be running.';

            if (! $lastReminderLog || $lastReminderLog->created_at->lt(now()->subMinutes(5))) {
                $reminderStatus = 'warning';
                $reminderMessage = 'Reminder task may not be running (no activity in last 5 minutes).';
            }

            // Check RSS check
            $lastRssCheck = BotLog::where('type', 'rss_check')
                ->orderByDesc('created_at')
                ->first();

            $rssStatus = 'healthy';
            $rssMessage = 'RSS check appears to be running.';

            if (! $lastRssCheck || $lastRssCheck->created_at->lt(now()->subHour())) {
                $rssStatus = 'warning';
                $rssMessage = 'RSS check may not be running (no activity in last hour).';
            }

            $overallStatus = ($reminderStatus === 'warning' || $rssStatus === 'warning')
                ? 'warning'
                : 'healthy';

            return [
                'status' => $overallStatus,
                'message' => "Reminders: {$reminderMessage} | RSS: {$rssMessage}",
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Scheduled tasks check failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkDiskSpace(): array
    {
        try {
            $freeSpace = disk_free_space(base_path());
            $totalSpace = disk_total_space(base_path());
            $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usedPercentage > 90) {
                return [
                    'status' => 'unhealthy',
                    'message' => sprintf('Disk space critical: %.2f%% used.', $usedPercentage),
                ];
            }

            if ($usedPercentage > 80) {
                return [
                    'status' => 'warning',
                    'message' => sprintf('Disk space warning: %.2f%% used.', $usedPercentage),
                ];
            }

            return [
                'status' => 'healthy',
                'message' => sprintf('Disk space healthy: %.2f%% used.', $usedPercentage),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check disk space: '.$e->getMessage(),
            ];
        }
    }

    protected function checkMemory(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->getMemoryLimit();

            if ($memoryLimit === -1) {
                return [
                    'status' => 'healthy',
                    'message' => sprintf('Memory usage: %s (no limit)', $this->formatBytes($memoryUsage)),
                ];
            }

            $usedPercentage = ($memoryUsage / $memoryLimit) * 100;

            if ($usedPercentage > 90) {
                return [
                    'status' => 'unhealthy',
                    'message' => sprintf('Memory critical: %.2f%% used (%s / %s)', $usedPercentage, $this->formatBytes($memoryUsage), $this->formatBytes($memoryLimit)),
                ];
            }

            if ($usedPercentage > 75) {
                return [
                    'status' => 'warning',
                    'message' => sprintf('Memory warning: %.2f%% used (%s / %s)', $usedPercentage, $this->formatBytes($memoryUsage), $this->formatBytes($memoryLimit)),
                ];
            }

            return [
                'status' => 'healthy',
                'message' => sprintf('Memory healthy: %.2f%% used (%s / %s)', $usedPercentage, $this->formatBytes($memoryUsage), $this->formatBytes($memoryLimit)),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Could not check memory: '.$e->getMessage(),
            ];
        }
    }

    protected function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return -1;
        }

        return $this->convertToBytes($memoryLimit);
    }

    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
