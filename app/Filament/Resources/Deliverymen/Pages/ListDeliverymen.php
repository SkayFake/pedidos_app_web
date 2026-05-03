<?php

namespace App\Filament\Resources\Deliverymen\Pages;

use App\Filament\Resources\Deliverymen\DeliverymanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliverymen extends ListRecords
{
    protected static string $resource = DeliverymanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
