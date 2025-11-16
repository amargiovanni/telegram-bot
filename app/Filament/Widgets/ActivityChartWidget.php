<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\BotLog;
use Filament\Widgets\ChartWidget;

class ActivityChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Attività Bot (Ultimi 30 giorni)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = 30;
        $labels = [];
        $messagesData = [];
        $commandsData = [];
        $errorsData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $labels[] = $date->format('d M');

            $messagesData[] = BotLog::where('type', 'message_received')
                ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                ->count();

            $commandsData[] = BotLog::where('type', 'command_executed')
                ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                ->count();

            $errorsData[] = BotLog::where('type', 'error')
                ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Messaggi',
                    'data' => $messagesData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Comandi',
                    'data' => $commandsData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Errori',
                    'data' => $errorsData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
