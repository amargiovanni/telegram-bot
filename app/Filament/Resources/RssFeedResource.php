<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RssFeedResource\Pages;
use App\Models\RssFeed;
use DefStudio\Telegraph\Models\TelegraphChat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RssFeedResource extends Resource
{
    protected static ?string $model = RssFeed::class;

    protected static ?string $navigationIcon = 'heroicon-o-rss';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'RSS Feed';

    protected static ?string $pluralModelLabel = 'RSS Feeds';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feed Information')
                    ->schema([
                        Forms\Components\Select::make('telegraph_bot_id')
                            ->label('Bot')
                            ->relationship('bot', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('telegraph_chat_id', null)),
                        Forms\Components\Select::make('telegraph_chat_id')
                            ->label('Target Chat')
                            ->options(function (Forms\Get $get) {
                                $botId = $get('telegraph_bot_id');
                                if (! $botId) {
                                    return [];
                                }

                                return TelegraphChat::where('telegraph_bot_id', $botId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('The chat where RSS updates will be posted'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Feed Name')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(255)
                            ->label('RSS Feed URL')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('check_interval')
                            ->required()
                            ->numeric()
                            ->default(60)
                            ->minValue(5)
                            ->maxValue(1440)
                            ->suffix('minutes')
                            ->label('Check Interval')
                            ->helperText('How often to check for new entries (5-1440 minutes)'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Placeholder::make('last_checked_at')
                            ->label('Last Checked')
                            ->content(fn ($record) => $record?->last_checked_at?->diffForHumans() ?? 'Never'),
                        Forms\Components\Placeholder::make('last_entry_date')
                            ->label('Last Entry Date')
                            ->content(fn ($record) => $record?->last_entry_date?->diffForHumans() ?? 'None'),
                    ])
                    ->columns(2)
                    ->hidden(fn ($livewire) => $livewire instanceof Pages\CreateRssFeed),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Feed Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('bot.name')
                    ->label('Bot')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chat.name')
                    ->label('Chat')
                    ->searchable()
                    ->sortable()
                    ->default('Not set')
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_interval')
                    ->label('Interval')
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label('Last Check')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All feeds')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\SelectFilter::make('telegraph_bot_id')
                    ->label('Bot')
                    ->relationship('bot', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRssFeeds::route('/'),
            'create' => Pages\CreateRssFeed::route('/create'),
            'edit' => Pages\EditRssFeed::route('/{record}/edit'),
        ];
    }
}
