<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckoutWalletRequest extends FormRequest
{
    /**
     * Allow everyone to make this request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1|max:500',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ];
    }

    /**
     * Handle validation failure for API requests
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
