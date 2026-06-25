<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Service\Models\Service;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = Service::findByAnyKey($this->route('service') ?? $this->input('id'))?->id;

        return [
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'service_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services')->ignore($id)->whereNull('deleted_at'),
            ],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
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
            'service_name.unique' => __('service::message.service_name_taken'),
        ];
    }
}
