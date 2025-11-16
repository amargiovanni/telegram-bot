<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AutoResponseResource\Pages;
use App\Models\AutoResponse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoResponseResource extends Resource
{
    protected static ?string $model = AutoResponse::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Telegram Bots';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Auto Response';

    protected static ?string $pluralModelLabel = 'Auto Responses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('telegraph_bot_id')
                            ->label('Bot')
                            ->relationship('bot', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Response Name')
                            ->helperText('Descriptive name for this auto-response'),
                        Forms\Components\TextInput::make('priority')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Priority')
                            ->helperText('Higher priority responses are checked first'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Trigger Configuration')
                    ->schema([
                        Forms\Components\TagsInput::make('keywords')
                            ->required()
                            ->label('Keywords')
                            ->helperText('Enter keywords that will trigger this response')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('match_type')
                            ->required()
                            ->options([
                                'exact' => 'Exact Match',
                                'contains' => 'Contains',
                                'starts_with' => 'Starts With',
                                'ends_with' => 'Ends With',
                                'regex' => 'Regular Expression',
                            ])
                            ->default('contains')
                            ->label('Match Type'),
                        Forms\Components\Toggle::make('case_sensitive')
                            ->label('Case Sensitive')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Response Configuration')
                    ->schema([
                        Forms\Components\Select::make('response_type')
                            ->required()
                            ->options([
                                'text' => 'Text',
                                'photo' => 'Photo',
                                'document' => 'Document',
                                'video' => 'Video',
                                'audio' => 'Audio',
                            ])
                            ->default('text')
                            ->live()
                            ->label('Response Type'),
                        Forms\Components\Textarea::make('response_text')
                            ->required()
                            ->rows(5)
                            ->label('Response Text')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('media_url')
                            ->url()
                            ->maxLength(255)
                            ->label('Media URL')
                            ->visible(fn (Forms\Get $get) => in_array($get('response_type'), ['photo', 'document', 'video', 'audio']))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Advanced Settings')
                    ->schema([
                        Forms\Components\Toggle::make('delete_trigger_message')
                            ->label('Delete Trigger Message')
                            ->helperText('Automatically delete the message that triggered this response')
                            ->default(false)
                            ->inline(false),
                        Forms\Components\TagsInput::make('allowed_chat_ids')
                            ->label('Allowed Chat IDs')
                            ->helperText('Leave empty to allow in all chats')
                            ->numeric()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('bot.name')
                    ->label('Bot')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('keywords')
                    ->label('Keywords')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->searchable(),
                Tables\Columns\TextColumn::make('match_type')
                    ->label('Match Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All responses')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\SelectFilter::make('telegraph_bot_id')
                    ->label('Bot')
                    ->relationship('bot', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('match_type')
                    ->label('Match Type')
                    ->options([
                        'exact' => 'Exact Match',
                        'contains' => 'Contains',
                        'starts_with' => 'Starts With',
                        'ends_with' => 'Ends With',
                        'regex' => 'Regular Expression',
                    ]),
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
            ->defaultSort('priority', 'desc');
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
            'index' => Pages\ListAutoResponses::route('/'),
            'create' => Pages\CreateAutoResponse::route('/create'),
            'edit' => Pages\EditAutoResponse::route('/{record}/edit'),
        ];
    }
}
