<?php

namespace Modules\Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                }),
            ],
            'unit_value' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('unit::message.enter_unit'),
            'name.unique' => __('unit::message.enter_unique_unit'),
            'unit_value.numeric' => __('unit::message.enter_valid_unit_value'),
            'unit_value.min' => __('unit::message.unit_value_min'),
        ];
    }
}
