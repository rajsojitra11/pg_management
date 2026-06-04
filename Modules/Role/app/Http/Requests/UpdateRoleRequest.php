<?php

namespace Modules\Role\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => 'required|unique:roles,name,'.$roleId.',id,deleted_at,NULL|min:2|max:255',
            'permission' => 'required|array|min:1',
            'permission.*' => 'exists:permissions,id',
            'all_years' => 'boolean',
            'allowed_year' => 'nullable|integer|min:1|max:9999',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('role::message.validation.name.required'),
            'name.unique' => __('role::message.validation.name.unique'),
            'name.min' => __('role::message.validation.name.min'),
            'name.max' => __('role::message.validation.name.max'),
            'permission.required' => __('role::message.validation.permissions.required'),
            'permission.array' => __('role::message.validation.permissions.required'),
            'permission.min' => __('role::message.validation.permissions.required'),
        ];
    }
}
