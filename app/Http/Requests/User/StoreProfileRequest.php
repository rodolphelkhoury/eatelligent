<?php

namespace App\Http\Requests\User;

use App\Enums\ActivityLevel;
use App\Enums\Goal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'min:20', 'max:300'],
            'height_cm' => ['required', 'numeric', 'min:100', 'max:250'],
            'age' => ['required', 'integer', 'min:10', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'activity_level' => ['required', Rule::in(ActivityLevel::values())],
            'goal' => ['required', Rule::in(Goal::values())],
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
