<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\BotLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopCommandsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Comandi Più Utilizzati (Ultimi 30 giorni)')
            ->query(
                BotLog::query()
                    ->where('type', 'command_executed')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->select(
                        DB::raw('JSON_EXTRACT(data, "$.command") as command_name'),
                        DB::raw('COUNT(*) as usage_count'),
                        DB::raw('MAX(created_at) as last_used')
                    )
                    ->whereNotNull(DB::raw('JSON_EXTRACT(data, "$.command")'))
                    ->groupBy('command_name')
                    ->orderByDesc('usage_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('command_name')
                    ->label('Comando')
                    ->formatStateUsing(fn ($state) => str_replace('"', '', $state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Utilizzi')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('last_used')
                    ->label('Ultimo Utilizzo')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('usage_count', 'desc')
            ->paginated(false);
    }
}
