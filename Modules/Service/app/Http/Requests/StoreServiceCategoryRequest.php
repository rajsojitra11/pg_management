<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_category_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_categories')->whereNull('deleted_at'),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_category_name' => __('service::message.service_category_name'),
        ];
    }

    public function messages(): array
    {
        return [
            'service_category_name.required' => __('service::message.enter_category_name'),
            'service_category_name.unique' => __('service::message.category_name_taken'),
        ];
    }
}
