<?php

namespace Modules\Year\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Year\Models\Year;

class UpdateYearRequest extends FormRequest
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
        $id = Year::findByAnyKey($this->route('year') ?? $this->input('id'))?->id;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('years')->where(function ($query) use ($id) {
                    return $query->where([['deleted_at', null], ['id', '!=', $id]]);
                }),
            ],
            'full_short' => 'nullable|string|max:255',
            'short_full' => 'nullable|string|max:255',
            'short_short' => 'nullable|string|max:255',
            'full_full' => 'nullable|string|max:255',
            'short' => 'nullable|string|max:255',
            'full' => 'nullable|string|max:255',
            'set_default' => 'nullable|boolean',
        ];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => __('year::message.name'),
            'full_short' => 'Full-Short Format',
            'short_full' => 'Short-Full Format',
            'short_short' => 'Short-Short Format',
            'full_full' => 'Full-Full Format',
            'short' => 'Short Format',
            'full' => 'Full Format',
            'set_default' => __('year::message.default'),
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('year::message.enter_year'),
            'name.unique' => __('year::message.enter_unique_year'),
        ];
    }
}
