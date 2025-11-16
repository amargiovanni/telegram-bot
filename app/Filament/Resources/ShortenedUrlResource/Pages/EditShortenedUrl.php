<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShortenedUrlResource\Pages;

use App\Filament\Resources\ShortenedUrlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShortenedUrl extends EditRecord
{
    protected static string $resource = ShortenedUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
