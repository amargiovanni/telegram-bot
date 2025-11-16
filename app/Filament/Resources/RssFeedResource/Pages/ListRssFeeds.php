<?php

declare(strict_types=1);

namespace App\Filament\Resources\RssFeedResource\Pages;

use App\Filament\Resources\RssFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRssFeeds extends ListRecords
{
    protected static string $resource = RssFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
