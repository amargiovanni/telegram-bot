<?php

declare(strict_types=1);

namespace App\Filament\Resources\BotLogResource\Pages;

use App\Filament\Resources\BotLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBotLog extends CreateRecord
{
    protected static string $resource = BotLogResource::class;
}
