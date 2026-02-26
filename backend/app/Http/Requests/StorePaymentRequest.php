<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePaymentRequest extends FormRequest
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
            'creditor_id' => [
                'required',
                'integer',
                'exist:users,id',
                function ($attribute, $value, $fail) {
                    if ((int) $value === Auth::id()) {
                        $fail('You cannot make a payment to yourself');
                    }
                },
            ],
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
