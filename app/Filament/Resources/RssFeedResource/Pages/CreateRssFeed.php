<?php

declare(strict_types=1);

namespace App\Filament\Resources\RssFeedResource\Pages;

use App\Filament\Resources\RssFeedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRssFeed extends CreateRecord
{
    protected static string $resource = RssFeedResource::class;
}
