<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MonitorRssFeeds;
use Illuminate\Console\Command;

class MonitorRssFeedsCommand extends Command
{
    protected $signature = 'telegram:monitor-rss {--feed= : Specific feed ID to monitor}';

    protected $description = 'Monitor RSS feeds and post new entries to Telegram';

    public function handle(): int
    {
        $feedId = $this->option('feed');

        $this->info('Dispatching RSS feed monitoring job...');

        MonitorRssFeeds::dispatch($feedId ? (int) $feedId : null);

        $this->info('RSS feed monitoring job dispatched successfully!');

        return Command::SUCCESS;
    }
}
