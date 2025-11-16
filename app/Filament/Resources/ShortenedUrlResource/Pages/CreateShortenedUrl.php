<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShortenedUrlResource\Pages;

use App\Filament\Resources\ShortenedUrlResource;
use App\Models\ShortenedUrl;
use Filament\Resources\Pages\CreateRecord;

class CreateShortenedUrl extends CreateRecord
{
    protected static string $resource = ShortenedUrlResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate short code if not provided
        if (empty($data['short_code'])) {
            $data['short_code'] = ShortenedUrl::generateUniqueCode();
        }

        return $data;
    }
}
