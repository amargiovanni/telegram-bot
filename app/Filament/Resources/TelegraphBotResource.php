<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TelegraphBotResource\Pages;
use DefStudio\Telegraph\Models\TelegraphBot;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegraphBotResource extends Resource
{
    protected static ?string $model = TelegraphBot::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Bot';

    protected static ?string $pluralModelLabel = 'Bots';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bot Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Bot Name'),
                        Forms\Components\TextInput::make('token')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->label('Bot Token')
                            ->helperText('Get your bot token from @BotFather on Telegram')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bot Status')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),
                        Forms\Components\Placeholder::make('chats_count')
                            ->label('Active Chats')
                            ->content(fn ($record) => $record?->chats()->count() ?? 0),
                    ])
                    ->columns(2)
                    ->hidden(fn ($livewire) => $livewire instanceof Pages\CreateTelegraphBot),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Bot Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('chats_count')
                    ->label('Chats')
                    ->counts('chats')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('register_webhook')
                    ->label('Setup Webhook')
                    ->icon('heroicon-o-link')
                    ->action(function (TelegraphBot $record) {
                        try {
                            $record->registerWebhook()->send();
                            Notification::make()
                                ->title('Webhook registered successfully')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Failed to register webhook')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ListTelegraphBots::route('/'),
            'create' => Pages\CreateTelegraphBot::route('/create'),
            'edit' => Pages\EditTelegraphBot::route('/{record}/edit'),
        ];
    }
}
