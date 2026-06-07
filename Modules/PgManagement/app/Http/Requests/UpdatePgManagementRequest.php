<?php

namespace Modules\PgManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PgManagement\Models\PgManagement;

class UpdatePgManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = PgManagement::findByAnyKey($this->route('pgmanagement') ?? $this->input('id'))?->id;

        return [
            'pg_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pg_management')->ignore($id)->whereNull('deleted_at'),
            ],
            'owner_id' => ['nullable', 'exists:users,id'],
            'mobile_no' => ['nullable', 'string', 'max:20'],
            'total_block' => ['nullable', 'integer', 'min:0'],
            'total_room' => ['nullable', 'integer', 'min:0'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pg_name' => __('pgmanagement::message.pg_name'),
            'owner_id' => __('pgmanagement::message.owner'),
            'mobile_no' => __('pgmanagement::message.mobile_no'),
            'total_block' => __('pgmanagement::message.total_block'),
            'total_room' => __('pgmanagement::message.total_room'),
            'country_id' => __('pgmanagement::message.country'),
            'state_id' => __('pgmanagement::message.state'),
            'city_id' => __('pgmanagement::message.city'),
            'pincode' => __('pgmanagement::message.pincode'),
            'address' => __('pgmanagement::message.address'),
        ];
    }

    public function messages(): array
    {
        return [
            'pg_name.required' => __('pgmanagement::message.enter_pg_name'),
            'pg_name.unique' => __('pgmanagement::message.enter_unique_pg_name'),
            'owner_id.exists' => __('pgmanagement::message.enter_valid_owner'),
        ];
    }
}
