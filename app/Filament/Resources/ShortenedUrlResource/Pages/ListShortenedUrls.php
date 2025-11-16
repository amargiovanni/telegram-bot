<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShortenedUrlResource\Pages;

use App\Filament\Resources\ShortenedUrlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShortenedUrls extends ListRecords
{
    protected static string $resource = ShortenedUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
