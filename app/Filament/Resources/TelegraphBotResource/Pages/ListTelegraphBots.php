<?php

declare(strict_types=1);

namespace App\Filament\Resources\TelegraphBotResource\Pages;

use App\Filament\Resources\TelegraphBotResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelegraphBots extends ListRecords
{
    protected static string $resource = TelegraphBotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
