<?php

namespace App\Filament\Resources\ExpenseParticipantSplitResource\Pages;

use App\Filament\Resources\ExpenseParticipantSplitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseParticipantSplit extends EditRecord
{
    protected static string $resource = ExpenseParticipantSplitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
