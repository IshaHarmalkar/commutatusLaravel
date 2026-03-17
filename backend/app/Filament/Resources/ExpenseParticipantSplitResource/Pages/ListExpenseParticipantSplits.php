<?php

namespace App\Filament\Resources\ExpenseParticipantSplitResource\Pages;

use App\Filament\Resources\ExpenseParticipantSplitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseParticipantSplits extends ListRecords
{
    protected static string $resource = ExpenseParticipantSplitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
