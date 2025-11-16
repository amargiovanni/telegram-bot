<?php

declare(strict_types=1);

namespace App\Filament\Resources\RssFeedResource\Pages;

use App\Filament\Resources\RssFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRssFeed extends EditRecord
{
    protected static string $resource = RssFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
