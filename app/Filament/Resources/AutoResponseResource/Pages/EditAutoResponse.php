<?php

declare(strict_types=1);

namespace App\Filament\Resources\AutoResponseResource\Pages;

use App\Filament\Resources\AutoResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutoResponse extends EditRecord
{
    protected static string $resource = AutoResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
