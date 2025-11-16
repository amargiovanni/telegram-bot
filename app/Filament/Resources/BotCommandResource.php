<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BotCommandResource\Pages;
use App\Models\BotCommand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BotCommandResource extends Resource
{
    protected static ?string $model = BotCommand::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('telegraph_bot_id')->relationship('bot', 'name')->required(),
            Forms\Components\TextInput::make('command')->required(),
            Forms\Components\TextInput::make('description')->required(),
            Forms\Components\Textarea::make('response_text')->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('command')->formatStateUsing(fn ($state) => '/'.$state),
            Tables\Columns\TextColumn::make('bot.name'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBotCommands::route('/'),
            'create' => Pages\CreateBotCommand::route('/create'),
            'edit' => Pages\EditBotCommand::route('/{record}/edit'),
        ];
    }
}
