<?php

namespace App\Filament\Resources\RewardMilestones\Pages;

use App\Filament\Resources\RewardMilestones\RewardMilestoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRewardMilestones extends ManageRecords
{
    protected static string $resource = RewardMilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
