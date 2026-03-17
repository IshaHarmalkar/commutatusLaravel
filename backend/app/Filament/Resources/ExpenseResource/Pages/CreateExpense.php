<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Http\Requests\StoreExpenseRequest;
use App\Services\ExpenseService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ExpenseService::class)->createExpense($data);
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
