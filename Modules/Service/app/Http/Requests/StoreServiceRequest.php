<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'service_category_id' => ['required', 'exists:service_categories,id'],
        ];

        if ($this->has('services') && is_array($this->input('services'))) {
            $rules['services'] = ['required', 'array', 'min:1'];
            $rules['services.*.service_name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'service_name')->whereNull('deleted_at'),
            ];
            $rules['services.*.status'] = ['nullable', 'string', 'in:active,inactive'];
        } else {
            $rules['service_name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('services')->whereNull('deleted_at'),
            ];
            $rules['status'] = ['nullable', 'string', 'in:active,inactive'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [
            'service_category_id.required' => __('service::message.select_category'),
            'service_name.required' => __('service::message.enter_service_name'),
            'services.required' => __('service::message.add_at_least_one_service'),
            'services.min' => __('service::message.add_at_least_one_service'),
            'services.*.service_name.required' => __('service::message.enter_service_name'),
        ];
    }
}
