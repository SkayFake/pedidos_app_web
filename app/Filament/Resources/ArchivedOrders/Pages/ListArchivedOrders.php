<?php

namespace App\Filament\Resources\ArchivedOrders\Pages;

use App\Filament\Resources\ArchivedOrders\ArchivedOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListArchivedOrders extends ListRecords
{
    protected static string $resource = ArchivedOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
