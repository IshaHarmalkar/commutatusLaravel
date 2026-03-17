<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Http\Requests\StoreExpenseRequest;
use App\Services\ExpenseService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $expense = $this->record->load('participants.user', 'items');
        $data['participant_ids'] = $expense->participants->pluck('user_id')->toArray();

        $data['items'] = $expense->items->map(fn ($item) => [
            'name' => $item->name,
            'amount' => $item->amount,
            'type' => $item->type,
            'assigned_to_id' => $item->assigned_to_id,

        ])->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ExpenseService::class)->updateExpense($record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $request = new StoreExpenseRequest;
        $request->merge($data);

        validator($data, $request->rules())
            ->after(fn ($validator) => $request->withValidator($validator))
            ->validate();

        return $data;
    }
}
