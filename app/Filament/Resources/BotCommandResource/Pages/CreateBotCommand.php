<?php

declare(strict_types=1);

namespace App\Filament\Resources\BotCommandResource\Pages;

use App\Filament\Resources\BotCommandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBotCommand extends CreateRecord
{
    protected static string $resource = BotCommandResource::class;
}
