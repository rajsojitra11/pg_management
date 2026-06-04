<?php

namespace Modules\Country\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [];
    }
}
