<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\BotLog;
use App\Models\Reminder;
use DefStudio\Telegraph\Models\TelegraphChat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $weekAgo = now()->subWeek();
        $monthAgo = now()->subMonth();

        // Messages received
        $messagesToday = BotLog::where('type', 'message_received')
            ->where('created_at', '>=', $today)
            ->count();

        $messagesWeek = BotLog::where('type', 'message_received')
            ->where('created_at', '>=', $weekAgo)
            ->count();

        // Commands executed
        $commandsToday = BotLog::where('type', 'command_executed')
            ->where('created_at', '>=', $today)
            ->count();

        $commandsWeek = BotLog::where('type', 'command_executed')
            ->where('created_at', '>=', $weekAgo)
            ->count();

        // Errors
        $errorsToday = BotLog::where('type', 'error')
            ->where('created_at', '>=', $today)
            ->count();

        // Active chats
        $activeChatsToday = BotLog::where('created_at', '>=', $today)
            ->distinct('telegraph_chat_id')
            ->whereNotNull('telegraph_chat_id')
            ->count('telegraph_chat_id');

        $activeChatsWeek = BotLog::where('created_at', '>=', $weekAgo)
            ->distinct('telegraph_chat_id')
            ->whereNotNull('telegraph_chat_id')
            ->count('telegraph_chat_id');

        // RSS articles posted
        $rssToday = BotLog::where('type', 'rss_posted')
            ->where('created_at', '>=', $today)
            ->count();

        $rssWeek = BotLog::where('type', 'rss_posted')
            ->where('created_at', '>=', $weekAgo)
            ->count();

        // Reminders sent
        $remindersToday = Reminder::where('is_sent', true)
            ->where('sent_at', '>=', $today)
            ->count();

        $remindersWeek = Reminder::where('is_sent', true)
            ->where('sent_at', '>=', $weekAgo)
            ->count();

        // Total chats
        $totalChats = TelegraphChat::count();

        return [
            Stat::make('Messaggi Ricevuti', number_format($messagesToday))
                ->description($messagesWeek.' questa settimana')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getMessagesChart()),

            Stat::make('Comandi Eseguiti', number_format($commandsToday))
                ->description($commandsWeek.' questa settimana')
                ->descriptionIcon('heroicon-m-command-line')
                ->color('primary')
                ->chart($this->getCommandsChart()),

            Stat::make('Chat Attive', number_format($activeChatsToday))
                ->description($activeChatsWeek.' questa settimana')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),

            Stat::make('Errori', number_format($errorsToday))
                ->description('Oggi')
                ->descriptionIcon($errorsToday > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($errorsToday > 0 ? 'danger' : 'success'),

            Stat::make('Articoli RSS', number_format($rssToday))
                ->description($rssWeek.' questa settimana')
                ->descriptionIcon('heroicon-m-rss')
                ->color('warning'),

            Stat::make('Promemoria Inviati', number_format($remindersToday))
                ->description($remindersWeek.' questa settimana')
                ->descriptionIcon('heroicon-m-bell')
                ->color('success'),
        ];
    }

    protected function getMessagesChart(): array
    {
        return $this->getDailyChart('message_received', 7);
    }

    protected function getCommandsChart(): array
    {
        return $this->getDailyChart('command_executed', 7);
    }

    protected function getDailyChart(string $type, int $days): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = BotLog::where('type', $type)
                ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                ->count();

            $data[] = $count;
        }

        return $data;
    }
}
