<?php

declare(strict_types=1);

namespace App\Filament\Resources\BotCommandResource\Pages;

use App\Filament\Resources\BotCommandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBotCommands extends ListRecords
{
    protected static string $resource = BotCommandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
