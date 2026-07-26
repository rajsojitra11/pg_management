<?php

namespace Modules\MenuMaster\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMenuMasterRequest extends FormRequest
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
        return [
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
        ];
    }
}
