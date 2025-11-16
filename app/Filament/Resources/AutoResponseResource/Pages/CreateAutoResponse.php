<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutoResponseResource\Pages;

use App\Filament\Resources\AutoResponseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutoResponse extends CreateRecord
{
    protected static string $resource = AutoResponseResource::class;
}
