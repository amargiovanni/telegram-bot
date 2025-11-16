<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BotLog;
use App\Models\RssFeed;
use Carbon\Carbon;
use Exception;
use Feeds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MonitorRssFeeds implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $feedId = null
    ) {}

    public function handle(): void
    {
        $query = RssFeed::active()
            ->dueForCheck()
            ->with(['bot', 'chat']);

        if ($this->feedId) {
            $query->where('id', $this->feedId);
        }

        $feeds = $query->get();

        foreach ($feeds as $feed) {
            try {
                $this->checkFeed($feed);
            } catch (Exception $e) {
                Log::error("RSS Feed check failed for {$feed->name}", [
                    'feed_id' => $feed->id,
                    'error' => $e->getMessage(),
                ]);

                BotLog::log(
                    'error',
                    $feed->telegraph_bot_id,
                    $feed->telegraph_chat_id,
                    "RSS feed check failed: {$feed->name}",
                    ['error' => $e->getMessage()]
                );
            }
        }
    }

    protected function checkFeed(RssFeed $feed): void
    {
        try {
            $rss = Feeds::make($feed->url);

            BotLog::log(
                'rss_check',
                $feed->telegraph_bot_id,
                $feed->telegraph_chat_id,
                "Checking RSS feed: {$feed->name}"
            );

            if (! $rss) {
                throw new Exception('Failed to fetch RSS feed');
            }

            $lastDate = $feed->last_entry_date;
            $newEntriesPosted = 0;

            foreach ($rss->get_items(0, 10) as $item) {
                $itemDate = Carbon::parse($item->get_date());

                // Skip if we've already seen this entry
                if ($lastDate && $itemDate->lte($lastDate)) {
                    continue;
                }

                // Post to Telegram if chat is configured
                if ($feed->chat) {
                    $message = $this->formatMessage($item, $feed);
                    $feed->chat->html($message)->send();

                    $newEntriesPosted++;

                    BotLog::log(
                        'rss_posted',
                        $feed->telegraph_bot_id,
                        $feed->telegraph_chat_id,
                        "Posted RSS entry: {$item->get_title()}",
                        ['feed' => $feed->name, 'url' => $item->get_link()]
                    );
                }

                // Update last entry date
                if (! $lastDate || $itemDate->gt($lastDate)) {
                    $lastDate = $itemDate;
                }
            }

            // Update feed timestamps
            $feed->update([
                'last_checked_at' => now(),
                'last_entry_date' => $lastDate ?? $feed->last_entry_date,
            ]);

            if ($newEntriesPosted > 0) {
                Log::info("Posted {$newEntriesPosted} new RSS entries from {$feed->name}");
            }
        } catch (Exception $e) {
            Log::error("Failed to check RSS feed {$feed->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function formatMessage($item, RssFeed $feed): string
    {
        $title = $item->get_title();
        $link = $item->get_link();
        $description = $item->get_description();
        $date = Carbon::parse($item->get_date())->format('d/m/Y H:i');

        // Clean and truncate description
        $description = strip_tags($description);
        $description = mb_substr($description, 0, 200);
        if (mb_strlen($item->get_description()) > 200) {
            $description .= '...';
        }

        return "📰 <b>{$title}</b>\n\n{$description}\n\n🔗 <a href=\"{$link}\">Leggi di più</a>\n📅 {$date}\n\n<i>Da: {$feed->name}</i>";
    }
}
