<?php

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Tenant;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = Tenant::findByAnyKey($this->route('tenant') ?? $this->input('id'))?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenants')->ignore($id)->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('tenant::message.name'),
            'email' => __('tenant::message.email'),
            'phone' => __('tenant::message.phone'),
            'address' => __('tenant::message.address'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('tenant::message.enter_name'),
            'name.unique' => __('tenant::message.enter_unique_name'),
        ];
    }
}
