<?php

namespace App\Filament\Resources\ExpenseItemSplitResource\Pages;

use App\Filament\Resources\ExpenseItemSplitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseItemSplit extends EditRecord
{
    protected static string $resource = ExpenseItemSplitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
