<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ShortenedUrlResource\Pages;
use App\Models\ShortenedUrl;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShortenedUrlResource extends Resource
{
    protected static ?string $model = ShortenedUrl::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('telegraph_bot_id')
                    ->label('Bot')
                    ->options(TelegraphBot::all()->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->searchable(),

                Forms\Components\Select::make('telegraph_chat_id')
                    ->label('Created by Chat')
                    ->options(function (Forms\Get $get) {
                        $botId = $get('telegraph_bot_id');
                        if (! $botId) {
                            return [];
                        }

                        return TelegraphChat::where('telegraph_bot_id', $botId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->helperText('Optional: Track which chat created this URL'),

                Forms\Components\TextInput::make('original_url')
                    ->label('Original URL')
                    ->url()
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('https://example.com/very-long-url-with-parameters'),

                Forms\Components\TextInput::make('short_code')
                    ->label('Short Code')
                    ->helperText('Leave blank to auto-generate')
                    ->placeholder('Auto-generated')
                    ->maxLength(10),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->helperText('Optional: Set an expiration date for this URL'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bot.name')
                    ->label('Bot')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('short_code')
                    ->label('Short Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Short code copied!')
                    ->badge(),

                Tables\Columns\TextColumn::make('original_url')
                    ->label('Original URL')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->original_url),

                Tables\Columns\TextColumn::make('click_count')
                    ->label('Clicks')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('telegraph_bot_id')
                    ->label('Bot')
                    ->options(TelegraphBot::all()->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (ShortenedUrl $record) {
                        // URL is copied via copyable() on the clipboard
                    })
                    ->color('gray'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShortenedUrls::route('/'),
            'create' => Pages\CreateShortenedUrl::route('/create'),
            'edit' => Pages\EditShortenedUrl::route('/{record}/edit'),
        ];
    }
}
