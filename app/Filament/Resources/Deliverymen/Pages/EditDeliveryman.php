<?php

namespace App\Filament\Resources\Deliverymen\Pages;

use App\Filament\Resources\Deliverymen\DeliverymanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryman extends EditRecord
{
    protected static string $resource = DeliverymanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
