<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'tax' => 'nullable|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'required|integer|exists:users,id|distinct',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.type' => 'required|in:equal,assigned',
            'items.*.assigned_to_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $type = $this->input("items.{$index}.type");

                    if ($type === 'assigned' && empty($value)) {
                        $fail("The {$attribute} is required when item type is assigned.");
                    }
                    if ($type === 'equal' && ! empty($value)) {
                        $fail("The {$attribute} must be null when item type is equal.");
                    }
                },

            ],

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateTotals($validator);
            $this->validateAssignedUsersAreParticipants($validator);
        });
    }

    public function validateTotals($validator): void
    {
        $itemsTotal = collect($this->input('items', []))->sum('amount');

        $tax = (float) $this->input('tax', 0);
        $tip = (float) $this->input('tip', 0);
        $declared = (float) $this->input('amount');

        $computed = round($itemsTotal + $tax + $tip, 2);

        if ($computed !== round($declared, 2)) {
            $validator->errors()->add(
                'amount',
                'Invalid amount: The total item + tax + tip, does not match.'

            );
        }
    }

    private function validateAssignedUsersAreParticipants($validator): void
    {
        $validIds = $this->input('participant_ids', []);
        $validIds[] = Auth::id();

        foreach ($this->input('items', []) as $index => $item) {
            if (
                ($item['type'] ?? '') === 'assigned' &&
                isset($item['assigned_to_id']) &&
                ! in_array($item['assigned_to_id'], $validIds)
            ) {
                $validator->errors()->add(
                    "items.{$index}.assigned_to_id",
                    'The assigned user must be one of participants or the person paying'
                );
            }

        }

    }
}
