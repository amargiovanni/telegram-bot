<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BotLogResource\Pages;
use App\Models\BotLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BotLogResource extends Resource
{
    protected static ?string $model = BotLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('bot.name'),
            Tables\Columns\TextColumn::make('message')->limit(50),
        ])->defaultSort('created_at', 'desc')->poll('10s');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBotLogs::route('/')];
    }
}
