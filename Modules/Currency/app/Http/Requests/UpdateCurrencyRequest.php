<?php

namespace Modules\Currency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Currency\Models\Currency;

class UpdateCurrencyRequest extends FormRequest
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
        $id = Currency::findByAnyKey($this->route('currency') ?? $this->input('id'))?->id;

        $rules = [
            'currency_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('currencies')->ignore($id)->whereNull('deleted_at'),
            ],
            'currency_symbol' => [
                'required',
                'string',
                'max:10',
                Rule::unique('currencies')->ignore($id)->whereNull('deleted_at'),
            ],
        ];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'currency_name' => __('currency::message.name'),
            'currency_symbol' => __('currency::message.symbol'),
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'currency_name.required' => __('currency::message.enter_name'),
            'currency_name.unique' => __('currency::message.enter_unique_name'),
            'currency_symbol.required' => __('currency::message.enter_symbol'),
            'currency_symbol.unique' => __('currency::message.enter_unique_symbol'),
        ];
    }
}
